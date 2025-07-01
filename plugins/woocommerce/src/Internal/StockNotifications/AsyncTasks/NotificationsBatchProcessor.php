<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks;

use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessorInterface;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Factory;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\NotificationEligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationCancellationSource;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;

/**
 * The batch processor for the stock notifications.
 *
 * This is the primary process for sending out the stock notifications.
 * It runs in background, in batches.
 * It is triggered by the StockSyncController when a product comes back in stock.
 */
class NotificationsBatchProcessor implements BatchProcessorInterface {

	/**
	 * Logger object to be used to log events.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The email manager to be used to send emails.
	 *
	 * @var EmailManager
	 */
	private $email_manager;

	/**
	 * The eligibility service to be used to check if a notification should be skipped.
	 *
	 * @var NotificationEligibilityService
	 */
	private $eligibility_service;

	/**
	 * The batch processing controller.
	 *
	 * @var BatchProcessingController
	 */
	private $batch_processing_controller;

	/**
	 * The product registry to be used to store product instances.
	 *
	 * @var array
	 */
	private $product_registry = array();

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param EmailManager                   $email_manager The email manager to be used to send emails.
	 * @param NotificationEligibilityService $eligibility_service The eligibility service.
	 * @param BatchProcessingController      $batch_processing_controller The batch processing controller.
	 */
	final public function init(
		EmailManager $email_manager,
		NotificationEligibilityService $eligibility_service,
		BatchProcessingController $batch_processing_controller
	) {
		$this->logger                      = \wc_get_logger();
		$this->email_manager               = $email_manager;
		$this->eligibility_service         = $eligibility_service;
		$this->batch_processing_controller = $batch_processing_controller;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_customer_stock_notifications_product_sync', array( $this, 'enqueue_processor' ), 10 );
	}

	/**
	 * Get the name of the batch processor.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Back-in-stock notifications processor';
	}

	/**
	 * Get the description of the batch processor.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return 'Sends back-in-stock notification emails in batches.';
	}

	/**
	 * Get the total number of pending notifications.
	 *
	 * @return int
	 */
	public function get_total_pending_count(): int {
		global $wpdb;

		$data_store           = wc_get_container()->get( StockNotificationsDataStore::class );
		$table                = $data_store->get_table_name();
		$statuses_placeholder = implode( ', ', array_fill( 0, count( Config::get_eligible_stock_statuses() ), '%s' ) );

		$sql = $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			"SELECT COUNT(DISTINCT n.id)
			FROM %i AS n
			LEFT JOIN %i AS t ON n.product_id = t.post_id
			LEFT JOIN %i AS ss ON n.product_id = ss.post_id
			WHERE n.status = %s
			AND t.meta_key = %s
			AND t.meta_value > 0
			AND (n.date_last_attempt_gmt < FROM_UNIXTIME(t.meta_value) OR n.date_last_attempt_gmt IS NULL)
			AND ss.meta_key = '_stock_status'
			AND ss.meta_value IN ($statuses_placeholder)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			array(
				$table,
				$wpdb->postmeta,
				$wpdb->postmeta,
				NotificationStatus::ACTIVE,
				StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY,
				...Config::get_eligible_stock_statuses(),
			)
		);
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get the next batch to process.
	 *
	 * @param int $size The size of the batch.
	 * @return array
	 */
	public function get_next_batch_to_process( int $size ): array {
		global $wpdb;

		$data_store           = wc_get_container()->get( StockNotificationsDataStore::class );
		$table                = $data_store->get_table_name();
		$statuses_placeholder = implode( ', ', array_fill( 0, count( Config::get_eligible_stock_statuses() ), '%s' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$sql = $wpdb->prepare(
			"SELECT n.id
			FROM %i AS n
			LEFT JOIN %i AS t ON n.product_id = t.post_id
			LEFT JOIN %i AS ss ON n.product_id = ss.post_id
			WHERE n.status = %s
			AND t.meta_key = %s
			AND t.meta_value > 0
			AND (n.date_last_attempt_gmt < FROM_UNIXTIME(t.meta_value) OR n.date_last_attempt_gmt IS NULL)
			AND ss.meta_key = '_stock_status'
			AND ss.meta_value IN ($statuses_placeholder)
			ORDER BY n.product_id ASC
			LIMIT %d",
			array(
				$table,
				$wpdb->postmeta,
				$wpdb->postmeta,
				NotificationStatus::ACTIVE,
				StockSyncController::LAST_SYNC_TIMESTAMP_META_KEY,
				...Config::get_eligible_stock_statuses(),
				$size,
			)
		);
		// phpcs:enable

		$data = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $data );
	}

	/**
	 * Process a batch of notifications.
	 *
	 * @param array $batch The batch of notifications to process.
	 */
	public function process_batch( array $batch ): void {
		if ( empty( $batch ) ) {
			return;
		}

		$batch_metrics = array(
			'product_ids'   => array(),
			'skipped_count' => 0,
			'sent_count'    => 0,
			'failed_count'  => 0,
			'total_count'   => count( $batch ),
		);

		foreach ( $batch as $notification_id ) {

			try {

				$notification = Factory::get_notification( $notification_id );
				if ( ! $notification instanceof Notification ) {
					$this->logger->error(
						sprintf( 'Failed to get notification ID: %d', $notification_id ),
						array( 'source' => 'wc-customer-stock-notifications' )
					);
					++$batch_metrics['failed_count'];
					continue;
				}

				// Set the date last attempt to the current time.
				$notification->set_date_last_attempt( time() );

				// Get the product instance.
				$product = $this->get_product_instance( $notification->get_product_id() );
				if ( ! $product instanceof \WC_Product ) {
					$this->logger->error(
						sprintf( 'Failed to get product ID: %d', $notification->get_product_id() ),
						array( 'source' => 'wc-customer-stock-notifications' )
					);
					++$batch_metrics['failed_count'];
					$notification->save();
					continue;
				}

				if ( ! in_array( $product->get_stock_status(), Config::get_eligible_stock_statuses(), true ) ) {
					++$batch_metrics['skipped_count'];
					$notification->save();
					continue;
				}

				if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
					++$batch_metrics['skipped_count'];
					$notification->save();
					continue;
				}

				if ( $this->eligibility_service->should_skip_notification( $notification, $product ) ) {
					++$batch_metrics['skipped_count'];
					$notification->save();
					continue;
				}

				$this->email_manager->send_stock_notification_email( $notification );
				$notification->set_status( NotificationStatus::SENT );
				$notification->set_date_notified( time() );
				$notification->save();
				++$batch_metrics['sent_count'];

			} catch ( \Exception $e ) {
				$this->logger->error(
					sprintf( 'Failed to process notification ID: %d, Error: %s', $notification_id, $e->getMessage() ),
					array( 'source' => 'wc-customer-stock-notifications' )
				);

				if ( $notification instanceof Notification ) {
					$notification->set_status( NotificationStatus::CANCELLED );
					$notification->set_date_cancelled( time() );
					$notification->set_cancellation_source( NotificationCancellationSource::SYSTEM );
					$notification->set_date_last_attempt( time() );
					$notification->save();
				}

				++$batch_metrics['failed_count'];
			}

			$batch_metrics['product_ids'][ $notification->get_product_id() ] = true;
		}

		$this->logger->info(
			sprintf( 'Batch processed: %d notifications, %d skipped, %d sent, %d failed. Product IDs: [ %s ]', $batch_metrics['total_count'], $batch_metrics['skipped_count'], $batch_metrics['sent_count'], $batch_metrics['failed_count'], implode( ', ', array_keys( $batch_metrics['product_ids'] ) ) ),
			array( 'source' => 'wc-customer-stock-notifications' )
		);
	}

	/**
	 * Get the default batch size.
	 *
	 * @return int
	 */
	public function get_default_batch_size(): int {

		/**
		 * Filter: woocommerce_customer_stock_notifications_batch_size
		 *
		 * @since 0.0.0
		 *
		 * @param int $batch_size The default batch size.
		 */
		return (int) apply_filters( 'woocommerce_customer_stock_notifications_batch_size', 50 );
	}

	/**
	 * Enqueue the batch processor.
	 */
	public function enqueue_processor(): void {
		$this->batch_processing_controller->enqueue_processor( self::class );
	}

	/**
	 * Get the product instance.
	 *
	 * @param int $product_id The product ID.
	 * @return WC_Product|null
	 */
	private function get_product_instance( int $product_id ): ?\WC_Product {
		if ( ! isset( $this->product_registry[ $product_id ] ) ) {
			$product = \wc_get_product( $product_id );
			if ( $product instanceof \WC_Product ) {
				$this->product_registry[ $product_id ] = $product;
			}
		}

		return $this->product_registry[ $product_id ] ?? null;
	}
}
