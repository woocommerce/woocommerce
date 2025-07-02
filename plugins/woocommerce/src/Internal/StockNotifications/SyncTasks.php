<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Sync stock Queue Controller Class.
 */
class SyncTasks {
	/**
	 * Schedule async tasks for stock notifications.
	 */
	public static function schedule_async_tasks() {
		if ( ! wp_next_scheduled( 'customer_stock_notifications_daily' ) ) {
			wp_schedule_event( time(), 'daily', 'customer_stock_notifications_daily' );
		}
	}

	/*
	 * Clear the scheduled tasks for stock notifications.
	 */
	public static function clear_async_tasks() {
		wp_clear_scheduled_hook( 'customer_stock_notifications_daily' );
	}

	public static function do_wc_customer_stock_notifications_daily() {
		$time_threshold            = Config::get_delete_unverified_time_threshold();

		if ( 0 === $time_threshold ) {
			return;
		}
		$overdue_threshold  = time() - $time_threshold;

		$overdue_notifications = NotificationQuery::get_notifications(
			array(
				'status' => NotificationStatus::PENDING,
				'end_date' => gmdate( 'Y-m-d H:i:s', $overdue_threshold ),
			)
		);

		foreach ( $overdue_notifications as $notification_id ) {
			$notification   = Factory::get_notification( $notification_id );
			$notification->delete();
		}
	}
}
