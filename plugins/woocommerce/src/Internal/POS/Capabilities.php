<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

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
 * The preset layer — which `woocommerce_pos_*` caps a Cashier / Manager / Admin receives, and
 * the code that assigns them per user — is added separately.
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
}
