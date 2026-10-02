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

final class Item_Data_Display_Hidden_Test_Fixture {
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
	 * Add explicitly hidden item data entries.
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
		if ( 2 === (int) ( $cart_item['quantity'] ?? 0 ) ) {
			$item_data[] = array(
				'key'    => 'Secret 2',
				'value'  => 'v2',
				'hidden' => true,
			);
		}

		return $item_data;
	}
}

new Item_Data_Display_Hidden_Test_Fixture();
