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
		$this->assertEquals( 1, count( $discounts->get_items() ) );

		// Test setting items to an order.
		$discounts = new WC_Discounts();
		$discounts->set_items( $order->get_items() );
		$this->assertEquals( 1, count( $discounts->get_items() ) );

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
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		$tax_rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$product = WC_Helper_Product::create_simple_product();
		$product->set_tax_status( 'taxable' );
		$product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$coupon = new WC_Coupon;
		$coupon->set_code( 'test' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 20 );

		// Apply a percent discount.
		$coupon->set_discount_type( 'percent' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 8, $discounts->get_discounted_price( current( $discounts->get_items() ) ) );

		// Apply a fixed cart coupon.
		$coupon->set_discount_type( 'fixed_cart' );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 20, $discounts->get_discount( 'cart' ) );

		// Apply a fixed cart coupon.
		$coupon->set_discount_type( 'fixed_cart' );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 4 );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 20, $discounts->get_discount( 'cart' ) );

		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 40, $discounts->get_discount( 'cart' ) );

		// Apply a fixed product coupon.
		$coupon->set_discount_type( 'fixed_product' );
		$coupon->set_amount( 1 );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 4 );
		$discounts->set_items( WC()->cart->get_cart() );
		$discounts->apply_coupon( $coupon );
		$this->assertEquals( 36, $discounts->get_discounted_price( current( $discounts->get_items() ) ) );

		// Cleanup.
		WC()->cart->empty_cart();
		$product->delete( true );
		WC_Tax::_delete_tax_rate( $tax_rate_id );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}
}
