<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\POS;

defined( 'ABSPATH' ) || exit;

/**
 * Canonical capability lists for the two POS roles (POS Cashier, POS Manager).
 *
 * These lists are consumed by:
 *  - WC_Install::create_roles() when registering the roles on install/upgrade.
 *  - WC_Install::remove_roles() and uninstall.php when tearing them down.
 *
 * Server-side capability enforcement is intentionally NOT wired in milestones 1–2
 * of the POS staff iteration; roles + capabilities exist so the mobile client can
 * gate UI actions client-side based on the cached /staff payload, and so future
 * milestones can flip enforcement on without re-registering roles.
 *
 * # POS capability matrix (M1)
 *
 * Each row in the product table maps to exactly one gating capability. Override
 * behavior (cashier or manager temporarily acquiring a higher cap via a manager
 * PIN) is reintroduced in milestone 3; until then, override rows resolve to NO.
 *
 *     Action                         | Capability             | Cashier | Manager | Admin/SM | Gated where (M1)
 *     -------------------------------|------------------------|:-------:|:-------:|:--------:|------------------
 *     Process sales                  | publish_shop_orders    |  yes    |  yes    |  yes     | client only*
 *     View orders                    | read_shop_order        |  yes    |  yes    |  yes     | client only*
 *     Apply existing coupons         | read_shop_coupon       |  yes    |  yes    |  yes     | client only*
 *     Create coupons                 | publish_shop_coupons   |  no     |  yes    |  yes     | client only*
 *     Issue refunds                  | refund_shop_orders     |  no     |  yes    |  yes     | client only*
 *     View POS settings              | view_pos_settings      |  no     |  yes    |  yes     | client only
 *     Edit POS settings              | edit_pos_settings      |  no     |  no     |  yes     | client only
 *     Manage POS staff (wp-admin)    | manage_pos_staff       |  no     |  no     |  yes     | server (admin page + REST)
 *     Exit POS                       | exit_pos               |  no     |  no     |  yes     | client only
 *     Access wp-admin                | (implicit; no new cap) |  no     |  no     |  yes     | server (admin_init redirect)
 *
 * *"client only" caveat for M1: standard WC REST endpoints (orders, coupons, refunds)
 *  do run `current_user_can()` server-side against the requesting capability — but
 *  because every mobile request authenticates as the device admin in this iteration,
 *  those checks are always satisfied. The cashier/manager caps in the table govern
 *  the mobile app's UI gating (which reads them from the cached /staff payload),
 *  not the request-level authorization on the wire. Server-side per-operator
 *  enforcement returns in milestone 3 alongside the manager override design.
 *
 * Two rows DO enforce server-side in M1:
 *   - `manage_pos_staff` gates GET /wc/pos/v1/staff (POSStaffController) and the
 *     wp-admin → Settings → Point of Sale → Staff page.
 *   - `Access wp-admin` is enforced by an admin_init redirect in POSController
 *     that bounces any user with `view_pos` but without `manage_woocommerce` away
 *     from wp-admin pages (per the i2 proposal: POS-only roles get access only
 *     within POS, not within wp-admin).
 *
 * @since 10.9.0
 * @internal
 */
class Capabilities {

	public const ROLE_CASHIER = 'pos_cashier';
	public const ROLE_MANAGER = 'pos_manager';

	/**
	 * Capabilities granted to the POS Cashier role.
	 *
	 * Minimum set needed to process sales at the counter: read products, read coupons,
	 * create + edit own orders, create customers. No refunds, no settings access.
	 *
	 * @return array<string, bool>
	 *
	 * @since 10.9.0
	 */
	public static function cashier_capabilities(): array {
		return array(
			'read'                       => true,
			// POS entry.
			'view_pos'                   => true,
			// Process sales + view orders.
			'edit_shop_order'            => true,
			'edit_shop_orders'           => true,
			'edit_published_shop_orders' => true,
			'publish_shop_orders'        => true,
			'read_shop_order'            => true,
			'read_private_shop_orders'   => true,
			// Read products.
			'read_product'               => true,
			'read_private_products'      => true,
			// Apply (read) existing coupons only — no publish.
			'read_shop_coupon'           => true,
			'read_private_shop_coupons'  => true,
			// Allow ad-hoc customer creation at checkout.
			'create_customers'           => true,
		);
	}

	/**
	 * Capabilities granted to the POS Manager role.
	 *
	 * Cashier baseline + create coupons, issue refunds, view POS settings, edit products,
	 * see WooCommerce reports. Still no full wp-admin access (no manage_woocommerce, no
	 * edit_pos_settings, no manage_pos_staff, no exit_pos).
	 *
	 * @return array<string, bool>
	 *
	 * @since 10.9.0
	 */
	public static function manager_capabilities(): array {
		return array(
			'read'                        => true,
			'upload_files'                => true,
			// POS entry + view settings.
			'view_pos'                    => true,
			'view_pos_settings'           => true,
			// Issue refunds.
			'refund_shop_orders'          => true,
			// Process sales + view orders.
			'edit_shop_order'             => true,
			'edit_shop_orders'            => true,
			'edit_others_shop_orders'     => true,
			'edit_published_shop_orders'  => true,
			'publish_shop_orders'         => true,
			'read_shop_order'             => true,
			'read_private_shop_orders'    => true,
			'create_customers'            => true,
			'view_woocommerce_reports'    => true,
			// Read + edit products.
			'edit_products'               => true,
			'edit_published_products'     => true,
			'read_product'                => true,
			'read_private_products'       => true,
			// Read + create coupons.
			'edit_shop_coupon'            => true,
			'edit_shop_coupons'           => true,
			'edit_others_shop_coupons'    => true,
			'edit_published_shop_coupons' => true,
			'publish_shop_coupons'        => true,
			'read_shop_coupon'            => true,
			'read_private_shop_coupons'   => true,
		);
	}

	/**
	 * POS-specific capabilities introduced by this iteration.
	 *
	 * Granted to administrator + shop_manager on install/upgrade so privileged users
	 * have full POS access without needing to switch to the dedicated POS roles.
	 * Removed from those roles on uninstall.
	 *
	 * @return string[]
	 *
	 * @since 10.9.0
	 */
	public static function pos_specific_capabilities(): array {
		return array(
			'view_pos',
			'view_pos_settings',
			'edit_pos_settings',
			'refund_shop_orders',
			'manage_pos_staff',
			'exit_pos',
		);
	}
}
