<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use WC_Product;
use Exception;

/**
 * The manager for async tasks.
 */
class JobManager {

	/**
	 * The job hook for sending stock notifications.
	 */
	public const AS_JOB_SEND_STOCK_NOTIFICATIONS = 'wc_send_stock_notifications_batch';

	/**
	 * The job group for stock notifications.
	 */
	public const AS_JOB_GROUP = 'wc-stock-notifications';

	/**
	 * The logger instance.
	 *
	 * @var \WC_Logger_Interface
	 */
	private $logger;

	/**
	 * The queue instance.
	 *
	 * @var \WC_Queue_Interface
	 */
	private $queue;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->logger = \wc_get_logger();
		$this->queue  = \WC()->queue();
	}

	/**
	 * Schedule a job.
	 *
	 * @param int $product_id The product ID.
	 * @return bool True if the job was scheduled, false otherwise.
	 */
	public function schedule_initial_job_for_product( int $product_id ): bool {
		$args = array( 'product_id' => $product_id );

		try {

			if ( $this->queue->get_next( self::AS_JOB_SEND_STOCK_NOTIFICATIONS, $args, self::AS_JOB_GROUP ) ) {
				return false;
			}

			/**
			 * Filter: woocommerce_customer_stock_notifications_first_batch_delay
			 *
			 * @since 10.2.0
			 *
			 * Schedule the first batch with a delay to prevent overwhelming the system.
			 *
			 * @param int   $delay       Delay time in seconds before first batch.
			 * @param int   $product_id  Product ID being scheduled.
			 */
			$delay = (int) apply_filters( 'woocommerce_customer_stock_notifications_first_batch_delay', MINUTE_IN_SECONDS, $product_id );
			$delay = max( 0, $delay );

			$action_id = $this->queue->schedule_single(
				time() + $delay,
				self::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				$args,
				self::AS_JOB_GROUP
			);

			if ( ! $action_id ) {
				return false;
			}

			$this->logger->info(
				sprintf( 'Scheduled stock notification for product %d', $product_id ),
				array( 'source' => 'wc-customer-stock-notifications' )
			);

			return true;
		} catch ( Exception $e ) {
			$this->logger->error(
				sprintf( 'Failed to schedule stock notification for product %d: %s', $product_id, $e->getMessage() ),
				array( 'source' => 'wc-customer-stock-notifications' )
			);

			return false;
		}
	}

	/**
	 * Schedule the next batch for a product.
	 *
	 * @param int $product_id The product ID.
	 * @return bool
	 */
	public function schedule_next_batch_for_product( int $product_id ): bool {

		$args = array( 'product_id' => $product_id );

		if ( $this->queue->get_next( self::AS_JOB_SEND_STOCK_NOTIFICATIONS, $args, self::AS_JOB_GROUP ) ) {
			return false;
		}

		/**
		 * Filter: woocommerce_customer_stock_notifications_next_batch_delay
		 *
		 * @since 10.2.0
		 *
		 * @param int   $delay       Delay time in seconds before next batch.
		 * @param int   $product_id  Product ID being scheduled.
		 */
		$delay = (int) apply_filters( 'woocommerce_customer_stock_notifications_next_batch_delay', 0, $product_id );
		$delay = max( 0, $delay );

		if ( 0 === $delay ) {
			$action_id = $this->queue->add(
				self::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				$args,
				self::AS_JOB_GROUP
			);
		} else {
			$action_id = $this->queue->schedule_single(
				time() + $delay,
				self::AS_JOB_SEND_STOCK_NOTIFICATIONS,
				$args,
				self::AS_JOB_GROUP
			);
		}

		return ! empty( $action_id );
	}
}
