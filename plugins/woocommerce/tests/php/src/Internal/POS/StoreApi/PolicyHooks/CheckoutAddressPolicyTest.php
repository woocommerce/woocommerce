<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS Checkout Address policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy
 */
class CheckoutAddressPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CheckoutAddressPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CheckoutAddressPolicy();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_store_api_validate_addresses', array( $this->sut, 'maybe_skip_address_validation' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the address-validation opt-out unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_store_api_validate_addresses', array( $this->sut, 'maybe_skip_address_validation' ) ) );
	}

	/**
	 * @testdox maybe_skip_address_validation skips validation on POS requests.
	 */
	public function test_skips_validation_in_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->maybe_skip_address_validation( true ) );
	}

	/**
	 * @testdox maybe_skip_address_validation returns its input unchanged outside POS context.
	 */
	public function test_returns_input_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->maybe_skip_address_validation( true ) );
		$this->assertFalse( $this->sut->maybe_skip_address_validation( false ) );
	}
}
