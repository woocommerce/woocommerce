<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap;
use WC_Unit_Test_Case;

/**
 * Tests for CurrentUserSwap.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap
 */
class CurrentUserSwapTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CurrentUserSwap
	 */
	private $sut;

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	public function setUp(): void {
		parent::setUp();
		$this->sut              = new CurrentUserSwap();
		$this->original_user_id = get_current_user_id();
	}

	public function tearDown(): void {
		remove_filter( 'rest_dispatch_request', array( $this->sut, 'swap_to_guest' ), 10 );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	/**
	 * The filter is installed unconditionally; the POS gating happens inside the
	 * callback, not at registration time.
	 *
	 * @testdox register() attaches the dispatch hook unconditionally.
	 */
	public function test_register_attaches_filter_unconditionally(): void {
		$this->sut->register();

		$this->assertNotFalse( has_filter( 'rest_dispatch_request', array( $this->sut, 'swap_to_guest' ) ) );
	}

	/**
	 * @testdox swap_to_guest sets current_user to 0 on POS requests.
	 */
	public function test_swaps_to_guest_in_pos_context(): void {
		Context::set_test_override( true );

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( is_user_logged_in() );

		$this->sut->swap_to_guest( null );

		$this->assertSame( 0, get_current_user_id() );
		$this->assertFalse( is_user_logged_in() );

		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox swap_to_guest leaves the current user untouched outside POS context.
	 */
	public function test_does_not_swap_outside_pos_context(): void {
		Context::set_test_override( false );

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$this->sut->swap_to_guest( null );

		$this->assertSame( $admin_id, get_current_user_id(), 'Outside POS the cashier identity must be preserved.' );

		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox swap_to_guest returns the incoming dispatch result unchanged so the REST pipeline keeps flowing.
	 */
	public function test_returns_dispatch_result_unchanged(): void {
		Context::set_test_override( true );

		$sentinel = new \WP_REST_Response( array( 'sentinel' => true ), 200 );

		$result = $this->sut->swap_to_guest( $sentinel );

		$this->assertSame( $sentinel, $result );
	}

	/**
	 * When CapabilityGate has already rejected the request, the user must be left
	 * untouched and the error passed through unchanged.
	 *
	 * @testdox swap_to_guest does not swap when an earlier gate already returned an error.
	 */
	public function test_does_not_swap_when_dispatch_already_errored(): void {
		Context::set_test_override( true );

		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$error  = new \WP_Error( 'woocommerce_pos_rest_forbidden', 'Forbidden.', array( 'status' => 403 ) );
		$result = $this->sut->swap_to_guest( $error );

		$this->assertSame( $error, $result );
		$this->assertSame( $admin_id, get_current_user_id(), 'A rejected request must not have its user swapped.' );

		wp_delete_user( $admin_id );
	}
}
