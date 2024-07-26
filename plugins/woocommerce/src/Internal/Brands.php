<?php
/**
 * Brands class file.
 */

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Class to initiate Brands functionality in core.
 */
class Brands {

	/**
	 * Class initialization
	 *
	 * @internal
	 */
	final public static function init() {

		// If the WooCommerce Brands plugin is activated via the WP CLI using the '--skip-plugins' flag, deactivate it here.
		if ( function_exists( 'wc_brands_init' ) ) {
			remove_action( 'plugins_loaded', 'wc_brands_init', 1);
		}

		include_once WC_ABSPATH . 'includes/class-wc-brands.php';
		include_once WC_ABSPATH . 'includes/class-wc-brands-coupons.php';
		include_once WC_ABSPATH . 'includes/class-wc-brands-brand-settings-manager.php';
		include_once WC_ABSPATH . 'includes/wc-brands-functions.php';

		if ( wc_current_theme_is_fse_theme() ) {
			include_once WC_ABSPATH . 'includes/blocks/class-wc-brands-block-templates.php';
			include_once WC_ABSPATH . 'includes/blocks/class-wc-brands-block-template-utils-duplicated.php';
		}

		if ( is_admin() ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-brands.php';
		}
	}
}
