<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerIdPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for CustomerIdPolicy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerIdPolicy
 */
class CustomerIdPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CustomerIdPolicy
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new CustomerIdPolicy();
	}

	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox force_guest_for_pos returns the incoming customer_id outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertSame( 42, $this->sut->force_guest_for_pos( 42 ) );
		$this->assertSame( 0, $this->sut->force_guest_for_pos( 0 ) );
	}

	/**
	 * @testdox force_guest_for_pos returns 0 inside POS context regardless of input.
	 */
	public function test_returns_zero_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertSame( 0, $this->sut->force_guest_for_pos( 42 ) );
		$this->assertSame( 0, $this->sut->force_guest_for_pos( 0 ) );
	}
}
