<?php
/**
 * DbWriter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

use Automattic\WooCommerce\Internal\StockNotifications\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Live writer for the Back In Stock Notifications migration.
 *
 * Notification inserts are delegated to `BulkNotificationWriter`. Meta written onto an
 * existing notification — natural-key adoption markers and legacy unsubscribe tokens — is
 * always inserted, never updated, and goes through direct SQL rather than `add_meta_data()`,
 * which would bump `date_modified_gmt` on a row the merchant did not touch. `write_product_meta()`
 * is the one exception, going through the product CRUD layer per the plan.
 */
class DbWriter implements WriterInterface {

	/**
	 * Bulk insert engine for `wc_stock_notifications` and its meta.
	 *
	 * @var BulkNotificationWriter
	 */
	private BulkNotificationWriter $bulk_writer;

	/**
	 * Constructor.
	 *
	 * @param BulkNotificationWriter|null $bulk_writer Bulk insert engine to use. Defaults to a
	 *                                                  new instance with the writer's default chunk size.
	 */
	public function __construct( ?BulkNotificationWriter $bulk_writer = null ) {
		$this->bulk_writer = $bulk_writer ?? new BulkNotificationWriter();
	}

	/**
	 * This writer performs real writes.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool {
		return false;
	}

	/**
	 * Insert notifications together with their meta, in chunks, one transaction per chunk.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @return int Number of notifications written.
	 */
	public function insert_notifications( array $rows ): int {
		return $this->bulk_writer->insert_notifications( $rows );
	}

	/**
	 * Insert notification meta rows onto an existing notification.
	 *
	 * Used by natural-key adoption and by the legacy unsubscribe token. Rows are always
	 * inserted, never updated, and written by direct SQL so no date_modified_gmt bump occurs.
	 *
	 * @param int   $notification_id Target notification id.
	 * @param array $meta            List of `array{0:string,1:mixed}` key/value pairs.
	 * @return int Number of meta rows written.
	 */
	public function insert_notification_meta( int $notification_id, array $meta ): int {
		global $wpdb;

		if ( empty( $meta ) ) {
			return 0;
		}

		$meta_table = $wpdb->prefix . 'wc_stock_notificationmeta';
		$values     = array();

		foreach ( $meta as $pair ) {
			$values[] = $notification_id;
			$values[] = $pair[0];
			// This is the sole owner of maybe_serialize() for meta values, matching
			// BulkNotificationWriter; callers must hand over unserialized values.
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
			return 0;
		}

		$this->invalidate_meta_cache( $notification_id );

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

		$table = $wpdb->prefix . 'woocommerce_bis_notificationsmeta';

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
		$product = wc_get_product( $product_id );

		// A post that is typed as a product but will not resolve to one still needs its
		// migration marker written, or it stays a candidate forever and stalls the section.
		if ( ! $product ) {
			return false !== update_post_meta( $product_id, $meta_key, $meta_value );
		}

		$product->update_meta_data( $meta_key, $meta_value );

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
