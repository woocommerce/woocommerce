<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
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
	 * Original session reference, restored in tearDown.
	 *
	 * @var \WC_Session|null
	 */
	private $original_session;

	/**
	 * Reset POS context override and any session we swapped in.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		if ( null !== $this->original_session ) {
			WC()->session         = $this->original_session;
			$this->original_session = null;
		}
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

	/**
	 * Belt-and-suspenders: even if URI-based context detection somehow
	 * fails to return true (rewrite rules, reverse proxy, future refactor),
	 * the active session handler being POSSessionHandler is itself enough
	 * to skip persistent cart.
	 *
	 * @testdox disable_for_pos returns false when WC()->session is a POSSessionHandler regardless of context.
	 */
	public function test_disables_persistent_cart_when_session_handler_is_pos(): void {
		Context::set_test_override( false );

		$this->original_session = WC()->session;
		WC()->session           = new POSSessionHandler();

		$this->assertFalse( $this->sut->disable_for_pos( true ) );
		$this->assertFalse( $this->sut->disable_for_pos( false ) );
	}
}
