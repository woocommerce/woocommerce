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
 * into `wc_stock_notifications`, reads back the id each row was given, then does one
 * multi-row INSERT of the meta rows against those ids. Both statements run inside a single
 * transaction: a failure between them would leave Core rows with no `_wc_bis_legacy_id`
 * marker, invisible to the candidate predicate and re-inserted on the next run.
 *
 * The ids are read back rather than derived from `$wpdb->insert_id`, because a generated id
 * block is not guaranteed to be contiguous; see `resolve_inserted_ids()` for what goes wrong
 * when it is assumed to be.
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
	 * @throws \RuntimeException If the inserted rows cannot be mapped to the chunk one to one.
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

			if ( count( $chunk ) !== $written ) {
				throw new \RuntimeException(
					sprintf( 'Inserted %d of %d stock notification rows; refusing to attach meta.', $written, count( $chunk ) )
				);
			}

			$ids       = $this->resolve_inserted_ids( $table, $chunk, $first_id );
			$meta_rows = $this->build_meta_rows( $chunk, $ids );

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
	 * Resolve the id each row in the chunk was actually inserted with.
	 *
	 * Never derived from `$wpdb->insert_id` by arithmetic. That would assume the generated
	 * ids are contiguous, which InnoDB does not promise: under
	 * `innodb_autoinc_lock_mode = 2` (interleaved, the default on MySQL 8.0 and MariaDB) a
	 * concurrent insert into the same table can take an id from the middle of our block.
	 * `wc_stock_notifications` has exactly such a writer — a shopper signing up for a restock
	 * alert while a migration runs — and one interleaved id would shift every later row of the
	 * chunk, stamping `_wc_bis_legacy_id` onto the shopper's notification and leaving ours
	 * unmarked for the next run to migrate again. Duplicate restock emails cannot be recalled,
	 * so the ids are read back instead of assumed.
	 *
	 * Rows are matched on `product_id` and `user_email`, ordered by id: ids rise in insertion
	 * order within one statement, so the nth of our rows is the nth match. A key must answer
	 * exactly as many times as the chunk carries it — a surplus means a row this chunk did not
	 * insert shares the key, and the match is then ambiguous. Anything that does not resolve
	 * one to one throws, and the caller's transaction rolls the whole chunk back rather than
	 * writing meta against a guess.
	 *
	 * @param string $table    Notifications table name.
	 * @param array  $chunk    Rows for this chunk, in insertion order.
	 * @param int    $first_id First id `$wpdb->insert_id` reported for the statement.
	 * @throws \RuntimeException When the inserted rows cannot be mapped one to one.
	 * @return int[] Notification ids, in the same order as $chunk.
	 */
	private function resolve_inserted_ids( string $table, array $chunk, int $first_id ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $table is a fixed internal name; $first_id is cast to int.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, product_id, user_email FROM {$table} WHERE id >= %d ORDER BY id ASC",
				$first_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $this->map_inserted_ids( $chunk, (array) $rows );
	}

	/**
	 * Match each chunk row to the id it was inserted with.
	 *
	 * Kept free of the database so the ambiguity rules below can be exercised directly: the
	 * case that matters — a row this chunk did not insert taking an id inside the block — is
	 * a race that a single-threaded test cannot stage through a real insert.
	 *
	 * @param array $chunk Rows for this chunk, in insertion order.
	 * @param array $rows  `id`, `product_id` and `user_email` for every row at or above the
	 *                     first id of the block, ordered by id.
	 * @throws \RuntimeException When the rows cannot be matched to the chunk one to one.
	 * @return int[] Notification ids, in the same order as $chunk.
	 */
	private function map_inserted_ids( array $chunk, array $rows ): array {
		$available = array();

		foreach ( $rows as $row ) {
			$available[ $this->natural_key( (int) $row['product_id'], (string) $row['user_email'] ) ][] = (int) $row['id'];
		}

		$expected = array();

		foreach ( $chunk as $row ) {
			$columns          = $row['columns'];
			$key              = $this->natural_key( (int) $columns['product_id'], (string) $columns['user_email'] );
			$expected[ $key ] = ( $expected[ $key ] ?? 0 ) + 1;
		}

		// Every key must answer exactly as many times as this chunk carries it. A surplus means
		// a row the chunk did not insert shares the key — a shopper signing up for the same
		// product with the same address while the run is in flight — and there is then no way
		// to tell which id is ours. Guessing would stamp the legacy marker onto the shopper's
		// notification and leave ours unmarked for the next run to migrate again, so the chunk
		// is rolled back and retried instead.
		foreach ( $expected as $key => $count ) {
			$found = count( $available[ $key ] ?? array() );

			if ( $found !== $count ) {
				throw new \RuntimeException(
					sprintf(
						'Expected %d inserted row(s) for one product and address, found %d; rolling the chunk back.',
						(int) $count, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- integers, not output.
						(int) $found // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- integers, not output.
					)
				);
			}
		}

		$ids = array();

		foreach ( $chunk as $row ) {
			$columns = $row['columns'];
			$key     = $this->natural_key( (int) $columns['product_id'], (string) $columns['user_email'] );
			$id      = array_shift( $available[ $key ] );

			if ( null === $id ) {
				throw new \RuntimeException( 'Ran out of inserted ids while mapping a chunk; rolling the chunk back.' );
			}

			$ids[] = $id;
		}

		return $ids;
	}

	/**
	 * The key a chunk row and its inserted database row are matched on.
	 *
	 * @param int    $product_id Product id.
	 * @param string $user_email Subscriber email.
	 * @return string
	 */
	private function natural_key( int $product_id, string $user_email ): string {
		return $product_id . "\0" . strtolower( $user_email );
	}

	/**
	 * Expand each row's meta list into meta table records against the ids it was inserted with.
	 *
	 * @param array $chunk Rows for this chunk, in insertion order.
	 * @param int[] $ids   Notification ids resolved by `resolve_inserted_ids()`, same order.
	 * @return array List of `array{0:int,1:string,2:mixed}` notification_id/key/value rows.
	 */
	private function build_meta_rows( array $chunk, array $ids ): array {
		$meta_rows = array();

		foreach ( array_values( $chunk ) as $offset => $row ) {
			$notification_id = $ids[ $offset ];

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
