<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Logging;

use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\LoggingUtil;
use Automattic\WooCommerce\Utilities\OrderUtil;
use WC_Logger;

/**
 * Handles cleanup of place-order debug log files and associated order meta.
 *
 * @since 10.7.0
 */
class OrderLogsCleanupHelper {

	/**
	 * Maximum number of log files to delete per run.
	 */
	public const MAX_FILES_PER_RUN = 1000;

	/**
	 * Maximum number of orders to clean up per run.
	 */
	public const MAX_ORDERS_PER_RUN = 100;

	/**
	 * Hook of the action scheduled to continue a cleanup that didn't drain the backlog.
	 */
	public const EXTENDED_CLEANUP_HOOK = 'woocommerce_cleanup_logs_extended';

	/**
	 * Delay, in seconds, before a follow-up cleanup run.
	 */
	private const EXTENDED_CLEANUP_DELAY = 5 * MINUTE_IN_SECONDS;

	/**
	 * The instance of DataSynchronizer to use.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $data_synchronizer;

	/**
	 * Initialize the instance and register hooks.
	 * This is invoked by the dependency injection container.
	 *
	 * @internal
	 *
	 * @param DataSynchronizer $data_synchronizer The instance of DataSynchronizer to use.
	 *
	 * @return void
	 */
	final public function init( DataSynchronizer $data_synchronizer ): void {
		$this->data_synchronizer = $data_synchronizer;

		add_action( self::EXTENDED_CLEANUP_HOOK, array( $this, 'cleanup' ) );
	}

	/**
	 * Get the maximum age for debug logs before cleanup, in seconds.
	 * Returns 0 if cleanup is disabled via filter.
	 *
	 * @return int
	 */
	private function get_max_age_in_seconds(): int {
		/**
		 * Filter the retention period for place-order debug logs cleanup.
		 * Return 0 to disable cleanup entirely.
		 *
		 * @param int $max_age_in_seconds The maximum age in seconds before cleanup. Default 3 days.
		 *
		 * @since 10.7.0
		 */
		return absint( apply_filters( 'woocommerce_cleanup_order_debug_logs_max_age', 3 * DAY_IN_SECONDS ) );
	}

	/**
	 * Run all cleanup tasks: dangling order meta and old log files.
	 *
	 * Also the callback for the extended cleanup action.
	 *
	 * @since 10.7.0
	 */
	public function cleanup(): void {
		$max_age = $this->get_max_age_in_seconds();

		if ( 0 === $max_age ) {
			return;
		}

		$files_swept_in_bulk = LogHandlerFileV2::class === LoggingUtil::get_default_handler();

		$more_files  = $files_swept_in_bulk && $this->cleanup_old_log_files( $max_age );
		$more_orders = $this->cleanup_dangling_orders( $max_age, $files_swept_in_bulk );

		// Each run handles a single batch, so that it can't grow unbounded on a large
		// backlog. Anything left over is picked up by a follow-up run a few minutes later.
		if ( $more_files || $more_orders ) {
			$this->schedule_extended_cleanup();
		}
	}

	/**
	 * Clean up a batch of orders with dangling debug log meta.
	 *
	 * Dangling orders have `_debug_log_source` meta but no `_debug_log_source_pending_deletion`.
	 *
	 * @param int  $max_age             Maximum age in seconds before an order's debug log meta is eligible for cleanup.
	 * @param bool $files_swept_in_bulk True if the file sweep is already deleting these orders' log files.
	 *
	 * @return bool True if there may be more orders left to clean up.
	 */
	private function cleanup_dangling_orders( int $max_age, bool $files_swept_in_bulk ): bool {
		$dangling_orders = $this->get_dangling_orders( $max_age );

		if ( empty( $dangling_orders ) ) {
			return false;
		}

		// Clearing each order's log source individually scans the log directory once per
		// order, so it's only worth doing when the bulk sweep isn't deleting the files.
		$deleted = $files_swept_in_bulk
			? $this->delete_debug_log_meta_entries( array_keys( $dangling_orders ) )
			: $this->clear_logs_and_delete_meta_entries( $dangling_orders );

		return $deleted && self::MAX_ORDERS_PER_RUN === count( $dangling_orders );
	}

	/**
	 * Delete a batch of place-order-debug-* log files from the filesystem.
	 *
	 * @param int $max_age Maximum age in seconds before a file is eligible for deletion.
	 *
	 * @return bool True if there may be more files left to delete.
	 */
	private function cleanup_old_log_files( int $max_age ): bool {
		$deleted = wc_get_container()->get( FileController::class )->delete_stale_files(
			'place-order-debug',
			time() - $max_age,
			self::MAX_FILES_PER_RUN
		);

		return self::MAX_FILES_PER_RUN === $deleted;
	}

	/**
	 * Schedule a follow-up cleanup run to continue draining the backlog.
	 */
	private function schedule_extended_cleanup(): void {
		if ( ! function_exists( 'as_schedule_single_action' ) || ! function_exists( 'as_get_scheduled_actions' ) ) {
			return;
		}

		// Only pending actions count: when this runs as the extended cleanup callback, the
		// current action is in-progress and would otherwise match, blocking the follow-up.
		$pending = as_get_scheduled_actions(
			array(
				'hook'     => self::EXTENDED_CLEANUP_HOOK,
				'args'     => array(),
				'group'    => 'woocommerce',
				'status'   => \ActionScheduler_Store::STATUS_PENDING,
				'per_page' => 1,
				'orderby'  => 'none',
			),
			'ids'
		);

		if ( $pending ) {
			return;
		}

		as_schedule_single_action( time() + self::EXTENDED_CLEANUP_DELAY, self::EXTENDED_CLEANUP_HOOK, array(), 'woocommerce' );
	}

	/**
	 * Clear debug log files and delete associated order meta for the given items.
	 * Deletes both `_debug_log_source` and `_debug_log_source_pending_deletion` meta.
	 *
	 * @since 10.7.0
	 *
	 * @param array $items Associative array of order ID => log source name.
	 *
	 * @return void
	 */
	public function clear_logs_and_delete_meta( array $items ): void {
		$this->clear_logs_and_delete_meta_entries( $items );
	}

	/**
	 * Clear debug log files and delete associated order meta for the given items, reporting whether anything
	 * was deleted.
	 *
	 * This backs the public clear_logs_and_delete_meta(), whose `void` return type is kept for compatibility.
	 *
	 * @param array $items Associative array of order ID => log source name.
	 *
	 * @return bool True if any meta entries were deleted.
	 */
	private function clear_logs_and_delete_meta_entries( array $items ): bool {
		if ( empty( $items ) ) {
			return false;
		}

		$logger = wc_get_logger();
		if ( $logger instanceof WC_Logger ) {
			foreach ( $items as $source ) {
				$logger->clear( $source );
			}
		}

		return $this->delete_debug_log_meta_entries( array_keys( $items ) );
	}

	/**
	 * Get orders with `_debug_log_source` meta older than the given max age.
	 *
	 * Orders that also have `_debug_log_source_pending_deletion` will be handled
	 * by the batch processor, but cleaning them up here too is harmless.
	 *
	 * @param int $max_age Maximum age in seconds.
	 *
	 * @return array Associative array of order ID => log source name.
	 */
	private function get_dangling_orders( int $max_age ): array {
		if ( OrderUtil::unknown_orders_data_store_in_use() ) {
			return array();
		}

		global $wpdb;

		$hpos_in_use = OrderUtil::custom_orders_table_usage_is_enabled();
		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - $max_age );

		$meta_table  = $hpos_in_use ? "{$wpdb->prefix}wc_orders_meta" : $wpdb->postmeta;
		$order_table = $hpos_in_use ? "{$wpdb->prefix}wc_orders" : $wpdb->posts;
		$id_column   = $hpos_in_use ? 'order_id' : 'post_id';
		$type_column = $hpos_in_use ? 'type' : 'post_type';
		$date_column = $hpos_in_use ? 'date_created_gmt' : 'post_date_gmt';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.{$id_column} as order_id, m.meta_value
				 FROM {$meta_table} m
				 INNER JOIN {$order_table} o ON m.{$id_column} = o.id
				 WHERE m.meta_key = %s
				 AND o.{$type_column} = %s
				 AND o.{$date_column} < %s
				 LIMIT %d",
				'_debug_log_source',
				'shop_order',
				$cutoff_date,
				self::MAX_ORDERS_PER_RUN
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_column( $rows, 'meta_value', 'order_id' );
	}

	/**
	 * Delete `_debug_log_source` and `_debug_log_source_pending_deletion` meta entries for the given order IDs
	 * from the authoritative table and the backup table (when data sync is enabled).
	 *
	 * @param array $order_ids Array of order IDs to delete meta for.
	 *
	 * @return bool True if any meta entries were deleted.
	 */
	private function delete_debug_log_meta_entries( array $order_ids ): bool {
		global $wpdb;

		$hpos_in_use = OrderUtil::custom_orders_table_usage_is_enabled();

		$tables = array(
			array(
				'table'     => $hpos_in_use ? "{$wpdb->prefix}wc_orders_meta" : $wpdb->postmeta,
				'id_column' => $hpos_in_use ? 'order_id' : 'post_id',
			),
		);

		if ( $this->data_synchronizer->data_sync_is_enabled() ) {
			$tables[] = array(
				'table'     => $hpos_in_use ? $wpdb->postmeta : "{$wpdb->prefix}wc_orders_meta",
				'id_column' => $hpos_in_use ? 'post_id' : 'order_id',
			);
		}

		$id_placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

		$deleted = false;

		foreach ( $tables as $table_config ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$table_config['table']}
					 WHERE {$table_config['id_column']} IN ({$id_placeholders})
					 AND meta_key IN (%s, %s)",
					array_merge( $order_ids, array( '_debug_log_source', '_debug_log_source_pending_deletion' ) )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

			if ( is_int( $result ) && $result > 0 ) {
				$deleted = true;
			}
		}

		if ( ! $deleted ) {
			// These IDs came from a query that just matched them on `_debug_log_source`, so deleting nothing
			// means either another process got there first or the writes are failing. Worth surfacing either way.
			wc_get_logger()->warning(
				sprintf(
					'Expected to delete debug log meta for %d order(s), but no rows were removed.',
					count( $order_ids )
				),
				array( 'source' => 'wc-logs-cleanup' )
			);
		}

		return $deleted;
	}
}
