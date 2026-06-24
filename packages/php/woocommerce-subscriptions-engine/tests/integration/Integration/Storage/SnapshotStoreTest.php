<?php
/**
 * Integration tests for SnapshotStore.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Tests\Integration\Integration\Storage;

use EngineIntegrationTestCase;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SchemaInstaller;
use Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SnapshotStore;

/**
 * @covers \Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage\SnapshotStore
 */
class SnapshotStoreTest extends EngineIntegrationTestCase {

	use ScalarCoercion;

	/**
	 * The System Under Test.
	 *
	 * @var SnapshotStore
	 */
	private $sut;

	public function setUp(): void {
		parent::setUp();
		$this->sut = new SnapshotStore();
	}

	/**
	 * Count the rows in the snapshots table.
	 */
	private function snapshot_row_count(): int {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Read a snapshot row by id.
	 *
	 * @param int $id Snapshot row id.
	 * @return array<string, mixed>|null
	 */
	private function snapshot_row( int $id ): ?array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		return null === $row ? null : $row;
	}

	/**
	 * @testdox insert() writes a snapshot row and returns its id.
	 */
	public function test_insert_writes_a_row_and_returns_its_id(): void {
		$id = $this->sut->insert( 100, SnapshotStore::TYPE_PLAN, 7, array( 'selling_plan_id' => 7 ), 1 );

		$this->assertGreaterThan( 0, $id );
		$this->assertSame( 1, $this->snapshot_row_count() );
	}

	/**
	 * @testdox insert() persists the typed columns (contract, type, parent, schema version).
	 */
	public function test_insert_persists_the_typed_columns(): void {
		$id = $this->sut->insert( 100, SnapshotStore::TYPE_PLAN, 7, array( 'selling_plan_id' => 7 ), 2 );

		$row = $this->snapshot_row( $id );
		$this->assertNotNull( $row );
		$this->assertSame( '100', self::coerce_string( $row['contract_id'] ?? null ) );
		$this->assertSame( SnapshotStore::TYPE_PLAN, $row['snapshot_type'] );
		$this->assertSame( '7', self::coerce_string( $row['parent_id'] ?? null ) );
		$this->assertSame( '2', self::coerce_string( $row['schema_version'] ?? null ) );
	}

	/**
	 * @testdox insert() stores an items snapshot with a null parent_id.
	 */
	public function test_insert_stores_items_snapshot_with_null_parent(): void {
		$id = $this->sut->insert( 100, SnapshotStore::TYPE_ITEMS, null, array( array( 'product_id' => 200 ) ), 1 );

		$row = $this->snapshot_row( $id );
		$this->assertNotNull( $row );
		$this->assertSame( SnapshotStore::TYPE_ITEMS, $row['snapshot_type'] );
		$this->assertNull( $row['parent_id'] );
	}

	/**
	 * @testdox insert() JSON-encodes the payload and round-trips it.
	 */
	public function test_insert_round_trips_the_payload(): void {
		$payload = array(
			'selling_plan_id' => 7,
			'name'            => 'Monthly box',
		);

		$id  = $this->sut->insert( 100, SnapshotStore::TYPE_PLAN, 7, $payload, 1 );
		$row = $this->snapshot_row( $id );

		$this->assertNotNull( $row );
		$this->assertSame( $payload, json_decode( self::coerce_string( $row['payload'] ?? null ), true ) );
	}

	/**
	 * @testdox insert() writes a distinct row per call (dedup is the caller's copy-forward).
	 */
	public function test_insert_writes_a_distinct_row_per_call(): void {
		$first  = $this->sut->insert( 100, SnapshotStore::TYPE_PLAN, 7, array( 'selling_plan_id' => 7 ), 1 );
		$second = $this->sut->insert( 100, SnapshotStore::TYPE_PLAN, 7, array( 'selling_plan_id' => 7 ), 1 );

		$this->assertNotSame( $first, $second, 'Each insert() is a fresh row; reuse is decided by the caller, not the store.' );
		$this->assertSame( 2, $this->snapshot_row_count() );
	}
}
