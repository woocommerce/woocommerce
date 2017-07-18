<?php

/**
 * Test for the discounts class.
 * @package WooCommerce\Tests\Discounts
 */
class WC_Tests_Discounts extends WC_Unit_Test_Case {


	public function test_get_set_items() {
		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart x1, calc and test
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();

		// Tests.
		$discounts = new WC_Discounts();
		$discounts->set_items( WC()->cart->get_cart() );

		$this->assertEquals( array( array( 'price' => '10', 'qty' => 1, 'discount' => 0 ) ), $discounts->get_items() );

		// Cleanup.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Product::delete_product( $product->get_id() );
	}
}
