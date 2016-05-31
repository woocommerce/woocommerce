<?php

/**
 * Class Cart.
 * @package WooCommerce\Tests\Cart
 */
class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * Test some discount logic which has caused issues in the past.
	 * Tickets:
	 * 	https://github.com/woothemes/woocommerce/issues/10573
	 *  https://github.com/woothemes/woocommerce/issues/10963
	 *
	 * Due to discounts being split amongst products in cart.
	 */
	public function test_cart_get_discounted_price() {
		global $wpdb;

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		# Test case 1 #10963

		// Create dummy coupon - fixed cart, 1 value
		$coupon  = WC_Helper_Coupon::create_coupon();

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart x1, calc and test
		WC()->cart->add_to_cart( $product->id, 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '9.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x2, calc and test
		WC()->cart->add_to_cart( $product->id, 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '19.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x3, calc and test
		WC()->cart->add_to_cart( $product->id, 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '29.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Clean up the cart
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		# Test case 2 #10573
		update_post_meta( $product->id, '_regular_price', '29.95' );
		update_post_meta( $product->id, '_price', '29.95' );
		update_post_meta( $coupon->id, 'discount_type', 'percent' );
		update_post_meta( $coupon->id, 'coupon_amount', '10' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => ''
		);
		WC_Tax::_insert_tax_rate( $tax_rate );
		$product = wc_get_product( $product->id );

		WC()->cart->add_to_cart( $product->id, 1 );
		WC()->cart->add_discount( $coupon->code );

		WC()->cart->calculate_totals();
		$cart_item = current( WC()->cart->get_cart() );
		$this->assertEquals( '24.51', number_format( $cart_item['line_total'], 2, '.', '' ) );

		// Cleanup
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->id );

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_remove_url.
	 *
	 * @since 2.3
	 */
	public function test_get_remove_url() {
		// Get the cart page id
		$cart_page_url = wc_get_page_permalink( 'cart' );

		// Test cart item key
		$cart_item_key = 'test';

		// Do the check
		$this->assertEquals( apply_filters( 'woocommerce_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'woocommerce-cart' ) : '' ), WC()->cart->get_remove_url( $cart_item_key ) );
	}

	/**
	 * Test add to cart simple product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_simple() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Check if we can add a trashed product to the cart.
	 */
	public function test_add_to_cart_trashed() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Trash product
		wp_trash_post( $product->id );

		// Refetch product, to be sure
		$product = wc_get_product( $product->id );

		// Add product to cart
		$this->assertFalse( WC()->cart->add_to_cart( $product->id, 1 ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test add to cart variable product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_variable() {
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1, $variation['variation_id'], array( 'Size' => ucfirst( $variation['attributes']['attribute_pa_size'] ) ) ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// @todo clean up the variable product
	}

	/**
	 * Check if adding a product that is sold individually is corrected when adding multiple times.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_sold_individually() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Set sold_individually to yes
		$product->sold_individually = 'yes';
		update_post_meta( $product->id, '_sold_individually', 'yes' );

		// Add the product twice to cart, should be corrected to 1. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 2 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test the find_product_in_cart method.
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

	/**
	 * Test the generate_cart_id method.
	 *
	 * @since 2.3
	 */
	public function test_generate_cart_id() {

		// Setup data
		$product_id     = 1;
		$variation_id   = 2;
		$variation      = array( 'Testing' => 'yup' );
		$cart_item_data = array(
			'string_val' => 'The string I was talking about',
			'array_val'  => array(
				'this',
				'is',
				'an',
				'array'
			)
		);

		// Manually generate ID
		$id_parts = array( $product_id );

		if ( $variation_id && 0 != $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( is_array( $variation ) && ! empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $key => $value ) {
				$variation_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $variation_key;
		}

		if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) {
			$cart_item_data_key = '';
			foreach ( $cart_item_data as $key => $value ) {

				if ( is_array( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_item_data_key .= trim( $key ) . trim( $value );

			}
			$id_parts[] = $cart_item_data_key;
		}

		$manual_cart_id = md5( implode( '_', $id_parts ) );

		// Assert
		$this->assertEquals( $manual_cart_id, WC()->cart->generate_cart_id( $product_id, $variation_id, array( 'Testing' => 'yup' ), $cart_item_data ) );

	}

	/**
	 * Test the set_quantity method.
	 *
	 * @since 2.3
	 */
	public function test_set_quantity() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add 1 product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Get cart id
		$cart_id = WC()->cart->generate_cart_id( $product->id );

		// Set quantity of product in cart to 2
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 2 ) );

		// Check if there are 2 items in cart now
		$this->assertEquals( 2, WC()->cart->get_cart_contents_count() );

		// Set quantity of product in cart to 0
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 0 ) );

		// Check if there are 0 items in cart now
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test check_cart_item_validity method.
	 *
	 * @since 2.3
	 */
	public function test_check_cart_item_validity() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Check cart validity, should pass
		$this->assertTrue( WC()->cart->check_cart_item_validity() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

	}

	/**
	 * Test get_total.
	 *
	 * @since 2.3
	 */
	public function test_get_total() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Check
		$this->assertEquals( apply_filters( 'woocommerce_cart_total', wc_price( WC()->cart->total ) ), WC()->cart->get_total() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_total_ex_tax.
	 *
	 * @since 2.3
	 */
	public function test_get_total_ex_tax() {

		// Set calc taxes option
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Calc total
		$total = WC()->cart->total - WC()->cart->tax_total - WC()->cart->shipping_tax_total;
		if ( $total < 0 ) {
			$total = 0;
		}

		// Check
		$this->assertEquals( apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( $total ) ), WC()->cart->get_total_ex_tax() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

		// Restore option
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test needs_shipping_address method.
	 */
	public function test_needs_shipping_address() {
		$needs_shipping_address = false;

		if ( WC()->cart->needs_shipping() === true && ! wc_ship_to_billing_address_only() ) {
			$needs_shipping_address = true;
		}

		$this->assertEquals( apply_filters( 'woocommerce_cart_needs_shipping_address', $needs_shipping_address ), WC()->cart->needs_shipping_address() );
	}

	/**
	 * Test shipping total.
	 *
	 * @since 2.3
	 */
	public function test_shipping_total() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// Create a flat rate method
		WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the shipping total amount is equal 20
		$this->assertEquals( 10, WC()->cart->shipping_total );

		// Test if the cart total amount is equal 20
		$this->assertEquals( 20, WC()->cart->total );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test cart fee.
	 *
	 * @since 2.3
	 */
	public function test_cart_fee() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee
		WC_Helper_Fee::add_cart_fee();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Test if the cart total amount is equal 20
		$this->assertEquals( 20, WC()->cart->total );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove fee
		WC_Helper_Fee::remove_cart_fee();

		// Delete product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test cart coupons.
	 */
	public function test_get_coupons() {

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

		$this->assertEquals( count( WC()->cart->get_coupons() ), 1 );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->id );

	}

}
