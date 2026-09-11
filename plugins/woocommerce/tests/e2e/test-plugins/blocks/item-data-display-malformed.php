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

final class Item_Data_Display_Malformed_Test_Fixture {
	/**
	 * Register fixture hooks.
	 *
	 * @internal
	 */
	public function __construct() {
		add_action( 'woocommerce_init', array( $this, 'handle_woocommerce_init' ) );
	}

	/**
	 * Register the item data filter.
	 *
	 * @internal
	 */
	public function handle_woocommerce_init(): void {
		add_filter(
			'woocommerce_get_item_data',
			array( $this, 'handle_woocommerce_get_item_data' ),
			10,
			2
		);
	}

	/**
	 * Add malformed item data entries.
	 *
	 * @internal
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Cart item data.
	 * @return array Filtered item data.
	 */
	public function handle_woocommerce_get_item_data( array $item_data, array $cart_item ): array {
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
		if ( 2 === (int) ( $cart_item['quantity'] ?? 0 ) ) {
			$item_data[] = array(
				'key'   => '',
				'value' => '',
			);
		}

		return $item_data;
	}
}

new Item_Data_Display_Malformed_Test_Fixture();
