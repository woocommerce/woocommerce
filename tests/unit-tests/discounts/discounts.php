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
		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add product to a dummy order.
		$order = new WC_Order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order->save();

		// Test setting items to the cart.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );
		$this->assertEquals( array( (object) array( 'price' => '10', 'discounted_price' => '10', 'quantity' => 1 ) ), $discounts->get_items() );

		// Test setting items to an order.
		$discounts = new WC_Discounts();
		$discounts->set_items( $order->get_items() );
		$this->assertEquals( array( (object) array( 'price' => '10', 'discounted_price' => '10', 'quantity' => 1 ) ), $discounts->get_items() );

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
		$product->delete( true );
		$order->delete( true );
	}

	/**
	 * Test applying a coupon to a set of items.
	 */
	public function test_apply_coupon() {
		$discounts = new WC_Discounts();

		// Create dummy content.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$coupon = new WC_Coupon;
		$coupon->set_code( 'test' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 20 );

		// Apply a percent discount.
		$coupon->set_discount_type( 'percent' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( (object) array( 'price' => '10', 'discounted_price' => '8', 'quantity' => 1 ) ), $discounts->get_items() );

		// Apply a fixed coupon.
		$coupon->set_discount_type( 'fixed' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( (object) array( 'price' => '10', 'discounted_price' => '0', 'quantity' => 1 ) ), $discounts->get_items() );

		// Apply a fixed coupon.
		$coupon->set_discount_type( 'fixed_cart' );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 4 );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( array( (object) array( 'price' => '40', 'discounted_price' => '20', 'quantity' => 4 ) ), $discounts->get_items() );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
	}
}
