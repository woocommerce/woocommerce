<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS persistent-cart policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy
 */
class CartPersistencePolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CartPersistencePolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CartPersistencePolicy();
	}

	/**
	 * Reset POS context override.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox disable_for_pos returns the original value outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertTrue( $this->sut->disable_for_pos( true ) );
		$this->assertFalse( $this->sut->disable_for_pos( false ) );
	}

	/**
	 * @testdox disable_for_pos returns false inside POS context regardless of input.
	 */
	public function test_disables_persistent_cart_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertFalse( $this->sut->disable_for_pos( true ) );
		$this->assertFalse( $this->sut->disable_for_pos( false ) );
	}
}
