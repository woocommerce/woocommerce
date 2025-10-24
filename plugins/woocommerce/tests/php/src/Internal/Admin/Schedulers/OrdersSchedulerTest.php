<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Schedulers;

use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;
use WC_Unit_Test_Case;

/**
 * OrdersScheduler Test.
 *
 * @class OrdersSchedulerTest
 */
class OrdersSchedulerTest extends WC_Unit_Test_Case {

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up options.
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_ID_OPTION );

		// Remove filters.
		remove_all_filters( 'woocommerce_analytics_enable_immediate_import' );
		remove_all_filters( 'woocommerce_analytics_import_interval' );
	}

	/**
	 * Test that immediate import mode registers order hooks.
	 */
	public function test_immediate_import_enabled_registers_hooks() {
		add_filter( 'woocommerce_analytics_enable_immediate_import', '__return_true' );

		OrdersScheduler::init();

		$this->assertNotFalse(
			has_action( 'woocommerce_update_order', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should register woocommerce_update_order hook when immediate import is enabled'
		);

		$this->assertNotFalse(
			has_filter( 'woocommerce_create_order', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should register woocommerce_create_order hook when immediate import is enabled'
		);

		$this->assertNotFalse(
			has_action( 'woocommerce_refund_created', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should register woocommerce_refund_created hook when immediate import is enabled'
		);
	}

	/**
	 * Test that batch mode does not register immediate import hooks.
	 */
	public function test_batch_mode_does_not_register_immediate_hooks() {
		// Ensure immediate import is disabled (default behavior).
		add_filter( 'woocommerce_analytics_enable_immediate_import', '__return_false' );

		OrdersScheduler::init();

		$this->assertFalse(
			has_action( 'woocommerce_update_order', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should not register woocommerce_update_order hook when batch mode is enabled'
		);

		$this->assertFalse(
			has_filter( 'woocommerce_create_order', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should not register woocommerce_create_order hook when batch mode is enabled'
		);

		$this->assertFalse(
			has_action( 'woocommerce_refund_created', array( OrdersScheduler::class, 'possibly_schedule_import' ) ),
			'Should not register woocommerce_refund_created hook when batch mode is enabled'
		);
	}

	/**
	 * Test that the batch processor action is registered.
	 */
	public function test_get_scheduler_actions_includes_batch_processor() {
		$actions = OrdersScheduler::get_scheduler_actions();

		$this->assertArrayHasKey(
			'process_pending_batch',
			$actions,
			'Scheduler actions should include process_pending_batch'
		);

		$this->assertEquals(
			'wc-admin_process_pending_orders_batch',
			$actions['process_pending_batch'],
			'process_pending_batch action should map to correct hook name'
		);
	}

	/**
	 * Test that last processed date is initialized correctly.
	 */
	public function test_initialize_sets_last_processed_date() {
		// Ensure option doesn't exist.
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );

		OrdersScheduler::schedule_recurring_batch_processor();

		$last_date = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );

		$this->assertNotFalse(
			$last_date,
			'Last processed date option should be set after initialization'
		);

		// Should be approximately 10 minutes ago (600 seconds).
		$expected_timestamp = time() - ( 10 * MINUTE_IN_SECONDS );
		$actual_timestamp   = strtotime( $last_date );

		$this->assertEqualsWithDelta(
			$expected_timestamp,
			$actual_timestamp,
			5,
			'Last processed date should be approximately 10 minutes ago'
		);
	}

	/**
	 * Test that last processed date is not re-initialized if already set.
	 */
	public function test_initialize_does_not_overwrite_existing_date() {
		$existing_date = '2024-01-01 12:00:00';
		update_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION, $existing_date );

		OrdersScheduler::schedule_recurring_batch_processor();

		$last_date = get_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );

		$this->assertEquals(
			$existing_date,
			$last_date,
			'Last processed date should not be overwritten if already set'
		);
	}

	/**
	 * Test that import interval filter is applied.
	 */
	public function test_import_interval_filter_is_applied() {
		delete_option( OrdersScheduler::LAST_PROCESSED_ORDER_DATE_OPTION );

		$custom_interval = 6 * HOUR_IN_SECONDS;
		$filter_called = false;
		add_filter(
			'woocommerce_analytics_import_interval',
			function() use ( $custom_interval, &$filter_called ) {
				$filter_called = true;
				return $custom_interval;
			}
		);

		// Enable batch mode.
		add_filter( 'woocommerce_analytics_enable_immediate_import', '__return_false' );

		// This will trigger the filter.
		OrdersScheduler::schedule_recurring_batch_processor();

		// Verify filter was applied (we can't directly check ActionScheduler without complex mocking,
		// but we can verify the filter is called by checking if it was applied).
		$this->assertTrue(
			$filter_called,
			'Import interval filter should be applied during initialization'
		);
	}
}
