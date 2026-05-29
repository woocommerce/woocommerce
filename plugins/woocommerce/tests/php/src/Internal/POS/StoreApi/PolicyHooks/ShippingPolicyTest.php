<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for ShippingPolicy.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy
 */
class ShippingPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var ShippingPolicy
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new ShippingPolicy();
	}

	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox no_shipping_for_pos passes the original value through outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->no_shipping_for_pos( true ) );
		$this->assertFalse( $this->sut->no_shipping_for_pos( false ) );
	}

	/**
	 * @testdox no_shipping_for_pos returns false inside POS context.
	 */
	public function test_returns_false_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->no_shipping_for_pos( true ) );
		$this->assertFalse( $this->sut->no_shipping_for_pos( false ) );
	}
}
