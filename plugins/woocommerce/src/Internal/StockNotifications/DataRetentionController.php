<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;

/**
 * Controller for managing data retention of customer stock notifications.
 *
 * This controller handles the scheduling and execution of tasks related to
 * deleting overdue notifications based on a configured time threshold.
 */
class DataRetentionController {
	public const DAILY_TASK_HOOK = 'customer_stock_notifications_daily';

	/**
	 * Constructor to set up hooks for managing data retention tasks.
	 */
	public function __construct() {
		add_action( self::DAILY_TASK_HOOK, array( $this, 'do_wc_customer_stock_notifications_daily' ) );
		add_action( 'update_option_woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', array( $this, 'schedule_or_unschedule_daily_task' ), 10, 2 );
		add_action( 'add_option_woocommerce_customer_stock_notifications_unverified_deletions_days_threshold', array( $this, 'schedule_or_unschedule_daily_task' ), 10, 2 );
		register_deactivation_hook( WC_PLUGIN_FILE, array( $this, 'clear_daily_task' ) );
	}

	/**
	 * Tasks to run when WooCommerce is installed or updated.
	 *
	 * @return void
	 */
	public function on_woo_install_or_update(): void {
		$this->schedule_or_unschedule_daily_task( null, Config::get_unverified_deletion_days_threshold() );
	}

	/**
	 * Responds to changes in the option for deleting unverified notifications.
	 * If the new value is numeric and greater than zero, it schedules a daily task.
	 * If the new value is not numeric or is empty, it clears the scheduled tasks.
	 *
	 * @param mixed $unused The old option value or option name (not used in this function).
	 * @param mixed $new_option_value The new value of the option.
	 * @return void
	 */
	public function schedule_or_unschedule_daily_task( $unused, $new_option_value ): void {
		if ( ! is_numeric( $new_option_value ) || empty( $new_option_value ) ) {
			$this->clear_daily_task();
			return;
		}

		if ( ! wp_next_scheduled( self::DAILY_TASK_HOOK ) ) {
			wp_schedule_event( time() + 10, 'daily', self::DAILY_TASK_HOOK );
		}
	}

	/**
	 * Unschedule the daily task when the plugin is deactivated, or the option is set to zero.
	 */
	public function clear_daily_task() {
		wp_clear_scheduled_hook( self::DAILY_TASK_HOOK );
	}

	/**
	 * Deletes overdue notifications based on the configured time threshold.
	 * It retrieves notifications that are pending and past the threshold,
	 * then deletes them.
	 *
	 * @return void
	 */
	public function do_wc_customer_stock_notifications_daily() {
		$time_threshold = Config::get_unverified_deletion_days_threshold();

		if ( 0 === $time_threshold ) {
			return;
		}
		$overdue_threshold = time() - $time_threshold * DAY_IN_SECONDS;

		$overdue_notifications = NotificationQuery::get_notifications(
			array(
				'status'   => NotificationStatus::PENDING,
				'end_date' => gmdate( 'Y-m-d H:i:s', $overdue_threshold ),
			)
		);

		foreach ( $overdue_notifications as $notification_id ) {
			$notification = Factory::get_notification( $notification_id );
			$notification->delete();
		}
	}
}
