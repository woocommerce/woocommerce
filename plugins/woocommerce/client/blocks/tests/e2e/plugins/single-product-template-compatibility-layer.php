<?php
/**
 * Plugin Name: WooCommerce Blocks Test Single Product Template Compatibility Layer
 * Description: Adds custom content to the Shop page with Product Collection included
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-single-product-template-compatibility-layer
 */

$hooks = array(
	'woocommerce_before_main_content',
	'woocommerce_sidebar',
	'woocommerce_before_add_to_cart_button',
	'woocommerce_before_single_product',
	'woocommerce_before_single_product_summary',
	'woocommerce_single_product_summary',
	'woocommerce_product_meta_start',
	'woocommerce_product_meta_end',
	'woocommerce_share',
	'woocommerce_after_single_product_summary',
	'woocommerce_after_single_product',
	'woocommerce_after_main_content',
	'woocommerce_before_add_to_cart_form',
	'woocommerce_after_add_to_cart_form',
	'woocommerce_before_add_to_cart_quantity',
	'woocommerce_after_add_to_cart_quantity',
	'woocommerce_after_add_to_cart_button',
	'woocommerce_before_variations_form',
	'woocommerce_after_variations_form'
);

foreach ( $hooks as $hook ) {
	add_action(
		$hook,
		function () use ( $hook ) {
			echo '<p data-testid="' . esc_attr( $hook ) . '">
			Hook: ' . esc_html( $hook ) . '
		</p>';
		}
	);
}
