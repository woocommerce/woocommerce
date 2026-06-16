<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\Auth;

use Automattic\WooCommerce\Internal\POS\Auth\POSAuthHandler;
use Automattic\WooCommerce\Internal\POS\Auth\POSRequestContext;
use Automattic\WooCommerce\Internal\POS\Capabilities;
use Automattic\WooCommerce\Internal\POS\POSPreset;
use Automattic\WooCommerce\Internal\POS\Service\POSPinService;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for POSAuthHandler — the POS staff current-user swap.
 */
class POSAuthHandlerTest extends WC_Unit_Test_Case {

	/**
	 * PIN service (real).
	 *
	 * @var POSPinService
	 */
	private POSPinService $pin_service;

	/**
	 * Device admin user id (administrator).
	 *
	 * @var int
	 */
	private int $device_admin_id;

	/**
	 * Cashier user id (holds process_sales, not issue_refunds).
	 *
	 * @var int
	 */
	private int $cashier_id;

	/**
	 * Manager user id (holds issue_refunds).
	 *
	 * @var int
	 */
	private int $manager_id;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->pin_service = new POSPinService();

		$this->device_admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->cashier_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->cashier_id, POSPreset::CASHIER );
		$this->pin_service->set_pin( $this->cashier_id, '1111' );

		$this->manager_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		Capabilities::set_pos_preset( $this->manager_id, POSPreset::MANAGER );
		$this->pin_service->set_pin( $this->manager_id, '2222' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Build a mocked request context.
	 *
	 * @param bool        $is_pos   Whether the request is POS-originated.
	 * @param int         $staff_id The asserted staff id.
	 * @param string      $pin      The presented PIN.
	 * @param string|null $intent   The resolved intent.
	 * @return POSRequestContext
	 */
	private function mock_context( bool $is_pos, int $staff_id, string $pin, ?string $intent ): POSRequestContext {
		$ctx = $this->createMock( POSRequestContext::class );
		$ctx->method( 'is_pos_request' )->willReturn( $is_pos );
		$ctx->method( 'get_staff_id' )->willReturn( $staff_id );
		$ctx->method( 'get_pin' )->willReturn( $pin );
		$ctx->method( 'get_intent' )->willReturn( $intent );
		return $ctx;
	}

	/**
	 * Build a handler around a context.
	 *
	 * @param POSRequestContext $ctx The (mocked) request context.
	 * @return POSAuthHandler
	 */
	private function make_handler( POSRequestContext $ctx ): POSAuthHandler {
		$handler = new POSAuthHandler();
		$handler->init( $ctx, $this->pin_service );
		return $handler;
	}

	/**
	 * @testdox Swaps to the cashier for an order create when the PIN verifies and the cap is held.
	 */
	public function test_swaps_to_cashier_for_order_create(): void {
		$ctx     = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$handler = $this->make_handler( $ctx );

		$result = $handler->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->cashier_id, $result, 'A cashier with process_sales should be swapped in for order create' );
		$this->assertSame( $this->device_admin_id, $handler->get_device_admin_id() );
	}

	/**
	 * @testdox Does not swap when the request is not POS-originated.
	 */
	public function test_no_swap_when_not_pos_request(): void {
		$ctx    = $this->mock_context( false, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$result = $this->make_handler( $ctx )->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->device_admin_id, $result, 'Non-POS requests must pass the device user through unchanged' );
	}

	/**
	 * @testdox Does not swap when the device user lacks manage_woocommerce.
	 */
	public function test_no_swap_when_device_not_admin(): void {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$ctx        = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );

		$result = $this->make_handler( $ctx )->maybe_swap( $subscriber );

		$this->assertSame( $subscriber, $result, 'A non-privileged device user must never be a swap base' );
	}

	/**
	 * @testdox Does not swap from an unauthenticated (user 0) request.
	 */
	public function test_no_swap_from_user_zero(): void {
		$ctx    = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$result = $this->make_handler( $ctx )->maybe_swap( 0 );

		$this->assertSame( 0, $result, 'A swap must never originate from user 0' );
	}

	/**
	 * @testdox Does not swap when the PIN does not verify.
	 */
	public function test_no_swap_when_pin_invalid(): void {
		$ctx    = $this->mock_context( true, $this->cashier_id, '9999', POSRequestContext::INTENT_ORDER_CREATE );
		$result = $this->make_handler( $ctx )->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->device_admin_id, $result, 'A wrong PIN must not swap' );
	}

	/**
	 * @testdox Does not swap a cashier into a refund they lack the capability for.
	 */
	public function test_no_swap_when_cashier_lacks_refund_cap(): void {
		$ctx    = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_REFUND_CREATE );
		$result = $this->make_handler( $ctx )->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->device_admin_id, $result, 'A cashier without issue_refunds must not be swapped in for a refund' );
	}

	/**
	 * @testdox Swaps to the manager (cap holder) for a refund.
	 */
	public function test_swaps_to_manager_for_refund(): void {
		$ctx    = $this->mock_context( true, $this->manager_id, '2222', POSRequestContext::INTENT_REFUND_CREATE );
		$result = $this->make_handler( $ctx )->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->manager_id, $result, 'The cap-holding manager is the swap target for an override refund' );
	}

	/**
	 * @testdox Swaps for a read (null intent) when the staff has POS access, with no cap requirement.
	 */
	public function test_swaps_for_read_with_pos_access(): void {
		$ctx    = $this->mock_context( true, $this->cashier_id, '1111', null );
		$result = $this->make_handler( $ctx )->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->cashier_id, $result, 'A read requires only POS access, not a specific cap' );
	}

	/**
	 * @testdox The swap decision is idempotent across repeated calls.
	 */
	public function test_swap_is_idempotent(): void {
		$ctx     = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$handler = $this->make_handler( $ctx );

		$first  = $handler->maybe_swap( $this->device_admin_id );
		$second = $handler->maybe_swap( $this->device_admin_id );

		$this->assertSame( $this->cashier_id, $first );
		$this->assertSame( $this->cashier_id, $second, 'A re-entered swap must resolve to the same staff member' );
	}

	/**
	 * @testdox The fallback safety-net applies the swap when the device admin is already current.
	 */
	public function test_fallback_applies_swap(): void {
		wp_set_current_user( $this->device_admin_id );
		$ctx     = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$handler = $this->make_handler( $ctx );

		$returned = $handler->maybe_swap_after_fallback( null );

		$this->assertNull( $returned, 'The fallback must return the auth-error state unchanged' );
		$this->assertSame( $this->cashier_id, get_current_user_id(), 'The fallback must set the current user to the staff member' );
	}

	/**
	 * @testdox The fallback never swaps into an already-failed authentication.
	 */
	public function test_fallback_skips_on_existing_error(): void {
		wp_set_current_user( $this->device_admin_id );
		$ctx     = $this->mock_context( true, $this->cashier_id, '1111', POSRequestContext::INTENT_ORDER_CREATE );
		$handler = $this->make_handler( $ctx );

		$error    = new WP_Error( 'some_error', 'nope' );
		$returned = $handler->maybe_swap_after_fallback( $error );

		$this->assertSame( $error, $returned, 'An existing auth error must pass through untouched' );
		$this->assertSame( $this->device_admin_id, get_current_user_id(), 'No swap should occur when authentication already failed' );
	}
}
