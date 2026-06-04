<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use WP_User;

/**
 * POS access + capability model.
 *
 * A user has POS access if and only if they hold at least one `pos_*`
 * capability — the same primitive WP uses for every other authorization
 * decision. The `_woocommerce_pos_preset` user meta records which preset was
 * assigned (for the UI to render and for set_pos_preset() to translate into
 * concrete caps), but it is not the gate: a stray or planted meta value alone
 * cannot grant access.
 *
 * set_pos_preset() never touches WP roles — it only manages caps + meta. POS
 * access can therefore be granted to any existing user (shop_manager,
 * administrator, …) without altering their role, and revoked without leaving
 * them roleless. This sidesteps the original concern entirely: there is no
 * `pos_staff` role bolted onto existing users for the users.php "Change role
 * to…" dropdown to overwrite.
 *
 * The `pos_staff` WP role exists only as the default role for brand-new
 * POS-only accounts created from the Add New Staff form (the form's role
 * dropdown defaults to it). It holds only `read`, matching the
 * customer/subscriber shape, so a POS-only user can manage their own profile
 * without gaining any non-POS capabilities. It is a convenience label, never
 * the authorization signal — see has_pos_access().
 *
 * Preset bundles live in capabilities_for_preset(). Changing a preset
 * definition does NOT retroactively update users — they keep whatever caps
 * were applied when the preset was last set. A future preset-version
 * migration will reapply caps across all POS-access users when needed.
 *
 * @since 11.0.0
 * @internal
 */
class Capabilities {

	/**
	 * Default WP role for brand-new POS-only accounts.
	 *
	 * Registered in WC_Install::create_roles() and assigned at account creation
	 * by the Add New Staff form (its role dropdown defaults to this). NOT added
	 * to existing users who are granted POS access — they keep their own role —
	 * and NOT the authorization signal. See has_pos_access().
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
	 * WP_User_Query args that select every user with POS access.
	 *
	 * Keyed off the preset meta so the result is stable even if the `pos_staff`
	 * role label has been dropped (e.g. by a users.php role overwrite). Callers
	 * still need to filter individual results through get_pos_preset() to skip
	 * stale or invalid preset values.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 11.0.0
	 */
	public static function pos_staff_user_query_args(): array {
		return array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- POS access is keyed on this meta key; the row count is bounded by the number of staff (typically a handful).
			'meta_key'     => self::POS_PRESET_META_KEY,
			'meta_compare' => 'EXISTS',
		);
	}

	/**
	 * Whether a user has any POS access at all.
	 *
	 * Returns true if the user holds at least one `pos_*` capability. The
	 * any-cap definition is the right shape for both fixed presets (where each
	 * preset's caps are granted as a bundle) and the future granular model
	 * (where individual `pos_*` caps may be assigned without any baseline
	 * cap like `pos_process_sales`).
	 *
	 * The `pos_staff` WP role is intentionally NOT consulted: it can be
	 * silently overwritten by the users.php "Change role to…" dropdown, which
	 * is the bug this access model exists to avoid. The preset meta is also
	 * not consulted — it records *which* preset was last assigned but is not
	 * the authorization signal in WP's capability model.
	 *
	 * @param int $user_id Target user.
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public static function has_pos_access( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		foreach ( self::all_pos_capabilities() as $cap ) {
			if ( user_can( $user_id, $cap ) ) {
				return true;
			}
		}
		return false;
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
	 * WP roles are never touched here — only caps + meta. Granting access to an
	 * existing user leaves their role intact; revoking it never leaves them
	 * roleless. The `pos_staff` role is assigned at account creation for new
	 * POS-only accounts (see POS_STAFF_ROLE), not by this method.
	 *
	 * Side effects:
	 *  - On assign: stores the preset in user meta and grants the preset's
	 *    `pos_*` capabilities via add_cap() (these are the access signal).
	 *  - On clear (null): strips every pos_* cap and deletes the preset meta,
	 *    which is what revokes access. Other roles and non-POS caps are kept.
	 *  - On preset change: strips the prior pos_* caps before granting the
	 *    new ones, so a user moving from Manager to Cashier doesn't keep
	 *    issue_refunds, etc.
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
			return true;
		}

		update_user_meta( $user_id, self::POS_PRESET_META_KEY, $preset );

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
