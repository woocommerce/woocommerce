<?php
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
	 */
	public const ALL = array(
		'woocommerce',
		'edit.php?post_type=product',
		'wc-admin&path=/analytics/overview',
		'woocommerce-marketing',
		'admin.php?page=wc-settings&tab=checkout&from=PAYMENTS_MENU_ITEM',
		'wc-admin&path=/payments/connect',
		'wc-admin&path=/payments/overview',
		'woocommerce-payments',
		'klaviyo_settings',
	);
}
