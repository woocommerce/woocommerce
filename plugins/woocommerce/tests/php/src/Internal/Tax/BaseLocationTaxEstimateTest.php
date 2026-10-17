<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Internal\Tax\BaseLocationTaxEstimate;
use WC_Customer;
use WC_Unit_Test_Case;

/**
 * Tests for the BaseLocationTaxEstimate class.
 */
class BaseLocationTaxEstimateTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var BaseLocationTaxEstimate
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new BaseLocationTaxEstimate();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_postcode', '94110' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
	}

	/**
	 * @testdox Fills the store postcode and city for an un-addressed shopper at the shop base.
	 */
	public function test_fills_base_postcode_and_city_for_unaddressed_shopper(): void {
		$result = $this->sut->estimate_base_location_address( array( 'US', 'CA', '', '' ), null );

		$this->assertSame( array( 'US', 'CA', '94110', 'San Francisco' ), $result );
	}

	/**
	 * @testdox Leaves the address alone once the shopper has entered a postcode.
	 */
	public function test_skips_when_shopper_has_postcode(): void {
		$address = array( 'US', 'CA', '90001', '' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Leaves the address alone once the shopper has entered a city.
	 */
	public function test_skips_when_shopper_has_city(): void {
		$address = array( 'US', 'CA', '', 'Los Angeles' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Does not estimate when the default customer location is not the shop base.
	 */
	public function test_skips_when_default_location_not_base(): void {
		update_option( 'woocommerce_default_customer_address', 'geolocation' );
		$address = array( 'US', 'CA', '', '' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Does not estimate when the assumed country or state is not the store's.
	 */
	public function test_skips_when_country_state_differ_from_base(): void {
		$address = array( 'GB', '', '', '' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Does not estimate when taxes are disabled.
	 */
	public function test_skips_when_taxes_disabled(): void {
		update_option( 'woocommerce_calc_taxes', 'no' );
		$address = array( 'US', 'CA', '', '' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Returns a non-array taxable address unchanged.
	 */
	public function test_returns_non_array_input_unchanged(): void {
		$this->assertSame( 'not-an-address', $this->sut->estimate_base_location_address( 'not-an-address', null ) );
	}

	/**
	 * @testdox Returns an incomplete taxable address unchanged.
	 */
	public function test_returns_short_array_unchanged(): void {
		$address = array( 'US', 'CA' );

		$this->assertSame( $address, $this->sut->estimate_base_location_address( $address, null ) );
	}

	/**
	 * @testdox Fills only the city when the store has a city but no postcode.
	 */
	public function test_fills_only_city_when_store_has_no_postcode(): void {
		update_option( 'woocommerce_store_postcode', '' );

		$result = $this->sut->estimate_base_location_address( array( 'US', 'CA', '', '' ), null );

		$this->assertSame( array( 'US', 'CA', '', 'San Francisco' ), $result );
	}

	/**
	 * @testdox Returns a clean numeric 4-tuple even when the taxable address is keyed associatively.
	 */
	public function test_normalizes_non_numeric_taxable_address_when_filling(): void {
		$result = $this->sut->estimate_base_location_address(
			array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '',
				'city'     => '',
			),
			null
		);

		$this->assertSame( array( 'US', 'CA', '94110', 'San Francisco' ), $result );
	}

	/**
	 * @testdox Keeps a trailing field (such as a tax provider's street) when filling postcode and city.
	 */
	public function test_preserves_trailing_fields_when_filling(): void {
		$result = $this->sut->estimate_base_location_address( array( 'US', 'CA', '', '', '123 Main St' ), null );

		$this->assertSame( array( 'US', 'CA', '94110', 'San Francisco', '123 Main St' ), $result );
	}

	/**
	 * @testdox The estimate reaches shipping-based taxable addresses without changing the stored address.
	 */
	public function test_estimate_applies_to_shipping_based_tax(): void {
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		$this->sut->register();

		$customer = new WC_Customer();
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_state( 'CA' );

		$taxable = $customer->get_taxable_address();

		$this->assertSame( '94110', $taxable[2], 'The tax lookup should use the estimated store postcode.' );
		$this->assertSame( 'San Francisco', $taxable[3], 'The tax lookup should use the estimated store city.' );
		$this->assertSame( '', $customer->get_shipping_postcode(), 'The stored shipping postcode must stay empty.' );
		$this->assertSame( '', $customer->get_shipping_city(), 'The stored shipping city must stay empty.' );
	}

	/**
	 * @testdox The estimate reaches the taxable address without changing the stored customer address.
	 */
	public function test_estimate_leaves_the_stored_customer_address_untouched(): void {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		$this->sut->register();

		$customer = new WC_Customer();
		$customer->set_billing_country( 'US' );
		$customer->set_billing_state( 'CA' );

		$taxable = $customer->get_taxable_address();

		$this->assertSame( '94110', $taxable[2], 'The tax lookup should use the estimated store postcode.' );
		$this->assertSame( 'San Francisco', $taxable[3], 'The tax lookup should use the estimated store city.' );
		$this->assertSame( '', $customer->get_billing_postcode(), 'The stored billing postcode must stay empty.' );
		$this->assertSame( '', $customer->get_billing_city(), 'The stored billing city must stay empty.' );
	}
}
