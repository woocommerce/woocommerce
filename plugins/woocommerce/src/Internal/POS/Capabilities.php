<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use WP_User;

/**
 * POS access + capability model.
 *
 * A user has POS access if and only if they hold the `pos_staff` WP role.
 * That role is the sole source of truth — the assigned preset meta is a
 * convenience for selecting which `pos_*` capabilities to grant, but the
 * meta key may disappear in a future iteration and must not be treated as
 * an authorization signal. The preset (cashier / manager / admin) is a
 * UI-curated bundle — selecting one applies the matching `pos_*`
 * capabilities to the user as **real WP capabilities** (stored in
 * `wp_capabilities` user meta, visible via `current_user_can( 'pos_*' )`
 * and the standard `/wp/v2/users` response).
 *
 * The `pos_staff` WP role itself holds only `read`, matching the
 * customer/subscriber shape, so POS-only users can manage their own profile
 * without gaining any non-POS capabilities. Existing WP users (shop_manager,
 * administrator, etc.) get `pos_staff` added as a secondary role; their
 * original role capabilities are untouched.
 *
 * Preset bundles live in capabilities_for_preset(). Changing a preset
 * definition does NOT retroactively update users — they keep whatever caps
 * were applied when the preset was last set. A future preset-version
 * migration will reapply caps across all pos_staff users when needed.
 *
 * @since 11.0.0
 * @internal
 */
class Capabilities {

	/**
	 * WP role marking that a user can use the POS app.
	 *
	 * Registered in WC_Install::create_roles(). Added to a user via
	 * set_pos_preset() and removed when the preset is cleared.
	 */
	public const POS_STAFF_ROLE = 'pos_staff';

	/**
	 * User meta key storing the assigned POS preset.
	 */
	public const POS_PRESET_META_KEY = '_woocommerce_pos_preset';

	/**
	 * POS preset identifiers.
	 *
	 * Presets are UI-curated capability bundles, not WP roles. The `pos_staff`
	 * WP role is the marker; the preset chooses which `pos_*` capabilities
	 * are granted client-side.
	 */
	public const POS_PRESET_CASHIER = 'pos_cashier';
	public const POS_PRESET_MANAGER = 'pos_manager';
	public const POS_PRESET_ADMIN   = 'pos_admin';

	/**
	 * POS capability identifiers.
	 *
	 * These are real WP capabilities — stored on each pos_staff user via
	 * add_cap() when a preset is assigned, removed when the preset is
	 * cleared or replaced. They are also the JSON keys in the
	 * /wc/pos/v1/staff payload, which the mobile client reads to gate
	 * in-app UI for the PIN-authenticated operator.
	 *
	 * Override rows from the i2 proposal (cashier creating coupons via
	 * manager approval, etc.) live on the override approver's preset, not
	 * the operator — a cashier never receives e.g. `pos_create_coupons`
	 * here, even temporarily.
	 */
	public const CAP_PROCESS_SALES  = 'pos_process_sales';
	public const CAP_VIEW_ORDERS    = 'pos_view_orders';
	public const CAP_APPLY_COUPONS  = 'pos_apply_coupons';
	public const CAP_CREATE_COUPONS = 'pos_create_coupons';
	public const CAP_ISSUE_REFUNDS  = 'pos_issue_refunds';
	public const CAP_VIEW_SETTINGS  = 'pos_view_settings';
	public const CAP_EDIT_SETTINGS  = 'pos_edit_settings';
	public const CAP_MANAGE_STAFF   = 'pos_manage_staff';
	public const CAP_EXIT_POS       = 'pos_exit';

	/**
	 * All assignable POS presets, in ascending capability order.
	 *
	 * @return string[]
	 */
	public static function assignable_pos_presets(): array {
		return array(
			self::POS_PRESET_CASHIER,
			self::POS_PRESET_MANAGER,
			self::POS_PRESET_ADMIN,
		);
	}

	/**
	 * All known POS capability identifiers.
	 *
	 * Used when applying or clearing per-user caps in set_pos_preset(), so a
	 * preset change strips every prior pos_* cap (including any that have
	 * since been removed from a preset definition) before granting the new set.
	 *
	 * @return string[]
	 */
	public static function all_pos_capabilities(): array {
		return array(
			self::CAP_PROCESS_SALES,
			self::CAP_VIEW_ORDERS,
			self::CAP_APPLY_COUPONS,
			self::CAP_CREATE_COUPONS,
			self::CAP_ISSUE_REFUNDS,
			self::CAP_VIEW_SETTINGS,
			self::CAP_EDIT_SETTINGS,
			self::CAP_MANAGE_STAFF,
			self::CAP_EXIT_POS,
		);
	}

	/**
	 * Resolve the assigned POS preset for a user, or null if they have none.
	 *
	 * Reads the `_woocommerce_pos_preset` user meta value and returns it only
	 * if it matches an assignable preset. A user without an explicit preset
	 * assignment has no POS access, regardless of their WP role.
	 *
	 * @param int $user_id Target user.
	 * @return string|null One of self::POS_PRESET_* or null.
	 *
	 * @since 11.0.0
	 */
	public static function get_pos_preset( int $user_id ): ?string {
		$meta = get_user_meta( $user_id, self::POS_PRESET_META_KEY, true );
		if ( in_array( $meta, self::assignable_pos_presets(), true ) ) {
			return (string) $meta;
		}
		return null;
	}

	/**
	 * Whether a user has any POS access at all.
	 *
	 * Checks the `pos_staff` WP role only. Preset meta is intentionally not
	 * consulted — it is informational about which preset was last assigned,
	 * not an access flag, and a stale or manually-set value must not grant
	 * access on its own.
	 *
	 * @param int $user_id Target user.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function has_pos_access( int $user_id ): bool {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return false;
		}
		return in_array( self::POS_STAFF_ROLE, $user->roles, true );
	}

	/**
	 * Whether a user holds the given POS capability.
	 *
	 * Delegates to user_can() so the source of truth is the user's stored
	 * capabilities — matching what /wp/v2/users and any other WP-native
	 * introspection report.
	 *
	 * @param int    $user_id    Target user.
	 * @param string $capability One of self::CAP_* values (e.g. 'pos_issue_refunds').
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function user_has_pos_capability( int $user_id, string $capability ): bool {
		return user_can( $user_id, $capability );
	}

	/**
	 * Assign or clear the POS preset for a user.
	 *
	 * Side effects:
	 *  - On assign: stores the preset in user meta, adds the `pos_staff` WP
	 *    role to the user (alongside any existing roles), and grants the
	 *    preset's `pos_*` capabilities via add_cap() so they are real WP
	 *    caps visible to current_user_can() and /wp/v2/users.
	 *  - On clear (null): strips every pos_* cap, removes the `pos_staff`
	 *    WP role, and deletes the preset meta. The user's other roles and
	 *    non-POS capabilities are preserved.
	 *  - On preset change: strips the prior pos_* caps before granting the
	 *    new ones, so a user moving from Manager to Cashier doesn't keep
	 *    issue_refunds, etc.
	 *
	 * If `pos_staff` would become the user's only role, the admin staff
	 * handler is responsible for offering to delete the user entirely —
	 * this method does not orphan users to a roleless state.
	 *
	 * @param int         $user_id Target user.
	 * @param string|null $preset  One of self::assignable_pos_presets(), or null to clear.
	 * @return bool True on success (including clears); false if the user
	 *              does not exist or the preset value is not assignable.
	 *
	 * @since 11.0.0
	 */
	public static function set_pos_preset( int $user_id, ?string $preset ): bool {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return false;
		}

		// Validate before mutating any state.
		if ( null !== $preset && ! in_array( $preset, self::assignable_pos_presets(), true ) ) {
			return false;
		}

		// Strip every known pos_* cap so a preset change (or clear) starts clean.
		foreach ( self::all_pos_capabilities() as $cap ) {
			if ( isset( $user->caps[ $cap ] ) ) {
				$user->remove_cap( $cap );
			}
		}

		if ( null === $preset ) {
			delete_user_meta( $user_id, self::POS_PRESET_META_KEY );
			if ( in_array( self::POS_STAFF_ROLE, $user->roles, true ) ) {
				$user->remove_role( self::POS_STAFF_ROLE );
			}
			return true;
		}

		update_user_meta( $user_id, self::POS_PRESET_META_KEY, $preset );

		if ( ! in_array( self::POS_STAFF_ROLE, $user->roles, true ) ) {
			$user->add_role( self::POS_STAFF_ROLE );
		}

		foreach ( array_keys( self::capabilities_for_preset( $preset ) ) as $cap ) {
			$user->add_cap( $cap );
		}

		return true;
	}

	/**
	 * The client-side POS capability map for a given preset.
	 *
	 *     Capability             Cashier   Manager   Admin
	 *     pos_process_sales        yes       yes      yes
	 *     pos_view_orders          yes       yes      yes
	 *     pos_apply_coupons        yes       yes      yes
	 *     pos_create_coupons       no        yes      yes
	 *     pos_issue_refunds        no        yes      yes
	 *     pos_view_settings        no        yes      yes
	 *     pos_edit_settings        no        no       yes
	 *     pos_manage_staff         no        no       yes
	 *     pos_exit                 no        no       yes
	 *
	 * @param string $preset One of self::POS_PRESET_* values.
	 * @return array<string, true>
	 *
	 * @since 11.0.0
	 */
	public static function capabilities_for_preset( string $preset ): array {
		$cashier = array(
			self::CAP_PROCESS_SALES => true,
			self::CAP_VIEW_ORDERS   => true,
			self::CAP_APPLY_COUPONS => true,
		);

		$manager = $cashier + array(
			self::CAP_CREATE_COUPONS => true,
			self::CAP_ISSUE_REFUNDS  => true,
			self::CAP_VIEW_SETTINGS  => true,
		);

		$admin = $manager + array(
			self::CAP_EDIT_SETTINGS => true,
			self::CAP_MANAGE_STAFF  => true,
			self::CAP_EXIT_POS      => true,
		);

		switch ( $preset ) {
			case self::POS_PRESET_CASHIER:
				return $cashier;
			case self::POS_PRESET_MANAGER:
				return $manager;
			case self::POS_PRESET_ADMIN:
				return $admin;
			default:
				return array();
		}
	}

	/**
	 * Translated label for a POS preset.
	 *
	 * @param string $preset One of self::POS_PRESET_* values.
	 * @return string Empty string if the preset is unknown.
	 *
	 * @since 11.0.0
	 */
	public static function preset_label( string $preset ): string {
		switch ( $preset ) {
			case self::POS_PRESET_CASHIER:
				return __( 'POS cashier', 'woocommerce' );
			case self::POS_PRESET_MANAGER:
				return __( 'POS manager', 'woocommerce' );
			case self::POS_PRESET_ADMIN:
				return __( 'POS admin', 'woocommerce' );
			default:
				return '';
		}
	}
}
