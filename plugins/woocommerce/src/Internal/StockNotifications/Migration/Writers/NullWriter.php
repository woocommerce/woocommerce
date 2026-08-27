<?php
/**
 * NullWriter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration\Writers;

defined( 'ABSPATH' ) || exit;

/**
 * Dry-run writer for the Back In Stock Notifications migration.
 *
 * Discards every write. Counts are computed the same way `DbWriter` would report them on
 * success, so a dry run produces the same report shape as a live one.
 */
class NullWriter implements WriterInterface {

	/**
	 * This writer discards its writes.
	 *
	 * @return bool
	 */
	public function is_dry_run(): bool {
		return true;
	}

	/**
	 * Report the number of notifications that would have been written, without writing them.
	 *
	 * @param array $rows List of rows, each `array{ columns: array<string,mixed>, meta: array<int,array{0:string,1:mixed}> }`.
	 * @return int Number of notifications that would have been written.
	 */
	public function insert_notifications( array $rows ): int {
		return count( $rows );
	}

	/**
	 * Report the number of meta rows that would have been written, without writing them.
	 *
	 * @param int   $notification_id Target notification id.
	 * @param array $meta            List of `array{0:string,1:mixed}` key/value pairs.
	 * @return int Number of meta rows that would have been written.
	 */
	public function insert_notification_meta( int $notification_id, array $meta ): int {
		return count( $meta );
	}

	/**
	 * Report success without writing into the legacy schema.
	 *
	 * @param int    $legacy_id  Legacy notification id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_legacy_meta( int $legacy_id, string $meta_key, $meta_value ): bool {
		return true;
	}

	/**
	 * Report success without writing the option.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 * @return bool
	 */
	public function write_option( string $option, $value ): bool {
		return true;
	}

	/**
	 * Report success without writing the product meta.
	 *
	 * @param int    $product_id Product id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return bool
	 */
	public function write_product_meta( int $product_id, string $meta_key, $meta_value ): bool {
		return true;
	}
}
