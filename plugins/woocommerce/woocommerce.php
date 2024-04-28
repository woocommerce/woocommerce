<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 8.9.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.4
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

function add_paragraph_after_order_summary_heading( $hooked_block_types, $relative_position, $anchor_block_type, $context ) {
	if ( 'woocommerce/cart-order-summary-heading-block' === $anchor_block_type && 'after' === $relative_position ) {
		$hooked_block_types[] = 'core/paragraph';
	}
	return $hooked_block_types;
}
add_filter( 'hooked_block_types', 'add_paragraph_after_order_summary_heading', 10, 4 );

function modify_hooked_paragraph_block_after_order_summary_heading( $parsed_hooked_block, $hooked_block_type, $relative_position, $parsed_anchor_block, $context ) {

	// Has the hooked block been suppressed by a previous filter?
	if ( is_null( $parsed_hooked_block ) ) {
		return $parsed_hooked_block;
	}
	$parsed_hooked_block['innerContent'] = array(
		'<p>Hello</p>',
	);

		return $parsed_hooked_block;
}
add_filter( 'hooked_block_core/paragraph', 'modify_hooked_paragraph_block_after_order_summary_heading', 10, 5 );
