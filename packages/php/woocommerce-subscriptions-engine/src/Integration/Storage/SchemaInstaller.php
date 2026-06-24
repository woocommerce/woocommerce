<?php
/**
 * SchemaInstaller - owns the engine's baseline database tables.
 *
 * Creates and drops the plan tables (`wc_selling_plan_groups`,
 * `wc_selling_plans`), the contract tables (`wc_subscription_contracts`,
 * `wc_subscription_contract_items`, `wc_subscription_contract_addresses`,
 * `wc_subscription_contract_meta`), and the cycle tables
 * (`wc_subscription_cycles`, `wc_subscription_snapshots`). Mirrors the
 * order/HPOS conventions: BIGINT UNSIGNED ids, `*_gmt` datetime columns, JSON
 * columns for policy bundles, no foreign-key constraints.
 *
 * A chain is not a stored table: it is the pair `(contract_id, kind)` on the
 * cycle rows, and its head and counters are derived from those rows.
 *
 * Pre-freeze, tables are private and mutable: schema changes ship via a
 * `VERSION` bump that re-runs dbDelta, not migrations.
 *
 * The engine is bundled rather than independently activated, so install runs
 * through {@see self::maybe_install()} (a version-gated check on boot), not a
 * plugin activation hook.
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
	 * 1.0.0 - baseline plan and contract tables, including the nullable `extension_slug`
	 *         column on plans and contracts.
	 * 2.0.0 - cycle-chain model. The contract is the live source of truth: it gains the
	 *         live schedule, the latest/live snapshot references, the four live totals,
	 *         and the four live stamps, with a nullable `origin_order_id`. Cycles are
	 *         immutable billing records keyed on `(contract_id, kind)` with `count`,
	 *         `order_id`, and `extension_slug` (no chains table). Per-contract typed
	 *         snapshots are deduped by copy-forward (no content hash).
	 *
	 * Pre-freeze, the tables are private and recreated rather than migrated. dbDelta
	 * adds new columns but does not change an existing column's nullability or drop
	 * values that are no longer used, so a development box carrying an earlier schema
	 * must drop and recreate the tables (and clear VERSION_OPTION) to pick up such
	 * changes - in-place column ALTERs and data backfills arrive with the freeze.
	 */
	const VERSION = '2.0.0';

	/**
	 * Option key tracking the installed schema version.
	 */
	const VERSION_OPTION = 'wc_subscriptions_engine_db_version';

	/**
	 * Logical table identifiers - keys map to unprefixed table names.
	 */
	const TABLE_PLAN_GROUPS        = 'plan_groups';
	const TABLE_PLANS              = 'plans';
	const TABLE_CONTRACTS          = 'contracts';
	const TABLE_CONTRACT_ITEMS     = 'contract_items';
	const TABLE_CONTRACT_ADDRESSES = 'contract_addresses';
	const TABLE_CONTRACT_META      = 'contract_meta';
	const TABLE_CYCLES             = 'cycles';
	const TABLE_SNAPSHOTS          = 'snapshots';

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
	 * Whether the installed schema version matches SchemaInstaller::VERSION.
	 */
	public static function is_current(): bool {
		return self::VERSION === get_option( self::VERSION_OPTION );
	}

	/**
	 * Map of logical => prefixed table names, keyed by TABLE_* constants.
	 *
	 * Contract tables use the `wc_subscription_*` prefix (what the data
	 * represents), while the namespace boundary is about code ownership.
	 *
	 * @param string $prefix Usually `$wpdb->prefix`.
	 * @return array<string, string>
	 */
	private static function get_table_names( string $prefix ): array {
		return array(
			self::TABLE_PLAN_GROUPS        => $prefix . 'wc_selling_plan_groups',
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
	 * The dbDelta function is fussy: each column on its own line, two spaces
	 * between name and type, `KEY` (not `INDEX`), no trailing comma before
	 * PRIMARY KEY. Do not reformat these without re-testing dbDelta diffing - it
	 * parses with regex.
	 *
	 * @param array<string, string> $names   Map of logical => prefixed table names.
	 * @param string                $collate Charset/collate clause from $wpdb.
	 * @return array<int, string>
	 */
	private static function get_table_definitions( array $names, string $collate ): array {
		$plan_groups        = $names[ self::TABLE_PLAN_GROUPS ];
		$plans              = $names[ self::TABLE_PLANS ];
		$contracts          = $names[ self::TABLE_CONTRACTS ];
		$contract_items     = $names[ self::TABLE_CONTRACT_ITEMS ];
		$contract_addresses = $names[ self::TABLE_CONTRACT_ADDRESSES ];
		$contract_meta      = $names[ self::TABLE_CONTRACT_META ];
		$cycles             = $names[ self::TABLE_CYCLES ];
		$snapshots          = $names[ self::TABLE_SNAPSHOTS ];

		// `merchant_code` is UNIQUE (not just KEY) for DB-enforced idempotency on
		// consumer-supplied codes. NULL values are allowed and treated as distinct,
		// so consumers that do not use merchant codes are unaffected.
		$plan_groups_sql = "CREATE TABLE {$plan_groups} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  merchant_code VARCHAR(64) NULL,
  options_display JSON NULL,
  app_id VARCHAR(64) NULL,
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY merchant_code (merchant_code),
  KEY app_id (app_id)
) {$collate};";

		// `extension_slug` records the registered slug of the extension that created the
		// plan. Nullable while owner identifier/registration semantics are still
		// open; tightened additively once decided.
		$plans_sql = "CREATE TABLE {$plans} (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  group_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  options JSON NOT NULL,
  billing_policy JSON NOT NULL,
  delivery_policy JSON NULL,
  inventory_policy JSON NULL,
  pricing_policy JSON NULL,
  category VARCHAR(32) NOT NULL DEFAULT 'SUBSCRIPTION',
  extension_slug VARCHAR(64) NULL,
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  KEY group_id (group_id),
  KEY category (category),
  KEY extension_slug (extension_slug)
) {$collate};";

		// The contract row is the live source of truth. It carries the stable identity
		// plus the live schedule (`next_payment_gmt`, which the `due` index keys the
		// scan off), the latest/live snapshot references (`plan_snapshot_id` /
		// `items_snapshot_id`), and the live config values: the four totals
		// (`billing_total` / `discount_total` / `shipping_total` / `tax_total`) and the
		// four stamps (`last_payment_gmt` / `last_attempt_gmt` / `trial_end_gmt` /
		// `end_gmt`). These are live values, not caches of cycles. `origin_order_id` is
		// NULLABLE (a manual/admin contract has no origin order). There is no generic
		// `cycle_count`: counters are per-chain and derived from the cycle rows
		// (`MAX(count)` over `(contract_id, kind)`). `currency` is first-class
		// (forward-compat for multi-currency recurring; today always the store base
		// currency). `schedule_source` distinguishes contracts whose renewals this
		// engine owns from gateway-owned schedules. `extension_slug` mirrors the
		// plans column.
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
		// (contract_id, address_type). Mirrors the order-addresses column shape.
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

		// Immutable billing records, created at billing. A chain is the pair
		// `(contract_id, kind)` - there is no chains table - so cycles carry
		// `contract_id` and `kind` directly. `status` is the charge lifecycle
		// (`pending` -> `billed`/`failed`/`cancelled`).
		// `chain_seq` (UNIQUE on `contract_id, kind, sequence_no`) keeps a
		// chain from holding two cycles at the same position. `chain_count` (UNIQUE
		// on `contract_id, kind, count`) is the per-charge idempotency anchor;
		// `count` is nullable and MySQL treats NULLs as distinct, so non-counting
		// cycles (for example future trial periods) coexist freely. The `due` index
		// keys the dispatcher's due-scan: billing-in-advance fires at
		// `starts_at_gmt`, so (kind, status, starts_at_gmt) is the scan order.
		// `order_id` is a plain indexed reference (not 1:1; an aggregate order may
		// serve many cycles); `extension_slug` records the owning extension for the
		// per-owner gate. `reason` is an open-ended nullable annotation usable by any
		// cycle. `contract_kind` serves targeted per-chain reads (MAX(count), head).
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
  date_created_gmt DATETIME NOT NULL,
  date_updated_gmt DATETIME NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY chain_seq (contract_id, kind, sequence_no),
  UNIQUE KEY chain_count (contract_id, kind, count),
  KEY due (kind, status, starts_at_gmt),
  KEY order_id (order_id),
  KEY contract_kind (contract_id, kind)
) {$collate};";

		// Per-contract typed snapshot payloads, deduped by copy-forward (a new cycle
		// reuses the previous cycle's snapshot id unless the plan/items changed), so
		// there is no content hash. `snapshot_type` distinguishes plan from items
		// payloads; `parent_id` is the weak link back to the source (the plan a plan
		// snapshot was taken from); `schema_version` is the payload-FORMAT version a
		// reader parses/upcasts by, not the plan's content version. LONGTEXT payload
		// for the MySQL 5.6 floor (no JSON column type).
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
			$plan_groups_sql,
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
