<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\DataMigrations;

defined( 'ABSPATH' ) || exit;

/**
 * Schedules the per-product sale Action Scheduler events for products that already
 * had future sale dates before those events existed.
 *
 * The events `wc_product_start_scheduled_sale` and `wc_product_end_scheduled_sale`
 * fire at the exact sale start/end time and are scheduled whenever a product's sale
 * date meta is written. Products whose sale dates were set on an earlier version have
 * no such events and depend on the once-a-day `woocommerce_scheduled_sales` safety-net
 * cron, so their sale doesn't start/end at the exact time the product editor promises.
 *
 * This migration schedules the missing events for every product or variation that still
 * has a sale start or end date in the future. It runs in batches through WooCommerce's
 * db-updates pipeline (see {@see \WC_Install::$db_updates}): each pass advances a stored
 * product-ID cursor and returns true while more products remain.
 *
 * @since 11.0.0
 */
class ScheduledSalesEventsBackfill {

	/**
	 * Option that stores the highest product ID processed so far, so each batch resumes
	 * where the previous one stopped.
	 *
	 * @var string
	 */
	private const CURSOR_OPTION = 'woocommerce_scheduled_sales_events_backfill_cursor';

	/**
	 * Number of products processed per batch.
	 *
	 * @var int
	 */
	private const BATCH_SIZE = 50;

	/**
	 * Schedule the per-product sale events for the next batch of products with future sale dates.
	 *
	 * @return bool True if there may be more products to process (run again), false when complete.
	 */
	public static function run(): bool {
		global $wpdb;

		$last_id = (int) get_option( self::CURSOR_OPTION, 0 );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key IN ( '_sale_price_dates_from', '_sale_price_dates_to' )
					AND meta_value > %d
					AND post_id > %d
				ORDER BY post_id ASC
				LIMIT %d",
				time(),
				$last_id,
				self::BATCH_SIZE
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $product_ids as $product_id ) {
			wc_maybe_schedule_product_sale_events( (int) $product_id );
		}

		// Fewer results than the batch size means this was the final page.
		if ( count( $product_ids ) < self::BATCH_SIZE ) {
			delete_option( self::CURSOR_OPTION );
			return false;
		}

		update_option( self::CURSOR_OPTION, (int) end( $product_ids ), false );
		return true;
	}
}
