<?php
/**
 * Requirements class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications\Migration;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Automattic\WooCommerce\Utilities\FeaturesUtil;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies that a migration run is allowed to start or continue.
 *
 * `check()` is called once at the start of a run and again on every batch, since a
 * merchant can toggle the feature off, or drop the legacy tables, mid-run and a
 * background worker must stop cleanly rather than fatal.
 *
 * `SHOW TABLES LIKE` lives here, and only here, in the whole migration. It is a
 * correctness guard inside a migration run, not a discovery mechanism: whether the
 * migration *registers* at all is decided elsewhere, from an already-autoloaded
 * option, at zero query cost. Do not call `SHOW TABLES LIKE` from any other class.
 */
class Requirements {

	/**
	 * Legacy Back In Stock Notifications tables the migration reads from.
	 *
	 * @var string[]
	 */
	private const LEGACY_TABLES = array(
		Constants::LEGACY_NOTIFICATIONS_TABLE,
		Constants::LEGACY_META_TABLE,
		'woocommerce_bis_activity',
	);

	/**
	 * Legacy notifications table, unprefixed. Kept as its own constant rather than
	 * indexed out of LEGACY_TABLES, so count_legacy_queued_rows() reads independently of
	 * that list's order.
	 *
	 * @var string
	 */
	private const LEGACY_NOTIFICATIONS_TABLE = Constants::LEGACY_NOTIFICATIONS_TABLE;

	/**
	 * Class defined by the legacy extension when it is active. Used only to decide whether
	 * the `is_queued='on'` flag can mean anything - no other queue will ever drain it.
	 *
	 * @var string
	 */
	private const LEGACY_EXTENSION_CLASS = 'WC_Back_In_Stock';

	/**
	 * The Core stock notifications data store, used to resolve the target table names.
	 *
	 * @var StockNotificationsDataStore
	 */
	private StockNotificationsDataStore $data_store;

	/**
	 * Init the service.
	 *
	 * @internal
	 *
	 * @param StockNotificationsDataStore $data_store The Core stock notifications data store.
	 */
	final public function init( StockNotificationsDataStore $data_store ): void {
		$this->data_store = $data_store;
	}

	/**
	 * Check whether a migration run may start or continue.
	 *
	 * @return true|WP_Error True when every requirement is met, otherwise a `WP_Error`
	 *                       carrying a translated, merchant-facing reason.
	 */
	public function check() {
		if ( ! FeaturesUtil::feature_is_enabled( StockNotifications::FEATURE_NAME ) ) {
			return new WP_Error(
				'feature_disabled',
				__( 'The "Customer stock notifications" feature is off. Turn it on under WooCommerce → Settings → Advanced → Features, then run the migration again.', 'woocommerce' )
			);
		}

		$missing_legacy_table = $this->find_missing_table( self::LEGACY_TABLES );
		if ( null !== $missing_legacy_table ) {
			return new WP_Error(
				'legacy_tables_missing',
				sprintf(
					/* translators: %s: database table name */
					__( 'The legacy Back In Stock Notifications table "%s" was not found. There is nothing to migrate.', 'woocommerce' ),
					$missing_legacy_table
				)
			);
		}

		$missing_target_table = $this->find_missing_table(
			array(
				$this->data_store->get_table_name(),
				$this->data_store->get_meta_table_name(),
			),
			false
		);
		if ( null !== $missing_target_table ) {
			return new WP_Error(
				'target_tables_missing',
				sprintf(
					/* translators: %s: database table name */
					__( 'The Stock Notifications table "%s" does not exist yet. Update WooCommerce to the latest version, then run the migration again.', 'woocommerce' ),
					$missing_target_table
				)
			);
		}

		return true;
	}

	/**
	 * Count legacy rows still queued by the legacy extension's own send processor.
	 *
	 * The `is_queued='on'` flag can only mean something while the extension that writes and
	 * drains it is active; with it inactive, nothing will ever drain that queue, so the flag
	 * is ignored entirely and this returns 0 without a query.
	 *
	 * Shared by the CLI `run` pre-flight and the Tools `start` pre-flight: both refuse to
	 * start a migration while legacy rows are still queued, since starting anyway risks the
	 * legacy extension and Core both sending the same notification.
	 *
	 * @return int
	 */
	public function count_legacy_queued_rows(): int {
		if ( ! class_exists( self::LEGACY_EXTENSION_CLASS ) ) {
			return 0;
		}

		global $wpdb;

		$table = $wpdb->prefix . self::LEGACY_NOTIFICATIONS_TABLE;

		// $table is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE is_queued = %s", 'on' );
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $sql was built with $wpdb->prepare() above.
	}

	/**
	 * Find the first table, from a list of table names, that does not exist.
	 *
	 * @param string[] $tables      Table names to check.
	 * @param bool     $needs_prefix Whether the names still need `$wpdb->prefix` applied.
	 * @return string|null The first missing table name, or null when all exist.
	 */
	private function find_missing_table( array $tables, bool $needs_prefix = true ): ?string {
		global $wpdb;

		foreach ( $tables as $table ) {
			$table_name = $needs_prefix ? $wpdb->prefix . $table : $table;

			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

			if ( $found !== $table_name ) {
				return $table_name;
			}
		}

		return null;
	}
}
