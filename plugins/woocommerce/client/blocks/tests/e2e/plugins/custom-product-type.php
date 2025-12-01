<?php
/**
 * Plugin Name: WooCommerce Blocks Test Custom Product Type
 * Description: Registers a custom product type.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-custom-product-type
 */

function woocommerce_register_custom_product_type( $product_types ) {
	$product_types[ 'custom-product-type' ] = 'Custom Product Type';
	return $product_types;
}

add_filter( 'product_type_selector', 'woocommerce_register_custom_product_type' );
