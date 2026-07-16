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

		// Get the checkout URL.
		$checkout_page_id = wc_get_page_id( 'checkout' );

		$checkout_url = '';

		// Check if there is a checkout page.
		if ( $checkout_page_id ) {

			// Get the permalink.
			$checkout_url = get_permalink( $checkout_page_id );

			// Force SSL if needed.
			if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
			}

			// Allow filtering of checkout URL.
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
		// Make sure pages exist.
		WC_Install::create_pages();

		// Force SSL checkout.
		update_option( 'woocommerce_force_ssl_checkout', 'no' );

		$this->assertEquals( $this->get_checkout_url(), wc_get_checkout_url() );
	}

	/**
	 * Test get_checkout_url over HTTP.
	 *
	 * @since 2.5.0
	 */
	public function test_get_checkout_url_ssl() {
		// Make sure pages exist.
		WC_Install::create_pages();

		// Force SSL checkout.
		update_option( 'woocommerce_force_ssl_checkout', 'yes' );

		$this->assertEquals( $this->get_checkout_url(), wc_get_checkout_url() );
	}

	/**
	 * Test wc_empty_cart().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_empty_cart() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Empty the cart.
		wc_empty_cart();

		// Check if the cart is empty.
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test wc_clear_cart_after_payment() clears the cart when the order cart hash matches the cart hash.
	 */
	public function test_wc_clear_cart_after_payment_clears_matching_cart_hash() {
		global $wp;

		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 4 );

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_cart_hash( WC()->cart->get_cart_hash() );
		$order->save();

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $order->get_order_key();

		wc_clear_cart_after_payment();

		unset( $wp->query_vars['order-received'], $_GET['key'] );

		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );
	}

	/**
	 * Test wc_clear_cart_after_payment() preserves the cart when the order cart hash does not match the cart hash.
	 */
	public function test_wc_clear_cart_after_payment_preserves_different_cart_hash() {
		global $wp;

		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$order->set_cart_hash( 'different-cart-hash' );
		$order->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$wp->query_vars['order-received'] = $order->get_id();
		$_GET['key']                      = $order->get_order_key();

		wc_clear_cart_after_payment();

		unset( $wp->query_vars['order-received'], $_GET['key'] );

		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		WC()->cart->empty_cart();
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
	 * @since 2.4
	 */
	public function test_wc_cart_totals_subtotal_html() {
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->add_to_cart( $product->get_id(), 1 );

		$this->expectOutputString( wc_price( $product->get_price( 'edit' ) ), wc_cart_totals_subtotal_html() );
	}

	/**
	 * Test wc_cart_totals_coupon_label().
	 *
	 * @since 2.4
	 */
	public function test_wc_cart_totals_coupon_label() {
		$coupon = WC_Helper_Coupon::create_coupon();

		$this->expectOutputString( apply_filters( 'woocommerce_cart_totals_coupon_label', 'Coupon: ' . $coupon->get_code() ), wc_cart_totals_coupon_label( $coupon ) );
	}

	/**
	 * @testdox Coupon totals pass positive amounts and explicit negative display intent to wc_price.
	 */
	public function test_wc_cart_totals_coupon_html_passes_explicit_negative_display_intent_to_wc_price(): void {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			unset( $formatted_price );

			return true === $args['is_negative'] ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		wc_cart_totals_coupon_html( $coupon );
		$coupon_html = ob_get_clean();

		remove_filter( 'wc_price', $price_filter );
		WC()->cart->empty_cart();
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );

		$this->assertStringStartsWith( 'localized-negative-price:1:1 ', $coupon_html, 'The wc_price filter should receive the positive coupon amount and explicit negative display intent.' );
	}

	/**
	 * @testdox Zero-value coupon totals pass explicit negative display intent to wc_price.
	 */
	public function test_wc_cart_totals_coupon_html_passes_negative_display_intent_for_zero_amount(): void {
		$product = WC_Helper_Product::create_simple_product();
		$coupon  = WC_Helper_Coupon::create_coupon( 'zero-value-coupon', array( 'coupon_amount' => '0' ) );

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $product->get_id() );
		WC()->cart->apply_coupon( $coupon->get_code() );
		WC()->cart->calculate_totals();

		$price_filter = static function ( $price_html, $formatted_price, $args, $unformatted_price, $original_price ) {
			unset( $formatted_price );

			return true === $args['is_negative'] ? 'localized-negative-price:' . (float) $original_price . ':' . $unformatted_price : $price_html;
		};
		add_filter( 'wc_price', $price_filter, 10, 5 );

		ob_start();
		wc_cart_totals_coupon_html( $coupon );
		$coupon_html = ob_get_clean();

		remove_filter( 'wc_price', $price_filter );
		WC()->cart->empty_cart();
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );

		$this->assertStringStartsWith( 'localized-negative-price:0:0 ', $coupon_html, 'Zero-value coupons should pass explicit negative display intent without changing the numeric filter values.' );
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

	/**
	 * Test wc_add_to_cart_message with cart page defined.
	 */
	public function test_wc_add_to_cart_message_with_cart_page_defined() {
		$product = WC_Helper_Product::create_simple_product();

		wc_create_page( 'cart', 'woocommerce_cart_page_id', 'Cart', '' );

		$cart_page_url = wc_get_page_permalink( 'cart' );

		$wp_button_class = esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' );

		$message = wc_add_to_cart_message( array( $product->get_id() => 1 ), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 3 ), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 1 ), true, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 3 ), true, true );
		$this->assertEquals( '3 &times; &ldquo;Dummy Product&rdquo; have been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		$message = wc_add_to_cart_message( $product->get_id(), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		$message = wc_add_to_cart_message( $product->get_id(), true, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart. <a href="' . $cart_page_url . '" class="button wc-forward' . $wp_button_class . '">View cart</a>', $message );

		delete_option( 'woocommerce_cart_page_id' );
	}

	/**
	 * Test wc_add_to_cart_message with cart page not defined.
	 */
	public function test_wc_add_to_cart_message_with_cart_page_not_defined() {
		$product = WC_Helper_Product::create_simple_product();

		$message = wc_add_to_cart_message( array( $product->get_id() => 1 ), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart.', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 3 ), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart.', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 1 ), true, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart.', $message );

		$message = wc_add_to_cart_message( array( $product->get_id() => 3 ), true, true );
		$this->assertEquals( '3 &times; &ldquo;Dummy Product&rdquo; have been added to your cart.', $message );

		$message = wc_add_to_cart_message( $product->get_id(), false, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart.', $message );

		$message = wc_add_to_cart_message( $product->get_id(), true, true );
		$this->assertEquals( '&ldquo;Dummy Product&rdquo; has been added to your cart.', $message );
	}
}
