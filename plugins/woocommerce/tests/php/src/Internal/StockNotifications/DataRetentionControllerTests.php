<?php

declare( strict_types = 1 );
namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\DataRetentionController;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;

/**
 * PrivacyEraser tests.
 */
class DataRetentionControllerTests extends \WC_Unit_Test_Case {

	private $controller;

	public function setUp(): void {
		parent::setUp();
		$this->controller = new DataRetentionController();
	}
	public function tearDown(): void {
		parent::tearDown();
		$this->controller->clear_daily_task();
		delete_option( 'wc_customer_stock_notifications_delete_after_days' );
	}

	/**
	 * Test that the daily task is scheduled when the option is set,
	 * and unscheduled when the option is zero.
	 */
	public function test_schedule_or_unschedule_daily_task() {
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
		update_option(
			'wc_customer_stock_notifications_delete_after_days',
			30
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertEquals( 'daily', $schedule );
		update_option(
			'wc_customer_stock_notifications_delete_after_days',
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
			'wc_customer_stock_notifications_delete_after_days',
			'banana'
		);
		$schedule = wp_get_schedule( DataRetentionController::DAILY_TASK_HOOK );
		$this->assertFalse( $schedule );
		update_option(
			'wc_customer_stock_notifications_delete_after_days',
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
			'wc_customer_stock_notifications_delete_after_days',
			$days_until_deletion
		);

		$notification_pending = new Notification();
		$notification_pending->set_user_email( 'jon@doe.com' );
		$notification_pending->set_product_id( 1 );
		$notification_pending->set_status( NotificationStatus::PENDING );
		$notification_pending->save();

		$notification_expired = new Notification();
		$notification_expired->set_user_email( 'jon@doe.com' );
		$notification_expired->set_product_id( 1 );
		$notification_expired->set_status( NotificationStatus::PENDING );
		$expired_time = time() - ( ( $days_until_deletion + 1 ) * DAY_IN_SECONDS );
		$notification_expired->set_date_created( gmdate( 'Y-m-d H:i:s', $expired_time ) );
		$notification_expired->save();

		$notifications = NotificationQuery::get_notifications( array() );

		$this->assertCount( 2, $notifications );

		$this->controller->do_wc_customer_stock_notifications_daily();

		$notifications_after = NotificationQuery::get_notifications( array() );

		$this->assertCount( 1, $notifications_after );
	}
}
