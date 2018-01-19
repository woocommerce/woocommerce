<?php
/**
 * Functions for registering and handling the Products block.
 *
 * @package  WooCommerce/Gutenberg
 * @since 3.4.0
 */

/**
 * Register the Products block and its scripts.
 */
function wc_register_products_block() {
	wp_register_script(
		'woocommerce-products-block-editor',
		WC()->plugin_url() . '/assets/js/gutenberg/products-block.js',
		array( 'wp-blocks', 'wp-element' ),
		rand() // @todo Change this to WC_VERSION later.
	);

	wp_register_style(
		'woocommerce-products-block-editor',
		WC()->plugin_url() . '/assets/css/gutenberg-products-block.css',
		array( 'wp-edit-blocks' ),
		rand() // @todo Change this to WC_VERSION later.
	);

	register_block_type( 'woocommerce/products', array(
		'editor_script' => 'woocommerce-products-block-editor',
		'editor_style'  => 'woocommerce-products-block-editor',
	) );
}
add_action( 'init', 'wc_register_products_block' );
