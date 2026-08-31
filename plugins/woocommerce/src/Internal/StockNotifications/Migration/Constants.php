<?php
/**
 * Constants class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;

defined( 'ABSPATH' ) || exit;

/**
 * The string keys the Back In Stock Notifications migration reads and writes.
 *
 * Every one of these is a contract between two or more classes: a marker one class writes
 * and another selects on, an option one class sets and another gates on. Declared once
 * here so a rename cannot desync the two sides, following the `src/Enums/` convention of
 * a final class of constants, with the accessors the migration's SQL needs to reach a table
 * by its prefixed name and to build a per-legacy-id meta key.
 */
final class Constants {

	/**
	 * Legacy notifications table, unprefixed.
	 */
	public const LEGACY_NOTIFICATIONS_TABLE = 'woocommerce_bis_notifications';

	/**
	 * The migration's batched sections, in the order they run. Settings are not among them:
	 * they are a fixed set of values `OptionsMigrator` writes on every batch, not something
	 * to scan through.
	 *
	 * @var string[]
	 */
	public const SECTION_ORDER = array( 'product-meta' );

	/**
	 * Legacy notifications meta table, unprefixed.
	 */
	public const LEGACY_META_TABLE = 'woocommerce_bis_notificationsmeta';

	/**
	 * Option recording that the legacy extension was ever installed. Absent means there is
	 * nothing to migrate.
	 */
	public const DB_VERSION_OPTION = 'wc_bis_db_version';

	/**
	 * Option holding the migration's run state: cursors, cached counts.
	 */
	public const STATE_OPTION = 'wc_bis_migration_state';

	/**
	 * Option holding the run lock. Its own row rather than a field of STATE_OPTION, so it
	 * can be claimed with an atomic INSERT instead of a read-then-write.
	 */
	public const LOCK_OPTION = 'wc_bis_migration_lock';

	/**
	 * Option holding the batch lock: mutual exclusion between two batches of the same run,
	 * which the run lock does not cover.
	 */
	public const BATCH_LOCK_OPTION = 'wc_bis_migration_batch_lock';

	/**
	 * Autoloaded flag set the first time any row is migrated, inserted or adopted.
	 */
	public const HAS_MIGRATED_ROWS_OPTION = 'wc_bis_migration_has_migrated_rows';

	/**
	 * Autoloaded flag guarding the legacy link shim's registration. Narrower than
	 * HAS_MIGRATED_ROWS_OPTION: only set when a migrated row carries a legacy token,
	 * of either kind — unsubscribe or verification.
	 */
	public const HAS_LEGACY_LINKS_OPTION = 'wc_bis_migration_has_legacy_links';

	/**
	 * Prefix of the Core notification meta marking the legacy id a row was migrated from.
	 * Inserted, never updated; a Core row can carry several.
	 *
	 * The legacy id lives in the meta key rather than the meta value, so every lookup is an
	 * equality match on the indexed `meta_key` column. `meta_value` is an unindexed
	 * `longtext`, and the one key these markers used to share was carried by every migrated
	 * row, so selecting on the value meant scanning the whole migrated population.
	 */
	public const LEGACY_ID_META_KEY_PREFIX = '_wc_bis_legacy_id_';

	/**
	 * Prefix of the Core notification meta marking a legacy id that adopted a pre-existing
	 * row rather than being inserted. Recorded for support: once the legacy tables are gone,
	 * an adopted row is otherwise indistinguishable from one this migration created.
	 */
	public const ADOPTED_MARKER_META_KEY_PREFIX = '_wc_bis_legacy_adopted_';

	/**
	 * Prefix of the Core notification meta holding the precomputed legacy unsubscribe token
	 * digest.
	 */
	public const LEGACY_UNSUB_HASH_META_KEY_PREFIX = '_wc_bis_legacy_unsub_hash_';

	/**
	 * Prefix of the Core notification meta holding the precomputed legacy verification token
	 * digest, together with the expiry resolved at migration time. Written only for rows
	 * migrated as pending whose legacy verification link had not already expired, and deleted
	 * the first time that link is followed.
	 */
	public const LEGACY_VERIFY_HASH_META_KEY_PREFIX = '_wc_bis_legacy_verify_hash_';

	/**
	 * Legacy meta key recording a permanent per-row failure. The migration's only write
	 * into the legacy schema. Cleared by `--retry-failed`.
	 */
	public const LEGACY_FAILED_META_KEY = '_wc_bis_migration_failed';

	/**
	 * Legacy post meta holding the per-product "sign-ups disabled" flag.
	 */
	public const PRODUCT_LEGACY_META_KEY = '_wc_bis_disabled';

	/**
	 * Product meta marking a product the product-meta section can never settle.
	 */
	public const PRODUCT_FAILED_META_KEY = '_wc_bis_migration_signups_failed';

	/**
	 * Core notification meta key marking one legacy id.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return string
	 */
	public static function legacy_id_meta_key( int $legacy_id ): string {
		return self::LEGACY_ID_META_KEY_PREFIX . $legacy_id;
	}

	/**
	 * Core notification meta key marking one legacy id as having adopted the row.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return string
	 */
	public static function adopted_marker_meta_key( int $legacy_id ): string {
		return self::ADOPTED_MARKER_META_KEY_PREFIX . $legacy_id;
	}

	/**
	 * Core notification meta key holding one legacy id's unsubscribe token digest.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return string
	 */
	public static function legacy_unsub_hash_meta_key( int $legacy_id ): string {
		return self::LEGACY_UNSUB_HASH_META_KEY_PREFIX . $legacy_id;
	}

	/**
	 * Core notification meta key holding one legacy id's verification token digest.
	 *
	 * @param int $legacy_id Legacy notification id.
	 * @return string
	 */
	public static function legacy_verify_hash_meta_key( int $legacy_id ): string {
		return self::LEGACY_VERIFY_HASH_META_KEY_PREFIX . $legacy_id;
	}

	/**
	 * Prefixed legacy notifications table.
	 *
	 * @return string
	 */
	public static function legacy_notifications(): string {
		global $wpdb;

		return $wpdb->prefix . self::LEGACY_NOTIFICATIONS_TABLE;
	}

	/**
	 * Prefixed legacy notifications meta table.
	 *
	 * @return string
	 */
	public static function legacy_meta(): string {
		global $wpdb;

		return $wpdb->prefix . self::LEGACY_META_TABLE;
	}

	/**
	 * Prefixed Core notifications table.
	 *
	 * Comes from the data store that owns it rather than being spelled out again here, so the
	 * migration cannot drift from the schema it writes into.
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
