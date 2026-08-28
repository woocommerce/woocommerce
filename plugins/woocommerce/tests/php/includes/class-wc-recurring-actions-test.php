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
	 * @testdox The response-aware tracker callback forwards the scheduled attempt number.
	 */
	public function test_tracker_attempt_callback_forwards_attempt_number(): void {
		$http_calls = 0;
		update_option( 'woocommerce_allow_tracking', 'yes' );
		WC()->add_recurring_action_wrappers();
		add_filter(
			'woocommerce_tracker_data',
			function () {
				return array( 'snapshot' => 'current' );
			}
		);
		add_filter(
			'pre_http_request',
			function () use ( &$http_calls ) {
				++$http_calls;
				return array( 'response' => array( 'code' => 503 ) );
			}
		);

		do_action( 'woocommerce_tracker_send_event_attempt', 3 );

		$this->assertSame( 1, $http_calls );
		$this->assertTrue( as_has_scheduled_action( 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 4 ), 'woocommerce-tracker' ) );
	}

	/**
	 * @testdox The recurring tracker hook preserves its legacy first argument when appending the response-aware flag.
	 */
	public function test_tracker_wrapper_preserves_legacy_hook_argument(): void {
		$hook_args = null;
		update_option( 'woocommerce_allow_tracking', 'yes' );
		delete_option( 'woocommerce_tracker_last_send' );
		WC()->add_recurring_action_wrappers();
		add_action(
			'woocommerce_tracker_send_event',
			function () use ( &$hook_args ) {
				$hook_args = func_get_args();
			},
			20,
			2
		);

		do_action( 'woocommerce_tracker_send_event_wrapper' );

		$this->assertSame( array( '', true ), $hook_args );
	}

	/**
	 * @testdox The recurring wrapper requests response-aware delivery without posting inline.
	 */
	public function test_tracker_wrapper_enqueues_response_aware_delivery(): void {
		$http_calls = 0;
		update_option( 'woocommerce_allow_tracking', 'yes' );
		delete_option( 'woocommerce_tracker_last_send' );
		WC()->add_recurring_action_wrappers();
		add_filter(
			'pre_http_request',
			function () use ( &$http_calls ) {
				++$http_calls;
				return array( 'response' => array( 'code' => 204 ) );
			}
		);

		do_action( 'woocommerce_tracker_send_event_wrapper' );

		$this->assertSame( 0, $http_calls );
		$this->assertTrue( as_has_scheduled_action( 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 0 ), 'woocommerce-tracker' ) );
	}

	/**
	 * @testdox Opting out removes recurrence and all argument-bearing tracker attempts.
	 */
	public function test_tracking_opt_out_removes_recurrence_and_all_attempts(): void {
		update_option( 'woocommerce_allow_tracking', 'no' );
		update_option( 'woocommerce_allow_tracking', 'yes' );
		as_schedule_single_action( time() + 900, 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 1 ), 'woocommerce-tracker' );
		as_schedule_single_action( time() + 1800, 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 2 ), 'woocommerce-tracker' );

		update_option( 'woocommerce_allow_tracking', 'no' );

		$this->assertFalse( as_has_scheduled_action( 'woocommerce_tracker_send_event_wrapper' ) );
		$this->assertFalse( as_has_scheduled_action( 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 1 ), 'woocommerce-tracker' ) );
		$this->assertFalse( as_has_scheduled_action( 'woocommerce_tracker_send_event_attempt', array( 'attempt' => 2 ), 'woocommerce-tracker' ) );
	}

	/**
	 * Helper method to clear all scheduled actions.
	 */
	private function clear_scheduled_actions() {
		$actions = array(
			'woocommerce_tracker_send_event_wrapper',
			'woocommerce_tracker_send_event_attempt',
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
