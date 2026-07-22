<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Logging;

use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use Automattic\WooCommerce\Internal\Admin\Logging\LogHandlerFileV2;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Utilities\LoggingUtil;
use WC_Logger;

/**
 * Handles cleanup of place-order debug log files and associated order meta.
 *
 * @since 10.7.0
 */
class OrderLogsCleanupHelper {

	/**
	 * Number of log files to delete per batch.
	 */
	public const MAX_FILES_PER_RUN = 1000;

	/**
	 * Number of orders to clean up per batch.
	 */
	public const MAX_ORDERS_PER_RUN = 100;

	/**
	 * True if HPOS is enabled.
	 *
	 * @var bool
	 */
	private bool $hpos_in_use = false;

	/**
	 * True if HPOS is disabled and the orders data store in use is the old CPT one.
	 *
	 * @var bool
	 */
	private bool $cpt_in_use = false;

	/**
	 * The instance of DataSynchronizer to use.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $data_synchronizer;

	/**
	 * Initialize the instance.
	 * This is invoked by the dependency injection container.
	 *
	 * @internal
	 *
	 * @param CustomOrdersTableController $hpos_controller The instance of CustomOrdersTableController to use.
	 * @param DataSynchronizer            $data_synchronizer The instance of DataSynchronizer to use.
	 *
	 * @return void
	 */
	final public function init( CustomOrdersTableController $hpos_controller, DataSynchronizer $data_synchronizer ): void {
		$this->hpos_in_use = $hpos_controller->custom_orders_table_usage_is_enabled();
		if ( ! $this->hpos_in_use ) {
			$this->cpt_in_use = \WC_Order_Data_Store_CPT::class === \WC_Data_Store::load( 'order' )->get_current_class_name();
		}

		$this->data_synchronizer = $data_synchronizer;
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
	 * @since 10.7.0
	 */
	public function cleanup(): void {
		$max_age = $this->get_max_age_in_seconds();

		if ( 0 === $max_age ) {
			return;
		}

		// Old files are deleted in bulk first, so that the per-order log clearing below
		// doesn't scan a directory still full of them.
		$this->cleanup_old_log_files( $max_age );

		$this->cleanup_dangling_orders( $max_age );
	}

	/**
	 * Clean up orders with dangling debug log meta, in batches, until none remain.
	 *
	 * Dangling orders have `_debug_log_source` meta but no `_debug_log_source_pending_deletion`.
	 *
	 * @param int $max_age Maximum age in seconds before an order's debug log meta is eligible for cleanup.
	 */
	private function cleanup_dangling_orders( int $max_age ): void {
		do {
			$dangling_orders = $this->get_dangling_orders( $max_age );
			$fetched_count   = count( $dangling_orders );

			if ( 0 === $fetched_count ) {
				break;
			}

			$deleted_meta_rows = $this->clear_logs_and_delete_meta( $dangling_orders );
		} while ( $deleted_meta_rows > 0 && self::MAX_ORDERS_PER_RUN === $fetched_count );
	}

	/**
	 * Delete place-order-debug-* log files from the filesystem, in batches, until none remain.
	 *
	 * @param int $max_age Maximum age in seconds before a file is eligible for deletion.
	 */
	private function cleanup_old_log_files( int $max_age ): void {
		if ( LoggingUtil::get_default_handler() !== LogHandlerFileV2::class ) {
			return;
		}

		$file_controller = wc_get_container()->get( FileController::class );
		$date_end        = time() - $max_age;

		do {
			$files = $file_controller->get_files(
				array(
					'source'      => 'place-order-debug',
					'date_filter' => 'modified',
					'date_start'  => 1,
					'date_end'    => $date_end,
					'per_page'    => self::MAX_FILES_PER_RUN,
				)
			);

			if ( ! is_array( $files ) ) {
				return;
			}

			$fetched_count = count( $files );
			$deleted_count = 0;

			foreach ( $files as $file ) {
				if ( $file->delete() ) {
					++$deleted_count;
				}
			}
		} while ( $deleted_count > 0 && self::MAX_FILES_PER_RUN === $fetched_count );
	}

	/**
	 * Clear debug log files and delete associated order meta for the given items.
	 * Deletes both `_debug_log_source` and `_debug_log_source_pending_deletion` meta.
	 *
	 * @since 10.7.0
	 *
	 * @param array $items Associative array of order ID => log source name.
	 *
	 * @return int Number of meta table rows that were deleted.
	 */
	public function clear_logs_and_delete_meta( array $items ): int {
		if ( empty( $items ) ) {
			return 0;
		}

		$logger = wc_get_logger();
		if ( $logger instanceof WC_Logger ) {
			foreach ( $items as $source ) {
				$logger->clear( $source );
			}
		}

		$order_ids = array_keys( $items );
		return $this->delete_debug_log_meta_entries( $order_ids );
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
		if ( ! $this->hpos_in_use && ! $this->cpt_in_use ) {
			return array();
		}

		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', time() - $max_age );

		$meta_table  = $this->hpos_in_use ? "{$wpdb->prefix}wc_orders_meta" : $wpdb->postmeta;
		$order_table = $this->hpos_in_use ? "{$wpdb->prefix}wc_orders" : $wpdb->posts;
		$id_column   = $this->hpos_in_use ? 'order_id' : 'post_id';
		$type_column = $this->hpos_in_use ? 'type' : 'post_type';
		$date_column = $this->hpos_in_use ? 'date_created_gmt' : 'post_date_gmt';

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
	 * @return int Number of meta table rows that were deleted.
	 */
	private function delete_debug_log_meta_entries( array $order_ids ): int {
		global $wpdb;

		$tables = array(
			array(
				'table'     => $this->hpos_in_use ? "{$wpdb->prefix}wc_orders_meta" : $wpdb->postmeta,
				'id_column' => $this->hpos_in_use ? 'order_id' : 'post_id',
			),
		);

		if ( $this->data_synchronizer->data_sync_is_enabled() ) {
			$tables[] = array(
				'table'     => $this->hpos_in_use ? $wpdb->postmeta : "{$wpdb->prefix}wc_orders_meta",
				'id_column' => $this->hpos_in_use ? 'post_id' : 'order_id',
			);
		}

		$id_placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );

		$deleted = 0;

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

			if ( is_int( $result ) ) {
				$deleted += $result;
			}
		}

		return $deleted;
	}
}
