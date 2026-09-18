<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler;

/**
 * Repairs for HPOS order dates that earlier migrations left empty.
 *
 * Each repair walks the orders in batches, keyed by a cursor option, and reports true while rows remain so the
 * database updater calls it again. The batch mechanics (cursor, error handling, cache and Analytics refresh) live
 * in run_batch(); the repairs only select their rows and write their dates.
 *
 * @internal
 * @since 11.3.0
 */
class HposOrderDateRepairs {

	/**
	 * The value MySQL stores for a DATETIME that was never set.
	 */
	private const ZERO_DATE = '0000-00-00 00:00:00';

	/**
	 * Values the posts data store reads as no date.
	 */
	private const EMPTY_DATES = array( '', '0', self::ZERO_DATE );

	/**
	 * Give HPOS orders migrated without a created or updated date the dates their posts still hold.
	 *
	 * Earlier migrations copied a zero post_date_gmt verbatim, so the HPOS row ended up with no created date and the
	 * next save stamped it with the current time. Only rows whose date is NULL or the zero date are touched, and only
	 * when the order's post has a usable date. Placeholder posts count too: legacy cleanup keeps the date columns when
	 * it converts a post, and placeholders created for new HPOS orders carry the order's own date.
	 *
	 * @return bool True to run again.
	 */
	public function repair_dates_from_posts(): bool {
		global $wpdb;

		$orders_table = OrdersTableDataStore::get_orders_table_name();
		$type_list    = array();
		$post_types   = array_merge( wc_get_order_types( 'cot-migration' ), array( DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE ) );
		foreach ( $post_types as $post_type ) {
			$escaped = esc_sql( $post_type );
			if ( is_string( $escaped ) ) {
				$type_list[] = "'" . $escaped . "'";
			}
		}
		$type_list = implode( ',', $type_list );

		$select = function ( int $cursor, int $batch_size ) use ( $wpdb, $orders_table, $type_list ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names and the escaped type list cannot be prepared.
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT o.id, o.date_created_gmt, o.date_updated_gmt, p.post_date, p.post_date_gmt, p.post_modified, p.post_modified_gmt
					FROM {$orders_table} o
					INNER JOIN {$wpdb->posts} p ON p.ID = o.id AND p.post_type IN ({$type_list})
					WHERE o.id > %d
					AND ( o.date_created_gmt IS NULL OR o.date_created_gmt = %s OR o.date_updated_gmt IS NULL OR o.date_updated_gmt = %s )
					ORDER BY o.id ASC
					LIMIT %d",
					$cursor,
					self::ZERO_DATE,
					self::ZERO_DATE,
					$batch_size
				),
				OBJECT_K
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		};

		$repair = function ( array $rows ) use ( $wpdb, $orders_table ) {
			$repaired_ids = array();
			foreach ( $rows as $row ) {
				$columns = array();
				if ( ! $row->date_created_gmt || self::ZERO_DATE === $row->date_created_gmt ) {
					$columns['date_created_gmt'] = $this->gmt_from_post( $row->post_date_gmt, $row->post_date );
				}
				if ( ! $row->date_updated_gmt || self::ZERO_DATE === $row->date_updated_gmt ) {
					$columns['date_updated_gmt'] = $this->gmt_from_post( $row->post_modified_gmt, $row->post_modified );
				}
				$columns = array_filter( $columns );
				if ( empty( $columns ) ) {
					continue;
				}
				// Each column is written only while it is still empty, so a save that lands between the read and the write wins.
				$assignments = array();
				$values      = array();
				foreach ( $columns as $column => $value ) {
					$assignments[] = "{$column} = IF( {$column} IS NULL OR {$column} = %s, %s, {$column} )";
					$values[]      = self::ZERO_DATE;
					$values[]      = $value;
				}
				$values[] = (int) $row->id;
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Table and column names are code-defined, values go through prepare().
				$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$orders_table} SET " . implode( ', ', $assignments ) . ' WHERE id = %d', $values ) );
				if ( false === $updated ) {
					return array( $repaired_ids, sprintf( 'at order #%d: %s', (int) $row->id, $wpdb->last_error ) );
				}
				// Zero rows means a save or another run filled the dates first, and that writer already took care of the rest.
				if ( $updated > 0 ) {
					$repaired_ids[] = (int) $row->id;
				}
			}
			return array( $repaired_ids, null );
		};

		return $this->run_batch( 'woocommerce_update_1130_last_repaired_order_id', 500, 'repairing HPOS order dates', $select, $repair );
	}

	/**
	 * Restore the paid and completed dates of HPOS orders that were migrated from posts carrying only the pre-3.0
	 * '_paid_date' / '_completed_date' meta keys, which the migration used to skip.
	 *
	 * Only empty dates are filled, and only for orders the old keys are known to describe: orders last saved by
	 * WooCommerce < 3.0, or orders whose post still holds the old key but not the new one. A date cleared under HPOS
	 * after the migration leaves the old key behind too, and must stay cleared.
	 *
	 * @return bool True to run again.
	 */
	public function restore_legacy_paid_and_completed_dates(): bool {
		global $wpdb;

		$op_table   = OrdersTableDataStore::get_operational_data_table_name();
		$meta_table = OrdersTableDataStore::get_meta_table_name();
		$keys       = array(
			'_paid_date'      => array( '_date_paid', 'date_paid_gmt' ),
			'_completed_date' => array( '_date_completed', 'date_completed_gmt' ),
		);

		$select = function ( int $cursor, int $batch_size ) use ( $wpdb, $op_table, $meta_table ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table names cannot be prepared.
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT op.order_id, op.woocommerce_version, op.date_paid_gmt, op.date_completed_gmt FROM {$op_table} AS op
					WHERE op.order_id > %d
					AND (
						( op.date_paid_gmt IS NULL AND EXISTS (
							SELECT 1 FROM {$meta_table} AS meta WHERE meta.order_id = op.order_id AND meta.meta_key = '_paid_date' AND meta.meta_value NOT IN ( '', '0', '0000-00-00 00:00:00' )
						) )
						OR ( op.date_completed_gmt IS NULL AND EXISTS (
							SELECT 1 FROM {$meta_table} AS meta WHERE meta.order_id = op.order_id AND meta.meta_key = '_completed_date' AND meta.meta_value NOT IN ( '', '0', '0000-00-00 00:00:00' )
						) )
					)
					ORDER BY op.order_id ASC
					LIMIT %d",
					$cursor,
					$batch_size
				),
				OBJECT_K
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		};

		$repair = function ( array $rows ) use ( $wpdb, $op_table, $meta_table, $keys ) {
			$repaired_ids    = array();
			$order_ids       = array_map( 'intval', array_keys( $rows ) );
			$id_placeholders = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );
			// The old keys as the migration copied them, and the post's own keys (absent for cleaned-up posts), in one query.
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Table names cannot be prepared, placeholders are generated per ID.
			$meta_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 'order' AS source, order_id AS order_id, meta_key, meta_value, id AS sort_id FROM {$meta_table}
					WHERE order_id IN ( {$id_placeholders} ) AND meta_key IN ( '_paid_date', '_completed_date' )
					UNION ALL
					SELECT 'post' AS source, post_id AS order_id, meta_key, meta_value, meta_id AS sort_id FROM {$wpdb->postmeta}
					WHERE post_id IN ( {$id_placeholders} ) AND meta_key IN ( '_paid_date', '_completed_date', '_date_paid', '_date_completed' )
					ORDER BY source ASC, sort_id ASC",
					array_merge( $order_ids, $order_ids )
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			if ( '' !== $this->last_db_error() ) {
				return array( $repaired_ids, 'reading meta: ' . $this->last_db_error() );
			}

			// Like the posts data store, only the first value of a key counts.
			$legacy    = array();
			$post_meta = array();
			foreach ( $meta_rows as $row ) {
				if ( 'order' === $row->source ) {
					$legacy[ $row->order_id ][ $row->meta_key ] = $legacy[ $row->order_id ][ $row->meta_key ] ?? $row->meta_value;
				} else {
					$post_meta[ $row->order_id ][ $row->meta_key ] = $post_meta[ $row->order_id ][ $row->meta_key ] ?? $row->meta_value;
				}
			}

			foreach ( $rows as $order_id => $order ) {
				$pre_3_0 = version_compare( (string) $order->woocommerce_version, '3.0', '<' );
				foreach ( $keys as $legacy_key => list( $new_key, $column ) ) {
					$value = $legacy[ $order_id ][ $legacy_key ] ?? null;
					if ( null !== $order->$column || null === $value || in_array( $value, self::EMPTY_DATES, true ) ) {
						continue;
					}
					$post_has_only_legacy = ! in_array( $post_meta[ $order_id ][ $legacy_key ] ?? '', self::EMPTY_DATES, true )
						&& in_array( $post_meta[ $order_id ][ $new_key ] ?? '', self::EMPTY_DATES, true );
					if ( ! $pre_3_0 && ! $post_has_only_legacy ) {
						continue;
					}
					$gmt = $this->legacy_meta_to_gmt( $value );
					if ( null === $gmt ) {
						continue;
					}
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and column names are code-defined.
					$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$op_table} SET {$column} = %s WHERE order_id = %d AND {$column} IS NULL", $gmt, $order_id ) );
					if ( false === $updated ) {
						return array( $repaired_ids, sprintf( 'at order #%d: %s', (int) $order_id, $wpdb->last_error ) );
					}
					if ( $updated > 0 ) {
						$repaired_ids[ (int) $order_id ] = (int) $order_id;
					}
				}
			}
			return array( $repaired_ids, null );
		};

		return $this->run_batch( 'woocommerce_update_1130_last_legacy_date_order_id', 250, 'restoring HPOS legacy paid and completed dates', $select, $repair );
	}

	/**
	 * Run one batch of a repair: select the rows after the cursor, let the repair write them, refresh the repaired
	 * orders, and move the cursor. Every database error is logged and ends the repair, so a broken batch is never
	 * silently skipped and a persistent error never loops.
	 *
	 * @param string   $cursor_option Option holding the last processed order ID.
	 * @param int      $batch_size    Rows per batch.
	 * @param string   $label         What the repair does, for log messages.
	 * @param callable $select        function ( int $cursor, int $batch_size ): array, rows keyed by order ID, in ID order.
	 * @param callable $repair        function ( array $rows ): array, writes the rows and returns the IDs it changed plus a
	 *                                description of the failure that stopped it, or null.
	 * @return bool True to run again.
	 */
	private function run_batch( string $cursor_option, int $batch_size, string $label, callable $select, callable $repair ): bool {
		if ( ! wc_get_container()->get( DataSynchronizer::class )->check_orders_table_exists() ) {
			delete_option( $cursor_option );
			return false;
		}

		$rows = $select( (int) get_option( $cursor_option, 0 ), $batch_size );
		if ( '' !== $this->last_db_error() ) {
			return $this->stop( $cursor_option, $label, $this->last_db_error() );
		}
		if ( empty( $rows ) ) {
			delete_option( $cursor_option );
			return false;
		}

		list( $repaired_ids, $failure ) = $repair( $rows );
		// Repaired orders leave the caches (a cached object still has the old data) and get queued for the Analytics import.
		OrdersScheduler::forget_and_reimport_orders( array_values( $repaired_ids ) );
		if ( null !== $failure ) {
			return $this->stop( $cursor_option, $label, $failure );
		}

		if ( count( $rows ) < $batch_size ) {
			delete_option( $cursor_option );
			return false;
		}

		// The cursor only ever moves forward: a concurrent run (the queue plus `wp wc update`) may already have saved this id
		// or a later one, and update_option() reports that as false just like a failed write. Without a saved cursor the next
		// run would pick the same rows again, and rows the repair cannot change never leave the selection.
		$cursor = (int) array_key_last( $rows );
		if ( (int) get_option( $cursor_option, 0 ) < $cursor && ! update_option( $cursor_option, $cursor, false ) ) {
			wp_cache_delete( $cursor_option, 'options' );
			if ( (int) get_option( $cursor_option, 0 ) < $cursor ) {
				return $this->stop( $cursor_option, $label, 'the progress cursor could not be saved.' );
			}
		}

		return true;
	}

	/**
	 * The last database error, read fresh each time.
	 *
	 * @return string The error message, or an empty string.
	 */
	private function last_db_error(): string {
		global $wpdb;
		return (string) $wpdb->last_error;
	}

	/**
	 * Log why a repair stopped, drop its cursor and report it as finished.
	 *
	 * @param string $cursor_option Option holding the last processed order ID.
	 * @param string $label         What the repair does.
	 * @param string $reason        What went wrong.
	 * @return bool Always false.
	 */
	private function stop( string $cursor_option, string $label, string $reason ): bool {
		wc_get_logger()->error( sprintf( 'Stopped %s: %s', $label, $reason ), array( 'source' => 'wc-updater' ) );
		delete_option( $cursor_option );
		return false;
	}

	/**
	 * The rule WordPress applies to its own posts: the GMT column, or the local one when the GMT one is the zero date.
	 *
	 * @param string|null $gmt_date   The post's GMT date column.
	 * @param string|null $local_date The post's local date column.
	 * @return string|null GMT datetime, or null when the post has no usable date.
	 */
	private function gmt_from_post( ?string $gmt_date, ?string $local_date ): ?string {
		if ( $gmt_date && self::ZERO_DATE !== $gmt_date ) {
			return $gmt_date;
		}
		if ( ! $local_date || self::ZERO_DATE === $local_date ) {
			return null;
		}
		$datetime = date_create( $local_date, wp_timezone() );
		return $datetime ? $datetime->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : null;
	}

	/**
	 * Convert a pre-3.0 date meta value, a MySQL datetime in the site timezone or a timestamp, to a GMT datetime.
	 *
	 * @param string $value The meta value.
	 * @return string|null GMT datetime, or null when the value is not a usable date.
	 */
	private function legacy_meta_to_gmt( string $value ): ?string {
		$datetime = is_numeric( $value ) ? date_create( '@' . (int) $value ) : date_create( $value, wp_timezone() );
		return $datetime ? $datetime->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : null;
	}
}
