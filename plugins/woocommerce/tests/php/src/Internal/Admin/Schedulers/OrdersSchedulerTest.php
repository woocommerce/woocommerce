<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Schedulers;

use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use WC_Unit_Test_Case;
use Automattic\WooCommerce\Admin\Features\Features;

/**
 * OrdersScheduler Test.
 *
 * @class OrdersSchedulerTest
 */
class OrdersSchedulerTest extends WC_Unit_Test_Case {

	/**
	 * Set up the test environment.
	 */
	public function setUp(): void {
		parent::setUp();

		// Enable the analytics-scheduled-import feature.
		Features::enable( 'analytics-scheduled-import' );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up options.
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_ID_OPTION );
		delete_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION );

		// Clean up any scheduled actions.
		$this->clear_scheduled_batch_processor();

		Features::disable( 'analytics-scheduled-import' );
	}

	/**
	 * Test that batch processor is scheduled when called.
	 */
	public function test_batch_processor_scheduled() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		OrdersScheduler::schedule_recurring_batch_processor();

		// Verify the recurring action is scheduled.
		$this->assertTrue(
			$this->is_batch_processor_scheduled(),
			'Batch processor should be scheduled'
		);
	}

	/**
	 * Test that batch processor is not scheduled twice.
	 */
	public function test_batch_processor_not_scheduled_twice() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		$action_hook = OrdersScheduler::get_action( 'process_pending_batch' );

		// Schedule first time.
		OrdersScheduler::schedule_recurring_batch_processor();
		// Try to schedule again.
		OrdersScheduler::schedule_recurring_batch_processor();

		// Verify it's still the same scheduled time (not rescheduled).
		$second_scheduled = as_get_scheduled_actions(
			array(
				'hook'     => $action_hook,
				'args'     => array(),
				'group'    => OrdersScheduler::$group,
				'status'   => 'pending',
				'per_page' => 1,
			),
			ARRAY_A
		);
		$this->assertCount( 1, $second_scheduled, 'Batch processor should be scheduled once' );
	}

	/**
	 * Test that import interval filter is applied.
	 */
	public function test_import_interval_filter_is_applied() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		$custom_interval = 6 * HOUR_IN_SECONDS;
		$filter_called   = false;
		add_filter(
			'woocommerce_analytics_import_interval',
			function () use ( $custom_interval, &$filter_called ) {
				$filter_called = true;
				return $custom_interval;
			}
		);

		// This will trigger the filter.
		OrdersScheduler::schedule_recurring_batch_processor();

		// Verify filter was applied.
		$this->assertTrue(
			$filter_called,
			'Import interval filter should be applied when scheduling batch processor'
		);
	}

	/**
	 * Test that handle_scheduled_import_option_change unschedules batch processor when switching to immediate import.
	 */
	public function test_handle_scheduled_import_option_change_unschedules_batch_when_disabling_scheduled() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		// Schedule the batch processor first.
		OrdersScheduler::schedule_recurring_batch_processor();
		$this->assertTrue(
			$this->is_batch_processor_scheduled(),
			'Batch processor should be scheduled initially'
		);

		// Switch from scheduled import ('yes') to immediate import ('no').
		OrdersScheduler::handle_scheduled_import_option_change( 'yes', 'no' );

		// Verify the batch processor is unscheduled.
		$this->assertFalse(
			$this->is_batch_processor_scheduled(),
			'Batch processor should be unscheduled when switching to immediate import'
		);
	}

	/**
	 * Test that handle_scheduled_import_option_change schedules batch processor when switching to scheduled import.
	 */
	public function test_handle_scheduled_import_option_change_schedules_batch_when_enabling_scheduled() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		// Switch from immediate import ('no') to scheduled import ('yes').
		OrdersScheduler::handle_scheduled_import_option_change( 'no', 'yes' );

		// Verify the batch processor is scheduled.
		$this->assertTrue(
			$this->is_batch_processor_scheduled(),
			'Batch processor should be scheduled when switching from immediate import to scheduled import'
		);

		// Verify the last processed date is set to approximately 1 minute ago.
		$last_date = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );
		$this->assertNotFalse(
			$last_date,
			'Last processed date should be set when switching to scheduled import'
		);

		$expected_timestamp = time() - MINUTE_IN_SECONDS;
		$actual_timestamp   = strtotime( $last_date );

		$this->assertEqualsWithDelta(
			$expected_timestamp,
			$actual_timestamp,
			5,
			'Last processed date should be approximately 1 minute ago'
		);

		// Verify the last processed order ID is reset to 0.
		$last_id = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_ID_OPTION );
		$this->assertEquals(
			0,
			$last_id,
			'Last processed order ID should be reset to 0'
		);
	}

	/**
	 * Test that handle_scheduled_import_option_change does nothing for other transitions.
	 */
	public function test_handle_scheduled_import_option_change_ignores_other_transitions() {
		// Clear any existing scheduled actions.
		$this->clear_scheduled_batch_processor();

		$action_hook = OrdersScheduler::get_action( 'process_pending_batch' );

		// Test transition from 'no' to 'no' (no change - stays immediate import).
		OrdersScheduler::handle_scheduled_import_option_change( 'no', 'no' );
		$this->assertFalse(
			$this->is_batch_processor_scheduled(),
			'Batch processor should not be scheduled when option stays as immediate import'
		);

		// Test transition from 'yes' to 'yes' (no change - stays scheduled import).
		OrdersScheduler::schedule_recurring_batch_processor();
		$scheduled_time = as_next_scheduled_action( $action_hook );
		OrdersScheduler::handle_scheduled_import_option_change( 'yes', 'yes' );
		$this->assertEquals(
			$scheduled_time,
			as_next_scheduled_action( $action_hook ),
			'Batch processor should remain scheduled when option stays as scheduled import'
		);
	}

	/**
	 * @testdox Should identify order with _wcpay_mode test as a test order.
	 */
	public function test_is_test_order_with_wcpay_test_mode(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		$this->assertTrue( OrdersScheduler::is_test_order( $order ) );
	}

	/**
	 * @testdox Should not identify a normal order as a test order.
	 */
	public function test_is_test_order_with_normal_order(): void {
		$order = \WC_Helper_Order::create_order();

		$this->assertFalse( OrdersScheduler::is_test_order( $order ) );
	}

	/**
	 * @testdox Should not identify an order with _wcpay_mode live as a test order.
	 */
	public function test_is_test_order_with_wcpay_live_mode(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'live' );
		$order->save();

		$this->assertFalse( OrdersScheduler::is_test_order( $order ) );
	}

	/**
	 * @testdox Should identify a refund of a test order as a test order.
	 */
	public function test_is_test_order_with_refund_of_test_order(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			)
		);

		$this->assertTrue( OrdersScheduler::is_test_order( $refund ) );
	}

	/**
	 * @testdox Should not identify a refund of a normal order as a test order.
	 */
	public function test_is_test_order_with_refund_of_normal_order(): void {
		$order = \WC_Helper_Order::create_order();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			)
		);

		$this->assertFalse( OrdersScheduler::is_test_order( $refund ) );
	}

	/**
	 * @testdox Should allow the woocommerce_analytics_is_test_order filter to mark a normal order as a test order.
	 */
	public function test_is_test_order_filter_can_override(): void {
		$order = \WC_Helper_Order::create_order();

		// Order has no _wcpay_mode meta, so default is false.
		$this->assertFalse( OrdersScheduler::is_test_order( $order ) );

		// Override via filter to mark it as test.
		add_filter( 'woocommerce_analytics_is_test_order', '__return_true' );

		$this->assertTrue( OrdersScheduler::is_test_order( $order ) );

		remove_filter( 'woocommerce_analytics_is_test_order', '__return_true' );
	}

	/**
	 * @testdox Should allow the woocommerce_analytics_is_test_order filter to include a test order in analytics.
	 */
	public function test_is_test_order_filter_can_allow_test_order(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		// Default is true because _wcpay_mode is 'test'.
		$this->assertTrue( OrdersScheduler::is_test_order( $order ) );

		// Override via filter to allow it.
		add_filter( 'woocommerce_analytics_is_test_order', '__return_false' );

		$this->assertFalse( OrdersScheduler::is_test_order( $order ) );

		remove_filter( 'woocommerce_analytics_is_test_order', '__return_false' );
	}

	/**
	 * @testdox Should return false for a refund whose parent order has been deleted.
	 */
	public function test_is_test_order_with_orphaned_refund(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			)
		);

		// Delete the parent order to create an orphaned refund.
		$order->delete( true );

		$this->assertFalse( OrdersScheduler::is_test_order( $refund ) );
	}

	/**
	 * @testdox Should pass the parent order to the filter when checking a refund.
	 */
	public function test_is_test_order_filter_receives_parent_order_for_refund(): void {
		$order = \WC_Helper_Order::create_order();
		$order->save();

		$refund = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
				'amount'   => 10,
				'reason'   => 'Test refund',
			)
		);

		$received_order  = null;
		$filter_callback = function ( $is_test, $filter_order ) use ( &$received_order ) {
			$received_order = $filter_order;
			return $is_test;
		};
		add_filter( 'woocommerce_analytics_is_test_order', $filter_callback, 10, 2 );

		OrdersScheduler::is_test_order( $refund );

		$this->assertNotNull( $received_order );
		$this->assertEquals( $order->get_id(), $received_order->get_id() );

		remove_filter( 'woocommerce_analytics_is_test_order', $filter_callback );
	}

	/**
	 * @testdox Should return -1 from DataStore update when given a test order.
	 */
	public function test_datastore_update_skips_test_order(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		$result = OrdersStatsDataStore::update( $order );

		$this->assertSame( -1, $result );
	}

	/**
	 * @testdox Should return -1 from DataStore sync_order when given a test order ID.
	 */
	public function test_datastore_sync_order_skips_test_order(): void {
		$order = \WC_Helper_Order::create_order();
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();

		$result = OrdersStatsDataStore::sync_order( $order->get_id() );

		$this->assertSame( -1, $result );
	}

	/**
	 * Clear any scheduled batch processor actions.
	 *
	 * @return void
	 */
	private function clear_scheduled_batch_processor(): void {
		$action_hook = OrdersScheduler::get_action( 'process_pending_batch' );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( $action_hook, array(), OrdersScheduler::$group );
		}
	}

	/**
	 * Check if the batch processor action is scheduled.
	 *
	 * @return bool
	 */
	private function is_batch_processor_scheduled(): bool {
		$action_hook = OrdersScheduler::get_action( 'process_pending_batch' );
		return function_exists( 'as_has_scheduled_action' ) ? as_has_scheduled_action( $action_hook, array(), OrdersScheduler::$group ) : (bool) as_next_scheduled_action( $action_hook, array(), OrdersScheduler::$group );
	}
}
