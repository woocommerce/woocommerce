<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 2.1.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * WC requires at least: 3.6
 * WC tested up to: 3.6
 *
 * @package WooCommerce\Blocks
 */

defined( 'ABSPATH' ) || die();

define( 'WGPB_VERSION', '2.1.0' );
define( 'WGPB_PLUGIN_FILE', __FILE__ );
define( 'WGPB_ABSPATH', dirname( WGPB_PLUGIN_FILE ) . '/' );

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {
	require_once plugin_dir_path( __FILE__ ) . 'assets/php/class-wgpb-block-library.php';

	// Remove core hook in favor of our local feature plugin handler.
	remove_action( 'init', array( 'WC_Block_Library', 'init' ) );
	// Remove core hooks from pre-3.6 (in 3.6.2 all functions were moved to one hook on init).
	remove_action( 'init', array( 'WC_Block_Library', 'register_blocks' ) );
	remove_action( 'init', array( 'WC_Block_Library', 'register_assets' ) );
	remove_filter( 'block_categories', array( 'WC_Block_Library', 'add_block_category' ) );
	remove_action( 'admin_print_footer_scripts', array( 'WC_Block_Library', 'print_script_settings' ), 1 );

	$files_exist = file_exists( plugin_dir_path( __FILE__ ) . '/build/featured-product.js' );
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && ! $files_exist ) {
		add_action( 'admin_notices', 'wgpb_plugins_notice' );
	}
}
add_action( 'woocommerce_loaded', 'wgpb_initialize' );

/**
 * Display a warning about building files.
 */
function wgpb_plugins_notice() {
	echo '<div class="error"><p>';
	printf(
		/* Translators: %1$s is the install command, %2$s is the build command, %3$s is the watch command. */
		esc_html__( 'WooCommerce Blocks development mode requires files to be built. From the plugin directory, run %1$s to install dependencies, %2$s to build the files or %3$s to build the files and watch for changes.', 'woo-gutenberg-products-block' ),
		'<code>npm install</code>',
		'<code>npm run build</code>',
		'<code>npm start</code>'
	);
	echo '</p></div>';
}
