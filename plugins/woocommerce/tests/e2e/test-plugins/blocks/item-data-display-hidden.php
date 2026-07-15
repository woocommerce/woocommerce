<?php
/**
 * Plugin Name: WooCommerce Blocks Test Item Data Display Hidden
 * Description: Adds a trailing explicitly-hidden item_data entry (or two consecutive ones) to cart items for Mini-Cart e2e tests.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-item-data-display-hidden
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

				// Well-formed (usable key/value) but explicitly hidden.
				$item_data[] = array(
					'key'    => 'Secret',
					'value'  => 'v',
					'hidden' => true,
				);

				// A cart line added with quantity 2 instead ends with two
				// *consecutive* explicitly-hidden entries, so both the
				// single- and double-trailing-hidden scenarios are covered
				// by this one fixture.
				if ( isset( $cart_item['quantity'] ) && 2 === (int) $cart_item['quantity'] ) {
					$item_data[] = array(
						'key'    => 'Secret 2',
						'value'  => 'v2',
						'hidden' => true,
					);
				}

				return $item_data;
			},
			10,
			2
		);
	}
);
