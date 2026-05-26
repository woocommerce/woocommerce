<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\SessionHandlerSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\POSSessionHandler;
use WC_Unit_Test_Case;

/**
 * Tests for the POS session handler swap hook.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\SessionHandlerSwap
 */
class SessionHandlerSwapTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var SessionHandlerSwap
	 */
	private $sut;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new SessionHandlerSwap();
	}

	/**
	 * Reset POS context override.
	 */
	public function tearDown(): void {
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox maybe_swap_session_handler returns the original handler outside POS context.
	 */
	public function test_passthrough_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->assertSame( 'WC_Session_Handler', $this->sut->maybe_swap_session_handler( 'WC_Session_Handler' ) );
	}

	/**
	 * @testdox maybe_swap_session_handler returns POSSessionHandler inside POS context.
	 */
	public function test_swaps_inside_pos_context(): void {
		Context::set_test_override( true );

		$this->assertSame(
			POSSessionHandler::class,
			$this->sut->maybe_swap_session_handler( 'WC_Session_Handler' )
		);
	}
}
