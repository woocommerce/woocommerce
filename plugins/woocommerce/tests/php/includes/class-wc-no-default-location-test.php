<?php
/**
 * Tests for the "No location by default" customer-location setting.
 *
 * @package WooCommerce\Tests\Customer
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\DefaultCustomerAddress;

/**
 * "No location by default" (Settings > General > Default customer location).
 *
 * With it chosen, a shopper who has entered no address is assumed to be nowhere, so they are quoted
 * no tax until they say where they are. Only the helper that reads the setting was pinned before;
 * these pin the end: an empty default location, and a real cart that quotes no tax against it.
 */
class WC_No_Default_Location_Test extends WC_Unit_Test_Case {

	/**
	 * The session customer to restore, since the tests replace it.
	 *
	 * @var WC_Customer
	 */
	private $original_customer;

	/**
	 * Turn taxes on, put a US rate in place, and a cart with a taxable product, so "no tax quoted"
	 * is a real outcome rather than the result of taxes being off or an empty cart.
	 */
	public function setUp(): void {
		parent::setUp();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'US Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// new WC_Customer( 0, true ) in the tests reads the shared session, so an address a
		// previous test left there would decide the result. Save the customer to restore it, and
		// start from an empty session customer so the outcome is the setting's doing.
		$this->original_customer = WC()->customer;
		WC()->session->set( 'customer', null );
	}

	/**
	 * Restore the session customer the tests replaced; the base teardown rolls back the rest.
	 */
	public function tearDown(): void {
		WC()->session->set( 'customer', null );
		WC()->customer = $this->original_customer;
		parent::tearDown();
	}

	/**
	 * With no default location, a shopper who entered no address is placed nowhere, so the default
	 * location carries no country and the cart quotes no tax.
	 *
	 * @testdox No location by default leaves the shopper nowhere, so the cart quotes no tax.
	 */
	public function test_no_default_location_quotes_no_tax(): void {
		update_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::NO_DEFAULT );

		$this->assertSame( '', wc_get_customer_default_location()['country'], 'No default location should leave the country empty.' );

		WC()->customer = new WC_Customer( 0, true );
		$this->assertSame( '', WC()->customer->get_taxable_address()[0], 'The taxable address should carry no country.' );

		WC()->cart->calculate_totals();
		$this->assertEquals( 0, WC()->cart->get_taxes_total(), 'With the shopper placed nowhere, no tax should be quoted.' );
	}

	/**
	 * The contrast: with "Shop base address", the shopper is placed at the shop's country, where the
	 * US rate applies, so the empty result above is the setting's doing rather than taxes being off.
	 *
	 * @testdox Shop base address places the shopper at the shop, where the rate is quoted.
	 */
	public function test_shop_base_places_the_shopper_at_the_shop(): void {
		update_option( 'woocommerce_default_customer_address', DefaultCustomerAddress::BASE );

		WC()->customer = new WC_Customer( 0, true );
		$this->assertSame( 'US', WC()->customer->get_taxable_address()[0], 'The shopper should be placed at the shop country.' );

		WC()->cart->calculate_totals();
		$this->assertGreaterThan( 0, WC()->cart->get_taxes_total(), 'At the shop, the US rate should be quoted, or the other test proves nothing.' );
	}
}
