<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woo.com/
 * Description: An eCommerce toolkit that helps you sell anything. Beautifully.
 * Version: 8.5.0-dev
 * Author: Automattic
 * Author URI: https://woo.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.3
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WC_BLOCKS_IS_FEATURE_PLUGIN' ) ) {
	define( 'WC_BLOCKS_IS_FEATURE_PLUGIN', true );
}

// Test Change

// Load core packages and the autoloader.
require __DIR__ . '/src/Autoloader.php';
require __DIR__ . '/src/Packages.php';

if ( ! \Automattic\WooCommerce\Autoloader::init() ) {
	return;
}
\Automattic\WooCommerce\Packages::init();

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce', false ) ) {
	include_once dirname( WC_PLUGIN_FILE ) . '/includes/class-woocommerce.php';
}

// Initialize dependency injection.
$GLOBALS['wc_container'] = new Automattic\WooCommerce\Container();

/**
 * Returns the main instance of WC.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return WooCommerce::instance();
}

/**
 * Returns the WooCommerce object container.
 * Code in the `includes` directory should use the container to get instances of classes in the `src` directory.
 *
 * @since  4.4.0
 * @return \Automattic\WooCommerce\Container The WooCommerce object container.
 */
function wc_get_container() {
	return $GLOBALS['wc_container'];
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();

// Jetpack's Rest_Authentication needs to be initialized even before plugins_loaded.
if ( class_exists( \Automattic\Jetpack\Connection\Rest_Authentication::class ) ) {
	\Automattic\Jetpack\Connection\Rest_Authentication::init();
}


add_action(
	'woocommerce_blocks_loaded',
	function() {
		woocommerce_blocks_register_checkout_field(
			array(
				'id'             => 'plugin-namespace/vat-number',
				'label'          => __( 'VAT Number', 'woo-gutenberg-products-block' ),
				'optionalLabel'  => __( 'VAT Number (optional)', 'woo-gutenberg-products-block' ),
				'autocomplete'   => 'vat-number',
				'autocapitalize' => 'characters',
				'location'       => 'address',
				'type'           => 'text',
			)
		);
		woocommerce_blocks_register_checkout_field(
			array(
				'id'             => 'plugin-namespace/road-type',
				'label'          => __( 'Road type', 'woo-gutenberg-products-block' ),
				'optionalLabel'  => __( 'Road type (optional)', 'woo-gutenberg-products-block' ),
				'autocomplete'   => 'road-type',
				'required'       => 'true',
				'autocapitalize' => 'characters',
				'location'       => 'address',
				'type'           => 'select',
				'options'        => array(
					array(
						'value' => 'alleyway',
						'label' => __( 'Alleyway', 'woo-gutenberg-products-block' ),
					),
					array(
						'value' => 'avenue',
						'label' => __( 'Avenue', 'woo-gutenberg-products-block' ),
					),
					array(
						'value' => 'boulevard',
						'label' => __( 'Boulevard', 'woo-gutenberg-products-block' ),
					),
					array(
						'value' => 'cul-de-sac',
						'label' => __( 'Cul-de-sac', 'woo-gutenberg-products-block' ),
					),
					array(
						'value' => 'street',
						'label' => __( 'Street', 'woo-gutenberg-products-block' ),
					),
				),
			)
		);

		woocommerce_blocks_register_checkout_field(
			array(
				'id'             => 'plugin-namespace/leave-at-door',
				'label'          => __( 'Leave at door?', 'woo-gutenberg-products-block' ),
				'optionalLabel'  => __( 'Leave at door (optional)', 'woo-gutenberg-products-block' ),
				'location'       => 'address',
				'type'           => 'checkbox',
			)
		);
	}
);

add_filter('woocommerce_get_country_locale', function($locale) {
	$locale['US']['plugin-namespace/road-type']['priority'] = 29;
	return $locale;
});