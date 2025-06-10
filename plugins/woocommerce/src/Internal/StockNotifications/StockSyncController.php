<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Internal\StockNotifications\Config;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
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
	 * Logger instance.
	 *
	 * @var \WC_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = \wc_get_logger();

		// Event handlers.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'handle_product_stock_status_change' ), 100, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'handle_product_stock_status_change' ), 100, 3 );

		// Process the queue on shutdown.
		add_action( 'shutdown', array( $this, 'process_queue' ) );
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

			if ( ! in_array( (string) $stock_status, Config::get_eligible_stock_statuses(), true ) ) {
				return;
			}

			// Get product if not provided.
			if ( null === $product ) {
				$product = \wc_get_product( $product_id );
			}

			// Validate product exists and is supported.
			if ( ! $this->validate_product( $product ) ) {
				return;
			}

			$lookup_ids = array( $product_id );
			// If product is variable, check for the variations that inherit stock management from the parent.
			if ( $product->is_type( ProductType::VARIABLE ) ) {
				$children_ids = StockManagementHelper::get_managed_variations( $product );
				$lookup_ids   = array_merge( $lookup_ids, $children_ids );
			}

			if ( ! NotificationQuery::product_has_active_notifications( $lookup_ids ) ) {
				return;
			}

			// Add to queue.
			$this->queue[ $product_id ] = true;

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
			return;
		}

		$product_ids = array_keys( $this->queue );
		foreach ( $product_ids as $product_id ) {

			/**
			 * Action: woocommerce_stock_notifications_product_sync
			 *
			 * @since 0.0.0
			 *
			 * @param int $product_id The product ID.
			 */
			do_action( 'woocommerce_stock_notifications_product_sync', $product_id );
		}

		$this->queue = array();
	}

	/**
	 * Validate product to be synced.
	 *
	 * @param WC_Product|null $product The product object.
	 * @return bool True if product is valid for sync, false otherwise.
	 */
	public function validate_product( ?WC_Product $product ): bool {
		if ( null === $product || ! is_a( $product, 'WC_Product' ) ) {
			return false;
		}

		$valid = $product->is_type( Config::get_supported_product_types() );

		/**
		 * Filter: woocommerce_stock_notifications_product_sync_validate
		 *
		 * @since 0.0.0
		 *
		 * Allow plugins to modify product validation logic.
		 *
		 * @param bool       $valid   Whether the product is valid for sync.
		 * @param WC_Product $product The product object.
		 */
		return (bool) apply_filters( 'woocommerce_stock_notifications_product_sync_validate', $valid, $product );
	}
}
