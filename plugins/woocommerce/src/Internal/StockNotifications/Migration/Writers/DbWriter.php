<?php
/**
 * DbWriter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Tables;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Live writer for the Back In Stock Notifications migration.
 *
 * Notifications are never routed through `Notification` + `save()`. Each row is inserted on
 * its own so the id it was given comes straight back from `$wpdb->insert_id`, then its meta
 * goes in with one multi-row statement against that id. The whole call runs inside a single
 * transaction: a failure between a row and its meta would leave a Core row with no
 * `_wc_bis_legacy_id` marker, invisible to the candidate predicate and re-inserted on the
 * next run.
 *
 * Meta written onto an existing notification — natural-key adoption markers and legacy
 * unsubscribe tokens — is always inserted, never updated, and goes through direct SQL rather
 * than `add_meta_data()`, which would bump `date_modified_gmt` on a row the merchant did not
 * touch. `write_product_meta()` is the one exception, going through the product CRUD layer
 * per the plan.
 */
class DbWriter implements WriterInterface {

	/**
	 * Columns accepted on `wc_stock_notifications`, mapped to their `$wpdb->prepare()` format.
	 *
	 * @var array<string,string>
	 */
	private const COLUMN_FORMATS = array(
		'product_id'            => '%d',
		'user_id'               => '%d',
		'user_email'            => '%s',
		'status'                => '%s',
		'date_created_gmt'      => '%s',
		'date_modified_gmt'     => '%s',
		'date_confirmed_gmt'    => '%s',
		'date_last_attempt_gmt' => '%s',
		'date_notified_gmt'     => '%s',
		'date_cancelled_gmt'    => '%s',
		'cancellation_source'   => '%s',
	);

	/**
	 * This writer performs real writes.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool {
		return false;
	}

	/**
	 * Insert notifications together with their meta, inside a single transaction.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @throws \Exception If any insert fails; the whole call is rolled back first.
	 * @return int Number of notifications written.
	 */
	public function insert_notifications( array $rows ): int {
		global $wpdb;

		if ( empty( $rows ) ) {
			return 0;
		}

		$written = 0;

		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $rows as $row ) {
				$notification_id = $this->insert_notification_row( $row['columns'] );
				$meta            = $row['meta'] ?? array();

				if ( ! empty( $meta ) ) {
					$this->write_notification_meta( $notification_id, $meta );
				}

				++$written;
			}
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		$wpdb->query( 'COMMIT' );

		return $written;
	}

	/**
	 * Insert one row into the notifications table and return the id it was given.
	 *
	 * A column the caller left out is written as SQL NULL rather than skipped: `$wpdb->insert()`
	 * emits a NULL literal for a null value, where a missing column would fall back to whatever
	 * the schema defaults to. Core reads the date columns with `IS NULL`, so a zero date would
	 * read as "already attempted".
	 *
	 * @param array $columns Column values for this row.
	 * @throws \RuntimeException If the insert fails or reports no id.
	 * @return int Notification id.
	 */
	private function insert_notification_row( array $columns ): int {
		global $wpdb;

		$data    = array();
		$formats = array();

		foreach ( self::COLUMN_FORMATS as $column => $format ) {
			$data[ $column ] = $columns[ $column ] ?? null;
			$formats[]       = $format;
		}

		$result = $wpdb->insert( Tables::core_notifications(), $data, $formats );

		if ( false === $result ) {
			throw new \RuntimeException( 'Failed to insert stock notification row: ' . $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$notification_id = (int) $wpdb->insert_id;

		if ( $notification_id <= 0 ) {
			throw new \RuntimeException( 'Inserted a stock notification row but no id was reported.' );
		}

		return $notification_id;
	}

	/**
	 * Insert notification meta rows onto an existing notification.
	 *
	 * Used by natural-key adoption and by the legacy unsubscribe token. Rows are always
	 * inserted, never updated, and written by direct SQL so no date_modified_gmt bump occurs.
	 *
	 * @param int   $notification_id Target notification id.
	 * @param array $meta            List of `array{0:string,1:mixed}` key/value pairs.
	 * @throws \RuntimeException If the insert fails.
	 * @return int Number of meta rows written.
	 */
	public function insert_notification_meta( int $notification_id, array $meta ): int {
		if ( empty( $meta ) ) {
			return 0;
		}

		$written = $this->write_notification_meta( $notification_id, $meta );

		$this->invalidate_meta_cache( $notification_id );

		return $written;
	}

	/**
	 * Write one notification's meta rows with a single multi-row statement.
	 *
	 * @param int   $notification_id Target notification id.
	 * @param array $meta            List of `array{0:string,1:mixed}` key/value pairs.
	 * @throws \RuntimeException If the insert fails.
	 * @return int Number of meta rows written.
	 */
	private function write_notification_meta( int $notification_id, array $meta ): int {
		global $wpdb;

		$meta_table = Tables::core_meta();
		$values     = array();

		foreach ( $meta as $pair ) {
			$values[] = $notification_id;
			$values[] = $pair[0];
			// This writer is the sole owner of maybe_serialize() for meta values; callers
			// (e.g. MetaMapper) must hand over unserialized values, or this double-serializes.
			$values[] = maybe_serialize( $pair[1] );
		}

		$row_placeholders = implode( ', ', array_fill( 0, count( $meta ), '(%d, %s, %s)' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $meta_table is a fixed internal table name, never user input; values are prepared via $wpdb->prepare().
		$sql = $wpdb->prepare(
			"INSERT INTO {$meta_table} (notification_id, meta_key, meta_value) VALUES {$row_placeholders}",
			$values
		);

		$result = $wpdb->query( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		if ( false === $result ) {
			throw new \RuntimeException( 'Failed to insert stock notification meta rows: ' . $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return (int) $result;
	}

	/**
	 * Write a meta row into the legacy notifications meta table.
	 *
	 * The migration's only write into the legacy schema: the `_wc_bis_migration_failed` marker.
	 *
	 * @param int    $legacy_id  Legacy notification id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_legacy_meta( int $legacy_id, string $meta_key, $meta_value ): bool {
		global $wpdb;

		$table = Tables::legacy_meta();

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$result = $wpdb->insert(
			$table,
			array(
				'bis_notifications_id' => $legacy_id,
				'meta_key'             => $meta_key,
				'meta_value'           => maybe_serialize( $meta_value ),
			),
			array( '%d', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value

		return false !== $result;
	}

	/**
	 * Write a site option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	public function write_option( string $option, $value ): bool {
		return update_option( $option, $value );
	}

	/**
	 * Write product meta through the CRUD layer.
	 *
	 * @param int    $product_id Product id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_product_meta( int $product_id, string $meta_key, $meta_value ): bool {
		return $this->write_product_meta_pairs( $product_id, array( array( $meta_key, $meta_value ) ) );
	}

	/**
	 * Write several meta values onto one product through the CRUD layer, in one save.
	 *
	 * @param int   $product_id Product id.
	 * @param array $meta       List of `array{0:string,1:mixed}` key/value pairs.
	 * @return bool
	 */
	public function write_product_meta_pairs( int $product_id, array $meta ): bool {
		if ( empty( $meta ) ) {
			return true;
		}

		$product = wc_get_product( $product_id );

		// A post that is typed as a product but will not resolve to one still needs its
		// migration marker written, or it stays a candidate forever and stalls the section.
		if ( ! $product ) {
			$written = true;

			foreach ( $meta as $pair ) {
				$written = false !== update_post_meta( $product_id, $pair[0], $pair[1] ) && $written;
			}

			return $written;
		}

		foreach ( $meta as $pair ) {
			$product->update_meta_data( $pair[0], $pair[1] );
		}

		return false !== $product->save();
	}

	/**
	 * Invalidate the cached raw meta data for a notification.
	 *
	 * An adopted row, or one whose legacy token was just attached, may already be loaded
	 * elsewhere in the request; the direct SQL writes above bypass `WC_Data`'s own cache
	 * invalidation, so it is repeated here. The cache group matches `Notification`'s, which
	 * is currently unset — this is a no-op today and stays correct if that ever changes.
	 *
	 * @param int $notification_id Notification id.
	 * @return void
	 */
	private function invalidate_meta_cache( int $notification_id ): void {
		$cache_key = Notification::generate_meta_cache_key( $notification_id, '' );
		wp_cache_delete( $cache_key, '' );
	}
}
