<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Internal\Admin\Orders;

use Automattic\WooCommerce\Internal\Admin\Orders\EditLock;
use Automattic\WooCommerce\RestApi\UnitTests\HPOSToggleTrait;

/**
 * Tests related to order edit locking in admin.
 */
class EditLockTest extends \WC_Unit_Test_Case {
	use HPOSToggleTrait;

	/**
	 * @var EditLock
	 */
	private $sut;

	/**
	 * Test order.
	 *
	 * @var \WC_Order
	 */
	private $order;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user1;

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user2;

	/**
	 * Setup - enables HPOS and creates test users and order.
	 */
	public function setUp(): void {
		parent::setUp();

		add_filter( 'wc_allow_changing_orders_storage_while_sync_is_pending', '__return_true' );

		$this->setup_cot();
		$this->toggle_cot_feature_and_usage( true );

		$order = new \WC_Order();

		$this->sut   = new EditLock();
		$this->order = new \WC_Order();
		$this->order->save();
		$this->user1 = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$this->user2 = $this->factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Restore HPOS state.
	 */
	public function tearDown(): void {
		$this->clean_up_cot_setup();
		remove_all_filters( 'wc_allow_changing_orders_storage_while_sync_is_pending' );
		parent::tearDown();
	}

	/**
	 * @testDox Basic locking works as expected.
	 */
	public function test_basic_locking() {
		wp_set_current_user( $this->user1 );
		$this->sut->lock( $this->order );

		// Confirm order is locked and user1 owns the lock.
		$this->assertTrue( $this->sut->is_locked( $this->order ) );
		$this->assertFalse( $this->sut->is_locked_by_another_user( $this->order ) );

		// user2 can't edit.
		wp_set_current_user( $this->user2 );
		$this->assertTrue( $this->sut->is_locked_by_another_user( $this->order ) );

		// If the user no longer exists, the order shouldn't be considered locked.
		wp_delete_user( $this->user1 );
		$this->assertFalse( $this->sut->is_locked( $this->order ) );
	}

	/**
	 * @testDox Order locking respects the post lock window.
	 */
	public function test_lock_time_window() {
		$now           = time();
		$five_mins_ago = $now - ( 5 * MINUTE_IN_SECONDS );

		// Simulate a lock set "now".
		$this->simulate_lock_with_time( $this->order, $now, $this->user1 );
		$this->assertTrue( $this->sut->is_locked( $this->order ) );

		// Simulate a lock set 5 mins ago.
		$this->simulate_lock_with_time( $this->order, $five_mins_ago, $this->user1 );

		// Change the lock window to 6 mins. Order should be considered locked.
		add_filter(
			'wp_check_post_lock_window',
			function() {
				return 6 * MINUTE_IN_SECONDS;
			}
		);
		$this->assertTrue( $this->sut->is_locked( $this->order ) );

		// With the default post window (2.5 mins), order should no longer be considered locked.
		remove_all_filters( 'wp_check_post_lock_window' );
		$this->assertFalse( $this->sut->is_locked( $this->order ) );
	}

	/**
	 * @testDox Heartbeat AJAX requests correctly renew edit locks and/or inform of takeovers.
	 */
	public function test_edit_lock_ajax() {
		$request_data = array(
			'wc-refresh-order-lock' => $this->order->get_id(),
		);

		// user1 owns the lock first.
		wp_set_current_user( $this->user1 );
		$this->sut->lock( $this->order );

		// Simulate a heartbeat, which for user 1 should result in a renewed lock.
		$response = apply_filters( 'heartbeat_received', array(), $request_data, 'woocommerce_page_wc-orders' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle
		$this->assertArrayHasKey( 'wc-refresh-order-lock', $response );
		$this->assertArrayHasKey( 'lock', $response['wc-refresh-order-lock'] );

		// Refresh order.
		$this->order = wc_get_order( $this->order->get_id() );

		// user2 takes over.
		wp_set_current_user( $this->user2 );
		$this->sut->lock( $this->order );

		// user1 now gets the "taken over" message when the heartbeat attempts to renew the lock.
		wp_set_current_user( $this->user1 );
		$response = apply_filters( 'heartbeat_received', array(), $request_data, 'woocommerce_page_wc-orders' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment,WooCommerce.Commenting.CommentHooks.HookCommentWrongStyle
		$this->assertArrayHasKey( 'wc-refresh-order-lock', $response );
		$this->assertArrayHasKey( 'error', $response['wc-refresh-order-lock'] );

		$user2 = get_user_by( 'id', $this->user2 );
		$this->assertEquals( $response['wc-refresh-order-lock']['error']['user_name'], $user2->display_name );
	}

	/**
	 * Simulates locking an order at a specific time.
	 *
	 * @param \WC_Order $order   Order object.
	 * @param int       $timestamp Timestamp for the lock.
	 * @param int       $user_id   User owning the lock.
	 * @return void
	 */
	private function simulate_lock_with_time( \WC_Order $order, int $timestamp, int $user_id ): void {
		$this->order->update_meta_data( EditLock::META_KEY_NAME, $timestamp . ':' . $user_id );
		$this->order->save_meta_data();
	}


}
