<?php
/**
 * Plugin Name: WooCommerce Gutenberg Products Block
 * Plugin URI: https://github.com/woocommerce/woocommerce-gutenberg-products-block
 * Description: Prototype of the WooCommerce Gutenberg Products block.
 * Version: 1.0.0
 * Author: Automattic
 * Author URI: https://woocommerce.com
 */

defined( 'ABSPATH' ) || die();

/**
 * Load up the assets if Gutenberg is active.
 */
function wgpb_initialize() {

	if ( function_exists( 'register_block_type' ) ) {
		add_action( 'init', 'wgpb_register_products_block' );
	}

}
add_action( 'woocommerce_loaded', 'wgpb_initialize' );

/**
 * Register the Products block and its scripts.
 */
function wgpb_register_products_block() {
	wp_register_script(
		'woocommerce-products-block-editor',
		plugins_url( 'assets/js/products-block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element', 'react-transition-group' ),
		rand() // @todo Change this to WC_VERSION when merged into WooCommerce.
	);

	$product_block_data = array(
		'min_columns' => wc_get_theme_support( 'product_grid::min_columns', 1 ),
		'max_columns' => wc_get_theme_support( 'product_grid::max_columns', 6 ),
		'default_columns' => wc_get_default_products_per_row(),
		'min_rows' => wc_get_theme_support( 'product_grid::min_rows', 1 ),
		'max_rows' => wc_get_theme_support( 'product_grid::max_rows', 6 ),
		'default_rows' => wc_get_default_product_rows_per_page(),
	);
	wp_localize_script( 'woocommerce-products-block-editor', 'wc_product_block_data', $product_block_data );

	wp_register_style(
		'woocommerce-products-block-editor',
		plugins_url( 'assets/css/gutenberg-products-block.css', __FILE__ ),
		array( 'wp-edit-blocks' ),
		rand() // @todo Change this to WC_VERSION when merged into WooCommerce.
	);

	register_block_type( 'woocommerce/products', array(
		'editor_script' => 'woocommerce-products-block-editor',
		'editor_style'  => 'woocommerce-products-block-editor',
	) );
}

/**
 * Register extra scripts needed.
 */
function wgpb_extra_gutenberg_scripts() {
	wp_enqueue_script(
		'react-transition-group',
		plugins_url( 'assets/js/vendor/react-transition-group.js', __FILE__ ),
		array( 'wp-blocks', 'wp-element' ),
		'2.2.1'
	);
}
add_action( 'enqueue_block_assets', 'wgpb_extra_gutenberg_scripts' );