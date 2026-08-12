<?php
/**
 * Owns the engine's baseline database tables (plan, contract, and cycle tables).
 * Mirrors the order/HPOS conventions: BIGINT UNSIGNED ids, `*_gmt` datetime
 * columns, JSON columns for policy bundles, no foreign-key constraints. A chain is
 * not a stored table: it is the pair `(contract_id, kind)` on the cycle rows.
 *
 * Pre-freeze, tables are private and mutable: schema changes ship via a `VERSION`
 * bump that re-runs dbDelta, not migrations. Install runs through
 * {@see self::maybe_install()} (a version-gated check on boot), as the engine is
 * bundled rather than independently activated.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

defined( 'ABSPATH' ) || exit;

/**
 * Schema installer and table-name resolver.
 */
final class SchemaInstaller {

	/**
	 * Schema version. Bump when the CREATE TABLE statements change so the
	 * version-gated install runs dbDelta again.
	 *
	 * 1.0.0 - baseline plan and contract tables.
	 * 2.0.0 - cycle-chain model: contract as live source of truth (schedule, snapshot
	 *         references, totals, stamps); immutable cycle records keyed on
	 *         `(contract_id, kind)`; per-contract snapshots deduped by copy-forward.
	 * 2.1.0 - rename `app_id` to `extension_slug` in plan_groups table.
	 * 2.1.1 - add `status` and `sort_order` columns to plans table.
	 * 2.2.0 - dispatcher columns: cycle `claimed_until` (crash-recovery lease) and
	 *         reserved `retry_at`; a `due_contract (status, next_payment_gmt)` index on
	 *         contracts for the batch renewal scan.
	 * 2.3.0 - catalog flatten: drop the plan_groups table; plans lose group_id and
	 *         options, gain merchant_code (UNIQUE).
	 *
	 * Pre-freeze, tables are recreated rather than migrated. dbDelta adds columns but
	 * does not change an existing column's nullability or drop unused ones, so a dev box
	 * on an earlier schema must drop and recreate the tables (and clear VERSION_OPTION)
	 * to pick up such changes - in-place ALTERs and backfills arrive with the freeze.
	 */
	private const VERSION = '2.3.0';

	/**
	 * Option key tracking the installed schema version.
	 */
	private const VERSION_OPTION = 'wc_subscriptions_engine_db_version';

	/**
	 * Logical table identifiers - keys map to unprefixed table names.
	 */
	public const TABLE_PLANS              = 'plans';
	public const TABLE_CONTRACTS          = 'contracts';
	public const TABLE_CONTRACT_ITEMS     = 'contract_items';
	public const TABLE_CONTRACT_ADDRESSES = 'contract_addresses';
	public const TABLE_CONTRACT_META      = 'contract_meta';
	public const TABLE_CYCLES             = 'cycles';
	public const TABLE_SNAPSHOTS          = 'snapshots';

	/**
	 * Resolve a logical identifier to its prefixed table name.
	 *
	 * @param string $logical One of the TABLE_* constants.
	 * @return string Prefixed table name.
	 * @throws \InvalidArgumentException If $logical is unknown.
	 */
	public static function get_table_name( string $logical ): string {
		global $wpdb;

		$names = self::get_table_names( $wpdb->prefix );

		if ( ! isset( $names[ $logical ] ) ) {
			throw new \InvalidArgumentException(
				sprintf( 'Unknown subscriptions-engine table identifier: %s', esc_html( $logical ) )
			);
		}

		return $names[ $logical ];
	}

	/**
	 * Install or upgrade the tables when the stored version is behind the code.
	 *
	 * Cheap to call on every boot: it is a single option read in the common case.
	 */
	public static function maybe_install(): void {
		if ( self::is_current() ) {
			return;
		}

		self::install();
	}

	/**
	 * Install (or upgrade) the tables. Idempotent - dbDelta handles the diff.
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = $wpdb->get_charset_collate();
		$names   = self::get_table_names( $wpdb->prefix );

		foreach ( self::get_table_definitions( $names, $collate ) as $sql ) {
			dbDelta( $sql );
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Drop the tables and clear schema metadata.
	 *
	 * Intended for uninstall paths only, never deactivation.
	 */
	public static function uninstall(): void {
		global $wpdb;

		// TODO: Determine what we should do with the tables when uninstalling - WOOSUBS-1718.

		delete_option( self::VERSION_OPTION );
	}

	/**
	 * Whether the installed schema version matches SchemaInstaller::get_version().
	 */
	public static function is_current(): bool {
		return self::get_version() === self::get_database_version();
	}

	/**
	 * Get the schema version for this class.
	 */
	public static function get_version(): string {
		return self::VERSION;
	}

	/**
	 * Get the installed schema version from the database.
	 */
	public static function get_database_version(): ?string {
		$database_version = get_option( self::VERSION_OPTION );
		if ( ! is_string( $database_version ) ) {
			return null;
		}
		return $database_version;
	}

	/**
	 * Map of logical => prefixed table names, keyed by TABLE_* constants. Contract
	 * tables use the `wc_subscription_*` prefix (what the data represents), independent
	 * of the code-ownership namespace boundary.
	 *
	 * @param string $prefix Usually `$wpdb->prefix`.
	 * @return array<string, string>
	 */
	private static function get_table_names( string $prefix ): array {
		return array(
			self::TABLE_PLANS              => $prefix . 'wc_selling_plans',
			self::TABLE_CONTRACTS          => $prefix . 'wc_subscription_contracts',
			self::TABLE_CONTRACT_ITEMS     => $prefix . 'wc_subscription_contract_items',
			self::TABLE_CONTRACT_ADDRESSES => $prefix . 'wc_subscription_contract_addresses',
			self::TABLE_CONTRACT_META      => $prefix . 'wc_subscription_contract_meta',
			self::TABLE_CYCLES             => $prefix . 'wc_subscription_cycles',
			self::TABLE_SNAPSHOTS          => $prefix . 'wc_subscription_snapshots',
		);
	}

	/**
	 * CREATE TABLE statements, formatted for dbDelta.
	 *
	 * Formatting is fragile: dbDelta parses with regex (each column on its own line,
	 * two spaces between name and type, `KEY` not `INDEX`, no trailing comma before
	 * PRIMARY KEY). Do not reformat these without re-testing dbDelta diffing.
	 *
	 * @param array<string, string> $names   Map of logical => prefixed table names.
	 * @param string                $collate Charset/collate clause from $wpdb.
	 * @return array<int, string>
	 */
	private static function get_table_definitions( array $names, string $collate ): array {
		$plans              = $names[ self::TABLE_PLANS ];
		$contracts          = $names[ self::TABLE_CONTRACTS ];
		$contract_items     = $names[ self::TABLE_CONTRACT_ITEMS ];
		$contract_addresses = $names[ self::TABLE_CONTRACT_ADDRESSES ];
		$contract_meta      = $names[ self::TABLE_CONTRACT_META ];
		$cycles             = $names[ self::TABLE_CYCLES ];
		$snapshots          = $names[ self::TABLE_SNAPSHOTS ];

		// `merchant_code` is DB-enforced-unique per extension (composite with
		// `extension_slug`) for idempotency on consumer-supplied codes - each consumer
		// owns its own code namespace; NULLs are treated as distinct, so consumers that
		// do not use merchant codes are unaffected. `extension_slug` records the creating
		// extension's registered slug. Nullable while owner identifier/registration
		// semantics are still open; tightened additively once decided.
		$plans_sql = "CREATE TABLE {$plans} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  billing_policy JSON NOT NULL,
  delivery_policy JSON NULL,
  inventory_policy JSON NULL,
  pricing_policy JSON NULL,
  category VARCHAR(32) NOT NULL DEFAULT 'SUBSCRIPTION',
  status VARCHAR(20) NOT NULL DEFAULT 'active',
  sort_order INT NOT NULL DEFAULT 0,
  merchant_code VARCHAR(64) NULL,
  extension_slug VARCHAR(64) NULL,
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY extension_merchant_code (extension_slug, merchant_code),
  KEY category (category),
  KEY status_sort (status, sort_order, id),
  KEY extension_slug (extension_slug)
) {$collate};";

		// The contract row is the live source of truth: the totals and stamps are live
		// values, not caches of cycles. The `due_contract (status, next_payment_gmt)` index
		// keys the batch dispatcher's scan (status equality, then a range on the due date);
		// `due` is retained for next-bill-cache lookups keyed the other way. `origin_order_id`
		// is NULLABLE (a manual/admin contract has no origin order). There is no generic
		// `cycle_count` - counters are per-chain, derived as `MAX(count)` over
		// `(contract_id, kind)`. `currency` is first-class (forward-compat for multi-currency
		// recurring; today the store base currency). `schedule_source` distinguishes
		// engine-owned renewals from gateway-owned schedules.
		$contracts_sql = "CREATE TABLE {$contracts} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  status VARCHAR(20) NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  currency CHAR(3) NOT NULL,
  selling_plan_id BIGINT UNSIGNED NOT NULL,
  origin_order_id BIGINT UNSIGNED NULL,
  extension_slug VARCHAR(64) NULL,
  payment_method VARCHAR(100) NULL,
  payment_method_title VARCHAR(200) NULL,
  payment_token_id BIGINT UNSIGNED NULL,
  start_gmt DATETIME NOT NULL,
  next_payment_gmt DATETIME NULL,
  plan_snapshot_id BIGINT UNSIGNED NULL,
  items_snapshot_id BIGINT UNSIGNED NULL,
  billing_total DECIMAL(26,8) NOT NULL DEFAULT 0,
  discount_total DECIMAL(26,8) NOT NULL DEFAULT 0,
  shipping_total DECIMAL(26,8) NOT NULL DEFAULT 0,
  tax_total DECIMAL(26,8) NOT NULL DEFAULT 0,
  last_payment_gmt DATETIME NULL,
  last_attempt_gmt DATETIME NULL,
  trial_end_gmt DATETIME NULL,
  end_gmt DATETIME NULL,
  schedule_source VARCHAR(20) NOT NULL DEFAULT 'primitive',
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY customer_status (customer_id, status),
  KEY due (next_payment_gmt, status),
  KEY due_contract (status, next_payment_gmt),
  KEY origin_order (origin_order_id),
  KEY extension_slug (extension_slug)
) {$collate};";

		$contract_items_sql = "CREATE TABLE {$contract_items} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  item_type VARCHAR(32) NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  variation_id BIGINT UNSIGNED NULL,
  quantity DECIMAL(12,4) NOT NULL DEFAULT 1,
  subtotal DECIMAL(26,8) NOT NULL DEFAULT 0,
  total DECIMAL(26,8) NOT NULL DEFAULT 0,
  taxes JSON NULL,
  PRIMARY KEY  (id),
  KEY contract (contract_id)
) {$collate};";

		// One billing + one shipping address per contract: composite PK on
		// (contract_id, address_type).
		$contract_addresses_sql = "CREATE TABLE {$contract_addresses} (
  contract_id BIGINT UNSIGNED NOT NULL,
  address_type VARCHAR(20) NOT NULL,
  first_name TEXT NULL,
  last_name TEXT NULL,
  company TEXT NULL,
  address_1 TEXT NULL,
  address_2 TEXT NULL,
  city TEXT NULL,
  state TEXT NULL,
  postcode TEXT NULL,
  country TEXT NULL,
  email VARCHAR(320) NULL,
  phone VARCHAR(100) NULL,
  PRIMARY KEY  (contract_id, address_type)
) {$collate};";

		$contract_meta_sql = "CREATE TABLE {$contract_meta} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  meta_key VARCHAR(255) NOT NULL,
  meta_value LONGTEXT NULL,
  PRIMARY KEY  (id),
  KEY contract_key (contract_id, meta_key(100))
) {$collate};";

		// Immutable billing records. A chain is the pair `(contract_id, kind)` - there is
		// no chains table - so cycles carry both directly. `chain_seq` (UNIQUE) keeps a
		// chain from holding two cycles at one position; `chain_count` (UNIQUE) is the
		// per-charge idempotency anchor, with `count` nullable so non-counting cycles
		// (e.g. future trial periods) coexist freely under MySQL's NULL-distinct rule.
		// The `due` index keys the dispatcher's due-scan in (kind, status, starts_at_gmt)
		// order, since billing-in-advance fires at `starts_at_gmt`. `order_id` is non-1:1
		// (an aggregate order may serve many cycles); `contract_kind` serves targeted
		// per-chain reads (MAX(count), head). `claimed_until` is the crash-recovery lease:
		// it is stamped when a `pending` cycle is claimed and a stuck pending cycle past it
		// is reclaimable; the create-as-claim UNIQUE remains the primary concurrency guard.
		// `retry_at` is reserved (additive) for a later retry/dunning pass - not wired yet.
		$cycles_sql = "CREATE TABLE {$cycles} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(20) NOT NULL DEFAULT 'billing',
  sequence_no INT UNSIGNED NOT NULL,
  count INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL,
  reason TEXT NULL,
  starts_at_gmt DATETIME NOT NULL,
  ends_at_gmt DATETIME NOT NULL,
  expected_total DECIMAL(26,8) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL,
  plan_snapshot_id BIGINT UNSIGNED NULL,
  items_snapshot_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  extension_slug VARCHAR(64) NULL,
  claimed_until DATETIME NULL,
  retry_at DATETIME NULL,
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY chain_seq (contract_id, kind, sequence_no),
  UNIQUE KEY chain_count (contract_id, kind, count),
  KEY due (kind, status, starts_at_gmt),
  KEY order_id (order_id),
  KEY contract_kind (contract_id, kind)
) {$collate};";

		// Per-contract typed snapshot payloads, deduped by copy-forward (no content
		// hash). `parent_id` is the weak link back to the source (the plan a plan
		// snapshot was taken from). `schema_version` is the payload-FORMAT version a
		// reader parses/upcasts by, not the plan's content version. LONGTEXT payload for
		// the MySQL 5.6 floor (no JSON column type).
		$snapshots_sql = "CREATE TABLE {$snapshots} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  contract_id BIGINT UNSIGNED NOT NULL,
  snapshot_type VARCHAR(20) NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  schema_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  payload LONGTEXT NOT NULL,
  date_created_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY contract_type (contract_id, snapshot_type),
  KEY parent (parent_id)
) {$collate};";

		return array(
			$plans_sql,
			$contracts_sql,
			$contract_items_sql,
			$contract_addresses_sql,
			$contract_meta_sql,
			$cycles_sql,
			$snapshots_sql,
		);
	}
}
