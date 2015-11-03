<?php

namespace WooCommerce\Tests\Coupon;

/**
 * Class Coupon.
 * @package WooCommerce\Tests\Coupon
 */
class Coupon extends \WC_Unit_Test_Case {

	/**
	 * Test add_discount method.
	 *
	 * @since 2.3
	 */
	public function test_add_discount() {

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();

		// Add coupon, test return statement
		$this->assertTrue( WC()->cart->add_discount( $coupon->code ) );

		// Test if total amount of coupons is 1
		$this->assertEquals( 1, count( WC()->cart->get_applied_coupons() ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );
	}

	/**
	 * Test add_discount method.
	 *
	 * @since 2.3
	 */
	public function test_add_discount_duplicate() {

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();

		// Add coupon
		$this->assertTrue( WC()->cart->add_discount( $coupon->code ) );

		// Add coupon again, test return statement
		$this->assertFalse( WC()->cart->add_discount( $coupon->code ) );

		// Test if total amount of coupons is 1
		$this->assertEquals( 1, count( WC()->cart->get_applied_coupons() ) );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );
	}

	/**
	 * Test fixed cart discount method.
	 *
	 * @since 2.3
	 */
	public function test_fixed_cart_discount() {

		// Create product
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->id, 'discount_type', 'fixed_cart' );
		update_post_meta( $coupon->id, 'coupon_amount', '5' );

		// Create a flat rate method
		\WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

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
		\WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );

		// Delete product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test percent cart discount method.
	 *
	 * @since 2.3
	 */
	public function test_percent_cart_discount() {

		// Create product
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->id, 'discount_type', 'percent' );
		update_post_meta( $coupon->id, 'coupon_amount', '5' );

		// Create a flat rate method
		\WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the cart total amount is equal 19.5
		$this->assertEquals( 19.5, WC()->cart->total );

		// Clearing WC notices
		wc_clear_notices();

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		\WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );

		// Delete product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test fixed product discount method.
	 *
	 * @since 2.3
	 */
	public function test_fixed_product_discount() {

		// Create product
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->id, 'discount_type', 'fixed_product' );
		update_post_meta( $coupon->id, 'coupon_amount', '5' );

		// Create a flat rate method - $10
		\WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee - $10
		\WC_Helper_Fee::add_cart_fee();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

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
		\WC_Helper_Fee::remove_cart_fee();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		\WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );

		// Delete product
		\WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test percent product discount method.
	 *
	 * @since 2.3
	 */
	public function test_percent_product_discount() {

		// Create product
		$product = \WC_Helper_Product::create_simple_product();
		update_post_meta( $product->id, '_price', '10' );
		update_post_meta( $product->id, '_regular_price', '10' );

		// Create coupon
		$coupon = \WC_Helper_Coupon::create_coupon();
		update_post_meta( $coupon->id, 'discount_type', 'percent_product' );
		update_post_meta( $coupon->id, 'coupon_amount', '5' );

		// Create a flat rate method
		\WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee
		\WC_Helper_Fee::add_cart_fee();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Add coupon
		WC()->cart->add_discount( $coupon->code );

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
		\WC_Helper_Fee::remove_cart_fee();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		\WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete coupon
		\WC_Helper_Coupon::delete_coupon( $coupon->id );

		// Delete product
		\WC_Helper_Product::delete_product( $product->id );
	}

}
