<?php
/**
 * Integration tests for SchemaInstaller.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller
 */
class SchemaInstallerTest extends EngineIntegrationTestCase {

	/**
	 * The baseline tables the installer owns, including the cycle tables.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function table_provider(): array {
		return array(
			array( SchemaInstaller::TABLE_PLANS ),
			array( SchemaInstaller::TABLE_CONTRACTS ),
			array( SchemaInstaller::TABLE_CONTRACT_ITEMS ),
			array( SchemaInstaller::TABLE_CONTRACT_ADDRESSES ),
			array( SchemaInstaller::TABLE_CONTRACT_META ),
			array( SchemaInstaller::TABLE_CYCLES ),
			array( SchemaInstaller::TABLE_SNAPSHOTS ),
		);
	}

	/**
	 * @dataProvider table_provider
	 *
	 * @param string $logical Logical table identifier.
	 */
	public function test_each_baseline_table_exists( string $logical ): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( $logical );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$this->assertSame( $table, $found, "Expected table {$table} to exist." );
	}

	public function test_version_option_is_set_after_install(): void {
		$this->assertTrue( SchemaInstaller::is_current() );
		$this->assertSame( SchemaInstaller::get_version(), SchemaInstaller::get_database_version() );
	}

	public function test_install_is_idempotent(): void {
		// Running install again must not error or change the recorded version.
		SchemaInstaller::install();

		$this->assertSame( SchemaInstaller::get_version(), SchemaInstaller::get_database_version() );
	}

	public function test_unknown_table_identifier_throws(): void {
		$this->expectException( \InvalidArgumentException::class );
		SchemaInstaller::get_table_name( 'not_a_table' );
	}

	/**
	 * @testdox A chain is the pair (contract_id, kind), so there is no chains table.
	 */
	public function test_there_is_no_chains_table(): void {
		global $wpdb;

		$chains = $wpdb->prefix . 'wc_subscription_chains';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $chains ) );

		$this->assertNull( $found, 'Did not expect a wc_subscription_chains table.' );
	}

	/**
	 * @testdox The chains logical identifier is no longer registered.
	 */
	public function test_chains_logical_identifier_is_unknown(): void {
		$this->expectException( \InvalidArgumentException::class );
		SchemaInstaller::get_table_name( 'chains' );
	}

	public function test_plans_table_has_extension_slug_column(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'extension_slug' ) );

		$this->assertSame( 'extension_slug', $column );
	}

	public function test_plans_table_has_status_and_sort_order_columns(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_PLANS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$status = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'status' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sort_order = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'sort_order' ) );

		$this->assertSame( 'status', $status );
		$this->assertSame( 'sort_order', $sort_order );
	}

	public function test_contracts_table_has_extension_slug_column(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$column = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'extension_slug' ) );

		$this->assertSame( 'extension_slug', $column );
	}

	public function test_contracts_table_has_the_live_source_of_truth_columns(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$columns = array(
			'id',
			'status',
			'customer_id',
			'currency',
			'selling_plan_id',
			'origin_order_id',
			'extension_slug',
			'payment_method',
			'payment_method_title',
			'payment_token_id',
			'start_gmt',
			'next_payment_gmt',
			'plan_snapshot_id',
			'items_snapshot_id',
			'billing_total',
			'discount_total',
			'shipping_total',
			'tax_total',
			'last_payment_gmt',
			'last_attempt_gmt',
			'trial_end_gmt',
			'end_gmt',
			'schedule_source',
		);

		foreach ( $columns as $column ) {
			$this->assertTrue( $this->has_column( $table, $column ), "Expected contracts.{$column} column." );
		}
	}

	/**
	 * @testdox origin_order_id is nullable so a manual/admin contract can omit it.
	 */
	public function test_contracts_origin_order_id_is_nullable(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'origin_order_id' ), ARRAY_A );

		$this->assertIsArray( $row, 'Expected a contracts.origin_order_id column.' );
		$this->assertArrayHasKey( 'Null', $row );
		$this->assertIsString( $row['Null'] );
		$this->assertSame( 'YES', $row['Null'], 'Expected contracts.origin_order_id to be NULLable.' );
	}

	/**
	 * @testdox The contract row has no generic cycle_count - counters are per-chain and derived.
	 */
	public function test_contracts_table_has_no_generic_cycle_count(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$this->assertFalse(
			$this->has_column( $table, 'cycle_count' ),
			'Did not expect a generic contracts.cycle_count column; per-chain counts are derived from the cycle rows.'
		);
	}

	/**
	 * @testdox The contract carries the live config: the four totals and the four stamps.
	 */
	public function test_contracts_table_carries_live_config_columns(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// These are live values on the contract (the source of truth), not caches of
		// cycles: the four recurring totals and the four lifecycle stamps.
		$live_config = array(
			'billing_total',
			'discount_total',
			'shipping_total',
			'tax_total',
			'last_payment_gmt',
			'last_attempt_gmt',
			'trial_end_gmt',
			'end_gmt',
		);

		foreach ( $live_config as $column ) {
			$this->assertTrue( $this->has_column( $table, $column ), "Expected the live contracts.{$column} column." );
		}
	}

	/**
	 * @testdox The contract carries the latest/live snapshot references.
	 */
	public function test_contracts_table_carries_latest_snapshot_refs(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$this->assertTrue( $this->has_column( $table, 'plan_snapshot_id' ), 'Expected contracts.plan_snapshot_id.' );
		$this->assertTrue( $this->has_column( $table, 'items_snapshot_id' ), 'Expected contracts.items_snapshot_id.' );
	}

	public function test_contracts_due_index_keys_the_next_bill_cache(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$this->assertContains( 'due', $this->index_names( $table ) );
		$this->assertSame(
			array( 'next_payment_gmt', 'status' ),
			$this->index_columns( $table, 'due' )
		);
	}

	/**
	 * @testdox The due_contract index keys the dispatcher scan as (status, next_payment_gmt).
	 */
	public function test_contracts_due_contract_index_keys_the_dispatcher_scan(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// The dispatcher scans status=active AND next_payment_gmt <= now; the status-first
		// column order is load-bearing for the index, so assert it exactly.
		$this->assertContains( 'due_contract', $this->index_names( $table ) );
		$this->assertSame(
			array( 'status', 'next_payment_gmt' ),
			$this->index_columns( $table, 'due_contract' )
		);
	}

	public function test_cycles_table_has_expected_columns(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$columns = array(
			'id',
			'contract_id',
			'kind',
			'sequence_no',
			'count',
			'status',
			'reason',
			'starts_at_gmt',
			'ends_at_gmt',
			'expected_total',
			'currency',
			'plan_snapshot_id',
			'items_snapshot_id',
			'order_id',
			'extension_slug',
			'claimed_until',
			'retry_at',
		);

		foreach ( $columns as $column ) {
			$this->assertTrue( $this->has_column( $table, $column ), "Expected cycles.{$column} column." );
		}
	}

	/**
	 * @testdox The cycle dispatcher columns (claimed_until lease, reserved retry_at) are nullable.
	 */
	public function test_cycles_table_dispatcher_columns_are_nullable(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		foreach ( array( 'claimed_until', 'retry_at' ) as $column ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ), ARRAY_A );

			$this->assertIsArray( $row, "Expected a cycles.{$column} column." );
			$this->assertArrayHasKey( 'Null', $row );
			$this->assertIsString( $row['Null'] );
			$this->assertSame( 'YES', $row['Null'], "Expected cycles.{$column} to be NULLable." );
		}
	}

	/**
	 * @testdox A chain is (contract_id, kind), so cycles carry no chain_id column.
	 */
	public function test_cycles_table_has_no_chain_id_column(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertFalse(
			$this->has_column( $table, 'chain_id' ),
			'Did not expect a cycles.chain_id column; a chain is the pair (contract_id, kind).'
		);
	}

	public function test_cycles_table_has_all_expected_indexes(): void {
		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$indexes = $this->index_names( $table );

		$this->assertContains( 'chain_seq', $indexes, 'Expected the UNIQUE (contract_id, kind, sequence_no) index.' );
		$this->assertContains( 'chain_count', $indexes, 'Expected the UNIQUE (contract_id, kind, count) index.' );
		$this->assertContains( 'due', $indexes, 'Expected the due (kind, status, starts_at_gmt) index.' );
		$this->assertContains( 'order_id', $indexes, 'Expected the (order_id) index.' );
		$this->assertContains( 'contract_kind', $indexes, 'Expected the (contract_id, kind) index.' );
	}

	public function test_cycles_chain_sequence_index_columns_are_in_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertSame(
			array( 'contract_id', 'kind', 'sequence_no' ),
			$this->index_columns( $table, 'chain_seq' )
		);
	}

	public function test_cycles_chain_count_index_columns_are_in_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertSame(
			array( 'contract_id', 'kind', 'count' ),
			$this->index_columns( $table, 'chain_count' )
		);
	}

	public function test_cycles_due_index_columns_are_in_scan_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// The dispatcher scans the due index as (kind, status, starts_at_gmt); the
		// column order is load-bearing, so assert it exactly, not just existence.
		$this->assertSame(
			array( 'kind', 'status', 'starts_at_gmt' ),
			$this->index_columns( $table, 'due' )
		);
	}

	public function test_cycles_contract_kind_index_columns_are_in_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertSame(
			array( 'contract_id', 'kind' ),
			$this->index_columns( $table, 'contract_kind' )
		);
	}

	public function test_cycles_chain_sequence_index_is_unique(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertTrue(
			$this->index_is_unique( $table, 'chain_seq' ),
			'Expected the (contract_id, kind, sequence_no) index to be UNIQUE.'
		);
	}

	/**
	 * @testdox The chain_count index is UNIQUE so (contract_id, kind, count) is the per-charge idempotency anchor.
	 */
	public function test_cycles_chain_count_index_is_unique(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		$this->assertTrue(
			$this->index_is_unique( $table, 'chain_count' ),
			'Expected the (contract_id, kind, count) index to be UNIQUE.'
		);
	}

	/**
	 * @testdox A nullable count lets multiple non-counting cycles coexist under the UNIQUE chain_count index.
	 */
	public function test_cycles_count_column_is_nullable(): void {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'count' ), ARRAY_A );

		$this->assertIsArray( $row, 'Expected a cycles.count column.' );
		$this->assertArrayHasKey( 'Null', $row );
		$this->assertIsString( $row['Null'] );
		$this->assertSame( 'YES', $row['Null'], 'Expected cycles.count to be NULLable so NULL counts coexist.' );
	}

	public function test_snapshots_table_has_expected_columns(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		$columns = array(
			'id',
			'contract_id',
			'snapshot_type',
			'parent_id',
			'schema_version',
			'payload',
			'date_created_gmt',
		);

		foreach ( $columns as $column ) {
			$this->assertTrue( $this->has_column( $table, $column ), "Expected snapshots.{$column} column." );
		}
	}

	/**
	 * @testdox Snapshots are deduped by copy-forward, so there is no content_hash column.
	 */
	public function test_snapshots_table_has_no_content_hash_column(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		$this->assertFalse(
			$this->has_column( $table, 'content_hash' ),
			'Did not expect a snapshots.content_hash column; dedup is by copy-forward, not content hashing.'
		);
	}

	/**
	 * @testdox The owner lives on the cycle, not the snapshot, so snapshots have no extension_slug column.
	 */
	public function test_snapshots_table_has_no_extension_slug_column(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		$this->assertFalse(
			$this->has_column( $table, 'extension_slug' ),
			'Did not expect a snapshots.extension_slug column; the owner is recorded on the cycle.'
		);
	}

	public function test_snapshots_table_has_contract_type_and_parent_indexes(): void {
		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );
		$indexes = $this->index_names( $table );

		$this->assertContains( 'contract_type', $indexes, 'Expected the (contract_id, snapshot_type) index.' );
		$this->assertContains( 'parent', $indexes, 'Expected the (parent_id) index.' );
	}

	public function test_snapshots_contract_type_index_columns_are_in_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		$this->assertSame(
			array( 'contract_id', 'snapshot_type' ),
			$this->index_columns( $table, 'contract_type' )
		);
	}

	public function test_snapshots_parent_index_columns_are_in_order(): void {
		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		$this->assertSame(
			array( 'parent_id' ),
			$this->index_columns( $table, 'parent' )
		);
	}

	/**
	 * Whether `$table` has a column named `$column`.
	 *
	 * @param string $table  Prefixed table name.
	 * @param string $column Column name.
	 */
	private function has_column( string $table, string $column ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );

		return $column === $found;
	}

	/**
	 * The set of index names defined on `$table`.
	 *
	 * @param string $table Prefixed table name.
	 * @return array<int, string>
	 */
	private function index_names( string $table ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SHOW INDEX FROM {$table}", ARRAY_A );

		$names = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$names[] = ScalarCoercion::coerce_string( $row['Key_name'] ?? null );
			}
		}

		return array_values( array_unique( $names ) );
	}

	/**
	 * The ordered column names of a named index on `$table`.
	 *
	 * @param string $table     Prefixed table name.
	 * @param string $key_name  Index name.
	 * @return array<int, string> Column names in index order (by Seq_in_index).
	 */
	private function index_columns( string $table, string $key_name ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", $key_name ), ARRAY_A );

		$rows = is_array( $rows ) ? $rows : array();
		usort(
			$rows,
			static function ( $a, $b ): int {
				$a_seq = is_array( $a ) ? ScalarCoercion::coerce_int( $a['Seq_in_index'] ?? null ) : 0;
				$b_seq = is_array( $b ) ? ScalarCoercion::coerce_int( $b['Seq_in_index'] ?? null ) : 0;

				return $a_seq <=> $b_seq;
			}
		);

		$columns = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$columns[] = ScalarCoercion::coerce_string( $row['Column_name'] ?? null );
			}
		}

		return $columns;
	}

	/**
	 * Whether the named index on `$table` is UNIQUE.
	 *
	 * @param string $table    Prefixed table name.
	 * @param string $key_name Index name.
	 */
	private function index_is_unique( string $table, string $key_name ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", $key_name ), ARRAY_A );

		$rows = is_array( $rows ) ? $rows : array();
		if ( empty( $rows ) ) {
			return false;
		}

		foreach ( $rows as $row ) {
			// Non_unique = 0 marks a UNIQUE index.
			if ( is_array( $row ) && '0' !== ScalarCoercion::coerce_string( $row['Non_unique'] ?? null ) ) {
				return false;
			}
		}

		return true;
	}
}
