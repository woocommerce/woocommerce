<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

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
}
