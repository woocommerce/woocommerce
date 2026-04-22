<?php
/**
 * Renderer for navigation_v2.
 *
 * On Woo pages the body gets a `wc-nav-v2-active` class and our JS
 * (admin-navigation-v2.js) replaces the contents of the native #adminmenu
 * with the Woo tree. On non-Woo pages the native rail renders normally;
 * the same JS injects a second-level cascade into the woocommerce flyout.
 *
 * No HTML is emitted from PHP here — the previous separate <nav> rail was
 * replaced so that WP's admin-menu.css applies to our items natively
 * (instead of relying on an inline alias of the entire stylesheet).
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Sets the Woo-page body class; the rail is rendered client-side.
 */
class Renderer {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'admin_body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Add .wc-nav-v2-active to body on Woo pages so CSS / JS can key off it.
	 *
	 * @param string $classes Existing classes.
	 * @return string
	 */
	public function add_body_class( string $classes ): string {
		$tree = Menu_Reconciler::get_tree();
		if ( null !== $tree && Context::is_woo_page( $tree ) ) {
			$classes .= ' wc-nav-v2-active';
		}
		return $classes;
	}
}
