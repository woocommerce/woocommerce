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
		// Delete overdue unverified notifications given a specified time threshold.
		$expiration_time_threshold = Functions::get_verification_expiration_time_threshold();
		$time_threshold            = Functions::get_delete_unverified_time_threshold();

		if ( 0 === $time_threshold ) {
			return;
		}

		$now                = time();
		$overdue_threshold  = $now - $expiration_time_threshold - $time_threshold;
		$overdue_query_args = array(
			'is_active'   => 'off',
			'is_verified' => 'no',
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => 'awaiting_verification',
					'value'   => 'yes',
					'compare' => '=',
				),
				array(
					'key'     => '_verification_created_at',
					'value'   => $overdue_threshold,
					'compare' => '<',
					'type'    => 'UNSIGNED',
				),
			),
			'order_by'    => array( 'id' => 'DESC' ),
		);

		$overdue_notifications = \WC_Data_Store::load( 'stock_notification' )->query( $overdue_query_args );

		foreach ( $overdue_notifications as $notification_id ) {
			$notification   = Factory::get_notification( $notification_id );
			if ( $notification->is_active() || ! $notification->is_expired() || (int) $notification->get_meta( '_verification_created_at' ) > $overdue_threshold ) {
				continue;
			}
			$notification->delete();
		}
	}
}
