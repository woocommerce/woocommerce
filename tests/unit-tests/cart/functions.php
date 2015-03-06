<?php

namespace WooCommerce\Tests\Cart;

/**
 * Class Functions
 * @package WooCommerce\Tests\Cart
 */
class Functions extends \WC_Unit_Test_Case {

	/**
	 * Test wc_empty_cart()
	 *
	 * @since 2.3.0
	 */
	public function test_wc_empty_cart() {
		// Create dummy product
		$product = \WC_Helper_Product::create_simple_product();

		// Add the product to the cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Empty the cart
		wc_empty_cart();

		// Check if the cart is empty
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Delete the previously created product
		\WC_Helper_Product::delete_product( $product->id );
	}
}
