<?php
/**
 * Writer class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Migration\Constants;
use Automattic\WooCommerce\Internal\StockNotifications\Notification;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Every persistent write the migration performs goes through this class.
 *
 * A dry run constructs it with `$dry_run = true`: each write method then returns the count or
 * flag a successful live write would have reported, without touching the store, so the
 * migrators carry no dry-run branching and both modes produce the same report shape.
 *
 * The boolean the write methods return means only that the write was issued without error. It
 * is not a change indicator: a live write passes through WordPress functions that report false
 * for a value already equal to the one being written, while a dry run returns true
 * unconditionally. A caller must therefore never read `false` as "the store does not hold this
 * value", and never read `true` as "a row changed". Code that needs proof a value landed reads
 * it back and compares.
 *
 * Notifications are never routed through `Notification` + `save()`. Each row is inserted on
 * its own so the id it was given comes straight back from `$wpdb->insert_id`, then its meta
 * goes in with one multi-row statement against that id. The whole call runs inside a single
 * transaction: a failure between a row and its meta would leave a Core row with no
 * `_wc_bis_legacy_id_*` marker, invisible to the candidate predicate and re-inserted on the
 * next run.
 *
 * Meta written onto an existing notification — natural-key adoption markers and legacy
 * unsubscribe tokens — is always inserted, never updated, and goes through direct SQL rather
 * than `add_meta_data()`, which would bump `date_modified_gmt` on a row the merchant did not
 * touch. `write_product_meta()` is the one exception, going through the product CRUD layer
 * per the plan; see that method for what the exception costs and why it is accepted. The
 * bookkeeping markers the migrators write alongside it stay on direct SQL, in
 * `write_product_marker()`.
 */
class Writer {

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
	 * Whether this writer discards its writes.
	 *
	 * @var bool
	 */
	private bool $dry_run;

	/**
	 * Constructor.
	 *
	 * The argument is optional so the container can resolve a live writer by reflection; a dry
	 * run builds its own instance with `new Writer( true )`.
	 *
	 * @param bool $dry_run Whether to discard every write.
	 */
	public function __construct( bool $dry_run = false ) {
		$this->dry_run = $dry_run;
	}

	/**
	 * Whether this writer discards its writes.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool {
		return $this->dry_run;
	}

	/**
	 * Insert notifications together with their meta, inside a single transaction.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @throws \Throwable If any insert fails, or a row is malformed; the whole call is
	 *                    rolled back first, so the connection is never left mid-transaction.
	 * @return int Number of notifications written.
	 */
	public function insert_notifications( array $rows ): int {
		global $wpdb;

		if ( empty( $rows ) ) {
			return 0;
		}

		if ( $this->dry_run ) {
			return count( $rows );
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
		} catch ( \Throwable $e ) {
			// Throwable, not Exception: a malformed row reaches the typed helpers below as a
			// \TypeError, which is an \Error. Letting that escape would skip the ROLLBACK and
			// leave the connection mid-transaction for the rest of the request.
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

		$result = $wpdb->insert( Constants::core_notifications(), $data, $formats );

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

		if ( $this->dry_run ) {
			return count( $meta );
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

		$meta_table = Constants::core_meta();
		$values     = array();

		foreach ( $meta as $pair ) {
			$values[] = $notification_id;
			$values[] = $pair[0];
			// This writer is the sole owner of maybe_serialize() for meta values; callers must
			// hand over unserialized values, or this double-serializes.
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

		if ( $this->dry_run ) {
			return true;
		}

		$table = Constants::legacy_meta();

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
		if ( $this->dry_run ) {
			return true;
		}

		return update_option( $option, $value );
	}

	/**
	 * Write product meta through the CRUD layer.
	 *
	 * The full `$product->save()` this costs is deliberate, and the one place this class
	 * leaves direct SQL for the CRUD layer. The flag it writes is read back through the
	 * product cache, so a raw meta write would leave stale reads behind it. The price is
	 * that `WC_Product_Data_Store_CPT::update()` fires `woocommerce_update_product` on every
	 * save whatever changed, so a large catalog produces that many hook fires — webhooks,
	 * search indexers, third-party cache invalidation. That fan-out is accepted: only
	 * products that carried the legacy disable-signups flag are ever written here.
	 *
	 * @param WC_Product $product    Loaded product to write to.
	 * @param string     $meta_key   Meta key.
	 * @param mixed      $meta_value Meta value.
	 * @return bool
	 */
	public function write_product_meta( WC_Product $product, string $meta_key, $meta_value ): bool {
		if ( $this->dry_run ) {
			return true;
		}

		$product->update_meta_data( $meta_key, $meta_value );

		return false !== $product->save();
	}

	/**
	 * Write a migration bookkeeping marker onto a product, bypassing the CRUD layer.
	 *
	 * Markers are read back only by the migrators' own SQL, never off a `WC_Product`, so
	 * they do not need the save `write_product_meta()` pays for. Skipping it is the point:
	 * this is what failure recovery writes, and recovery must not run the save that a
	 * `woocommerce_update_product` callback can throw from.
	 *
	 * Markers only. A value that is read back through a product object must go through
	 * `write_product_meta()` instead: this method leaves the `products` cache group alone,
	 * so an already-loaded product would keep serving the old value.
	 *
	 * @param int    $product_id Product id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_product_marker( int $product_id, string $meta_key, $meta_value ): bool {
		if ( $this->dry_run ) {
			return true;
		}

		return false !== update_post_meta( $product_id, $meta_key, $meta_value );
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
