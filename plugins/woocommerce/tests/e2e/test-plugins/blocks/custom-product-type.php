<?php
/**
 * Plugin Name: WooCommerce Blocks Test Custom Product Type
 * Description: Registers a custom product type.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-custom-product-type
 */

// phpcs:disable Squiz.Commenting.ClassComment.Missing
// phpcs:disable Squiz.Commenting.FunctionComment.Missing

declare(strict_types=1);

add_action(
	'init',
	function () {
		class WC_Product_Custom extends \WC_Product_Simple {
			public function get_type() {
				return 'custom';
			}
		}
	}
);

add_filter(
	'woocommerce_product_class',
	function ( $classname, $product_type ) {
		if ( 'custom' === $product_type ) {
			return WC_Product_Custom::class;
		}
		return $classname;
	},
	10,
	2
);

add_action(
	'woocommerce_custom_add_to_cart',
	function () {
		global $product;

		echo '<form class="cart" action="' . esc_url( $product->get_permalink() ) . '" method="post" enctype="multipart/form-data">';
		woocommerce_quantity_input(
			array(
				'min_value'   => 1,
				'max_value'   => 10,
				'input_value' => 1,
			)
		);

		echo '<button type="submit" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '">Add to cart</button></form>';
	}
);

add_filter(
	'product_type_selector',
	function ( $product_types ) {
		$product_types['custom'] = 'Custom Product Type';
		return $product_types;
	}
);
