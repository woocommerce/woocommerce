<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Internal\OrderReviews\Scheduler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Email_Customer_Review_Request;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Scheduler test.
 *
 * @covers \Automattic\WooCommerce\Internal\OrderReviews\Scheduler
 */
class SchedulerTest extends WC_Unit_Test_Case {

	/**
	 * Prepare the mailer and enable the review-request email.
	 */
	public function setUp(): void {
		parent::setUp();

		// Make sure the email class is available for WC()->mailer().
		WC()->mailer();

		$this->set_review_email_enabled( true );
	}

	/**
	 * Reset between tests.
	 */
	public function tearDown(): void {
		$this->set_review_email_enabled( false );
		remove_all_filters( 'woocommerce_should_send_review_request' );
		remove_all_filters( 'woocommerce_review_request_delay_seconds' );

		parent::tearDown();
	}

	/**
	 * @testdox Completing an order schedules the review-request action and records the scheduled-at meta.
	 */
	public function test_schedules_on_order_completed(): void {
		$order = $this->create_pending_order();

		$order->update_status( 'completed' );

		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
		$this->assertNotEmpty( wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox The delay comes from the email class's get_delay_seconds() helper.
	 */
	public function test_schedules_using_email_delay(): void {
		$email = $this->get_email();
		$email->update_option( 'delay_days', '3' );

		$order  = $this->create_pending_order();
		$before = time();
		$order->update_status( 'completed' );

		$when = (int) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		// Allow a few seconds of wall-clock drift during the test.
		$expected = $before + ( 3 * DAY_IN_SECONDS );
		$this->assertGreaterThanOrEqual( $expected - 5, $when );
		$this->assertLessThanOrEqual( $expected + 5, $when );
	}

	/**
	 * @testdox Scheduling is skipped when the email is disabled.
	 */
	public function test_skips_when_email_disabled(): void {
		$this->set_review_email_enabled( false );

		$order = $this->create_pending_order();
		$order->update_status( 'completed' );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox A second completion for the same order does not reschedule.
	 */
	public function test_is_idempotent(): void {
		$order = $this->create_pending_order();
		$order->update_status( 'completed' );
		$first = (int) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		// Simulate a second completed-notification firing (e.g. status toggled back and forth).
		sleep( 1 );
		do_action( 'woocommerce_order_status_completed', $order->get_id() ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- existing core hook, fired here only to simulate a duplicate transition in the test.
		$second = (int) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->assertSame( $first, $second, 'Scheduled-at meta should not change on re-completion.' );
	}

	/**
	 * @testdox woocommerce_should_send_review_request=false skips scheduling.
	 */
	public function test_opt_out_filter_skips_scheduling(): void {
		add_filter( 'woocommerce_should_send_review_request', '__return_false' );

		$order = $this->create_pending_order();
		$order->update_status( 'completed' );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox Cancelling or refunding the order unschedules the pending action and clears the meta.
	 *
	 * @dataProvider cancellation_status_provider
	 *
	 * @param string $new_status The order status to transition into.
	 */
	public function test_status_transition_cancels_pending_action( string $new_status ): void {
		$order = $this->create_pending_order();
		$order->update_status( 'completed' );
		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );

		$order->update_status( $new_status );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * Provides order statuses whose transition should cancel the pending email.
	 *
	 * @return array<string, array{string}>
	 */
	public function cancellation_status_provider(): array {
		return array(
			'cancelled'  => array( 'cancelled' ),
			'refunded'   => array( 'refunded' ),
			// Any other transition out of `completed` must also unschedule.
			'processing' => array( 'processing' ),
			'on-hold'    => array( 'on-hold' ),
			'pending'    => array( 'pending' ),
			'failed'     => array( 'failed' ),
		);
	}

	/**
	 * @testdox Trashing the order unschedules the pending action.
	 */
	public function test_trashing_order_cancels_pending_action(): void {
		$order = $this->create_pending_order();
		$order->update_status( 'completed' );
		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );

		// A non-forced delete routes through the order data store's trash path.
		$order->delete( false );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
	}

	/**
	 * @testdox Deleting the order unschedules the pending action.
	 */
	public function test_deleting_order_cancels_pending_action(): void {
		$order = $this->create_pending_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();
		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order_id ) ) );

		$order->delete( true );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order_id ) ) );
	}

	/**
	 * @testdox The woocommerce_review_order_eligible_statuses filter keeps the action queued through transitions inside the widened set.
	 */
	public function test_status_changed_respects_eligible_statuses_filter(): void {
		$widen = static function () {
			return array( 'completed', 'processing' );
		};
		add_filter( 'woocommerce_review_order_eligible_statuses', $widen );

		try {
			$order = $this->create_pending_order();
			$order->update_status( 'completed' );
			$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );

			// `processing` is eligible per the filter, so the pending action stays.
			$order->update_status( 'processing' );
			$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );

			// `on-hold` is NOT in the filter's eligible set, so the action is now unscheduled.
			$order->update_status( 'on-hold' );
			$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
		} finally {
			remove_filter( 'woocommerce_review_order_eligible_statuses', $widen );
		}
	}

	/**
	 * @testdox Cancellation unschedules the action even when the tracking meta is missing.
	 *
	 * Guards against an out-of-sync meta value leaving a stray scheduled send.
	 */
	public function test_cancellation_unschedules_when_meta_missing(): void {
		$order = $this->create_pending_order();
		$order->update_status( 'completed' );
		$order_id = $order->get_id();
		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order_id ) ) );

		// Simulate an out-of-sync state: meta cleared while the action is still pending.
		$order->delete_meta_data( Scheduler::SCHEDULED_META_KEY );
		$order->save();

		$order->update_status( 'cancelled' );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order_id ) ) );
	}

	/**
	 * Create an order in a non-completed status so transitioning to completed fires the hook cleanly.
	 */
	private function create_pending_order(): WC_Order {
		$order = OrderHelper::create_order();
		$order->set_status( 'pending' );
		$order->save();
		return $order;
	}

	/**
	 * Get the review-request email instance from the mailer.
	 */
	private function get_email(): WC_Email_Customer_Review_Request {
		$emails = WC()->mailer()->get_emails();
		return $emails['WC_Email_Customer_Review_Request'];
	}

	/**
	 * Toggle the review-request email's enabled state both in the DB and on the live instance.
	 *
	 * @param bool $enabled Whether the email should be enabled.
	 */
	private function set_review_email_enabled( bool $enabled ): void {
		$email = $this->get_email();
		$email->update_option( 'enabled', $enabled ? 'yes' : 'no' );
		$email->enabled = $enabled ? 'yes' : 'no';
	}
}
