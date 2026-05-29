<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS checkout-address policy hook.
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
	 * Reset POS context override.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox allow_missing_address_for_pos returns the original value outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->allow_missing_address_for_pos( true ) );
		$this->assertFalse( $this->sut->allow_missing_address_for_pos( false ) );
	}

	/**
	 * @testdox allow_missing_address_for_pos returns false inside POS context regardless of input.
	 */
	public function test_disables_address_validation_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->allow_missing_address_for_pos( true ) );
		$this->assertFalse( $this->sut->allow_missing_address_for_pos( false ) );
	}
}
