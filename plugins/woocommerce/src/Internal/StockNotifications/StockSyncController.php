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
	 * Product IDs restocked during this request, using product IDs as keys. Named in the admin notice.
	 *
	 * @var array<int, bool>
	 */
	private array $notice_product_ids = array();

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

			$this->notice_product_ids[ $product->get_id() ] = true;

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
		$this->store_admin_notice();

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
	 * Store the admin notice for the current user.
	 *
	 * Runs once per request on shutdown, so a request that restocks several products (for example, a CSV
	 * import batch) writes once and lists all of them. Pending IDs from earlier requests are kept, so the
	 * notice covers every product restocked since the user last loaded an admin page.
	 *
	 * @return void
	 */
	private function store_admin_notice(): void {
		$product_ids              = array_keys( $this->notice_product_ids );
		$this->notice_product_ids = array();

		if ( empty( $product_ids ) || ! is_admin() || ! get_current_user_id() ) {
			return;
		}

		$transient_key = $this->get_notice_transient_key();
		$pending_ids   = array_map( 'absint', (array) get_transient( $transient_key ) );

		set_transient( $transient_key, array_values( array_unique( array_merge( $pending_ids, $product_ids ) ) ), 5 * MINUTE_IN_SECONDS );
	}

	/**
	 * Output the admin notice for the current user, naming the restocked products.
	 *
	 * @return void
	 */
	public function output_admin_notice(): void {
		if ( ! get_current_user_id() || ! function_exists( 'wp_admin_notice' ) ) {
			return;
		}

		$transient_key = $this->get_notice_transient_key();
		$product_ids   = array_filter( array_map( 'absint', (array) get_transient( $transient_key ) ) );
		if ( empty( $product_ids ) ) {
			return;
		}

		delete_transient( $transient_key );

		$product_names = array();
		foreach ( array_slice( $product_ids, 0, 3 ) as $product_id ) {
			$product = \wc_get_product( $product_id );
			if ( $product ) {
				$product_names[] = esc_html( $product->get_name() );
			}
		}

		$remaining = count( $product_ids ) - count( $product_names );
		if ( $remaining > 0 ) {
			/* translators: %d: number of additional restocked products */
			$product_names[] = sprintf( _n( '%d more product', '%d more products', $remaining, 'woocommerce' ), $remaining );
		}

		$list_url = 1 === count( $product_ids )
			? sprintf( admin_url( 'admin.php?page=wc-customer-stock-notifications&customer_stock_notifications_product_filter=%d&status=active_customer_stock_notifications&filter_action=Filter' ), reset( $product_ids ) )
			: admin_url( 'admin.php?page=wc-customer-stock-notifications&status=active_customer_stock_notifications' );

		/* translators: 1: restocked product names, 2: URL of the Back in Stock Notifications page */
		$notice_message = sprintf( __( 'Back-in-stock notifications for %1$s are now being processed. Subscribed customers will receive these emails over the next few minutes. You can monitor or manage individual subscriptions on the <a href="%2$s">Stock Notifications page</a>.', 'woocommerce' ), wp_sprintf( '%l', $product_names ), esc_url( $list_url ) );

		\wp_admin_notice(
			$notice_message,
			array(
				'type'        => 'info',
				'id'          => 'woocommerce_customer_stock_notifications_product_sync_notice',
				'dismissible' => false,
			)
		);
	}

	/**
	 * Get the transient key holding the current user's pending notice product IDs.
	 *
	 * @return string
	 */
	private function get_notice_transient_key(): string {
		return 'wc_customer_stock_notifications_product_sync_notice_' . get_current_user_id();
	}
}
