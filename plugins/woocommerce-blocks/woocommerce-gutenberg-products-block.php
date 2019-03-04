<?php
/**
 * Plugin Name: WooCommerce Blocks
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: WooCommerce blocks for the Gutenberg editor.
 * Version: 2.0.0-alpha
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Text Domain:  woo-gutenberg-products-block
 * WC requires at least: 3.5
 * WC tested up to: 3.6
 *
 * @package WooCommerce\Blocks
 */

defined( 'ABSPATH' ) || die();

define( 'WGPB_VERSION', '2.0.0-alpha' );
define( 'WGPB_PLUGIN_FILE', __FILE__ );
define( 'WGPB_ABSPATH', dirname( WGPB_PLUGIN_FILE ) . '/' );

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {
	require_once plugin_dir_path( __FILE__ ) . 'assets/php/class-wgpb-block-library.php';

	// Remove core hooks in favor of our local feature plugin handlers.
	remove_action( 'init', array( 'WC_Block_Library', 'register_blocks' ) );
	remove_action( 'init', array( 'WC_Block_Library', 'register_assets' ) );
	remove_filter( 'block_categories', array( 'WC_Block_Library', 'add_block_category' ) );
	remove_action( 'admin_print_footer_scripts', array( 'WC_Block_Library', 'print_script_settings' ), 1 );

	$files_exist = file_exists( plugin_dir_path( __FILE__ ) . '/build/featured-product.js' );
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && ! $files_exist ) {
		add_action( 'admin_notices', 'wgpb_plugins_notice' );
	}

	add_action( 'rest_api_init', 'wgpb_register_api_routes' );
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

/**
 * Register extra API routes with functionality specific for product blocks.
 */
function wgpb_register_api_routes() {
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-products-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-categories-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-attributes-controller.php';
	include_once dirname( __FILE__ ) . '/includes/class-wgpb-product-attribute-terms-controller.php';

	$products = new WGPB_Products_Controller();
	$products->register_routes();

	$categories = new WGPB_Product_Categories_Controller();
	$categories->register_routes();

	$attributes = new WGPB_Product_Attributes_Controller();
	$attributes->register_routes();

	$attribute_terms = new WGPB_Product_Attribute_Terms_Controller();
	$attribute_terms->register_routes();
}
