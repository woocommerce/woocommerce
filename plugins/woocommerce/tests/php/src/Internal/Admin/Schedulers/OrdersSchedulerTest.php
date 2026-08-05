<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Schedulers;

use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;
use WC_Unit_Test_Case;

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

		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'yes' );
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
		delete_option( OrdersScheduler::LEGACY_IMMEDIATE_IMPORT_OPTION );
		delete_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION );

		// Clean up any scheduled actions.
		$this->clear_scheduled_batch_processor();

		delete_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION );
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
	 * @testdox process_pending_batch skips a failing order and advances the cursor past it.
	 */
	public function test_process_pending_batch_skips_failing_order_and_advances_cursor(): void {
		global $wpdb;
		// Anchor the cursor just before test orders so existing DB orders are excluded.
		$cursor_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}wc_orders" );

		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$cursor_date     = '2000-01-01 00:00:00';
		$throwing_filter = function ( $is_test, $checked_order ) use ( $order ) {
			if ( $checked_order instanceof \WC_Abstract_Order && $checked_order->get_id() === $order->get_id() ) {
				throw new \DivisionByZeroError( 'Division by zero' );
			}

			return $is_test;
		};

		OrdersScheduler::clear_queued_actions();
		add_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10, 2 );
		OrdersScheduler::process_pending_batch( $cursor_date, $cursor_id );
		remove_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10 );

		// Cursor must advance past the failing order so it is not retried on the next run.
		$this->assertSame( $order->get_id(), (int) get_option( OrdersScheduler::LAST_PROCESSED_ORDER_ID_OPTION ) );
		$this->assertNotSame( $cursor_date, get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION ) );
	}

	/**
	 * @testdox process_pending_batch advances the cursor to the last-processed order when a later order fails.
	 */
	public function test_process_pending_batch_cursor_reflects_last_processed_order_on_partial_failure(): void {
		global $wpdb;
		// Anchor the cursor just before test orders so existing DB orders are excluded.
		$cursor_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}wc_orders" );

		// Both orders get the same timestamp in tests; ordering falls back to id ASC,
		// so order_a (lower ID) is processed before order_b.
		$order_a = \WC_Helper_Order::create_order();
		$order_a->set_status( 'completed' );
		$order_a->save();

		$order_b = \WC_Helper_Order::create_order();
		$order_b->set_status( 'completed' );
		$order_b->save();

		$cursor_date     = '2000-01-01 00:00:00';
		$throwing_filter = function ( $is_test, $checked_order ) use ( $order_b ) {
			if ( $checked_order instanceof \WC_Abstract_Order && $checked_order->get_id() === $order_b->get_id() ) {
				throw new \DivisionByZeroError( 'Division by zero' );
			}

			return $is_test;
		};

		OrdersScheduler::clear_queued_actions();
		add_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10, 2 );
		OrdersScheduler::process_pending_batch( $cursor_date, $cursor_id );
		remove_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10 );

		// Cursor should be at order_b (the last order processed, even though it failed),
		// not at order_a or at the initial position.
		$this->assertSame( $order_b->get_id(), (int) get_option( OrdersScheduler::LAST_PROCESSED_ORDER_ID_OPTION ) );
	}

	/**
	 * @testdox is_scheduled_import_enabled falls back to legacy option when new option is absent.
	 */
	public function test_is_scheduled_import_enabled_falls_back_to_legacy_option(): void {
		// Simulate a pre-10.5.0 store that opted into scheduled imports (legacy 'no' = not immediate = scheduled).
		delete_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION );
		update_option( OrdersScheduler::LEGACY_IMMEDIATE_IMPORT_OPTION, 'no' );

		$reflection = new \ReflectionClass( OrdersScheduler::class );
		$method     = $reflection->getMethod( 'is_scheduled_import_enabled' );
		$method->setAccessible( true );

		$this->assertTrue(
			$method->invoke( null ),
			'Legacy option "no" (not immediate) should be interpreted as scheduled import enabled'
		);
	}

	/**
	 * @testdox is_scheduled_import_enabled falls back to legacy option 'yes' correctly.
	 */
	public function test_is_scheduled_import_enabled_falls_back_to_legacy_option_yes(): void {
		// Simulate a pre-10.5.0 store with default immediate import (legacy 'yes' = immediate = not scheduled).
		delete_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION );
		update_option( OrdersScheduler::LEGACY_IMMEDIATE_IMPORT_OPTION, 'yes' );

		$reflection = new \ReflectionClass( OrdersScheduler::class );
		$method     = $reflection->getMethod( 'is_scheduled_import_enabled' );
		$method->setAccessible( true );

		$this->assertFalse(
			$method->invoke( null ),
			'Legacy option "yes" (immediate) should be interpreted as scheduled import disabled'
		);
	}

	/**
	 * @testdox is_scheduled_import_enabled prefers new option over legacy option when they conflict.
	 */
	public function test_is_scheduled_import_enabled_prefers_new_option(): void {
		// New option says "not scheduled", legacy says "scheduled" (inverted 'no' = scheduled).
		// If the new option takes precedence, result should be false.
		update_option( OrdersScheduler::SCHEDULED_IMPORT_OPTION, 'no' );
		update_option( OrdersScheduler::LEGACY_IMMEDIATE_IMPORT_OPTION, 'no' );

		$reflection = new \ReflectionClass( OrdersScheduler::class );
		$method     = $reflection->getMethod( 'is_scheduled_import_enabled' );
		$method->setAccessible( true );

		$this->assertFalse(
			$method->invoke( null ),
			'New option "no" should take precedence over legacy option "no" (which would mean scheduled)'
		);
	}

	/**
	 * @testdox get_failed_order_imports normalizes malformed or legacy option values.
	 */
	public function test_get_failed_order_imports_normalizes_malformed_option(): void {
		// A scalar value (e.g. from a corrupted or manually edited option).
		update_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION, 'yes', false );
		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array(), $failed['ids'] );
		$this->assertSame( 0, $failed['overflow'] );

		// An array missing the 'ids' key, with a non-int overflow.
		update_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION, array( 'overflow' => '4' ), false );
		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array(), $failed['ids'] );
		$this->assertSame( 4, $failed['overflow'] );

		// 'ids' set to a non-array value.
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => 'not-an-array',
				'overflow' => 1,
			),
			false
		);
		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array(), $failed['ids'] );
		$this->assertSame( 1, $failed['overflow'] );
	}

	/**
	 * @testdox record_failed_order_import stores deduplicated order IDs.
	 */
	public function test_record_failed_order_import_dedupes(): void {
		OrdersScheduler::record_failed_order_import( 11 );
		OrdersScheduler::record_failed_order_import( 22 );
		OrdersScheduler::record_failed_order_import( 11 );

		$failed = OrdersScheduler::get_failed_order_imports();

		$this->assertSame( array( 11, 22 ), $failed['ids'] );
		$this->assertSame( 0, $failed['overflow'] );
	}

	/**
	 * @testdox record_failed_order_import ignores invalid order IDs.
	 */
	public function test_record_failed_order_import_ignores_invalid_ids(): void {
		OrdersScheduler::record_failed_order_import( 0 );

		$failed = OrdersScheduler::get_failed_order_imports();

		$this->assertSame( array(), $failed['ids'] );
	}

	/**
	 * @testdox record_failed_order_import drops the oldest ID and counts overflow when the cap is reached.
	 */
	public function test_record_failed_order_import_caps_list_and_counts_overflow(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => range( 1, 1000 ),
				'overflow' => 2,
			),
			false
		);

		OrdersScheduler::record_failed_order_import( 1001 );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertCount( 1000, $failed['ids'], 'List should stay at the cap' );
		$this->assertNotContains( 1, $failed['ids'], 'Oldest ID should be dropped' );
		$this->assertContains( 1001, $failed['ids'], 'New ID should be recorded' );
		$this->assertSame( 3, $failed['overflow'] );
	}

	/**
	 * @testdox clear_failed_order_import removes a recorded ID.
	 */
	public function test_clear_failed_order_import_removes_id(): void {
		OrdersScheduler::record_failed_order_import( 11 );
		OrdersScheduler::record_failed_order_import( 22 );

		OrdersScheduler::clear_failed_order_import( 11 );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array( 22 ), $failed['ids'] );
	}

	/**
	 * @testdox clear_failed_order_import deletes the option when nothing is left.
	 */
	public function test_clear_failed_order_import_deletes_option_when_empty(): void {
		OrdersScheduler::record_failed_order_import( 11 );

		OrdersScheduler::clear_failed_order_import( 11 );

		$this->assertFalse( get_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION ) );
	}

	/**
	 * @testdox reset_failed_order_imports_overflow resets the counter but keeps stored IDs.
	 */
	public function test_reset_failed_order_imports_overflow(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 5 ),
				'overflow' => 7,
			),
			false
		);

		OrdersScheduler::reset_failed_order_imports_overflow();

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array( 5 ), $failed['ids'] );
		$this->assertSame( 0, $failed['overflow'] );
	}

	/**
	 * @testdox clear_failed_order_import preserves the option when overflow is non-zero after clearing all IDs.
	 */
	public function test_clear_failed_order_import_keeps_option_when_overflow_remains(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array( 11 ),
				'overflow' => 3,
			),
			false
		);

		OrdersScheduler::clear_failed_order_import( 11 );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertSame( array(), $failed['ids'] );
		$this->assertSame( 3, $failed['overflow'] );
	}

	/**
	 * @testdox reset_failed_order_imports_overflow deletes the option when IDs are empty after reset.
	 */
	public function test_reset_failed_order_imports_overflow_deletes_option_when_ids_empty(): void {
		update_option(
			OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION,
			array(
				'ids'      => array(),
				'overflow' => 5,
			),
			false
		);

		OrdersScheduler::reset_failed_order_imports_overflow();

		$this->assertFalse( get_option( OrdersScheduler::FAILED_ORDER_IMPORTS_OPTION ) );
	}

	/**
	 * @testdox process_pending_batch records the ID of an order that fails to import.
	 */
	public function test_process_pending_batch_records_failed_order(): void {
		global $wpdb;
		// Anchor the cursor just before test orders so existing DB orders are excluded.
		$cursor_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$wpdb->prefix}wc_orders" );

		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		$throwing_filter = function ( $is_test, $checked_order ) use ( $order ) {
			if ( $checked_order instanceof \WC_Abstract_Order && $checked_order->get_id() === $order->get_id() ) {
				throw new \DivisionByZeroError( 'Division by zero' );
			}

			return $is_test;
		};

		OrdersScheduler::clear_queued_actions();
		add_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10, 2 );
		OrdersScheduler::process_pending_batch( '2000-01-01 00:00:00', $cursor_id );
		remove_filter( 'woocommerce_analytics_is_test_order', $throwing_filter, 10 );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertContains( $order->get_id(), $failed['ids'] );
	}

	/**
	 * @testdox import clears the failed record for an order that imports successfully.
	 */
	public function test_import_clears_failed_order_on_success(): void {
		$order = \WC_Helper_Order::create_order();
		$order->set_status( 'completed' );
		$order->save();

		OrdersScheduler::record_failed_order_import( $order->get_id() );

		OrdersScheduler::import( $order->get_id() );

		$failed = OrdersScheduler::get_failed_order_imports();
		$this->assertNotContains( $order->get_id(), $failed['ids'] );
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
