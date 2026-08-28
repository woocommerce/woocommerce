<?php
/**
 * Constants class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * The string keys the Back In Stock Notifications migration reads and writes.
 *
 * Every one of these is a contract between two or more classes: a marker one class writes
 * and another selects on, an option one class sets and another gates on. Declared once
 * here so a rename cannot desync the two sides, following the `src/Enums/` convention of
 * a final class of constants with no behaviour. Table names live in `Tables`, which has
 * to prefix them.
 */
final class Constants {

	/**
	 * Legacy notifications table, unprefixed.
	 */
	public const LEGACY_NOTIFICATIONS_TABLE = 'woocommerce_bis_notifications';

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
	 * Option holding the migration's run state: lock, cursors, cached counts.
	 */
	public const STATE_OPTION = 'wc_bis_migration_state';

	/**
	 * Autoloaded flag set the first time any row is migrated, inserted or adopted.
	 */
	public const HAS_MIGRATED_ROWS_OPTION = 'wc_bis_migration_has_migrated_rows';

	/**
	 * Autoloaded flag gating the legacy unsubscribe shim's registration. Narrower than
	 * HAS_MIGRATED_ROWS_OPTION: only set when a migrated row carries a legacy token.
	 */
	public const HAS_LEGACY_LINKS_OPTION = 'wc_bis_migration_has_legacy_links';

	/**
	 * Core notification meta marking the legacy id a row was migrated from. Inserted,
	 * never updated; a Core row can carry several.
	 */
	public const LEGACY_ID_META_KEY = '_wc_bis_legacy_id';

	/**
	 * Core notification meta marking a legacy id that adopted a pre-existing row rather
	 * than being inserted. Distinguishes the two so `rollback` never deletes an adopted row.
	 */
	public const ADOPTED_MARKER_META_KEY = '_wc_bis_legacy_adopted';

	/**
	 * Core notification meta holding the precomputed legacy unsubscribe token digest.
	 */
	public const LEGACY_UNSUB_HASH_META_KEY = '_wc_bis_legacy_unsub_hash';

	/**
	 * Legacy meta key recording a permanent per-row failure. The migration's only write
	 * into the legacy schema. Cleared by `--retry-failed` and by `rollback`.
	 */
	public const LEGACY_FAILED_META_KEY = '_wc_bis_migration_failed';

	/**
	 * Legacy post meta holding the per-product "sign-ups disabled" flag.
	 */
	public const PRODUCT_LEGACY_META_KEY = '_wc_bis_disabled';

	/**
	 * Product meta holding the product-meta section's per-product fingerprint.
	 */
	public const PRODUCT_FINGERPRINT_META_KEY = '_wc_bis_migration_signups_written';

	/**
	 * Product meta marking a product the product-meta section can never settle.
	 */
	public const PRODUCT_FAILED_META_KEY = '_wc_bis_migration_signups_failed';
}
