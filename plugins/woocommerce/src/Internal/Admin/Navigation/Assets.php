<?php

declare( strict_types = 1 );

/**
 * Asset enqueuing for navigation_v2.
 *
 * Enqueues the SCSS/JS that power the native-flyout cascade and (on Woo
 * pages) the rail replacement. Since the rail replacement re-uses WP's
 * `#adminmenu` element directly, admin-menu.css applies natively — no CSS
 * alias trick required.
 *
 * @package WooCommerce\Internal\Admin\Navigation
 */

namespace Automattic\WooCommerce\Internal\Admin\Navigation;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the navigation_v2 CSS and JS.
 */
class Assets {

	public const STYLE_HANDLE  = 'wc-nav-v2';
	public const SCRIPT_HANDLE = 'wc-nav-v2';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue.
	 */
	public function enqueue(): void {
		if ( ! is_admin() ) {
			return;
		}

		$version = defined( 'WC_VERSION' ) ? WC_VERSION : '1.0.0';

		wp_enqueue_style(
			self::STYLE_HANDLE,
			WC()->plugin_url() . '/assets/css/admin-navigation-v2.css',
			array( 'admin-menu' ),
			$version
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WC()->plugin_url() . '/assets/js/admin/admin-navigation-v2.js',
			// `common` declared as a dep so WP's hoverIntent binding on
			// `li.wp-has-submenu` runs before our DOM-ready handler — our
			// injectNativeCascade() unbinds those hover handlers on the
			// WooCommerce top-level item so our longer close delay can win.
			array( 'jquery', 'common' ),
			$version,
			true
		);

		// Expose the computed tree and current-page flag to JS.
		$tree = Menu_Reconciler::get_tree() ?? array();

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcNavV2Config',
			array(
				'isWooPage'      => Context::is_woo_page( $tree ) ? '1' : '0',
				'adminUrl'       => admin_url(),
				'wpDashboardUrl' => admin_url( 'index.php' ),
				'backLabel'      => __( 'Back', 'woocommerce' ),
				'tree'           => $tree,
			)
		);
	}
}
