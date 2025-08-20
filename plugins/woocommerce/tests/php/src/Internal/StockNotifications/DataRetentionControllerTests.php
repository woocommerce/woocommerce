<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\DataRetentionController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

/**
 * DataRetentionControllerTests tests.
 */
class DataRetentionControllerTests extends \WC_Unit_Test_Case {

	/**
	 * The controller instance.
	 *
	 * @var DataRetentionController
	 */
	private $controller;

	/**
	 * Set up test case
	 */
	public function setUp(): void {
		parent::setUp();
		$this->controller = new DataRetentionController();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		parent::tearDown();
		$this->controller->clear_daily_task();
		delete_option( 'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold' );
	}

	/**
	 * Test that the daily task is scheduled when the option is set,
	 * and unscheduled when the option is zero.
	 */
	public function test_schedule_or_unschedule_daily_task() {
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
		update_option(
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			30
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertEquals( 'daily', $schedule );
		update_option(
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			0
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
	}

	/**
	 * Test that the daily task is not scheduled when the option is somehow set to a bogus value.
	 */
	public function test_schedule_or_unschedule_daily_task_bogus_data() {
		update_option(
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			'banana'
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
		update_option(
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			false
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
	}

	/**
	 * Test that the daily task deletes expired notifications.
	 *
	 * This test creates two notifications: one that is pending and one that is expired.
	 * After running the daily task, it checks that only the expired notification is deleted.
	 */
	public function test_dail_task_only_deletes_expired_notifications() {
		$days_until_deletion = 5;
		update_option(
			'woocommerce_customer_stock_notifications_unverified_deletions_days_threshold',
			$days_until_deletion
		);

		$notification_pending = new Notification();
		$notification_pending->set_user_email( 'pending@test.com' );
		$notification_pending->set_product_id( 1 );
		$notification_pending->set_status( NotificationStatus::PENDING );
		$pending_id = $notification_pending->save();

		$notification_expired = new Notification();
		$notification_expired->set_user_email( 'expired@test.com' );
		$notification_expired->set_product_id( 1 );
		$notification_expired->set_status( NotificationStatus::PENDING );
		$expired_time = time() - ( ( $days_until_deletion + 1 ) * DAY_IN_SECONDS );
		$notification_expired->set_date_created( gmdate( 'Y-m-d H:i:s', $expired_time ) );
		$expired_id = $notification_expired->save();

		$notifications = NotificationQuery::get_notifications( array() );

		$this->assertCount( 2, $notifications );

		$this->controller->do_wc_customer_stock_notifications_daily();

		$notifications_after = NotificationQuery::get_notifications( array() );

		$this->assertCount( 1, $notifications_after );

		$pending_notification_after = new Notification( $pending_id );
		$this->assertEquals( 'pending@test.com', $pending_notification_after->get_user_email() );

		$this->expectException( \Exception::class );
		new Notification( $expired_id );
	}
}
