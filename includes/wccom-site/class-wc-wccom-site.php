<?php
/**
 * WooCommerce.com Product Installation.
 *
 * @class    WC_WCCOM_Site_Installer
 * @package  WooCommerce/WCCOM_Site
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_WCCOM_Site Class
 *
 * Main class for WooCommerce.com connected site.
 */
class WC_WCCOM_Site {
	/**
	 * Load the WCCOM site class.
	 */
	public static function load() {
		self::includes();

		add_action( 'woocommerce_wccom_install_products', array( 'WC_WCCOM_Site_Installer', 'install' ) );
	}

	/**
	 * Include support files.
	 */
	protected static function includes() {
		require_once( WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php' );
		require_once( __DIR__ . '/class-wc-wccom-site-installer.php' );
	}
}

WC_WCCOM_Site::load();
