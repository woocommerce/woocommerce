<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Tracker;

/**
 * Tracker tests.
 */
class TrackerTests extends \WC_Unit_Test_Case {

	/**
	 * The tracker instance under test.
	 *
	 * @var Tracker
	 */
	private $tracker;

	/**
	 * Set up test case.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		// The real WOOCOMMERCE_BIS_ALPHA_ENABLED PHP constant is define()d by tests/legacy/bootstrap.php
		// so clear_single_constant() can't unset it. Force the Jetpack Constants wrapper to false
		// explicitly so individual tests can opt in to the "disabled" state by leaving this default.
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', false );
		$this->tracker = new Tracker();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		remove_filter( 'woocommerce_tracker_data', array( $this->tracker, 'append_tracker_data' ), 20 );
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );
		delete_option( 'woocommerce_customer_stock_notifications_require_double_opt_in' );
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wc_stock_notifications" );
		parent::tearDown();
	}

	/**
	 * @testdox enabled reports "yes" when the BIS alpha constant is defined and truthy.
	 */
	public function test_enabled_yes_when_alpha_constant_is_true() {
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		$data = $this->tracker->get_tracker_data();

		$this->assertSame( 'yes', $data['enabled'] );
	}

	/**
	 * @testdox enabled reports "no" when the BIS alpha constant is not defined.
	 */
	public function test_enabled_no_when_alpha_constant_not_defined() {
		$data = $this->tracker->get_tracker_data();

		$this->assertSame( 'no', $data['enabled'] );
	}

	/**
	 * @testdox Counts reflect what the NotificationQuery repository reports, not a divergent raw-SQL path.
	 */
	public function test_counts_come_from_the_repository() {
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		$this->seed_notification( 'active-1@example.test', NotificationStatus::ACTIVE );
		$this->seed_notification( 'pending-1@example.test', NotificationStatus::PENDING );
		$this->seed_notification( 'sent-1@example.test', NotificationStatus::SENT );
		$this->seed_notification( 'sent-2@example.test', NotificationStatus::SENT );

		$data = $this->tracker->get_tracker_data();

		$expected_signups = NotificationQuery::count_notifications();
		$expected_sent    = NotificationQuery::count_notifications(
			array(
				'status' => NotificationStatus::SENT,
			)
		);

		$this->assertSame( $expected_signups, $data['signups_total'] );
		$this->assertSame( $expected_sent, $data['notifications_sent_total'] );
		$this->assertSame( 4, $data['signups_total'] );
		$this->assertSame( 2, $data['notifications_sent_total'] );
	}

	/**
	 * @testdox Block keys are always present regardless of feature state.
	 */
	public function test_shape_is_stable_when_feature_off() {
		$data = $this->tracker->get_tracker_data();

		$this->assertShapeKeys( $data );
		$this->assertSame( 'no', $data['enabled'] );
		$this->assertSame( 0, $data['signups_total'] );
		$this->assertSame( 0, $data['notifications_sent_total'] );
	}

	/**
	 * @testdox Block keys are always present when feature is on.
	 */
	public function test_shape_is_stable_when_feature_on() {
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );

		$data = $this->tracker->get_tracker_data();

		$this->assertShapeKeys( $data );
	}

	/**
	 * @testdox verification_required reflects the double opt-in option.
	 */
	public function test_verification_required_reflects_option() {
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );
		$data = $this->tracker->get_tracker_data();
		$this->assertSame( 'yes', $data['verification_required'] );

		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'no' );
		$data = $this->tracker->get_tracker_data();
		$this->assertSame( 'no', $data['verification_required'] );
	}

	/**
	 * @testdox allow_signups reflects the allow-signups option.
	 */
	public function test_allow_signups_reflects_option() {
		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );
		$data = $this->tracker->get_tracker_data();
		$this->assertSame( 'yes', $data['allow_signups'] );

		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'no' );
		$data = $this->tracker->get_tracker_data();
		$this->assertSame( 'no', $data['allow_signups'] );
	}

	/**
	 * @testdox Repeated applications of the filter callback produce the same result and leave sibling keys untouched.
	 */
	public function test_filter_is_idempotent_under_repeated_application() {
		Constants::set_constant( 'WOOCOMMERCE_BIS_ALPHA_ENABLED', true );
		$this->seed_notification( 'active@example.test', NotificationStatus::ACTIVE );

		$first  = $this->tracker->append_tracker_data( array( 'foo' => 'bar' ) );
		$second = $this->tracker->append_tracker_data( array( 'foo' => 'bar' ) );

		$this->assertArrayHasKey( Tracker::TRACKER_KEY, $first );
		$this->assertSame( 'bar', $first['foo'], 'Filter should leave pre-existing keys untouched.' );
		// Whole-array equality: catches regressions where a repeated filter
		// application leaks state into sibling keys, not just the BIS subtree.
		$this->assertSame( $first, $second );
	}

	/**
	 * @testdox append_tracker_data returns the input unchanged when it is not an array.
	 */
	public function test_append_is_no_op_when_input_not_array() {
		$result = $this->tracker->append_tracker_data( 'not-an-array' );
		$this->assertSame( 'not-an-array', $result );
	}

	/**
	 * Assert all expected top-level shape keys are present.
	 *
	 * @param array $data The BIS tracker block.
	 */
	private function assertShapeKeys( array $data ): void {
		$this->assertArrayHasKey( 'enabled', $data );
		$this->assertArrayHasKey( 'allow_signups', $data );
		$this->assertArrayHasKey( 'verification_required', $data );
		$this->assertArrayHasKey( 'signups_total', $data );
		$this->assertArrayHasKey( 'notifications_sent_total', $data );
	}

	/**
	 * Seed a notification row.
	 *
	 * @param string $email  The user email to attach.
	 * @param string $status The notification status.
	 */
	private function seed_notification( string $email, string $status ): void {
		$notification = new Notification();
		$notification->set_user_email( $email );
		$notification->set_product_id( 1 );
		$notification->set_status( $status );
		$notification->save();
	}
}
