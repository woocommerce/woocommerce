<?php
/**
 * Tables class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;

defined( 'ABSPATH' ) || exit;

/**
 * Prefixed table names for the migration's SQL.
 *
 * The Core tables come from the data store that owns them rather than being spelled out
 * again here, so the migration cannot drift from the schema it writes into. The legacy
 * tables have no owner left in Core, so they are prefixed from `Constants`.
 */
final class Tables {

	/**
	 * Prefixed legacy notifications table.
	 *
	 * @return string
	 */
	public static function legacy_notifications(): string {
		global $wpdb;

		return $wpdb->prefix . Constants::LEGACY_NOTIFICATIONS_TABLE;
	}

	/**
	 * Prefixed legacy notifications meta table.
	 *
	 * @return string
	 */
	public static function legacy_meta(): string {
		global $wpdb;

		return $wpdb->prefix . Constants::LEGACY_META_TABLE;
	}

	/**
	 * Prefixed Core notifications table.
	 *
	 * @return string
	 */
	public static function core_notifications(): string {
		return self::data_store()->get_table_name();
	}

	/**
	 * Prefixed Core notifications meta table.
	 *
	 * @return string
	 */
	public static function core_meta(): string {
		return self::data_store()->get_meta_table_name();
	}

	/**
	 * The data store that owns the Core tables.
	 *
	 * @return StockNotificationsDataStore
	 */
	private static function data_store(): StockNotificationsDataStore {
		return wc_get_container()->get( StockNotificationsDataStore::class );
	}
}
