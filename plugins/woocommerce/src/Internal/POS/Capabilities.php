<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Utilities\Users;
use WP_User;

/**
 * POS capability model.
 *
 * POS access is defined entirely by `woocommerce_pos_*` capabilities granted per-user — the
 * same primitive WordPress uses for every other authorization decision. A user
 * has POS access if and only if they hold at least one of the known `woocommerce_pos_*`
 * capabilities (those in all_pos_capabilities(); see has_pos_access()).
 *
 * Capabilities are granted per-user via add_cap(), never bundled onto a WP role.
 * POS access can therefore be added to any existing user (shop_manager,
 * administrator, …) without altering their role, and revoked without leaving
 * them roleless.
 *
 * The preset layer maps each preset (see POSPreset) to a bundle of
 * `woocommerce_pos_*` caps and assigns or clears them per user via
 * set_pos_preset(). The assigned preset is recorded in `woocommerce_pos_preset`
 * user meta for the UI; the caps remain the authorization signal, so a stray
 * meta value alone grants nothing.
 *
 * @since 11.0.0
 * @internal
 */
class Capabilities {

	/**
	 * Default WP role for brand-new POS-only accounts.
	 *
	 * POS access is keyed on `woocommerce_pos_*` capabilities, not this role (see
	 * has_pos_access()), so new POS-only accounts use the stock `subscriber` role.
	 * A dedicated `pos_staff` role is planned for a later iteration.
	 */
	public const DEFAULT_STAFF_ROLE = 'subscriber';

	/**
	 * POS capability identifiers.
	 *
	 * Real WP capabilities, granted per-user via add_cap() when POS access is
	 * assigned. They surface in current_user_can() and the standard /wp/v2/users
	 * response — no shadow permission store. All share the `woocommerce_pos_`
	 * prefix to stay isolated from core and third-party caps; what each one grants
	 * is described inline below.
	 */
	// Ring up and complete a sale at checkout.
	public const CAP_PROCESS_SALES = 'woocommerce_pos_process_sales';
	// Look up and view existing orders.
	public const CAP_VIEW_ORDERS = 'woocommerce_pos_view_orders';
	// Apply an existing coupon to a cart.
	public const CAP_APPLY_COUPONS = 'woocommerce_pos_apply_coupons';
	// Create a new coupon during a sale.
	public const CAP_CREATE_COUPONS = 'woocommerce_pos_create_coupons';
	// Refund a paid order.
	public const CAP_ISSUE_REFUNDS = 'woocommerce_pos_issue_refunds';
	// View POS settings (read-only).
	public const CAP_VIEW_SETTINGS = 'woocommerce_pos_view_settings';
	// Change POS settings.
	public const CAP_EDIT_SETTINGS = 'woocommerce_pos_edit_settings';
	// Manage POS staff and their access.
	public const CAP_MANAGE_STAFF = 'woocommerce_pos_manage_staff';
	// Leave POS mode for the full admin.
	public const CAP_EXIT_POS = 'woocommerce_pos_exit';

	/**
	 * User meta key recording which preset was assigned to a user.
	 *
	 * The value is one of the POSPreset constants. It drives the admin UI, but it
	 * is not the authorization signal: has_pos_access() reads the `woocommerce_pos_*` caps, not
	 * this meta. Stored per-site via Users::*_site_user_meta() (which suffixes the blog prefix)
	 * so it stays aligned with the blog-scoped capabilities on multisite.
	 */
	public const POS_PRESET_META_KEY = 'woocommerce_pos_preset';

	/**
	 * All known POS capability identifiers.
	 *
	 * The canonical list of `woocommerce_pos_*` caps — used to test for POS access and, by the
	 * preset layer, to apply or clear a user's caps as a set.
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
	 * Whether a user has any POS access at all.
	 *
	 * True if the user holds at least one of the known `woocommerce_pos_*` capabilities (those
	 * in all_pos_capabilities()). This is the single authorization signal for POS
	 * access: neither a WP role nor any meta value grants it on its own. The
	 * any-cap definition fits both fixed presets
	 * (each preset's caps granted as a bundle) and a future granular model
	 * (individual `woocommerce_pos_*` caps assigned without a baseline cap).
	 *
	 * Reads the resolved capability map (WP_User::$allcaps) directly rather than
	 * looping over user_can(). user_can() re-runs map_meta_cap() and fires the
	 * user_has_cap filter on every call, so the loop would dispatch that machinery
	 * once per POS cap; an $allcaps lookup is a plain array check per cap.
	 *
	 * Reading $allcaps also scopes access to caps the user actually holds: unlike
	 * user_can(), it does not honor the multisite super-admin grant, which
	 * has_cap() applies as a runtime gate rather than storing in $allcaps. A super
	 * admin therefore does not implicitly count as POS staff — they need an
	 * explicit `woocommerce_pos_*` cap like anyone else.
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

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		foreach ( self::all_pos_capabilities() as $cap ) {
			if ( ! empty( $user->allcaps[ $cap ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Resolve the assigned POS preset for a user, or null if none is set.
	 *
	 * Returns the `woocommerce_pos_preset` meta value only if it matches an
	 * assignable preset, so a stale or hand-edited value reads as "no preset".
	 *
	 * The meta is stored per-site (see set_pos_preset()) so it stays aligned with the
	 * blog-scoped POS capabilities on multisite.
	 *
	 * @param int $user_id Target user.
	 * @return string|null One of the POSPreset constants, or null.
	 *
	 * @since 11.0.0
	 */
	public static function get_pos_preset( int $user_id ): ?string {
		$meta = Users::get_site_user_meta( $user_id, self::POS_PRESET_META_KEY, true );
		if ( in_array( $meta, POSPreset::get_all(), true ) ) {
			return (string) $meta;
		}
		return null;
	}

	/**
	 * WP_User_Query args selecting candidate POS staff — every user holding any
	 * `woocommerce_pos_*` capability, via WP_User_Query's capability__in.
	 *
	 * Use it to enumerate POS staff — e.g. the GET /wc/pos/v1/staff endpoint and the
	 * wp-admin Staff list — which refine the candidates for their own needs (the staff
	 * endpoint also requires a PIN). Keying on caps rather than the preset meta keeps
	 * this aligned with the authorization signal: a user whose caps were stripped is
	 * excluded, and a cap granted outside a preset is still included.
	 *
	 * This is a candidate query, not exact parity with has_pos_access(): capability__in
	 * matches the capability *name* wherever it appears in the serialized capabilities
	 * row, so a user with an explicit denial (add_cap( $cap, false )) is still selected
	 * even though has_pos_access() — which reads the resolved capabilities — treats them
	 * as no access. Capabilities only ever grants or strips caps, never denies, so this
	 * diverges only when external code sets an explicit denial; callers needing exact
	 * parity should refine results with has_pos_access().
	 *
	 * @return array<string, mixed>
	 *
	 * @since 11.0.0
	 */
	public static function pos_staff_user_query_args(): array {
		return array(
			'capability__in' => self::all_pos_capabilities(),
		);
	}

	/**
	 * Preset metadata: the `woocommerce_pos_*` cap bundle and display label for each preset.
	 *
	 * Single source of truth for capabilities_for_preset() and preset_label(), so
	 * adding or renaming a preset is one edit here (plus the POSPreset constant)
	 * rather than several parallel switches.
	 *
	 *     Capability                       Cashier  Manager  Admin
	 *     woocommerce_pos_process_sales    yes      yes      yes
	 *     woocommerce_pos_view_orders      yes      yes      yes
	 *     woocommerce_pos_apply_coupons    yes      yes      yes
	 *     woocommerce_pos_create_coupons   no       yes      yes
	 *     woocommerce_pos_issue_refunds    no       yes      yes
	 *     woocommerce_pos_view_settings    no       yes      yes
	 *     woocommerce_pos_edit_settings    no       no       yes
	 *     woocommerce_pos_manage_staff     no       no       yes
	 *     woocommerce_pos_exit             no       no       yes
	 *
	 * @return array<string, array{caps: array<string, true>, label: string}>
	 */
	private static function preset_definitions(): array {
		$cashier_caps = array(
			self::CAP_PROCESS_SALES => true,
			self::CAP_VIEW_ORDERS   => true,
			self::CAP_APPLY_COUPONS => true,
		);

		$manager_caps = $cashier_caps + array(
			self::CAP_CREATE_COUPONS => true,
			self::CAP_ISSUE_REFUNDS  => true,
			self::CAP_VIEW_SETTINGS  => true,
		);

		$admin_caps = $manager_caps + array(
			self::CAP_EDIT_SETTINGS => true,
			self::CAP_MANAGE_STAFF  => true,
			self::CAP_EXIT_POS      => true,
		);

		return array(
			POSPreset::CASHIER => array(
				'caps'  => $cashier_caps,
				'label' => __( 'POS cashier', 'woocommerce' ),
			),
			POSPreset::MANAGER => array(
				'caps'  => $manager_caps,
				'label' => __( 'POS manager', 'woocommerce' ),
			),
			POSPreset::ADMIN   => array(
				'caps'  => $admin_caps,
				'label' => __( 'POS admin', 'woocommerce' ),
			),
		);
	}

	/**
	 * The `woocommerce_pos_*` capability bundle for a given preset (see preset_definitions()).
	 *
	 * @param string $preset One of the POSPreset constants.
	 * @return array<string, true> Map of granted cap => true. Empty for unknown presets.
	 *
	 * @since 11.0.0
	 */
	public static function capabilities_for_preset( string $preset ): array {
		$definitions = self::preset_definitions();

		return isset( $definitions[ $preset ] ) ? $definitions[ $preset ]['caps'] : array();
	}

	/**
	 * Assign or clear the POS preset for a user.
	 *
	 * Touches only caps + meta, never WP roles: granting access to an existing user
	 * leaves their role intact, and clearing it never leaves them roleless. Every
	 * `woocommerce_pos_*` cap the user holds directly (via add_cap — the only way this class grants
	 * them) is stripped first, so a preset change (Manager to Cashier) drops the caps
	 * the new preset omits, and a clear (null) removes them along with the preset meta.
	 * Role-granted `woocommerce_pos_*` caps, if any, are out of scope: this class never adds caps
	 * to roles.
	 *
	 * @param int         $user_id Target user.
	 * @param string|null $preset  One of POSPreset::get_all(), or null to clear.
	 * @return bool True on success (including clears); false if the user does not
	 *              exist or the preset value is not assignable.
	 *
	 * @since 11.0.0
	 */
	public static function set_pos_preset( int $user_id, ?string $preset ): bool {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user instanceof WP_User ) {
			return false;
		}

		// Validate before mutating any state.
		if ( null !== $preset && ! in_array( $preset, POSPreset::get_all(), true ) ) {
			return false;
		}

		// Strip the user's directly-held woocommerce_pos_* caps so a preset change (or clear)
		// starts clean. remove_cap() is a no-op for caps the user does not hold and strips a cap
		// whether it was granted (true) or explicitly denied (false). This class only grants caps
		// per-user via add_cap(), so role-granted caps are out of scope.
		foreach ( self::all_pos_capabilities() as $cap ) {
			$user->remove_cap( $cap );
		}

		if ( null === $preset ) {
			Users::delete_site_user_meta( $user_id, self::POS_PRESET_META_KEY );
			return true;
		}

		// Store the preset per-site so the bookkeeping stays aligned with the blog-scoped
		// POS capabilities on multisite (Users::update_site_user_meta suffixes the blog prefix,
		// so the key still matches the woocommerce_% uninstall sweep).
		Users::update_site_user_meta( $user_id, self::POS_PRESET_META_KEY, $preset );

		foreach ( array_keys( self::capabilities_for_preset( $preset ) ) as $cap ) {
			$user->add_cap( $cap );
		}

		return true;
	}

	/**
	 * Translated label for a POS preset.
	 *
	 * @param string $preset One of the POSPreset constants.
	 * @return string Empty string for an unknown preset.
	 *
	 * @since 11.0.0
	 */
	public static function preset_label( string $preset ): string {
		$definitions = self::preset_definitions();

		return isset( $definitions[ $preset ] ) ? $definitions[ $preset ]['label'] : '';
	}
}
