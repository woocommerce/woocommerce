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

	/**
	 * Test wc_format_list_of_items()
	 *
	 * @since 2.4
	 */
	public function test_wc_format_list_of_items() {
		$items = array( 'Title 1', 'Title 2' );

		$this->assertEquals( "&ldquo;Title 1&rdquo; and &ldquo;Title 2&rdquo;", wc_format_list_of_items( $items ) );
	}

	/**
	 * Test wc_cart_totals_subtotal_html()
	 *
	 * @todo  test with taxes incl./excl.
	 * @since 2.4
	 */
	public function test_wc_cart_totals_subtotal_html() {
		$product = \WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->id, 1 );

		$this->expectOutputString( wc_price( $product->price ), wc_cart_totals_subtotal_html() );

		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_cart_totals_coupon_label()
	 *
	 * @since 2.4
	 */
	public function test_wc_cart_totals_coupon_label() {
		$coupon = \WC_Helper_Coupon::create_coupon();

		$this->expectOutputString( apply_filters( 'woocommerce_cart_totals_coupon_label', 'Coupon: ' . $coupon->code ), wc_cart_totals_coupon_label( $coupon ) );

		\WC_Helper_Coupon::delete_coupon( $coupon->id );
	}
}
