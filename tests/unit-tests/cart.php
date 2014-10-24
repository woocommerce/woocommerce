<?php

class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * Helper method to get the checkout URL
	 *
	 * @since 2.3
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

			// Force SLL if needed
			if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
			}

			// Allow filtering of checkout URL
			$checkout_url = apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
		}

		return $checkout_url;
	}

	/**
	 * Test get_checkout_url over HTTP
	 *
	 * @since 2.3
	 */
	public function test_get_checkout_url_regular() {

		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SLL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'no' );

		$this->assertEquals( $this->get_checkout_url(), WC()->cart->get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test get_checkout_url over HTTP
	 *
	 * @since 2.3
	 */
	public function test_get_checkout_url_ssl() {

		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SLL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'yes' );

		$this->assertEquals( $this->get_checkout_url(), WC()->cart->get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test test_get_cart_url method
	 *
	 * @since 2.3
	 */
	public function test_get_cart_url() {
		$cart_page_id = wc_get_page_id( 'cart' );
		$this->assertEquals( apply_filters( 'woocommerce_get_cart_url', $cart_page_id ? get_permalink( $cart_page_id ) : '' ), WC()->cart->get_cart_url() );
	}

	/**
	 * Test add to cart simple product
	 */
	public function test_add_to_cart_simple() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart
		// Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1 ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test add to cart variable product
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_variable() {
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Add the product to the cart
		// Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1, $variation['variation_id'], array( 'Size' => ucfirst( $variation['attributes']['attribute_pa_size'] ) ) ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// @todo clean up the variable product
	}

	/**
	 * Test the find_product_in_cart method
	 *
	 * @since 2.3
	 */
	public function test_find_product_in_cart() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Generate cart id
		$cart_id = WC()->cart->generate_cart_id( $product->id );

		// Get the product from the cart
		$this->assertNotEquals( '', WC()->cart->find_product_in_cart( $cart_id ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

	}




}