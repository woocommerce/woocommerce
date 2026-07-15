<?php
/**
 * Plugin Name: WooCommerce Blocks Test Item Data Display Malformed
 * Description: Adds a trailing malformed item_data entry (or two consecutive ones) to cart items for Mini-Cart e2e tests.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-item-data-display-malformed
 */

declare(strict_types=1);

add_action(
	'woocommerce_init',
	function () {
		add_filter(
			'woocommerce_get_item_data',
			function ( $item_data, $cart_item ) {
				// A well-formed leading entry, so there is always a defined
				// "last visible entry" to check the separator against.
				$item_data[] = array(
					'key'   => 'Gift Message',
					'value' => 'Happy Birthday!',
				);

				// Malformed: no usable key/attribute/name and no usable
				// display/value.
				$item_data[] = array(
					'key'   => '',
					'value' => '',
				);

				// A cart line added with quantity 2 instead ends with two
				// *consecutive* malformed entries, so both the single- and
				// double-trailing-malformed scenarios are covered by this
				// one fixture.
				if ( isset( $cart_item['quantity'] ) && 2 === (int) $cart_item['quantity'] ) {
					$item_data[] = array(
						'key'   => '',
						'value' => '',
					);
				}

				return $item_data;
			},
			10,
			2
		);
	}
);
