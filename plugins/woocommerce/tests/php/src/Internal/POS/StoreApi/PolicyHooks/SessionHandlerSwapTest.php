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
	 * @testdox register() attaches the filter unconditionally; context is decided per call.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		Context::set_test_override( false );

		$this->sut->register();

		$this->assertNotFalse( has_filter( 'woocommerce_session_handler', array( $this->sut, 'swap_session_handler' ) ) );
	}

	/**
	 * Context must be evaluated when the filter FIRES, not when it is
	 * registered — registration happens at plugin load, before late-loading
	 * code (e.g. a theme filtering rest_url_prefix) can influence detection.
	 *
	 * @testdox The swap decision is lazy: a context change after registration is honoured.
	 */
	public function test_swap_decision_is_lazy(): void {
		Context::set_test_override( false );
		$this->sut->register();

		/**
		 * Other filters (e.g. the test bootstrap's mock handler) may run on the
		 * same hook; the assertion is only that the POS handler does not win here.
		 *
		 * @since 11.0.0
		 */
		$this->assertNotSame( POSSessionHandler::class, apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' ) );

		Context::set_test_override( true );
		$this->assertSame( POSSessionHandler::class, apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
	}

	/**
	 * @testdox Outside POS context the incoming value passes through untouched, whatever its type.
	 */
	public function test_passthrough_preserves_third_party_values(): void {
		Context::set_test_override( false );

		$this->assertSame( 'Some_Handler', $this->sut->swap_session_handler( 'Some_Handler' ) );
		// Filter callbacks must tolerate sloppy third-party return values without fataling.
		$this->assertNull( $this->sut->swap_session_handler( null ) );
	}

	/**
	 * @testdox In POS context the POS handler wins regardless of the incoming value.
	 */
	public function test_pos_context_returns_pos_handler(): void {
		Context::set_test_override( true );

		$this->assertSame( POSSessionHandler::class, $this->sut->swap_session_handler( 'WC_Session_Handler' ) );
		$this->assertSame( POSSessionHandler::class, $this->sut->swap_session_handler( null ) );
	}
}
