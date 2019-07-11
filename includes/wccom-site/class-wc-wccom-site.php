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
		add_action( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_rest_namespace' ) );
	}

	/**
	 * Include support files.
	 */
	protected static function includes() {
		require_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper.php';
		require_once __DIR__ . '/class-wc-wccom-site-installer.php';
	}

	/**
	 * Register wccom-site REST namespace.
	 *
	 * @param array $namespaces List of registered namespaces.
	 *
	 * @return array Registered namespaces.
	 */
	public static function register_rest_namespace( $namespaces ) {
		require_once __DIR__ . '/rest-api/v1/class-wc-rest-wccom-site-installer-v1-controller.php';

		$namespaces['wccom-site/v1'] = array(
			'installer' => 'WC_REST_WCCOM_Site_Installer_V1_Controller',
		);

		return $namespaces;
	}
}

WC_WCCOM_Site::load();
