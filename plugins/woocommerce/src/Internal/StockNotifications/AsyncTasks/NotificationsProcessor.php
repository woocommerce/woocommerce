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
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use WC_Product;
use Exception;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * The async processor for sending stock notifications in bulk.
 */
class NotificationsProcessor {

	/**
	 * The email manager.
	 *
	 * @var EmailManager
	 */
	private $email_manager;

	/**
	 * The logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/*
	|--------------------------------------------------------------------------
	| Constants.
	|--------------------------------------------------------------------------
	*/

	/**
	 * The batch size for processing notifications.
	 */
	protected const BATCH_SIZE = 50;

	/**
	 * The delay for the first batch.
	 */
	protected const FIRST_BATCH_DELAY = MINUTE_IN_SECONDS;

	/**
	 * The spam threshold for processing notifications.
	 */
	protected const SPAM_THRESHOLD = HOUR_IN_SECONDS;

	/**
	 * State option prefix.
	 */
	public const STATE_OPTION_PREFIX = 'wc_stock_notifications_cycle_state_';

	/**
	 * The job hook for sending stock notifications.
	 */
	public const AS_JOB_SEND_STOCK_NOTIFICATIONS = 'wc_send_stock_notifications_batch';

	/**
	 * AS job group.
	 */
	public const AS_JOB_GROUP = 'wc-stock-notifications';

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 *
	 * @param EmailManager $email_manager The email manager.
	 * @return void
	 */
	final public function init( EmailManager $email_manager ): void {
		$this->email_manager = $email_manager;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = \wc_get_logger();

		add_action( 'woocommerce_customer_stock_notifications_product_sync', array( $this, 'schedule' ) );
		add_action( self::AS_JOB_SEND_STOCK_NOTIFICATIONS, array( $this, 'process_batch' ) );
	}

	/**
	 * Schedule a notification job for a specific product.
	 *
	 * @param int $product_id  The product ID.
	 * @return bool True if job was scheduled, false otherwise.
	 */
	public function schedule( $product_id ) {
		$args = array( 'product_id' => $product_id );

		try {

			if ( WC()->queue()->get_next( self::AS_JOB_SEND_STOCK_NOTIFICATIONS, $args, self::AS_JOB_GROUP ) ) {
				return false;
			}

			/**
			 * Filter: woocommerce_stock_notifications_first_batch_delay
			 *
			 * @since 0.0.0
			 *
			 * Schedule the first batch with a delay to prevent overwhelming the system.
			 *
			 * @param int   $delay       Delay time in seconds before first batch.
			 * @param int   $product_id  Product ID being scheduled.
			 */
			$delay = (int) apply_filters( 'woocommerce_stock_notifications_first_batch_delay', self::FIRST_BATCH_DELAY, $product_id );
			$delay = max( 0, $delay );

			$action_id = WC()->queue()->schedule_single(
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
				array( 'source' => 'wc-stock-notifications' )
			);

			return true;
		} catch ( Exception $e ) {
			$this->logger->error(
				sprintf( 'Failed to schedule stock notification for product %d: %s', $product_id, $e->getMessage() ),
				array( 'source' => 'wc-stock-notifications' )
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
	private function schedule_next_batch( int $product_id ): bool {
		$scheduled = WC()->queue()->add(
			self::AS_JOB_SEND_STOCK_NOTIFICATIONS,
			array( 'product_id' => $product_id ),
			self::AS_JOB_GROUP
		);

		return ! empty( $scheduled );
	}

	/**
	 * Get the batch size for processing notifications.
	 *
	 * @return int
	 */
	private function get_batch_size(): int {
		/**
		 * Filter: woocommerce_stock_notifications_batch_size
		 *
		 * @since 0.0.0
		 *
		 * Allow customization of batch size for processing notifications.
		 *
		 * @param int $batch_size Default batch size.
		 * @return int
		 */
		return (int) apply_filters( 'woocommerce_stock_notifications_batch_size', self::BATCH_SIZE );
	}

	/**
	 * Parse the product ID from the arguments.
	 *
	 * @param int $product_id The product ID.
	 * @return int
	 * @throws \Exception If the product is not found.
	 */
	private function parse_args( $product_id ): int {
		if ( empty( $product_id ) || ! is_numeric( $product_id ) ) {
			throw new \Exception( 'Invalid arguments.' );
		}

		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			throw new \Exception( 'Product ID is required.' );
		}

		return $product_id;
	}

	/**
	 * Parse the product.
	 *
	 * @param int $product_id The product ID.
	 * @return \WC_Product
	 * @throws \Exception If the product is not valid for notifications.
	 */
	private function parse_product( int $product_id ): WC_Product {

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			throw new \Exception( sprintf( 'Product %d not found.', absint( $product_id ) ) );
		}

		$valid_types    = Config::get_supported_product_types();
		$has_valid_type = in_array( $product->get_type(), $valid_types, true );

		if ( ! $has_valid_type ) {
			throw new \Exception( sprintf( 'Product %d is not a valid type for notifications.', $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$valid_statuses   = Config::get_eligible_stock_statuses();
		$has_valid_status = in_array( $product->get_stock_status(), $valid_statuses, true );

		if ( ! $has_valid_status ) {
			throw new \Exception( sprintf( 'Product %d is not valid for notifications (i.e. not in stock).', $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $product;
	}

	/**
	 * Parse the cycle state for a product.
	 *
	 * @param int $product_id The product ID.
	 * @return array
	 * @throws \Exception If the cycle state is invalid.
	 */
	private function parse_cycle_state( int $product_id ): array {

		if ( $product_id <= 0 ) {
			throw new \Exception( 'Product ID is required.' );
		}

		$default_state = array(
			'cycle_start_time' => time(),
			'product_ids'      => array( $product_id ),
			'total_count'      => 0,
			'skipped_count'    => 0,
			'sent_count'       => 0,
			'failed_count'     => 0,
			'duration'         => 0,
		);

		$option_name = self::STATE_OPTION_PREFIX . $product_id;
		$cycle_state = get_option( $option_name, false );
		if ( ! is_array( $cycle_state ) ) {
			return $default_state;
		}

		if ( array_diff_key( $default_state, $cycle_state ) || empty( $cycle_state['cycle_start_time'] ) ) {
			throw new \Exception( 'Invalid cycle state.' );
		}

		$cycle_state = wp_parse_args( $cycle_state, $default_state );

		return $cycle_state;
	}

	/**
	 * Complete the cycle.
	 *
	 * @param int|string $product_id The product ID.
	 * @param array      $cycle_state The cycle state.
	 * @return void
	 */
	private function complete_cycle( int $product_id, array $cycle_state ): void {

		$cycle_state['duration'] = time() - $cycle_state['cycle_start_time'];
		$this->logger->info(
			sprintf( 'Completed cycle for product %d. Sent: %d, Skipped: %d, Failed: %d, Duration: %d seconds. Total notifications processed: %d', $product_id, $cycle_state['sent_count'], $cycle_state['skipped_count'], $cycle_state['failed_count'], $cycle_state['duration'], $cycle_state['total_count'] ),
			array( 'source' => 'wc-stock-notifications' )
		);

		if ( $product_id > 0 ) {
			delete_option( self::STATE_OPTION_PREFIX . $product_id );
		}
	}

	/**
	 * Save the cycle state.
	 *
	 * @param int   $product_id The product ID.
	 * @param array $cycle_state The cycle state.
	 * @return void
	 */
	private function save_cycle_state( int $product_id, array $cycle_state ): void {
		if ( $product_id <= 0 ) {
			return;
		}

		update_option( self::STATE_OPTION_PREFIX . $product_id, $cycle_state, false );
	}

	/**
	 * Process a batch of notifications.
	 *
	 * @param int $product_id The product ID from AS job args.
	 * @return void
	 */
	public function process_batch( $product_id ) {

		// Sanity checks.
		try {
			$product_id  = $this->parse_args( $product_id );
			$cycle_state = $this->parse_cycle_state( $product_id );
			$product     = $this->parse_product( $product_id );
		} catch ( \Throwable $e ) {
			$product_id = (int) $product_id ?? 0;
			$this->logger->error(
				sprintf( 'Background process for product %s terminated. Reason: %s', $product_id, $e->getMessage() ),
				array(
					'source'     => 'wc-stock-notifications',
					'product_id' => $product_id,
					'exception'  => get_class( $e ),
				)
			);

			// Clean up the cycle state.
			if ( isset( $cycle_state ) && is_array( $cycle_state ) ) {
				$this->complete_cycle( $product_id, $cycle_state );
			}

			return;
		}

		// For variable products, check if we're only processing the parent product.
		// If so, add any variations that inherit stock management from the parent to the cycle state.
		if ( $product->is_type( ProductType::VARIABLE ) && 1 === count( $cycle_state['product_ids'] ) ) {
			$cycle_state['product_ids'] = array_merge( $cycle_state['product_ids'], StockManagementHelper::get_managed_variations( $product ) );
		}

		// Get notifications.
		$notifications = NotificationQuery::get_notifications(
			array(
				'status'             => NotificationStatus::ACTIVE,
				'product_id'         => $cycle_state['product_ids'],
				'last_attempt_limit' => (int) $cycle_state['cycle_start_time'],
				'return'             => 'ids',
				'limit'              => $this->get_batch_size(),
				'orderby'            => 'id',
				'order'              => 'ASC',
			)
		);

		if ( empty( $notifications ) ) {
			$this->complete_cycle( $product_id, $cycle_state );
			return;
		}

		foreach ( $notifications as $notification_id ) {
			$notification = Factory::get_notification( $notification_id );
			if ( ! $notification instanceof Notification ) {
				$this->logger->error(
					sprintf( 'Failed to get notification ID: %d', $notification_id ),
					array( 'source' => 'wc-stock-notifications' )
				);
				continue;
			}

			$notification->set_date_last_attempt( time() );
			++$cycle_state['total_count'];

			if ( $this->should_skip_notification( $notification ) ) {
				++$cycle_state['skipped_count'];
				$notification->save();
				continue;
			}

			$is_sent = true;
			try {
				$this->email_manager->send_stock_notification_email( $notification );
			} catch ( \Throwable $e ) {
				$is_sent = false;
			}

			if ( $is_sent ) {
				$notification->set_date_notified( time() );
				$notification->set_status( NotificationStatus::SENT );
				++$cycle_state['sent_count'];
			} else {
				$notification->set_status( NotificationStatus::CANCELLED );
				$notification->set_cancellation_source( NotificationCancellationSource::SYSTEM );
				++$cycle_state['failed_count'];
			}

			// Always save the notification to reflect last attempt time.
			$notification->save();
		}

		if ( count( $notifications ) === $this->get_batch_size() ) {
			$this->save_cycle_state( $product_id, $cycle_state );
			$this->schedule_next_batch( $product_id );
			return;
		}

		$this->complete_cycle( $product_id, $cycle_state );
	}

	/**
	 * Check if a notification should be skipped.
	 *
	 * @param Notification $notification The notification object.
	 * @return bool
	 */
	private function should_skip_notification( Notification $notification ): bool {

		$is_throttled         = $this->is_notification_throttled( $notification );
		$is_product_published = in_array( $notification->get_product()->get_status(), Config::get_supported_product_statuses(), true );
		$should_skip          = $is_throttled || ! $is_product_published;

		// Bypass for privileged users.
		if ( $should_skip ) {
			$user_id = $notification->get_user_id();
			if ( $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( $user && ( user_can( $user, 'manage_woocommerce' ) || user_can( $user, 'manage_options' ) ) ) {
					$should_skip = false;
				}
			}
		}

		/**
		 * Filter: woocommerce_stock_notification_should_skip_sending
		 *
		 * @since 0.0.0
		 *
		 * Prevent or manage sending a specific notification.
		 *
		 * @param bool $should_skip Whether to skip sending.
		 * @param int  $notification_id The notification ID.
		 * @return bool
		 */
		return (bool) apply_filters( 'woocommerce_stock_notification_should_skip_sending', $should_skip, $notification->get_id() );
	}

	/**
	 * Check if notification is throttled.
	 *
	 * @param Notification $notification The notification object.
	 * @return bool
	 */
	private function is_notification_throttled( Notification $notification ): bool {

		/**
		 * Filter: woocommerce_stock_notifications_throttle_threshold
		 *
		 * @since 0.0.0
		 *
		 * @param int $threshold Throttle time in seconds should pass from the last notification delivery time.
		 */
		$threshold = (int) apply_filters( 'woocommerce_stock_notifications_throttle_threshold', self::SPAM_THRESHOLD );
		if ( $threshold <= 0 ) {
			return false;
		}

		$last_notified = $notification->get_date_notified();
		$is_throttled  = $last_notified instanceof \WC_DateTime && $last_notified->getTimestamp() > ( time() - $threshold );

		return $is_throttled;
	}
}
