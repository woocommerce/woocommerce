<?php
/**
 * BulkNotificationWriter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Bulk-inserts legacy Back In Stock Notifications rows into the Core notification tables.
 *
 * Never routes through `Notification` + `save()`. Per chunk this does one multi-row INSERT
 * into `wc_stock_notifications`, captures the contiguous id block from `$wpdb->insert_id` and
 * the affected row count, then does one multi-row INSERT of the meta rows against that block.
 * Both statements run inside a single transaction: a failure between them would leave Core
 * rows with no `_wc_bis_legacy_id` marker, invisible to the candidate predicate and
 * re-inserted on the next run. The contiguous-id-block assumption holds only for a single
 * multi-row statement, so a batch larger than the chunk size is split into several chunks,
 * each with its own transaction and id capture.
 */
class BulkNotificationWriter {

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
	 * Default number of notification rows written per statement/transaction.
	 *
	 * @var int
	 */
	private const DEFAULT_CHUNK_SIZE = 500;

	/**
	 * Maximum number of notification rows written by a single multi-row INSERT.
	 *
	 * @var positive-int
	 */
	private int $chunk_size;

	/**
	 * Constructor.
	 *
	 * @param int $chunk_size Maximum number of notification rows per multi-row INSERT and
	 *                        transaction. Keeps a single statement inside `max_allowed_packet`.
	 */
	public function __construct( int $chunk_size = self::DEFAULT_CHUNK_SIZE ) {
		$this->chunk_size = max( 1, $chunk_size );
	}

	/**
	 * Insert notification rows together with their meta, in chunks, one transaction per chunk.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @return int Number of notifications written.
	 */
	public function insert_notifications( array $rows ): int {
		if ( empty( $rows ) ) {
			return 0;
		}

		$written = 0;

		foreach ( array_chunk( $rows, $this->chunk_size ) as $chunk ) {
			$written += $this->write_chunk( array_values( $chunk ) );
		}

		return $written;
	}

	/**
	 * Insert one chunk of rows inside a single transaction.
	 *
	 * @param array $chunk Rows for this chunk, at most `$this->chunk_size` entries.
	 * @throws \Exception If either statement fails.
	 * @return int Number of notifications written.
	 */
	private function write_chunk( array $chunk ): int {
		global $wpdb;

		$table      = Tables::core_notifications();
		$meta_table = Tables::core_meta();

		$wpdb->query( 'START TRANSACTION' );

		try {
			$written  = $this->insert_notification_rows( $table, $chunk );
			$first_id = (int) $wpdb->insert_id;

			$meta_rows = $this->build_meta_rows( $chunk, $first_id );

			if ( ! empty( $meta_rows ) ) {
				$this->insert_meta_rows( $meta_table, $meta_rows );
			}
		} catch ( \Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}

		$wpdb->query( 'COMMIT' );

		return $written;
	}

	/**
	 * Insert the notification rows for one chunk with a single multi-row statement.
	 *
	 * @param string $table Notifications table name.
	 * @param array  $chunk Rows for this chunk.
	 * @throws \RuntimeException If the INSERT fails.
	 * @return int Number of rows inserted.
	 */
	private function insert_notification_rows( string $table, array $chunk ): int {
		global $wpdb;

		$columns          = array_keys( self::COLUMN_FORMATS );
		$values           = array();
		$row_placeholders = array();

		foreach ( $chunk as $row ) {
			$placeholders = array();

			foreach ( $columns as $column ) {
				$value = $row['columns'][ $column ] ?? null;

				// A null column is written as a SQL NULL literal rather than through a
				// placeholder: $wpdb->prepare() renders null as an empty string, which MySQL
				// stores in a datetime column as '0000-00-00 00:00:00'. Core reads these
				// columns with `IS NULL`, so a zero date would read as "already attempted".
				if ( null === $value ) {
					$placeholders[] = 'NULL';
					continue;
				}

				$placeholders[] = self::COLUMN_FORMATS[ $column ];
				$values[]       = $value;
			}

			$row_placeholders[] = '(' . implode( ', ', $placeholders ) . ')';
		}

		$row_placeholders = implode( ', ', $row_placeholders );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $table and $columns are a fixed internal list, never user input; values are prepared via $wpdb->prepare().
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (" . implode( ', ', $columns ) . ") VALUES {$row_placeholders}",
			$values
		);

		$result = $wpdb->query( $sql );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		if ( false === $result ) {
			throw new \RuntimeException( 'Failed to insert stock notification rows: ' . $wpdb->last_error ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return (int) $result;
	}

	/**
	 * Expand each row's meta list into meta table records against a just-inserted id block.
	 *
	 * @param array $chunk    Rows for this chunk, in insertion order.
	 * @param int   $first_id First id of the contiguous block `$wpdb->insert_id` returned.
	 * @return array List of `array{0:int,1:string,2:mixed}` notification_id/key/value rows.
	 */
	private function build_meta_rows( array $chunk, int $first_id ): array {
		$meta_rows = array();

		foreach ( array_values( $chunk ) as $offset => $row ) {
			$notification_id = $first_id + $offset;

			foreach ( $row['meta'] as $meta ) {
				$meta_rows[] = array( $notification_id, $meta[0], $meta[1] );
			}
		}

		return $meta_rows;
	}

	/**
	 * Insert the meta rows for one chunk with a single multi-row statement.
	 *
	 * @param string $meta_table Meta table name.
	 * @param array  $meta_rows  List of `array{0:int,1:string,2:mixed}` rows.
	 * @throws \RuntimeException If the INSERT fails.
	 * @return void
	 */
	private function insert_meta_rows( string $meta_table, array $meta_rows ): void {
		global $wpdb;

		$values = array();

		foreach ( $meta_rows as $meta_row ) {
			$values[] = $meta_row[0];
			$values[] = $meta_row[1];
			// This writer is the sole owner of maybe_serialize() for meta values; callers
			// (e.g. MetaMapper) must hand over unserialized values, or this double-serializes.
			$values[] = maybe_serialize( $meta_row[2] );
		}

		$row_placeholders = implode( ', ', array_fill( 0, count( $meta_rows ), '(%d, %s, %s)' ) );

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
	}
}
