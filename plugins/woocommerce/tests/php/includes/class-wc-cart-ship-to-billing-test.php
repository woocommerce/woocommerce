<?php
/**
 * Tests for forced billing deciding whether a separate shipping address is collected.
 *
 * @package WooCommerce\Tests\Cart
 */

declare( strict_types = 1 );

/**
 * WC_Cart::needs_shipping_address() under the "Shipping destination" setting.
 *
 * "Force shipping to the customer billing address" is the setting that decides which address an
 * order ships to. Its server-side effect is that the cart stops asking for a separate shipping
 * address, which is what hides the second form and writes the billing address onto the order. The
 * legacy test for needs_shipping_address() re-derives the method's own formula and so never pins a
 * concrete outcome; these set the outcome explicitly.
 */
class WC_Cart_Ship_To_Billing_Test extends WC_Unit_Test_Case {

	/**
	 * Put a shippable product in the cart and a shipping method on the store, so the cart needs
	 * shipping at all.
	 */
	public function setUp(): void {
		parent::setUp();

		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Ship to billing' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		delete_transient( 'wc_shipping_method_count' );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
	}

	/**
	 * With the destination set to the shipping address, a cart that needs shipping asks for a
	 * separate shipping address.
	 *
	 * @testdox A cart that needs shipping asks for a shipping address when the destination is the shipping address.
	 */
	public function test_a_separate_shipping_address_is_asked_for_when_the_destination_is_shipping(): void {
		update_option( 'woocommerce_ship_to_destination', 'shipping' );

		$this->assertTrue( WC()->cart->needs_shipping(), 'The fixture cart should need shipping, or this proves nothing.' );
		$this->assertTrue(
			WC()->cart->needs_shipping_address(),
			'A separate shipping address should be asked for when shipping is not forced to billing.'
		);
	}

	/**
	 * Forcing shipping to the billing address stops the cart asking for a separate shipping
	 * address, which is what hides the second form and sends the billing address to the order.
	 *
	 * @testdox Forcing shipping to the billing address stops the cart asking for a separate one.
	 */
	public function test_forced_billing_stops_asking_for_a_shipping_address(): void {
		update_option( 'woocommerce_ship_to_destination', 'billing_only' );

		$this->assertTrue( WC()->cart->needs_shipping(), 'The cart still needs shipping; only the address question changes.' );
		$this->assertFalse(
			WC()->cart->needs_shipping_address(),
			'With billing forced, no separate shipping address should be asked for.'
		);
	}

	/**
	 * "Default to customer billing address" (the plain billing value, not billing_only) leaves the
	 * shipping address question in place: it changes the default, not whether the form appears.
	 *
	 * @testdox Defaulting to billing without forcing still asks for a shipping address.
	 */
	public function test_defaulting_to_billing_without_forcing_still_asks(): void {
		update_option( 'woocommerce_ship_to_destination', 'billing' );

		$this->assertTrue( WC()->cart->needs_shipping(), 'The cart should need shipping, or the address question below proves nothing.' );
		$this->assertTrue(
			WC()->cart->needs_shipping_address(),
			'Only the forced billing_only value removes the shipping address; the plain billing default keeps it.'
		);
	}
}
