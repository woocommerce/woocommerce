<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\PolicyHooks;

use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap;
use WC_Unit_Test_Case;
use WP_REST_Request;

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
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		parent::tearDown();
	}

	/**
	 * @testdox swap_to_guest_for_pos leaves the current user untouched for non-POS REST routes.
	 */
	public function test_does_not_swap_for_non_pos_route(): void {
		Context::set_test_override( false );
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request = new WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );

		$this->sut->swap_to_guest_for_pos( null, $request );

		$this->assertSame(
			$admin_id,
			get_current_user_id(),
			'Non-POS routes must not have the current WP user dropped.'
		);

		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox swap_to_guest_for_pos sets current_user to 0 for POS routes.
	 */
	public function test_swaps_to_guest_for_pos_route(): void {
		Context::set_test_override( false );
		$admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertSame( $admin_id, get_current_user_id() );
		$this->assertTrue( is_user_logged_in() );

		$request = new WP_REST_Request( 'POST', '/wc/pos/v1/cart/add-item' );

		$this->sut->swap_to_guest_for_pos( null, $request );

		$this->assertSame(
			0,
			get_current_user_id(),
			'POS routes must run with current_user = 0 so extensions see a guest, not the cashier.'
		);
		$this->assertFalse(
			is_user_logged_in(),
			'is_user_logged_in() must return false during POS request business logic.'
		);

		wp_delete_user( $admin_id );
	}

	/**
	 * @testdox swap_to_guest_for_pos returns the incoming response unchanged so the REST pipeline keeps flowing.
	 */
	public function test_returns_response_argument_unchanged(): void {
		Context::set_test_override( true );

		$request = new WP_REST_Request( 'POST', '/wc/pos/v1/cart/add-item' );

		$sentinel = new \WP_REST_Response( array( 'sentinel' => true ), 200 );
		$result   = $this->sut->swap_to_guest_for_pos( $sentinel, $request );

		$this->assertSame( $sentinel, $result );
	}
}
