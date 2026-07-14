<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Internal\OrderReviews\Scheduler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Email_Customer_Review_Request;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Scheduler test.
 *
 * @covers \Automattic\WooCommerce\Internal\OrderReviews\Scheduler
 */
class SchedulerTest extends WC_Unit_Test_Case {
	/**
	 * Product ID shared by generic reviewable-order fixtures.
	 *
	 * @var int
	 */
	private static $reviewable_product_id;

	/**
	 * Create the immutable reviewable product before per-test transactions begin.
	 */
	public static function wpSetUpBeforeClass(): void {
		self::enable_direct_product_attribute_lookup_updates();

		try {
			self::$reviewable_product_id = WC_Helper_Product::create_simple_product()->get_id();
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Delete the shared product through the WooCommerce data store.
	 */
	public static function wpTearDownAfterClass(): void {
		self::enable_direct_product_attribute_lookup_updates();

		try {
			$product = wc_get_product( self::$reviewable_product_id );
			if ( $product ) {
				$product->delete( true );
			}
		} finally {
			self::disable_direct_product_attribute_lookup_updates();
		}
	}

	/**
	 * Prepare the mailer and enable the review-request email.
	 */
	public function setUp(): void {
		parent::setUp();

		// Feature flag gates the OrderReviews stack. Enable it, then resolve
		// the Scheduler from the container (singleton across the test run)
		// and call init() to wire hooks. Re-init WC_Emails so the
		// review-request email class lands in the mailer map.
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
		wc_get_container()->get( Scheduler::class )->init();
		WC()->mailer()->init();

		$this->set_review_email_enabled( true );
	}

	/**
	 * Reset between tests.
	 */
	public function tearDown(): void {
		$this->set_review_email_enabled( false );
		remove_all_filters( 'woocommerce_should_send_review_request' );
		remove_all_filters( 'woocommerce_review_request_delay_seconds' );
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );

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

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Existing core hook, fired here only to simulate a second completed-notification (e.g. status toggled back and forth).
		do_action( 'woocommerce_order_status_completed', $order->get_id() );
		$second = (int) wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY );

		$this->assertSame( $first, $second, 'Scheduled-at meta should not change on re-completion.' );
		$this->assertCount(
			1,
			as_get_scheduled_actions(
				array(
					'hook'   => Scheduler::ACTION_HOOK,
					'args'   => array( $order->get_id() ),
					'status' => \ActionScheduler_Store::STATUS_PENDING,
				),
				'ids'
			),
			'A duplicate completion must not schedule a second review-request action.'
		);
	}

	/**
	 * @testdox Scheduling is skipped when every product on the order has reviews disabled per-product.
	 */
	public function test_skips_when_all_items_have_reviews_disabled(): void {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_reviews_allowed( false );
		$product->save();

		$order = $this->create_pending_order_with_product( $product );
		$order->update_status( 'completed' );

		$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
		$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ) );
	}

	/**
	 * @testdox Scheduling is skipped when site-wide reviews are disabled.
	 *
	 * The `woocommerce_enable_reviews=no` setting removes `comments` support
	 * from the product post type so `comments_open()` returns false for every
	 * product, which `ItemEligibility::has_actionable_items()` reads.
	 */
	public function test_skips_when_site_wide_reviews_disabled(): void {
		$previous = get_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_reviews', 'no' );
		// `comments` post-type support is registered at init based on the
		// option, so reflect the option change for the rest of this test.
		remove_post_type_support( 'product', 'comments' );

		try {
			$order = $this->create_pending_order();
			$order->update_status( 'completed' );

			$this->assertFalse( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
			$this->assertEmpty( wc_get_order( $order->get_id() )->get_meta( Scheduler::SCHEDULED_META_KEY ) );
		} finally {
			update_option( 'woocommerce_enable_reviews', $previous );
			if ( 'yes' === $previous ) {
				add_post_type_support( 'product', 'comments' );
			}
		}
	}

	/**
	 * @testdox A mixed order with at least one reviewable item still schedules.
	 */
	public function test_schedules_when_at_least_one_item_is_reviewable(): void {
		$reviewable = WC_Helper_Product::create_simple_product();
		$disabled   = WC_Helper_Product::create_simple_product();
		$disabled->set_reviews_allowed( false );
		$disabled->save();

		$order = OrderHelper::create_order( 1, $reviewable );
		$item  = new WC_Order_Item_Product();
		$item->set_props(
			array(
				'product'  => $disabled,
				'quantity' => 1,
				'subtotal' => wc_get_price_excluding_tax( $disabled ),
				'total'    => wc_get_price_excluding_tax( $disabled ),
			)
		);
		$item->save();
		$order->add_item( $item );
		$order->set_status( 'pending' );
		$order->calculate_totals();
		$order->save();

		$order->update_status( 'completed' );

		$this->assertTrue( (bool) as_next_scheduled_action( Scheduler::ACTION_HOOK, array( $order->get_id() ) ) );
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
		$order = OrderHelper::create_order( 1, wc_get_product( self::$reviewable_product_id ) );
		$order->set_status( 'pending' );
		$order->save();
		return $order;
	}

	/**
	 * Create a pending order whose single line item is the provided product.
	 *
	 * @param \WC_Product $product Product to add to the order.
	 */
	private function create_pending_order_with_product( \WC_Product $product ): WC_Order {
		$order = OrderHelper::create_order( 1, $product );
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
