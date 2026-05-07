<?php

declare( strict_types = 1 );

/**
 * Hard-coded list of Woo-related top-level menu slugs that the reconciler
 * removes from WP's native rail when the navigation_v2 feature is enabled.
 *
 * This list matches the WooPro prototype's rehomed slugs. It is hard-coded
 * rather than discovered dynamically because we control which items get
 * rehomed, not the plugins that register them.
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the rehomed-slugs constant.
 */
final class Rehomed_Slugs {

	/**
	 * Top-level slugs that are removed from `$menu` and re-homed inside the
	 * Woo tree when the feature is enabled.
	 *
	 * The `woocommerce` slug itself is intentionally NOT in this list — we
	 * keep Woo's own top-level registration (with its native submenu of
	 * Home/Orders/Products/etc.) as the single consolidated rail item.
	 */
	public const ALL = array(
		'edit.php?post_type=product',
		'wc-admin&path=/analytics/overview',
		'woocommerce-marketing',
		'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
		'wc-admin&path=/payments/connect',
		'wc-admin&path=/payments/overview',
		'woocommerce-payments',
		'klaviyo_settings',
	);

	/**
	 * WC-internal submenu slugs that we don't want surfaced in the rail.
	 *
	 * These are either legacy redirects or duplicates of items already in
	 * the default tree. Auto-attach skips them so the rail doesn't end up
	 * with two "Orders" / two "Extensions" entries, etc.
	 */
	public const AUTO_ATTACH_EXCLUDE = array(
		// Pre-HPOS Orders — HPOS (`wc-orders`) is the default tree entry.
		'edit.php?post_type=shop_order',
		// Legacy redirect page.
		'coupons-moved',
		// Legacy reports page (deprecated).
		'wc-reports',
		// Legacy Extensions page — the default tree uses the modern
		// `wc-admin&path=/extensions` Marketplace path instead.
		'wc-addons',
	);
}
