<?php
/**
 * StockThresholdResync class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal;

use Automattic\WooCommerce\Enums\ProductStockStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Recomputes the stock status of stock-managed products when the site-wide
 * out-of-stock threshold (`woocommerce_notify_no_stock_amount`) changes.
 *
 * Without this, raising the threshold above a product's current stock quantity
 * would leave the product flagged as in stock until the next product save or
 * order, allowing it to remain purchasable below the new threshold. See
 * https://github.com/woocommerce/woocommerce/issues/55422.
 *
 * The recompute work is scheduled through Action Scheduler in small batches so
 * that large catalogs are processed asynchronously without blocking the
 * settings save request.
 *
 * @since 10.9.0
 */
class StockThresholdResync {

	/**
	 * Action Scheduler hook used to process a single batch of affected products.
	 *
	 * @var string
	 */
	public const RESYNC_BATCH_HOOK = 'woocommerce_resync_stock_status_after_threshold_change';

	/**
	 * Action Scheduler group used for the scheduled batches.
	 *
	 * @var string
	 */
	private const RESYNC_GROUP = 'woocommerce-stock-threshold';

	/**
	 * Maximum number of product IDs handled in a single scheduled batch.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_action( 'add_option_woocommerce_notify_no_stock_amount', array( $this, 'handle_option_added' ), 10, 2 );
		add_action( 'update_option_woocommerce_notify_no_stock_amount', array( $this, 'handle_option_updated' ), 10, 2 );
		add_action( self::RESYNC_BATCH_HOOK, array( $this, 'process_batch' ), 10, 1 );
	}

	/**
	 * Handle the option being added for the first time.
	 *
	 * @param string $option    Option name.
	 * @param mixed  $new_value The new option value.
	 */
	public function handle_option_added( $option, $new_value ): void {
		$this->maybe_schedule_resync( 0, (int) $new_value );
	}

	/**
	 * Handle the option being updated.
	 *
	 * @param mixed $old_value The previous option value.
	 * @param mixed $new_value The new option value.
	 */
	public function handle_option_updated( $old_value, $new_value ): void {
		$this->maybe_schedule_resync( (int) $old_value, (int) $new_value );
	}

	/**
	 * Schedule one or more Action Scheduler batches to re-save the products
	 * whose stock status would change under the new threshold.
	 *
	 * @param int $old_threshold The previous threshold value.
	 * @param int $new_threshold The new threshold value.
	 */
	private function maybe_schedule_resync( int $old_threshold, int $new_threshold ): void {
		if ( $old_threshold === $new_threshold ) {
			return;
		}

		// Stock management must be enabled site-wide for the threshold to take effect.
		if ( 'yes' !== get_option( 'woocommerce_manage_stock' ) ) {
			return;
		}

		$product_ids = $this->get_affected_product_ids( $new_threshold );
		if ( empty( $product_ids ) ) {
			return;
		}

		$batches = array_chunk( $product_ids, self::BATCH_SIZE );
		$delay   = 1;
		foreach ( $batches as $batch ) {
			WC()->call_function(
				'as_schedule_single_action',
				WC()->call_function( 'time' ) + $delay,
				self::RESYNC_BATCH_HOOK,
				array( array_values( array_map( 'intval', $batch ) ) ),
				self::RESYNC_GROUP
			);
			// Stagger batches slightly so they don't all execute on the same queue tick.
			++$delay;
		}
	}

	/**
	 * Build the list of product IDs whose recorded stock status disagrees with
	 * the new threshold and therefore needs to be re-evaluated.
	 *
	 * Two transitions are possible:
	 *   - Currently `instock` with quantity at or below the new threshold (must become out of stock,
	 *     or `onbackorder` if backorders are allowed).
	 *   - Currently `outofstock` with quantity above the new threshold (may become in stock when the
	 *     previous status was driven solely by the threshold).
	 *
	 * @param int $new_threshold The new threshold value.
	 *
	 * @return int[] Product IDs requiring re-save.
	 */
	private function get_affected_product_ids( int $new_threshold ): array {
		global $wpdb;

		$lookup_table = $wpdb->prefix . 'wc_product_meta_lookup';

		// Raising the threshold: in-stock products whose quantity now sits at or below it.
		// Lowering the threshold: out-of-stock products whose quantity now sits above it.
		// Querying both cases covers either direction and avoids missing edge cases where
		// the threshold both rises and falls between option reads.
		$instock     = ProductStockStatus::IN_STOCK;
		$outofstock  = ProductStockStatus::OUT_OF_STOCK;
		$onbackorder = ProductStockStatus::ON_BACKORDER;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT product_id FROM {$lookup_table}
				 WHERE manage_stock = 1
				 AND stock_quantity IS NOT NULL
				 AND (
					( stock_status = %s AND stock_quantity <= %d )
					OR ( stock_status IN ( %s, %s ) AND stock_quantity > %d )
				 )",
				$instock,
				$new_threshold,
				$outofstock,
				$onbackorder,
				$new_threshold
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', (array) $results );
	}

	/**
	 * Action Scheduler callback: re-save each product in the batch so that
	 * `WC_Product::validate_props()` re-evaluates stock status against the
	 * current threshold value.
	 *
	 * Products that have changed in the meantime (e.g. were deleted, had their
	 * manage_stock flag turned off, or already crossed the threshold via
	 * another path) are silently skipped.
	 *
	 * @param int[] $product_ids List of product IDs to re-save.
	 */
	public function process_batch( $product_ids ): void {
		if ( ! is_array( $product_ids ) || empty( $product_ids ) ) {
			return;
		}

		foreach ( $product_ids as $product_id ) {
			$product_id = (int) $product_id;
			if ( $product_id <= 0 ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->managing_stock() ) {
				continue;
			}

			// Saving runs WC_Product::validate_props(), which re-derives the stock status
			// from the current quantity and threshold and persists any change.
			$product->save();
		}
	}
}
