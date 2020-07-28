<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Cart.
 */

/**
 * Class WC_Cart_Test
 */
class WC_Cart_Test extends \WC_Unit_Test_Case {

	/**
	 * tearDown.
	 */
	public function tearDown() {
		parent::tearDown();

		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * Test show shipping.
	 */
	public function test_show_shipping() {
		// Test with an empty cart.
		$this->assertFalse( WC()->cart->show_shipping() );

		// Add a product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test with "woocommerce_ship_to_countries" disabled.
		$default_ship_to_countries = get_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Test with default "woocommerce_ship_to_countries" and "woocommerce_shipping_cost_requires_address".
		update_option( 'woocommerce_ship_to_countries', $default_ship_to_countries );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Test with "woocommerce_shipping_cost_requires_address" enabled.
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Set address for shipping calculation required for "woocommerce_shipping_cost_requires_address".
		WC()->cart->get_customer()->set_shipping_country( 'US' );
		WC()->cart->get_customer()->set_shipping_state( 'NY' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}
}
