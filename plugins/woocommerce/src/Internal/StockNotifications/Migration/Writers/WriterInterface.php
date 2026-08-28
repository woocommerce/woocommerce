<?php
/**
 * WriterInterface class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

defined( 'ABSPATH' ) || exit;

/**
 * Every persistent write the migration performs goes through this contract.
 *
 * A dry run swaps DbWriter for NullWriter, so the migrators themselves carry no
 * dry-run branching and both modes produce the same report shape.
 */
interface WriterInterface {

	/**
	 * Whether this writer discards its writes.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool;

	/**
	 * Insert notifications together with their meta, in chunks, one transaction per chunk.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @return int Number of notifications written.
	 */
	public function insert_notifications( array $rows ): int;

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
	public function insert_notification_meta( int $notification_id, array $meta ): int;

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
	public function write_legacy_meta( int $legacy_id, string $meta_key, $meta_value ): bool;

	/**
	 * Write a site option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	public function write_option( string $option, $value ): bool;

	/**
	 * Write product meta through the CRUD layer.
	 *
	 * @param int    $product_id Product id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_product_meta( int $product_id, string $meta_key, $meta_value ): bool;

	/**
	 * Write several meta values onto one product through the CRUD layer, in one save.
	 *
	 * Each `write_product_meta()` call is a product load plus a full save pipeline, so a
	 * caller with more than one key to write goes through here instead.
	 *
	 * @param int   $product_id Product id.
	 * @param array $meta       List of `array{0:string,1:mixed}` key/value pairs.
	 * @return bool
	 */
	public function write_product_meta_pairs( int $product_id, array $meta ): bool;
}
