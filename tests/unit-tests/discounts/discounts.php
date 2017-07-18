<?php

/**
 * Test for the discounts class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discounts extends WC_Unit_Test_Case {

	/**
	 * Test get and set items.
	 */
	public function test_get_set_items() {
		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add product to a dummy order.
		$order = new WC_Order();
		$order->add_product( $product, 4 );
		$order->calculate_totals();
		$order->save();

		// Test setting items to the cart.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );
		$this->assertEquals( array( (object) array( 'price' => '10', 'quantity' => 1, 'discount' => 0 ) ), $discounts->get_items() );

		// Test setting items to an order.
		$discounts = new WC_Discounts();
		$discounts->set_items( $order->get_items() );
		$this->assertEquals( array( (object) array( 'price' => '40', 'quantity' => 4, 'discount' => 0 ) ), $discounts->get_items() );

		// Empty array of items.
		$discounts = new WC_Discounts();
		$discounts->set_items( array() );
		$this->assertEquals( array(), $discounts->get_items() );

		// Invalid items.
		$discounts = new WC_Discounts();
		$discounts->set_items( false );
		$this->assertEquals( array(), $discounts->get_items() );

		// Cleanup.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Product::delete_product( $product->get_id() );
	}
}
