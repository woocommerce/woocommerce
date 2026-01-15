<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;
use WC_Product;

/**
 * The controller for the stock events.
 */
class StockSyncController {

	/**
	 * The queue using product IDs as keys.
	 *
	 * @var array<int, bool>
	 */
	private array $queue = array();

	/**
	 * The eligibility service instance.
	 *
	 * @var EligibilityService
	 */
	private EligibilityService $eligibility_service;

	/**
	 * The job manager instance.
	 *
	 * @var JobManager
	 */
	private JobManager $job_manager;

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
	 * @param EligibilityService $eligibility_service The eligibility service instance.
	 * @param JobManager         $job_manager         The job manager instance.
	 */
	final public function init(
		EligibilityService $eligibility_service,
		JobManager $job_manager
	): void {
		$this->logger              = \wc_get_logger();
		$this->eligibility_service = $eligibility_service;
		$this->job_manager         = $job_manager;
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

			$this->store_admin_notice( $product->get_id() );

		} catch ( \Throwable $e ) {
			$this->logger->error(
				sprintf( 'StockSyncController: Failed to process product %d: %s', $product_id, $e->getMessage() ),
				array( 'source' => 'wc-customer-stock-notifications' )
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

		$product_ids = array_filter( array_keys( $this->queue ) );
		if ( empty( $product_ids ) ) {
			return;
		}

		foreach ( $product_ids as $product_id ) {
			$this->job_manager->schedule_initial_job_for_product( $product_id );
		}

		/**
		 * Allows for additional processing of the product IDs after they have been queued.
		 *
		 * @since 10.2.0
		 *
		 * @param array $product_ids The product IDs to process.
		 */
		do_action( 'woocommerce_customer_stock_notifications_product_sync', $product_ids );
		$this->queue = array();
	}

	/**
	 * Store the admin notice.
	 *
	 * @param int $product_id The product ID to sync.
	 * @return void
	 */
	private function store_admin_notice( $product_id ): void {
		if ( ! is_admin() || ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		/* translators: 1 = URL of the Back in Stock Notifications page */
		$notice_message = sprintf( __( 'Back-in-stock notifications for this product are now being processed. Subscribed customers will receive these emails over the next few minutes. You can monitor or manage individual subscriptions on the <a href="%s">Stock Notifications page</a>.', 'woocommerce' ), sprintf( admin_url( 'admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=%d&status=active_customer_stock_notifications&filter_action=Filter' ), $product_id ) );

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
