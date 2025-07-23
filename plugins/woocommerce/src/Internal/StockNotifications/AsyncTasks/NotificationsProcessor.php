<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\CycleStateService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\NotificationQuery;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use WC_Product;

/**
 * The async processor for sending stock notifications in bulk.
 */
class NotificationsProcessor {

	/**
	 * The email manager.
	 *
	 * @var EmailManager
	 */
	private EmailManager $email_manager;

	/**
	 * The logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The eligibility service.
	 *
	 * @var EligibilityService
	 */
	private EligibilityService $eligibility_service;

	/**
	 * The job manager.
	 *
	 * @var JobManager
	 */
	private JobManager $job_manager;

	/**
	 * The cycle state service.
	 *
	 * @var CycleStateService
	 */
	private CycleStateService $cycle_state_service;

	/**
	 * The batch size for processing notifications.
	 */
	protected const BATCH_SIZE = 50;

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 *
	 * @param EligibilityService $eligibility_service The eligibility service.
	 * @param JobManager         $job_manager The job manager.
	 * @param CycleStateService  $cycle_state_service The cycle state service.
	 * @param EmailManager       $email_manager The email manager.
	 * @return void
	 */
	final public function init(
		EligibilityService $eligibility_service,
		JobManager $job_manager,
		CycleStateService $cycle_state_service,
		EmailManager $email_manager
	): void {
		$this->eligibility_service = $eligibility_service;
		$this->job_manager         = $job_manager;
		$this->cycle_state_service = $cycle_state_service;
		$this->email_manager       = $email_manager;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = \wc_get_logger();
		add_action( JobManager::AS_JOB_SEND_STOCK_NOTIFICATIONS, array( $this, 'process_batch' ) );
	}

	/**
	 * Get the batch size for processing notifications.
	 *
	 * @return int
	 */
	private function get_batch_size(): int {
		/**
		 * Filter: woocommerce_customer_stock_notifications_batch_size
		 *
		 * @since 10.2.0
		 *
		 * Allow customization of batch size for processing notifications.
		 *
		 * @param int $batch_size Default batch size.
		 * @return int
		 */
		return (int) apply_filters( 'woocommerce_customer_stock_notifications_batch_size', self::BATCH_SIZE );
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

		if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
			throw new \Exception( sprintf( 'Product %d is not eligible for notifications.', $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( ! $this->eligibility_service->is_stock_status_eligible( $product->get_stock_status() ) ) {
			throw new \Exception( sprintf( 'Product %d stock status is not eligible for notifications (i.e. not in stock).', $product->get_id() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $product;
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
			$cycle_state = $this->cycle_state_service->get_or_initialize_cycle_state( $product_id );
			$product     = $this->parse_product( $product_id );
		} catch ( \Throwable $e ) {
			$product_id = (int) $product_id ?? 0;
			$this->logger->error(
				sprintf( 'Background process for product %s terminated. Reason: %s', $product_id, $e->getMessage() ),
				array(
					'source'     => 'wc-customer-stock-notifications',
					'product_id' => $product_id,
					'exception'  => get_class( $e ),
				)
			);

			// Clean up the cycle state.
			if ( isset( $cycle_state ) ) {
				$this->cycle_state_service->complete_cycle( $product_id, $cycle_state );
			}

			return;
		}

		$cycle_state['product_ids'] = $this->eligibility_service->get_target_product_ids( $product );

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
			$this->cycle_state_service->complete_cycle( $product_id, $cycle_state );
			return;
		}

		foreach ( $notifications as $notification_id ) {
			$notification = Factory::get_notification( $notification_id );
			if ( ! $notification instanceof Notification ) {
				$this->logger->error(
					sprintf( 'Failed to get notification ID: %d', $notification_id ),
					array( 'source' => 'wc-customer-stock-notifications' )
				);
				continue;
			}

			$notification->set_date_last_attempt( time() );
			++$cycle_state['total_count'];

			if ( $this->eligibility_service->should_skip_notification( $notification, $product ) ) {
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
			$this->cycle_state_service->save_cycle_state( $product_id, $cycle_state );
			$this->job_manager->schedule_next_batch_for_product( $product_id );
			return;
		}

		$this->cycle_state_service->complete_cycle( $product_id, $cycle_state );
	}
}
