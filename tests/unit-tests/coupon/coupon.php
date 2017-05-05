<?php

/**
 * Class Coupon.
 * @package WooCommerce\Tests\Coupon
 */
class WC_Tests_Coupon extends WC_Unit_Test_Case {

	/**
	 * Test add_discount method.
	 *
	 * @since 2.3
	 */
	public function test_add_discount() {

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon, test return statement
		$this->assertTrue( WC()->cart->add_discount( $coupon->get_code() ) );

		// Test if total amount of coupons is 1
		$this->assertEquals( 1, count( WC()->cart->get_applied_coupons() ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
	}

	/**
	 * Test add_discount method.
	 *
	 * @since 2.3
	 */
	public function test_add_discount_duplicate() {

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon
		$this->assertTrue( WC()->cart->add_discount( $coupon->get_code() ) );

		// Add coupon again, test return statement
		$this->assertFalse( WC()->cart->add_discount( $coupon->get_code() ) );

		// Test if total amount of coupons is 1
		$this->assertEquals( 1, count( WC()->cart->get_applied_coupons() ) );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
	}

	/**
	 * Test fixed cart discount method.
	 *
	 * @since 2.3
	 */
	public function test_fixed_cart_discount() {

		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->get_id(), 'discount_type', 'fixed_cart' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '5' );

		// Create a flat rate method
		WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->get_code() );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the cart total amount is equal 15
		$this->assertEquals( 15, WC()->cart->total );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

		// Delete product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test fixed product discount method.
	 *
	 * @since 2.3
	 */
	public function test_fixed_product_discount() {

		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->get_id(), 'discount_type', 'fixed_product' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '5' );

		// Create a flat rate method - $10
		WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee - $10
		WC_Helper_Fee::add_cart_fee();

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->get_code() );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the cart total amount is equal 25
		$this->assertEquals( 25, WC()->cart->total );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Remove fee
		WC_Helper_Fee::remove_cart_fee();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

		// Delete product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test percent product discount method.
	 *
	 * @since 2.3
	 */
	public function test_percent_discount() {

		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->get_id(), 'discount_type', 'percent' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '5' );

		// Create a flat rate method
		WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee
		WC_Helper_Fee::add_cart_fee();

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->get_code() );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the cart total amount is equal 29.5
		$this->assertEquals( 29.5, WC()->cart->total );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Remove fee
		WC_Helper_Fee::remove_cart_fee();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

		// Delete product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test date setters/getters.
	 *
	 * @since 3.0.0
	 */
	public function test_dates() {
		$valid_coupon = WC_Helper_Coupon::create_coupon();
		$valid_coupon->set_date_expires( time() + 1000 );
		$valid_coupon->set_date_created( time() );
		$valid_coupon->set_date_modified( time() );

		$expired_coupon = WC_Helper_Coupon::create_coupon();
		$expired_coupon->set_date_expires( time() - 10 );
		$expired_coupon->set_date_created( time() - 20 );
		$expired_coupon->set_date_modified( time() - 20 );

		$this->assertInstanceOf( 'WC_DateTime', $valid_coupon->get_date_created() );
		$this->assertTrue( $valid_coupon->is_valid() );
		$this->assertFalse( $expired_coupon->is_valid() );
		$this->assertEquals( $expired_coupon->get_error_message(), $expired_coupon->get_coupon_error( WC_Coupon::E_WC_COUPON_EXPIRED ) );
	}
}
