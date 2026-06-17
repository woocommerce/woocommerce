<?php
declare( strict_types=1 );
/**
 * Unit tests for WooCommerce recurring actions.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WC_Recurring_Actions_Test
 */
class WC_Recurring_Actions_Test extends WC_Unit_Test_Case {

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		// Clear any existing scheduled actions first.
		$this->clear_scheduled_actions();
	}

	/**
	 * Test that recurring actions are properly enqueued when ensure_recurring_actions is called.
	 */
	public function test_recurring_actions_are_enqueued() {
		$this->assertTrue(
			as_supports( 'ensure_recurring_actions_hook' ),
			'Action Scheduler must support ensure_recurring_actions_hook for WooCommerce recurring actions to work properly'
		);

		// Allow tracking for the purpose of this test.
		update_option( 'woocommerce_allow_tracking', 'yes' );
		// Clear any existing scheduled actions first.
		$this->clear_scheduled_actions();

		// Ensure recurring actions are scheduled.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_scheduler_ensure_recurring_actions' );

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Tracker send event wrapper should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'wc_admin_daily_wrapper' ),
			'Admin daily wrapper should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'generate_category_lookup_table_wrapper' ),
			'Category lookup table wrapper should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_cleanup_rate_limits_wrapper' ),
			'Rate limits cleanup wrapper should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_scheduled_sales' ),
			'Scheduled sales should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_cleanup_personal_data' ),
			'Personal data cleanup should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_cleanup_logs' ),
			'Log cleanup should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_cleanup_sessions' ),
			'Session cleanup should be scheduled'
		);

		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_geoip_updater' ),
			'GeoIP updater should be scheduled'
		);
	}

	/**
	 * Test that the tracker wrapper is not added when tracking is disabled.
	 */
	public function test_tracker_wrapper_not_added_when_tracking_disabled() {
		// Disable tracking.
		update_option( 'woocommerce_allow_tracking', 'no' );
		$this->assertFalse(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Tracker send event wrapper should not be scheduled'
		);
		// Validate the wrapper is not scheduled when ensure_recurring_actions is called with tracking disabled.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_scheduler_ensure_recurring_actions' );
		$this->assertFalse(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Tracker send event wrapper should not be scheduled'
		);
	}

	/**
	 * Test that tracker wrapper is removed when tracking is disabled and added back when tracking is enabled.
	 */
	public function test_tracker_wrapper_added_then_removed_on_tracking_toggle() {
		// Ensure we transition from "no" -> "yes" so update_option hooks fire.
		update_option( 'woocommerce_allow_tracking', 'no' );
		// Enable tracking.
		update_option( 'woocommerce_allow_tracking', 'yes' );
		$this->assertTrue(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Tracker send event wrapper should be scheduled'
		);
		// Disable tracking.
		update_option( 'woocommerce_allow_tracking', 'no' );
		$this->assertFalse(
			as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ),
			'Tracker send event wrapper should not be scheduled'
		);
	}

	/**
	 * @testDox The scheduled sales action is rescheduled to midnight when it was previously set to run at another time of day.
	 */
	public function test_scheduled_sales_is_rescheduled_to_midnight() {
		// Simulate a legacy install where the recurring action is stuck at an arbitrary time of day.
		$arbitrary_time = strtotime( 'tomorrow 09:15' );
		as_schedule_recurring_action( $arbitrary_time, DAY_IN_SECONDS, 'woocommerce_scheduled_sales', array(), 'woocommerce', true );
		$this->assertSame(
			$arbitrary_time,
			as_next_scheduled_action( 'woocommerce_scheduled_sales', array(), 'woocommerce' ),
			'Precondition: the action should start at the arbitrary legacy time'
		);

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_scheduler_ensure_recurring_actions' );

		$this->assertSame(
			$this->expected_midnight_timestamp(),
			as_next_scheduled_action( 'woocommerce_scheduled_sales', array(), 'woocommerce' ),
			'The action should be rescheduled to midnight'
		);
	}

	/**
	 * @testDox The scheduled sales action is left untouched when it already runs at midnight.
	 */
	public function test_scheduled_sales_is_not_rescheduled_when_already_at_midnight() {
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_scheduler_ensure_recurring_actions' );
		$first_run = as_next_scheduled_action( 'woocommerce_scheduled_sales', array(), 'woocommerce' );

		// A second ensure pass must not reschedule or duplicate the action.
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'action_scheduler_ensure_recurring_actions' );

		$this->assertSame(
			$first_run,
			as_next_scheduled_action( 'woocommerce_scheduled_sales', array(), 'woocommerce' ),
			'The action should keep its midnight run time across ensure passes'
		);
		$pending = as_get_scheduled_actions(
			array(
				'hook'     => 'woocommerce_scheduled_sales',
				'group'    => 'woocommerce',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => -1,
			),
			'ids'
		);
		$this->assertCount( 1, $pending, 'Only a single scheduled sales action should be pending' );
	}

	/**
	 * Compute the timestamp of the next midnight in the site timezone, matching the value
	 * used by WC()->register_recurring_actions().
	 *
	 * @return int
	 */
	private function expected_midnight_timestamp(): int {
		$gmt_offset   = get_option( 'gmt_offset' );
		$offset_hours = ( $gmt_offset > 0 ? '-' : '+' ) . absint( $gmt_offset ) . ' hours';

		$midnight = strtotime( '00:00 tomorrow ' . $offset_hours );
		if ( false === $midnight ) {
			$midnight = strtotime( '00:00 tomorrow' );
		}

		return $midnight;
	}

	/**
	 * Helper method to clear all scheduled actions.
	 */
	private function clear_scheduled_actions() {
		$actions = array(
			'woocommerce_tracker_send_event_wrapper',
			'wc_admin_daily_wrapper',
			'generate_category_lookup_table_wrapper',
			'woocommerce_cleanup_rate_limits_wrapper',
			'woocommerce_scheduled_sales',
			'woocommerce_cleanup_personal_data',
			'woocommerce_cleanup_logs',
			'woocommerce_cleanup_sessions',
			'woocommerce_geoip_updater',
		);

		foreach ( $actions as $action ) {
			as_unschedule_all_actions( $action );
		}
	}
}
