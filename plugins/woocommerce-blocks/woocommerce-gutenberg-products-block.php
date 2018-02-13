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
		array( 'wp-blocks', 'wp-element' ),
		rand() // @todo Change this to WC_VERSION when merged into WooCommerce.
	);

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
