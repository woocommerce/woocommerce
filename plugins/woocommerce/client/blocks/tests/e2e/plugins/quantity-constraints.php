<?php
/**
 * Plugin Name: WooCommerce Blocks Test Quantity Constraints
 * Description: Used to modify quantity constraints for products.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-quantity-constraints
 */

declare(strict_types=1);

add_action(
	'woocommerce_init',
	function () {
		$tshirt_id       = wc_get_product_id_by_sku( 'woo-tshirt' );
		$blue_hoodie_id  = wc_get_product_id_by_sku( 'woo-hoodie-blue' );
		$green_hoodie_id = wc_get_product_id_by_sku( 'woo-hoodie-green' );

		add_filter(
			'woocommerce_quantity_input_min',
			function ( $min, $product ) use ( $tshirt_id, $blue_hoodie_id ) {
				if ( $product->get_id() === $tshirt_id || $product->get_id() === $blue_hoodie_id ) {
					return 4;
				}
				return $min;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_quantity_input_step',
			function ( $step, $product ) use ( $tshirt_id, $blue_hoodie_id ) {
				if ( $product->get_id() === $tshirt_id || $product->get_id() === $blue_hoodie_id ) {
					return 2;
				}
				return $step;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_quantity_input_max',
			function ( $max, $product ) use ( $tshirt_id, $blue_hoodie_id ) {
				if ( $product->get_id() === $tshirt_id || $product->get_id() === $blue_hoodie_id ) {
					return 8;
				}
				return $max;
			},
			10,
			2
		);

		add_filter(
			'woocommerce_is_sold_individually',
			function ( $val, $product ) use ( $green_hoodie_id ) {
				if ( $product->get_id() === $green_hoodie_id ) {
					return true;
				}
				return $val;
			},
			10,
			2
		);
	}
);
