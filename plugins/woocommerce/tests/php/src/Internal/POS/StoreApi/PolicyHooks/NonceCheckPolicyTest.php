<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\NonceCheckPolicy;
use WC_Unit_Test_Case;

/**
 * Tests for the POS nonce-check policy hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\NonceCheckPolicy
 */
class NonceCheckPolicyTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NonceCheckPolicy
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new NonceCheckPolicy();
	}

	/**
	 * Reset POS context override.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox maybe_disable_nonce_check returns the original value outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertFalse( $this->sut->maybe_disable_nonce_check( false ) );
		$this->assertTrue( $this->sut->maybe_disable_nonce_check( true ) );
	}

	/**
	 * @testdox maybe_disable_nonce_check returns true inside POS context regardless of input.
	 */
	public function test_disables_nonce_check_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertTrue( $this->sut->maybe_disable_nonce_check( false ) );
		$this->assertTrue( $this->sut->maybe_disable_nonce_check( true ) );
	}
}
