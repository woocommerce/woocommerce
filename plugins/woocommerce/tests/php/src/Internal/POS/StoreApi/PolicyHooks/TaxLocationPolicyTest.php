<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy;
use WC_Customer;
use WC_Unit_Test_Case;

/**
 * Tests for TaxLocationPolicy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy
 */
class TaxLocationPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var TaxLocationPolicy
	 */
	private $sut;

	/**
	 * Original store base options captured in setUp and restored in tearDown.
	 *
	 * @var array<string,string|false>
	 */
	private $original_base = array();

	public function setUp(): void {
		parent::setUp();
		$this->sut = new TaxLocationPolicy();

		foreach ( array( 'woocommerce_default_country', 'woocommerce_store_postcode', 'woocommerce_store_city' ) as $opt ) {
			$this->original_base[ $opt ] = get_option( $opt );
		}

		// Pin store base address so the test is independent of the wp-env default.
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_store_postcode', '94103' );
		update_option( 'woocommerce_store_city', 'San Francisco' );
		WC()->countries->load_country_states();
	}

	public function tearDown(): void {
		remove_filter( 'woocommerce_customer_taxable_address', array( $this->sut, 'override_taxable_address' ), 10 );
		Context::set_test_override( null );
		remove_all_filters( 'woocommerce_pos_tax_location' );
		foreach ( $this->original_base as $opt => $value ) {
			if ( false === $value ) {
				delete_option( $opt );
			} else {
				update_option( $opt, $value );
			}
		}
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the taxable-address override unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_customer_taxable_address', array( $this->sut, 'override_taxable_address' ) ) );
	}

	/**
	 * @testdox override_taxable_address returns the input address unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$customer = new WC_Customer( 0, true );
		$input    = array( 'GB', '', 'SW1A 1AA', 'London' );

		$this->assertSame( $input, $this->sut->override_taxable_address( $input, $customer ) );
	}

	/**
	 * @testdox override_taxable_address returns the store base address regardless of input.
	 */
	public function test_returns_store_base(): void {
		Context::set_test_override( true );

		$customer = new WC_Customer( 0, true );

		$result = $this->sut->override_taxable_address( array( 'GB', '', 'SW1A 1AA', 'London' ), $customer );

		$this->assertSame( 'US', $result[0], 'Country should be store base country.' );
		$this->assertSame( 'CA', $result[1], 'State should be store base state.' );
		$this->assertSame( '94103', $result[2], 'Postcode should be store base postcode.' );
		$this->assertSame( 'San Francisco', $result[3], 'City should be store base city.' );
	}

	/**
	 * @testdox override_taxable_address returns store base even when customer's address is empty.
	 */
	public function test_returns_store_base_when_customer_address_empty(): void {
		Context::set_test_override( true );

		$customer = new WC_Customer( 0, true );

		$result = $this->sut->override_taxable_address( array( '', '', '', '' ), $customer );

		$this->assertSame( 'US', $result[0] );
		$this->assertSame( 'CA', $result[1] );
	}

	/**
	 * The `woocommerce_pos_tax_location` filter exists so future POS work that
	 * adds structured per-register address fields can hook it without
	 * re-architecting this policy. This test pins the contract.
	 *
	 * @testdox woocommerce_pos_tax_location filter overrides the store-base default.
	 */
	public function test_pos_tax_location_filter_overrides_default(): void {
		Context::set_test_override( true );

		add_filter(
			'woocommerce_pos_tax_location',
			static function () {
				return array( 'GB', 'ENG', 'SW1A 1AA', 'London' );
			}
		);

		$customer = new WC_Customer( 0, true );
		$result   = $this->sut->override_taxable_address( array( '', '', '', '' ), $customer );

		$this->assertSame( array( 'GB', 'ENG', 'SW1A 1AA', 'London' ), $result );
	}
}
