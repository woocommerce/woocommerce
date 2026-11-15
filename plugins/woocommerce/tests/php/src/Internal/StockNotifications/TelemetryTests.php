<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Telemetry;

/**
 * Telemetry tests.
 */
class TelemetryTests extends \WC_Unit_Test_Case {

	use StockNotificationsFeatureTrait;

	/**
	 * Set up the test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->enable_stock_notifications_feature();
	}

	/**
	 * Tear down the test.
	 */
	public function tearDown(): void {
		delete_option( 'woocommerce_customer_stock_notifications_allow_signups' );
		delete_option( 'woocommerce_customer_stock_notifications_require_double_opt_in' );
		delete_option( 'woocommerce_customer_stock_notifications_require_account' );
		$this->restore_stock_notifications_feature_option();
		parent::tearDown();
	}

	/**
	 * The snapshot reports the feature's settings as the merchant configured them.
	 */
	public function test_snapshot_reports_settings() {
		update_option( 'woocommerce_customer_stock_notifications_allow_signups', 'yes' );
		update_option( 'woocommerce_customer_stock_notifications_require_double_opt_in', 'yes' );
		update_option( 'woocommerce_customer_stock_notifications_require_account', 'no' );

		$settings = Telemetry::collect_snapshot()['settings'];

		$this->assertEquals( 'yes', $settings['allow_signups'] );
		$this->assertEquals( 'yes', $settings['require_double_opt_in'] );
		$this->assertEquals( 'no', $settings['require_account'] );
		$this->assertArrayHasKey( 'unverified_deletions_days_threshold', $settings );
		$this->assertArrayHasKey( 'hide_out_of_stock_items', $settings );
	}

	/**
	 * The snapshot counts the notifications in total and per status.
	 */
	public function test_snapshot_reports_notification_counts() {
		$this->create_notification( NotificationStatus::ACTIVE );
		$this->create_notification( NotificationStatus::ACTIVE );
		$this->create_notification( NotificationStatus::SENT );
		$this->create_notification( NotificationStatus::CANCELLED );

		$counts = Telemetry::collect_snapshot()['notifications'];

		$this->assertEquals( 4, $counts['total'] );
		$this->assertEquals( 2, $counts[ NotificationStatus::ACTIVE ] );
		$this->assertEquals( 1, $counts[ NotificationStatus::SENT ] );
		$this->assertEquals( 1, $counts[ NotificationStatus::CANCELLED ] );
		$this->assertEquals( 0, $counts[ NotificationStatus::PENDING ] );
	}

	/**
	 * Create a saved notification in the given status.
	 *
	 * @param string $status The notification status.
	 * @return void
	 */
	private function create_notification( string $status ): void {
		$notification = new Notification();
		$notification->set_product_id( 1 );
		$notification->set_user_email( uniqid( 'shopper' ) . '@example.com' );
		$notification->set_status( $status );
		$notification->save();
	}
}
