<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications\Migration\Helpers;

/**
 * Creates and seeds the legacy Back In Stock Notifications tables for migration tests.
 *
 * The legacy extension is not installed in the test environment, so its three tables are
 * created here with the same schema the extension's installer uses. Column defaults match
 * the legacy schema so a seeded row that omits a column looks exactly like one the
 * extension would have written.
 */
class LegacyStore {

	/**
	 * Verification key seeded by add_verification_data() unless a test says otherwise.
	 *
	 * @var string
	 */
	public const VERIFICATION_KEY = 'this-is-a-32-byte-verify-key-ab';

	/**
	 * Verification iv seeded by add_verification_data() unless a test says otherwise.
	 *
	 * @var string
	 */
	public const VERIFICATION_IV = 'verify-iv-16-byt';

	/**
	 * Create the three legacy tables, dropping any left over from a previous test.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		self::drop_tables();

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		// Table names are $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}woocommerce_bis_notifications (
				`id` BIGINT UNSIGNED NOT NULL auto_increment,
				`type` VARCHAR(128) default 'one-time' NOT NULL,
				`product_id` BIGINT UNSIGNED NOT NULL,
				`user_id` BIGINT UNSIGNED NOT NULL,
				`user_email` VARCHAR(191) NOT NULL,
				`create_date` INT UNSIGNED default 0 NOT NULL,
				`subscribe_date` INT UNSIGNED default 0 NOT NULL,
				`last_notified_date` INT UNSIGNED default 0 NOT NULL,
				`is_queued` CHAR(3) default 'off' NOT NULL,
				`is_active` CHAR(3) default 'off' NOT NULL,
				`is_verified` CHAR(3) default 'yes' NOT NULL,
				PRIMARY KEY  (`id`),
				KEY `product_id` (`product_id`)
			) {$collate}"
		);

		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}woocommerce_bis_notificationsmeta (
				meta_id BIGINT UNSIGNED NOT NULL auto_increment,
				bis_notifications_id BIGINT UNSIGNED NOT NULL,
				meta_key varchar(191) default NULL,
				meta_value longtext NULL,
				PRIMARY KEY  (meta_id),
				KEY bis_notifications_id (bis_notifications_id),
				KEY meta_key (meta_key(191))
			) {$collate}"
		);

		$wpdb->query(
			"CREATE TABLE {$wpdb->prefix}woocommerce_bis_activity (
				`id` BIGINT UNSIGNED NOT NULL auto_increment,
				`notification_id` BIGINT UNSIGNED NOT NULL,
				`product_id` BIGINT UNSIGNED NOT NULL,
				`type` VARCHAR(20) NOT NULL,
				`user_id` BIGINT UNSIGNED NOT NULL,
				`user_email` VARCHAR(255) NOT NULL,
				`object_id` BIGINT UNSIGNED default 0 NOT NULL,
				`date` INT UNSIGNED NOT NULL,
				`note` text NULL,
				PRIMARY KEY  (`id`),
				KEY `notification_id` (`notification_id`)
			) {$collate}"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Create the Core stock notification tables for the current site.
	 *
	 * WooCommerce installs these once, so a blog created mid-test never gets them. Cloning
	 * the main site's definitions keeps this in step with the real schema without
	 * duplicating it here.
	 *
	 * @return void
	 */
	public static function create_core_tables(): void {
		global $wpdb;

		foreach ( array( 'wc_stock_notifications', 'wc_stock_notificationmeta' ) as $table ) {
			// Table names are $wpdb->prefix-based, never user input.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$table} LIKE {$wpdb->base_prefix}{$table}" );
		}
	}

	/**
	 * Drop the three legacy tables.
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		foreach ( array( 'woocommerce_bis_notifications', 'woocommerce_bis_notificationsmeta', 'woocommerce_bis_activity' ) as $table ) {
			// Table names are $wpdb->prefix-based, never user input.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" );
		}
	}

	/**
	 * Empty the Core stock notification tables and the legacy ones, without dropping them.
	 *
	 * @return void
	 */
	public static function truncate_all(): void {
		global $wpdb;

		$tables = array(
			'woocommerce_bis_notifications',
			'woocommerce_bis_notificationsmeta',
			'woocommerce_bis_activity',
			'wc_stock_notifications',
			'wc_stock_notificationmeta',
		);

		foreach ( $tables as $table ) {
			// Table names are $wpdb->prefix-based, never user input.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
			$wpdb->query( "DELETE FROM {$wpdb->prefix}{$table}" );
		}
	}

	/**
	 * Insert one legacy notification row.
	 *
	 * @param array<string,mixed> $overrides Column values overriding the defaults.
	 * @return int The inserted legacy id.
	 */
	public static function add_notification( array $overrides = array() ): int {
		global $wpdb;

		$row = array_merge(
			array(
				'type'               => 'one-time',
				'product_id'         => 0,
				'user_id'            => 0,
				'user_email'         => 'shopper@example.com',
				'create_date'        => 1600000000,
				'subscribe_date'     => 1600000000,
				'last_notified_date' => 0,
				'is_queued'          => 'off',
				'is_active'          => 'on',
				'is_verified'        => 'yes',
			),
			$overrides
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_bis_notifications', $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Insert one legacy notification meta row.
	 *
	 * @param int    $legacy_id  Legacy notification id.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value, serialized when not scalar.
	 * @return void
	 */
	public static function add_meta( int $legacy_id, string $meta_key, $meta_value ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'woocommerce_bis_notificationsmeta',
			array(
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Column names in the legacy meta table, not a query argument.
				'meta_key'             => $meta_key,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- As above.
				'meta_value'           => maybe_serialize( $meta_value ),
				'bis_notifications_id' => $legacy_id,
			)
		);
	}

	/**
	 * Seed the legacy verification secrets a pending row carries.
	 *
	 * Mirrors `WC_BIS_Notification_Data::setup_verification_data()`: a code plus the
	 * per-row AES key and iv the verification hash is built from, and the timestamp the
	 * link's expiry is measured from.
	 *
	 * @param int    $legacy_id  Legacy notification id.
	 * @param string $code       Verification code.
	 * @param int    $created_at Timestamp the verification data was created at.
	 * @param string $key        Verification key.
	 * @param string $iv         Verification iv.
	 * @return void
	 */
	public static function add_verification_data( int $legacy_id, string $code, int $created_at, string $key = self::VERIFICATION_KEY, string $iv = self::VERIFICATION_IV ): void {
		self::add_meta( $legacy_id, '_verification_code', $code );
		self::add_meta( $legacy_id, '_verification_key', $key );
		self::add_meta( $legacy_id, '_verification_iv', $iv );
		self::add_meta( $legacy_id, '_verification_created_at', $created_at );
	}

	/**
	 * Insert one legacy activity row.
	 *
	 * @param int                 $legacy_id Legacy notification id.
	 * @param string              $type      Activity type, e.g. `unsubscribed`.
	 * @param int                 $date      Activity date, as a Unix timestamp.
	 * @param array<string,mixed> $overrides Other column values.
	 * @return void
	 */
	public static function add_activity( int $legacy_id, string $type, int $date, array $overrides = array() ): void {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'woocommerce_bis_activity',
			array_merge(
				array(
					'notification_id' => $legacy_id,
					'product_id'      => 0,
					'type'            => $type,
					'user_id'         => 0,
					'user_email'      => 'shopper@example.com',
					'object_id'       => 0,
					'date'            => $date,
					'note'            => '',
				),
				$overrides
			)
		);
	}

	/**
	 * Insert a Core notification row directly, bypassing `Notification::save()`.
	 *
	 * Used for rows Core's own validation would refuse today - an address that fails
	 * `is_email()`, for instance - which still exist on real stores.
	 *
	 * @param array<string,mixed> $overrides Column values overriding the defaults.
	 * @return int The inserted notification id.
	 */
	public static function add_core_notification( array $overrides = array() ): int {
		global $wpdb;

		$row = array_merge(
			array(
				'product_id'        => 0,
				'user_id'           => 0,
				'user_email'        => 'shopper@example.com',
				'status'            => 'active',
				'date_created_gmt'  => gmdate( 'Y-m-d H:i:s', 1600000000 ),
				'date_modified_gmt' => gmdate( 'Y-m-d H:i:s', 1600000000 ),
			),
			$overrides
		);

		$wpdb->insert( $wpdb->prefix . 'wc_stock_notifications', $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Insert one Core meta row directly, for a target the migration will consider adopting.
	 *
	 * @param int    $notification_id Core notification id.
	 * @param string $meta_key        Meta key.
	 * @param string $meta_value      Stored value, already serialized if it needs to be.
	 * @return void
	 */
	public static function add_core_meta( int $notification_id, string $meta_key, string $meta_value ): void {
		global $wpdb;

		// phpcs:disable WordPress.DB.SlowDBQuery -- Column names of the meta table this inserts into, not query args.
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prefix . 'wc_stock_notificationmeta',
			array(
				'notification_id' => $notification_id,
				'meta_key'        => $meta_key,
				'meta_value'      => $meta_value,
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery
	}

	/**
	 * Read every Core notification row, ascending by id.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_core_rows(): array {
		global $wpdb;

		// Table name is $wpdb->prefix-based, never user input.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wc_stock_notifications ORDER BY id ASC", ARRAY_A );
	}

	/**
	 * Read the Core meta values stored under one key, keyed by notification id.
	 *
	 * Matches on a prefix, because the migration's own markers end in the legacy id they
	 * were written for. Passing a whole key still works: it is its own prefix.
	 *
	 * @param string $meta_key Meta key, or the prefix shared by a family of them.
	 * @return array<int,array<int,string>> Values per notification id.
	 */
	public static function get_core_meta( string $meta_key ): array {
		global $wpdb;

		// Table name is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT notification_id, meta_value FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE meta_key LIKE %s ORDER BY id ASC",
				$wpdb->esc_like( $meta_key ) . '%'
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		$indexed = array();

		foreach ( $rows as $row ) {
			$indexed[ (int) $row['notification_id'] ][] = (string) $row['meta_value'];
		}

		return $indexed;
	}

	/**
	 * Read the Core meta keys beginning with one prefix, in insertion order.
	 *
	 * @param string $prefix Meta key prefix.
	 * @return string[]
	 */
	public static function get_core_meta_keys( string $prefix ): array {
		global $wpdb;

		// Table name is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$keys = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_key FROM {$wpdb->prefix}wc_stock_notificationmeta WHERE meta_key LIKE %s ORDER BY id ASC",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map( 'strval', $keys );
	}

	/**
	 * Read the legacy meta values stored under one key for one legacy row.
	 *
	 * @param int    $legacy_id Legacy notification id.
	 * @param string $meta_key  Meta key.
	 * @return array<int,mixed> Values, unserialized.
	 */
	public static function get_legacy_meta( int $legacy_id, string $meta_key ): array {
		global $wpdb;

		// Table name is $wpdb->prefix-based, never user input.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$values = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}woocommerce_bis_notificationsmeta WHERE bis_notifications_id = %d AND meta_key = %s",
				$legacy_id,
				$meta_key
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		return array_map( 'maybe_unserialize', $values );
	}
}
