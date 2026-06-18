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
	 * Original $_SERVER['HTTP_CART_TOKEN'] captured in setUp so it can be restored.
	 *
	 * @var string|null
	 */
	private $original_cart_token;

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut                 = new SessionHandlerSwap();
		$this->original_cart_token = $_SERVER['HTTP_CART_TOKEN'] ?? null;
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_session_handler', array( $this->sut, 'maybe_swap_session_handler' ), 1 );
		if ( null === $this->original_cart_token ) {
			unset( $_SERVER['HTTP_CART_TOKEN'] );
		} else {
			$_SERVER['HTTP_CART_TOKEN'] = $this->original_cart_token;
		}
		Context::set_test_override( null );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the session handler swap unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_session_handler', array( $this->sut, 'maybe_swap_session_handler' ) ) );
	}

	/**
	 * On the first (tokenless) request of a POS transaction the default handler
	 * is replaced by POSSessionHandler so a fresh isolated guest session starts.
	 *
	 * @testdox maybe_swap_session_handler returns POSSessionHandler for a tokenless POS request.
	 */
	public function test_swaps_to_pos_handler_for_tokenless_pos_request(): void {
		Context::set_test_override( true );
		$_SERVER['HTTP_CART_TOKEN'] = '';

		$this->assertSame( POSSessionHandler::class, $this->sut->maybe_swap_session_handler( 'WC_Session_Handler' ) );
		$this->assertSame( POSSessionHandler::class, $this->sut->maybe_swap_session_handler( 'AnythingElse' ) );
	}

	/**
	 * @testdox maybe_swap_session_handler returns the supplied handler unchanged outside POS context.
	 */
	public function test_returns_handler_unchanged_outside_pos_context(): void {
		Context::set_test_override( false );
		$_SERVER['HTTP_CART_TOKEN'] = '';

		$this->assertSame( 'WC_Session_Handler', $this->sut->maybe_swap_session_handler( 'WC_Session_Handler' ) );
	}

	/*
	 * Not covered here: the "POS request that already carries a valid Cart-Token"
	 * branch, in which the swap leaves the Store API's own token-backed handler in
	 * place. Minting a fully valid signed cart token without a live session is
	 * impractical in a pure unit test, so that branch is exercised end-to-end by
	 * the cart integration tests (the Cart-Token replay there resumes the same
	 * session rather than minting a new POSSessionHandler one).
	 */
}
