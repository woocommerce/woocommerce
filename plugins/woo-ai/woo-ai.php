<?php
/**
 * Plugin Name: Woo AI
 * Plugin URI: https://github.com/woocommerce/woocommerce/
 * Description: Enable AI experiments within the WooCommerce experience. <a href="https://automattic.com/ai-guidelines" target="_blank" rel="noopener noreferrer">Learn more</a>.
 * Version: 0.6.0
 * Author: WooCommerce
 * Author URI: woocommerce.com/
 * Requires at least: 5.8
 * Tested up to: 6.5
 * WC requires at least: 6.7
 * WC tested up to: 8.7
 * Text Domain: woo-ai
 * Requires Plugins: woocommerce
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

	add_action(
		'before_woocommerce_init',
		function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}
	);

	if ( class_exists( 'Automattic\Jetpack\Forms\ContactForm\Admin' ) ) {
		remove_action( 'media_buttons', array( Automattic\Jetpack\Forms\ContactForm\Admin::init(), 'grunion_media_button' ), 999 );
	}

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

	if ( ! class_exists( 'Woo_AI_Settings' ) ) {
		include dirname( __FILE__ ) . '/includes/class-woo-ai-settings.php';
		add_action( 'wp_loaded', array( 'Woo_AI_Settings', 'instance' ), 10 );
	}

}

add_action( 'plugins_loaded', '_woo_ai_bootstrap' );
