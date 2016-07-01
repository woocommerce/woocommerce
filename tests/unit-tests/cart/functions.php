<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Cart
 */
class WC_Tests_Cart_Functions extends WC_Unit_Test_Case {

	/**
	 * Helper method to get the checkout URL.
	 *
	 * @since 2.5.0
	 *
	 * @return string
	 */
	private function get_checkout_url() {

		// Get the checkout URL
		$checkout_page_id = wc_get_page_id( 'checkout' );

		$checkout_url = '';

		// Check if there is a checkout page
		if ( $checkout_page_id ) {

			// Get the permalink
			$checkout_url = get_permalink( $checkout_page_id );

			// Force SSL if needed
			if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
			}

			// Allow filtering of checkout URL
			$checkout_url = apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
		}

		return $checkout_url;
	}

	/**
	 * Test get_checkout_url over HTTP.
	 *
	 * @since 2.5.0
	 */
	public function test_get_checkout_url_regular() {
		// Make sure pages exist
		WC_Install::create_pages();
		
		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SSL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'no' );

		$this->assertEquals( $this->get_checkout_url(), wc_get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test get_checkout_url over HTTP.
	 *
	 * @since 2.5.0
	 */
	public function test_get_checkout_url_ssl() {
		// Make sure pages exist
		WC_Install::create_pages();

		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SSL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'yes' );

		$this->assertEquals( $this->get_checkout_url(), wc_get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test wc_empty_cart().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_empty_cart() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Empty the cart
		wc_empty_cart();

		// Check if the cart is empty
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Delete the previously created product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_format_list_of_items().
	 *
	 * @since 2.4
	 */
	public function test_wc_format_list_of_items() {
		$items = array( 'Title 1', 'Title 2' );

		$this->assertEquals( 'Title 1 and Title 2', wc_format_list_of_items( $items ) );
	}

	/**
	 * Test wc_cart_totals_subtotal_html().
	 *
	 * @todo  test with taxes incl./excl.
	 * @since 2.4
	 */
	public function test_wc_cart_totals_subtotal_html() {
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->id, 1 );

		$this->expectOutputString( wc_price( $product->price ), wc_cart_totals_subtotal_html() );

		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test wc_cart_totals_coupon_label().
	 *
	 * @since 2.4
	 */
	public function test_wc_cart_totals_coupon_label() {
		$coupon = WC_Helper_Coupon::create_coupon();

		$this->expectOutputString( apply_filters( 'woocommerce_cart_totals_coupon_label', 'Coupon: ' . $coupon->code ), wc_cart_totals_coupon_label( $coupon ) );

		WC_Helper_Coupon::delete_coupon( $coupon->id );
	}

	/**
	 * Test get_cart_url method.
	 *
	 * @since 2.5.0
	 */
	public function test_wc_get_cart_url() {
		$cart_page_url = wc_get_page_permalink( 'cart' );

		$this->assertEquals( apply_filters( 'woocommerce_get_cart_url', $cart_page_url ? $cart_page_url : '' ), wc_get_cart_url() );
	}
}
