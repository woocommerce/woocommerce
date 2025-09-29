<?php
/**
 * Tests for WC Cancel Unpaid Orders functionality.
 *
 * @package WooCommerce\Tests\Core
 */

declare(strict_types=1);


/**
 * Class WC_Tests_Cancel_Unpaid_Orders.
 */
class WC_Tests_Cancel_Unpaid_Orders extends WC_Unit_Test_Case {

	/**
	 * Original hold stock minutes option value.
	 *
	 * @var int
	 */
	public $original_hold_stock_minutes = 0;

	/**
	 * Original manage stock option value.
	 *
	 * @var string
	 */
	public $original_manage_stock_option = 'yes';

	/**
	 * Set up test environment.
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Don't allow actual order cancellations during tests.
		add_filter( 'woocommerce_cancel_unpaid_order', '__return_false', 10, 0 );
		$this->original_hold_stock_minutes  = get_option( 'woocommerce_hold_stock_minutes', 0 );
		$this->original_manage_stock_option = get_option( 'woocommerce_manage_stock', 'yes' );
	}

	/**
	 * Test that wc_cancel_unpaid_orders reschedules itself after running.
	 *
	 * This test protects against the regression where the action was marked as unique,
	 * preventing it from re-queuing itself while still running (introduced in PR #59325,
	 * fixed in PR #60607).
	 */
	public function test_cancel_unpaid_orders_reschedules_itself() {
		// Enable hold stock functionality with a short interval (1 minute).
		update_option( 'woocommerce_hold_stock_minutes', 1 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Clear any existing scheduled actions to start clean.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}

		// Verify no action is currently scheduled.
		$this->assertFalse(
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
			'No cancel unpaid orders action should be scheduled initially'
		);

		// Invoke the function that should schedule the action.
		$now = time();
		wc_cancel_unpaid_orders();

		// Assert that the action is now scheduled for the future.
		$next_scheduled = as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );
		$this->assertIsInt( $next_scheduled, 'Action should be scheduled and return a timestamp' );
		$this->assertGreaterThanOrEqual(
			$now,
			$next_scheduled,
			'Action should be scheduled for a future time'
		);

		// Verify the scheduled time is approximately correct (1 minute + 30 seconds buffer).
		$expected_time = $now + ( 1 * MINUTE_IN_SECONDS );
		$this->assertGreaterThanOrEqual(
			$expected_time - 30,
			$next_scheduled,
			'Action should not be scheduled too early'
		);

		$this->assertLessThan(
			$expected_time + 30,
			$next_scheduled,
			'Action should be scheduled within the expected time range'
		);
	}

	/**
	 * Test that existing actions are cleared before scheduling new ones.
	 */
	public function test_cancel_unpaid_orders_clears_existing_actions() {
		// Enable hold stock functionality.
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Manually schedule an action first, in the past so it is "past-due" - this prevents race conditions in the test.
		as_schedule_single_action( time() - 3600, 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );

		// Verify action is scheduled.
		$old_scheduled = as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );
		$this->assertIsInt( $old_scheduled, 'Initial action should be scheduled' );

		// Run the function which should clear and reschedule.
		$now = time();
		wc_cancel_unpaid_orders();

		// Verify action is still scheduled but at a different time.
		$new_scheduled = as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );
		$this->assertIsInt( $new_scheduled, 'Action should still be scheduled after clearing and rescheduling' );
		$this->assertNotEquals( $old_scheduled, $new_scheduled, 'New scheduled time should be different from the old one' );

		// The new scheduled time should be based on current time + 60 minutes.
		$expected_time = $now + ( 60 * MINUTE_IN_SECONDS );
		$this->assertLessThan(
			$expected_time + 60,
			$new_scheduled,
			'New scheduled action should be based on current settings'
		);
		$this->assertGreaterThan(
			$expected_time - 60,
			$new_scheduled,
			'New scheduled action should be approximately at the expected time'
		);
	}

	/**
	 * Test that the woocommerce_cancel_unpaid_orders_interval_minutes filter works.
	 */
	public function test_cancel_unpaid_orders_respects_interval_filter() {
		// Set up test conditions.
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Clear existing actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}

		// Add filter to change the interval.
		$custom_interval = 30; // 30 minutes instead of 60.
		add_filter(
			'woocommerce_cancel_unpaid_orders_interval_minutes',
			function () use ( $custom_interval ) {
				return $custom_interval;
			},
			10,
			0
		);

		// Run the function.
		$now = time();
		wc_cancel_unpaid_orders();

		// Verify the scheduled time reflects the filtered interval.
		$next_scheduled = as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );
		$this->assertIsInt( $next_scheduled );

		$expected_time = $now + ( $custom_interval * MINUTE_IN_SECONDS );
		$this->assertLessThan(
			$expected_time + 60,
			$next_scheduled,
			'Action should be scheduled based on filtered interval'
		);
		$this->assertGreaterThan(
			$expected_time - 60,
			$next_scheduled,
			'Action should be scheduled approximately at the filtered interval time'
		);
	}

	/**
	 * Test that no rescheduling happens when hold stock minutes is 0.
	 */
	public function test_cancel_unpaid_orders_no_reschedule_when_hold_stock_zero() {
		// Set hold stock minutes to 0.
		update_option( 'woocommerce_hold_stock_minutes', 0 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Clear existing actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

		// Run the function.
		wc_cancel_unpaid_orders();

		// Verify no action is scheduled.
		$this->assertFalse(
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
			'No action should be scheduled when hold stock minutes is 0'
		);

		// Also check WordPress cron.
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
			'No WP cron event should be scheduled when hold stock minutes is 0'
		);
	}

	/**
	 * Test that no rescheduling happens when stock management is disabled.
	 */
	public function test_cancel_unpaid_orders_no_reschedule_when_stock_management_disabled() {
		// Disable stock management.
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		update_option( 'woocommerce_manage_stock', 'no' );

		// Clear existing actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

		// Run the function.
		wc_cancel_unpaid_orders();

		// Verify no action is scheduled.
		$this->assertFalse(
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
			'No action should be scheduled when stock management is disabled'
		);

		// Also check WordPress cron.
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
			'No WP cron event should be scheduled when stock management is disabled'
		);
	}

	/**
	 * Test that no rescheduling happens when interval is filtered to 0 to prevent endless loops.
	 */
	public function test_cancel_unpaid_orders_no_reschedule_when_interval_zero() {
		// Set up test conditions.
		update_option( 'woocommerce_hold_stock_minutes', 1 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Clear existing actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

		// Add filter to force interval to 0 (which should prevent rescheduling).
		add_filter(
			'woocommerce_cancel_unpaid_orders_interval_minutes',
			function () {
				return 0;
			},
			10,
			0
		);

		// Run the function.
		wc_cancel_unpaid_orders();

		// Verify no action is scheduled when interval is 0.
		$this->assertFalse(
			as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
			'No action should be scheduled when interval is filtered to 0 to prevent endless loops'
		);

		// Also check WordPress cron.
		$this->assertFalse(
			wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
			'No WP cron event should be scheduled when interval is filtered to 0'
		);
	}

	/**
	 * Test that both Action Scheduler and WP-Cron events are cleared.
	 */
	public function test_cancel_unpaid_orders_clears_both_as_and_wp_cron() {
		// Set up test conditions.
		update_option( 'woocommerce_hold_stock_minutes', 60 );
		update_option( 'woocommerce_manage_stock', 'yes' );

		// Schedule both types of events.
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 3600, 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' );
		}
		wp_schedule_single_event( time() + 7200, 'woocommerce_cancel_unpaid_orders' );

		// Verify both are scheduled.
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$this->assertIsInt(
				as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
				'Action Scheduler event should be scheduled initially'
			);
		}
		$this->assertIsInt(
			wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
			'WP-Cron event should be scheduled initially'
		);

		// Run the function which should clear both and reschedule only one.
		wc_cancel_unpaid_orders();

		// If Action Scheduler is available, it should be the only one scheduled.
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$this->assertIsInt(
				as_next_scheduled_action( 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce' ),
				'Action Scheduler event should be scheduled'
			);
			// WP-Cron should be cleared.
			$this->assertFalse(
				wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
				'WP-Cron event should be cleared when Action Scheduler is used'
			);
		} else {
			// If Action Scheduler is not available, only WP-Cron should be scheduled.
			$this->assertIsInt(
				wp_next_scheduled( 'woocommerce_cancel_unpaid_orders' ),
				'WP-Cron event should be scheduled when Action Scheduler is not available'
			);
		}
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up scheduled actions and settings.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'woocommerce_cancel_unpaid_orders' );
		}
		wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

		update_option( 'woocommerce_hold_stock_minutes', $this->original_hold_stock_minutes );
		update_option( 'woocommerce_manage_stock', $this->original_manage_stock_option );

		// Remove any filters that might have been added.
		remove_all_filters( 'woocommerce_cancel_unpaid_orders_interval_minutes' );
		remove_all_filters( 'woocommerce_cancel_unpaid_orders' );
		remove_all_filters( 'woocommerce_cancel_unpaid_order' );
	}
}
