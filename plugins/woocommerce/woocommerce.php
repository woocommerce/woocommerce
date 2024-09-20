<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An ecommerce toolkit that helps you sell anything. Beautifully.
 * Version: 9.4.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 * Requires at least: 6.5
 * Requires PHP: 7.4
 *
 * @package WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
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

add_filter(
	'hooked_block_woocommerce/order-confirmation-create-account',
	function ( $parsed_hooked_block, $hooked_block_type, $relative_position ) {
		// Has the hooked block been suppressed by a previous filter?
		if ( is_null( $parsed_hooked_block ) ) {
			return $parsed_hooked_block;
		}

		// Narrow to after the summary block.
		if ( 'after' !== $relative_position ) {
			return $parsed_hooked_block;
		}

		$parsed_hooked_block['attrs']['align'] = 'wide';
		$parsed_hooked_block['attrs']['lock']  = array(
			'remove' => true,
		);
		$parsed_hooked_block['innerBlocks']    = '';
		$parsed_hooked_block['innerContent']   = array(
			'<div class="wp-block-woocommerce-order-confirmation-create-account alignwide">
			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading">Create an account with Test Store</h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"is-style-checkmark-list"} -->
			<ul class="wp-block-list is-style-checkmark-list"><!-- wp:list-item -->
			<li>Faster future purchases</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Securely save payment info</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item -->
			<li>Track orders &amp; view shopping history</li>
			<!-- /wp:list-item --></ul>
			<!-- /wp:list -->
			</div>',
		);

		return $parsed_hooked_block;
	},
	10,
	3
);
