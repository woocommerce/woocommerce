<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\NotificationEligibilityService;
use WC_Product;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsBatchProcessor;

/**
 * The controller for the stock events.
 */
class StockSyncController {

	/**
	 * The meta key for the last sync timestamp.
	 *
	 * @var string
	 */
	public const LAST_SYNC_TIMESTAMP_META_KEY = '_wc_customer_stock_notifications_last_sync_timestamp';

	/**
	 * The queue using product IDs as keys.
	 *
	 * @var array<int, bool>
	 */
	private array $queue = array();

	/**
	 * The eligibility service instance.
	 *
	 * @var NotificationEligibilityService
	 */
	private NotificationEligibilityService $eligibility_service;

	/**
	 * Logger instance.
	 *
	 * @var \WC_Logger_Interface
	 */
	protected $logger;

	/**
	 * Init.
	 *
	 * @internal
	 *
	 * @param NotificationEligibilityService $eligibility_service The eligibility service instance.
	 */
	final public function init( NotificationEligibilityService $eligibility_service ): void {
		$this->logger              = \wc_get_logger();
		$this->eligibility_service = $eligibility_service;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Event handlers.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'handle_product_stock_status_change' ), 100, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'handle_product_stock_status_change' ), 100, 3 );

		// Process the queue on shutdown.
		add_action( 'shutdown', array( $this, 'process_queue' ) );

		// Output the admin notice.
		add_action( 'admin_notices', array( $this, 'output_admin_notice' ) );
	}

	/**
	 * Handle product stock status changes.
	 *
	 * @param int             $product_id   The product ID.
	 * @param string          $stock_status The new stock status.
	 * @param WC_Product|null $product      The product object (optional).
	 * @return void
	 */
	public function handle_product_stock_status_change( $product_id, $stock_status, $product = null ) {

		try {

			if ( ! $this->eligibility_service->is_stock_status_eligible( $stock_status ) ) {
				return;
			}

			if ( null === $product ) {
				$product = \wc_get_product( $product_id );
			}

			if ( ! is_a( $product, 'WC_Product' ) ) {
				return;
			}

			if ( ! $this->eligibility_service->is_product_eligible( $product ) ) {
				return;
			}

			if ( ! $this->eligibility_service->has_active_notifications( $product ) ) {
				return;
			}

			// Add to queue.
			$target_product_ids = $this->eligibility_service->get_target_product_ids( $product );
			foreach ( $target_product_ids as $target_product_id ) {
				$this->queue[ $target_product_id ] = true;
			}
		} catch ( \Throwable $e ) {
			$this->logger->error(
				sprintf( 'StockSyncController: Failed to process product %d: %s', $product_id, $e->getMessage() ),
				array( 'source' => 'wc-stock-notifications' )
			);
		}
	}

	/**
	 * Process the product IDs in the queue.
	 *
	 * Called on shutdown to schedule Action Scheduler jobs
	 * for each product ID in the queue.
	 *
	 * @return void
	 */
	public function process_queue(): void {
		if ( empty( $this->queue ) || ! is_array( $this->queue ) ) {
			$this->queue = array();
			return;
		}

		$product_ids = array_keys( $this->queue );
		if ( empty( $product_ids ) ) {
			return;
		}

		foreach ( $product_ids as $product_id ) {

			$product_id = absint( $product_id );
			if ( ! $product_id ) {
				continue;
			}

			$this->update_last_sync_timestamp( $product_id );
		}

		$this->store_admin_notice();

		/**
		 * Triggers the batch processor to process the product IDs.
		 *
		 * @since 0.0.0
		 *
		 * @param array $product_ids The product IDs to process.
		 */
		do_action( 'woocommerce_customer_stock_notifications_product_sync', $product_ids );
		$this->queue = array();
	}

	/**
	 * Update the last sync timestamp.
	 *
	 * @param int $product_id The product ID.
	 * @return void
	 */
	private function update_last_sync_timestamp( int $product_id ): void {

		// Update the last stock sync timestamp.
		// Hint: This is an internal meta field used to track the last time a product was synced.
		// We don't use the WC_Data interface here because we want to avoid the overhead of
		// loading the product object and invalidating the cache.
		update_post_meta( $product_id, self::LAST_SYNC_TIMESTAMP_META_KEY, time() );

		$this->logger->info(
			sprintf( 'StockSyncController: Scheduled back-in-stock notifications for product ID: [ %d ]', $product_id ),
			array( 'source' => 'wc-stock-notifications' )
		);

		// Add admin notice.
		$notice_message = __( 'Subscribed customers will receive back-in-stock notifications within the next few minutes.', 'woocommerce' );
		update_option( 'wc_customer_stock_notifications_product_sync_notice', $notice_message );
	}

	/**
	 * Store the admin notice.
	 *
	 * @return void
	 */
	private function store_admin_notice(): void {
		if ( ! is_admin() || ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		/* translators: 1 = URL of the Back in Stock Notifications page */
		$notice_message = sprintf( __( 'Back-in-stock notifications for this product are now being processed. Subscribed customers will receive these emails over the next few minutes. You can monitor or manage individual subscriptions on the <a href="%s">Stock Notifications page</a>.', 'woocommerce' ), admin_url( 'admin.php?page=customer-stock-notifications' ) );

		update_option( 'wc_customer_stock_notifications_product_sync_notice', $notice_message );
	}

	/**
	 * Add admin notices.
	 *
	 * @return void
	 */
	public function output_admin_notice(): void {
		if ( ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		$notice_message = get_option( 'wc_customer_stock_notifications_product_sync_notice' );
		if ( empty( $notice_message ) ) {
			return;
		}

		\wp_admin_notice(
			$notice_message,
			array(
				'type'        => 'info',
				'id'          => 'woocommerce_customer_stock_notifications_product_sync_notice',
				'dismissible' => false,
			)
		);

		delete_option( 'wc_customer_stock_notifications_product_sync_notice' );
	}
}
