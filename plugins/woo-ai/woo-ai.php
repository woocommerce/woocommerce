<?php
/**
 * Plugin Name: Woo AI
 * Plugin URI: https://github.com/woocommerce/woocommerce/
 * Description: Enable AI experiments within the WooCommerce experience. <a href="https://automattic.com/ai-guidelines" target="_blank" rel="noopener noreferrer">Learn more</a>.
 * Version: 0.1.0
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Requires at least: 5.8
 * Tested up to: 6.0
 * WC requires at least: 6.7
 * WC tested up to: 7.0
 * Text Domain: woo-ai
 *
 * @package Woo_AI
 */

defined( 'ABSPATH' ) || exit;

// Define WOO_AI_FILE.
if ( ! defined( 'WOO_AI_FILE' ) ) {
	define( 'WOO_AI_FILE', __FILE__ );
}

/**
 * Load text domain before all other code.
 */
function _woo_ai_load_textdomain(): void {
	load_plugin_textdomain( 'woo-ai', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', '_woo_ai_load_textdomain' );

/**
 * Bootstrap plugin.
 */
function _woo_ai_bootstrap(): void {

	// Check if WooCommerce is enabled.
	if ( ! class_exists( 'WooCommerce' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai-admin-notices.php';
		$notices = new Woo_AI_Admin_Notices();

		add_action( 'admin_notices', array( $notices, 'woocoommerce_not_installed' ) );

		// Stop here.
		return;
	}

	add_action( 'before_woocommerce_init', function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	} );

	// Check if Jetpack is enabled.
	if ( ! class_exists( 'Jetpack' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai-admin-notices.php';
		$notices = new Woo_AI_Admin_Notices();

		add_action( 'admin_notices', array( $notices, 'jetpack_not_installed' ) );

		// Stop here.
		return;
	}

	if ( ! class_exists( 'Woo_AI' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai.php';

		register_activation_hook( __FILE__, array( 'Woo_AI', 'activate' ) );

		add_action( 'admin_init', array( 'Woo_AI', 'instance' ) );
	}

}

add_action(
	'wp_loaded',
	function () {
		require 'api/api.php';
		require_once dirname( __FILE__ ) . '/includes/exception/class-woo-ai-exception.php';
		require_once dirname( __FILE__ ) . '/includes/completion/class-completion-exception.php';
		require_once dirname( __FILE__ ) . '/includes/completion/interface-completion-service.php';
		require_once dirname( __FILE__ ) . '/includes/completion/class-jetpack-completion-service.php';
		require_once dirname( __FILE__ ) . '/includes/prompt-formatter/interface-prompt-formatter.php';
		require_once dirname( __FILE__ ) . '/includes/prompt-formatter/class-product-category-formatter.php';
		require_once dirname( __FILE__ ) . '/includes/prompt-formatter/class-product-attribute-formatter.php';
		require_once dirname( __FILE__ ) . '/includes/prompt-formatter/class-json-request-formatter.php';
		require_once dirname( __FILE__ ) . '/includes/product-data-suggestion/class-product-data-suggestion-exception.php';
		require_once dirname( __FILE__ ) . '/includes/product-data-suggestion/class-product-data-suggestion-request.php';
		require_once dirname( __FILE__ ) . '/includes/product-data-suggestion/class-product-data-suggestion-prompt-generator.php';
		require_once dirname( __FILE__ ) . '/includes/product-data-suggestion/class-product-data-suggestion-service.php';
	}
);

add_action( 'plugins_loaded', '_woo_ai_bootstrap' );
