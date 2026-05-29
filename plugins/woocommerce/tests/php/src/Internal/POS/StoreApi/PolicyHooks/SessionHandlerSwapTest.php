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
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_session_handler', array( $this->sut, 'swap_session_handler' ) );
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * @testdox register() attaches the session handler swap inside POS context.
	 */
	public function test_register_attaches_filter_in_pos_context(): void {
		Context::set_test_override( true );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_session_handler', array( $this->sut, 'swap_session_handler' ) ) );
	}

	/**
	 * @testdox register() does not attach the filter outside POS context.
	 */
	public function test_register_skips_filter_outside_pos_context(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertFalse( has_filter( 'woocommerce_session_handler', array( $this->sut, 'swap_session_handler' ) ) );
	}

	/**
	 * @testdox swap_session_handler returns POSSessionHandler regardless of input.
	 */
	public function test_swap_session_handler_returns_pos_handler(): void {
		$this->assertSame( POSSessionHandler::class, $this->sut->swap_session_handler( 'WC_Session_Handler' ) );
		$this->assertSame( POSSessionHandler::class, $this->sut->swap_session_handler( 'AnythingElse' ) );
	}
}
