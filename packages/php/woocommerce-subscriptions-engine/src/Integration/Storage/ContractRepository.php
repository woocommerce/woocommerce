<?php
/**
 * ContractRepository - persistence for the live {@see Contract} plus targeted
 * cycle access.
 *
 * The contract row and its items / addresses / meta are persisted and loaded
 * through this repository, which owns the $wpdb access and spans the
 * contract-side tables. The contract is the live source of truth: its row carries
 * the live schedule, the latest/live snapshot references, and the live config
 * values. A chain is NOT a stored entity: it is the pair `(contract_id, kind)`,
 * and its head and counters are derived from the cycle rows. The `Contract` entity
 * never carries a cycle graph in memory, so cycles are reached through
 * purpose-built reads ({@see self::find_current_cycle()},
 * {@see self::find_cycle_history()}, {@see self::max_count()},
 * {@see self::find_cycles_by_order_id()}) and written one at a time
 * ({@see self::append_cycle()}, {@see self::update_cycle()}).
 *
 * There is no whole-graph `save()`. {@see self::insert()} / {@see self::update()}
 * write the contract row and only its changed children; {@see self::find()}
 * hydrates the contract (no cycles) while {@see self::find_summary()} reads only
 * the contract row for list / guard paths. {@see self::insert_with_origin_cycle()}
 * is the checkout create path: it inserts the contract, freezes the signup cycle's
 * snapshots, records those snapshot ids on the contract too, and inserts cycle 1.
 *
 * Snapshots are deduped by copy-forward: {@see self::append_cycle()} reuses the
 * previous cycle's snapshot id when the plan / items are unchanged, inserting a
 * new row through {@see SnapshotStore} only when they differ.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Live contract repository with targeted cycle access.
 */
final class ContractRepository {

	use ScalarCoercion;

	/**
	 * Address columns persisted to the addresses table.
	 *
	 * @var array<int, string>
	 */
	private const ADDRESS_COLUMNS = array(
		'first_name',
		'last_name',
		'company',
		'address_1',
		'address_2',
		'city',
		'state',
		'postcode',
		'country',
		'email',
		'phone',
	);

	/**
	 * The per-contract typed snapshot store.
	 *
	 * @var SnapshotStore
	 */
	private $snapshots;

	/**
	 * Build a repository over the given snapshot store.
	 *
	 * @param SnapshotStore|null $snapshots Snapshot store; a default instance is
	 *                                      created when omitted.
	 */
	public function __construct( ?SnapshotStore $snapshots = null ) {
		$this->snapshots = $snapshots ?? new SnapshotStore();
	}

	/**
	 * Insert a new contract and its items, addresses, and meta.
	 *
	 * Durable-intent-first within the operation: the parent row is written, its
	 * id captured, then the children. This is the seam a later transaction-handling
	 * change wraps; it deliberately does not open a BEGIN/COMMIT now (a naive
	 * transaction would commit the integration suite's outer test transaction, and
	 * bare savepoints error in production without an ambient transaction), and the
	 * pre-freeze dev tables make a transient partial write low-risk.
	 *
	 * @param Contract $contract Contract to insert.
	 * @return int The new contract id.
	 * @throws \RuntimeException If the contract insert fails.
	 */
	public function insert( Contract $contract ): int {
		global $wpdb;

		$now  = gmdate( 'Y-m-d H:i:s' );
		$data = $contract->to_storage();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ),
			array_merge(
				$data,
				array(
					'date_created_gmt' => $now,
					'date_updated_gmt' => $now,
				)
			)
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert contract.' );
		}

		$id = (int) $wpdb->insert_id;
		$contract->set_id( $id );

		$this->insert_items( $id, $contract->get_items() );
		$this->insert_addresses( $id, $contract->get_addresses() );
		$this->insert_meta( $id, $contract->get_meta() );

		return $id;
	}

	/**
	 * Insert a contract together with its signup cycle (cycle 1).
	 *
	 * The checkout create path. Sync is single-direction downward, so the order is
	 * durable-intent-first: insert the contract -> freeze the signup cycle's plan /
	 * items snapshots (which need the contract id) -> record those snapshot ids on
	 * the contract too (its latest/live references) and update the contract row ->
	 * insert cycle 1. Cycle 1 carries the same snapshot ids as the contract by
	 * construction.
	 *
	 * The cycle is taken as built by the caller (the signup cycle is created
	 * directly `billed`); this method only stamps its contract id, resolves its
	 * snapshots, and inserts it. It is the operation-scoped seam a later
	 * transaction-handling change wraps.
	 *
	 * @param Contract $contract The contract to insert.
	 * @param Cycle    $cycle    The signup cycle (cycle 1), carrying its snapshot value objects.
	 * @return int The new contract id.
	 * @throws \RuntimeException If a contract, snapshot, or cycle write fails.
	 */
	public function insert_with_origin_cycle( Contract $contract, Cycle $cycle ): int {
		$contract_id = $this->insert( $contract );

		// The signup cycle is the first in its chain: no previous to copy-forward
		// from, so its snapshots are inserted fresh and their ids stamped onto it.
		$cycle->set_contract_id( $contract_id );
		$this->resolve_cycle_snapshots( $cycle, null );

		// Record the signup snapshots as the contract's latest/live references, then
		// persist the contract row before the cycle row (durable-intent-first).
		$contract->set_plan_snapshot_id( $cycle->get_plan_snapshot_id() );
		$contract->set_items_snapshot_id( $cycle->get_items_snapshot_id() );
		$this->update_contract_row( $contract );

		$this->insert_cycle( $cycle );

		return $contract_id;
	}

	/**
	 * Persist changes to an existing contract and its child rows.
	 *
	 * Updates the contract row in place, then reconciles the items, addresses,
	 * and meta rows only when they differ from what is stored - an unchanged child
	 * set is left untouched, so the common renewal-cache write (status,
	 * next_payment_gmt) does not churn child rows. This is the operation-scoped
	 * write seam a later transaction-handling change wraps; it does not open a
	 * transaction now (see {@see self::insert()}).
	 *
	 * @param Contract $contract Contract to update. Must have an id whose row still exists.
	 * @return bool True when the contract row was updated (or already current).
	 * @throws \RuntimeException If the contract has no id, or its row no longer exists.
	 */
	public function update( Contract $contract ): bool {
		$id = $contract->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'Cannot update a contract that has no id. Use ContractRepository::insert() for a new contract.' );
		}

		$this->assert_contract_exists( $id );

		$this->update_contract_row( $contract );
		$this->sync_children( $contract );

		return true;
	}

	/**
	 * Fetch a contract by id, hydrating the live entity (no cycles).
	 *
	 * Loads the contract row plus its items / addresses / meta into the live
	 * {@see Contract}. Cycles are NOT hydrated onto the entity - they are reached
	 * on demand through the targeted cycle reads. For list / guard paths that do
	 * not need the children, use {@see self::find_summary()}.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null Hydrated contract, or null if not found.
	 */
	public function find( int $id ): ?Contract {
		$row = $this->find_contract_row( $id );
		if ( null === $row ) {
			return null;
		}

		return Contract::from_storage(
			$row,
			$this->find_items( $id ),
			$this->find_addresses( $id ),
			$this->find_meta( $id )
		);
	}

	/**
	 * Lightweight read: the contract row only, no children.
	 *
	 * The contract row IS the live state, so for list screens and guards that need
	 * the contract's identity and live schedule this reads it without paying to load
	 * the children.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null The contract (row only), or null if not found.
	 */
	public function find_summary( int $id ): ?Contract {
		$row = $this->find_contract_row( $id );
		if ( null === $row ) {
			return null;
		}

		return Contract::from_storage( $row );
	}

	/**
	 * Whether a contract row exists for `$id`.
	 *
	 * @param int $id Contract id.
	 */
	public function exists( int $id ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) );

		return null !== $found;
	}

	/**
	 * Delete a contract and its child + cycle rows.
	 *
	 * Snapshot rows are per contract, so they are deleted with the contract too.
	 *
	 * @param int $id Contract id.
	 * @return bool True when the contract row was removed.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		foreach ( array(
			SchemaInstaller::TABLE_CYCLES,
			SchemaInstaller::TABLE_SNAPSHOTS,
			SchemaInstaller::TABLE_CONTRACT_ITEMS,
			SchemaInstaller::TABLE_CONTRACT_ADDRESSES,
			SchemaInstaller::TABLE_CONTRACT_META,
		) as $child ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( SchemaInstaller::get_table_name( $child ), array( 'contract_id' => $id ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ), array( 'id' => $id ) );

		return (bool) $deleted;
	}

	/**
	 * Append a cycle to its chain `(contract_id, kind)`, copy-forwarding snapshots.
	 *
	 * Resolves the cycle's snapshot references first (durable-intent-first), then
	 * inserts the cycle row and stamps the generated id back onto the entity. Each
	 * snapshot is reused from `$previous` when the in-memory value object is absent
	 * or carries the same payload + schema version as the previous cycle's stored
	 * snapshot (copy-forward); otherwise a new snapshot row is inserted. The whole
	 * append is the operation-scoped seam a later transaction-handling change wraps.
	 *
	 * @param Cycle      $cycle    Cycle to append. Carries its contract id and kind.
	 * @param Cycle|null $previous The chain's previous cycle, when copy-forward of its
	 *                             snapshot ids should be considered; null for the first cycle.
	 * @throws \RuntimeException If a snapshot or cycle write fails (e.g. a duplicate
	 *                          (contract_id, kind, sequence_no) the UNIQUE index rejects).
	 */
	public function append_cycle( Cycle $cycle, ?Cycle $previous = null ): void {
		$this->resolve_cycle_snapshots( $cycle, $previous );
		$this->insert_cycle( $cycle );
	}

	/**
	 * Update an existing cycle row.
	 *
	 * @param Cycle $cycle Cycle to update. Must have an id whose row still exists.
	 * @throws \RuntimeException If the cycle has no id, the update fails, or its row
	 *                          no longer exists (stale id or concurrent delete).
	 */
	public function update_cycle( Cycle $cycle ): void {
		global $wpdb;

		$id = $cycle->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'Cannot update a cycle that has no id. Use ContractRepository::append_cycle() for a new cycle.' );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES ),
			array_merge(
				$cycle->to_storage(),
				array( 'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ) )
			),
			array( 'id' => (int) $id )
		);

		// date_updated_gmt always changes, so a matched row reports 1 affected row;
		// 0 means no row matched (a stale id or a concurrent delete) and must fail
		// loudly rather than pass silently, mirroring the contract-row guard.
		if ( false === $updated || 0 === $updated ) {
			throw new \RuntimeException( 'Cycle row not found during update (concurrent delete or stale id).' );
		}
	}

	/**
	 * The chain's most-recent cycle, or null when the chain is empty.
	 *
	 * The most-recent cycle is the highest `sequence_no` in `(contract_id, kind)` -
	 * the last billing record appended. Its snapshots are decoded back into typed
	 * value objects when it is still in flight (a non-terminal `pending` / `failed`
	 * cycle); a settled record is returned without the extra snapshot reads.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $kind        Chain kind. Defaults to billing.
	 * @return Cycle|null The most-recent cycle, or null if the chain has none.
	 */
	public function find_current_cycle( int $contract_id, string $kind = Cycle::KIND_BILLING ): ?Cycle {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d AND kind = %s ORDER BY sequence_no DESC LIMIT 1", $contract_id, $kind ), ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_cycle( $row );
	}

	/**
	 * A window of a chain's cycle history, newest first.
	 *
	 * Paginated targeted read for history screens: the contract entity never holds
	 * the cycle graph, so callers page through it here. Snapshots are decoded into
	 * typed value objects for any in-flight (non-terminal) cycle in the window.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $kind        Chain kind. Defaults to billing.
	 * @param int    $limit       Maximum rows to return.
	 * @param int    $offset      Rows to skip (for paging).
	 * @return array<int, Cycle> Cycles newest first.
	 */
	public function find_cycle_history( int $contract_id, string $kind = Cycle::KIND_BILLING, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d AND kind = %s ORDER BY sequence_no DESC LIMIT %d OFFSET %d", $contract_id, $kind, $limit, $offset ), ARRAY_A );

		$cycles = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$cycles[] = $this->hydrate_cycle( self::as_string_keyed( $row ) );
			}
		}

		return $cycles;
	}

	/**
	 * The highest `count` in a chain `(contract_id, kind)`, or null when none counts.
	 *
	 * This is the chain's chargeable count - the per-chain counter the dispatcher's
	 * money-path advances (the next chargeable cycle is `MAX(count) + 1`). Returns
	 * null for a chain with no counting cycles (for example one holding only
	 * non-counting trial periods).
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $kind        Chain kind. Defaults to billing.
	 * @return int|null The highest count, or null when the chain has no counting cycle.
	 */
	public function max_count( int $contract_id, string $kind = Cycle::KIND_BILLING ): ?int {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$max = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(count) FROM {$table} WHERE contract_id = %d AND kind = %s", $contract_id, $kind ) );

		return null === $max ? null : (int) $max;
	}

	/**
	 * All cycles linked to `$order_id`, across kinds and contracts.
	 *
	 * `order_id` is a plain non-1:1 reference - an aggregate order may serve many
	 * cycles, even across contracts - so querying by it is a first-class path that
	 * returns every matching cycle. Snapshots are decoded for any in-flight cycle.
	 *
	 * @param int $order_id WooCommerce order id.
	 * @return array<int, Cycle> Cycles linked to the order, oldest first.
	 */
	public function find_cycles_by_order_id( int $order_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id ASC", $order_id ), ARRAY_A );

		$cycles = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$cycles[] = $this->hydrate_cycle( self::as_string_keyed( $row ) );
			}
		}

		return $cycles;
	}

	/**
	 * Resolve a cycle's in-memory snapshot value objects into stored row ids.
	 *
	 * For each snapshot type: when the cycle carries a fresh value object and no
	 * stored id yet, the value object is reused from `$previous` by copy-forward
	 * when their payload + schema version match, or inserted as a new snapshot row,
	 * and its id stamped onto the cycle (write-once). A cycle that already carries a
	 * snapshot id (or no value object) is left as is - a frozen reference is never
	 * re-pointed.
	 *
	 * @param Cycle      $cycle    Cycle whose snapshots to resolve.
	 * @param Cycle|null $previous The previous cycle to copy-forward from, or null.
	 */
	private function resolve_cycle_snapshots( Cycle $cycle, ?Cycle $previous ): void {
		$contract_id = $cycle->get_contract_id();

		$plan = $cycle->get_plan_snapshot();
		if ( $plan instanceof PlanSnapshot && null === $cycle->get_plan_snapshot_id() ) {
			$cycle->set_plan_snapshot_id(
				$this->copy_forward_or_insert(
					$contract_id,
					SnapshotStore::TYPE_PLAN,
					$plan->get_selling_plan_id(),
					$plan->to_payload(),
					$plan->get_schema_version(),
					null === $previous ? null : $previous->get_plan_snapshot_id()
				)
			);
		}

		$items = $cycle->get_items_snapshot();
		if ( $items instanceof ItemsSnapshot && null === $cycle->get_items_snapshot_id() ) {
			$cycle->set_items_snapshot_id(
				$this->copy_forward_or_insert(
					$contract_id,
					SnapshotStore::TYPE_ITEMS,
					null,
					$items->to_payload(),
					$items->get_schema_version(),
					null === $previous ? null : $previous->get_items_snapshot_id()
				)
			);
		}
	}

	/**
	 * Reuse the previous cycle's snapshot id when its payload matches, else insert.
	 *
	 * Copy-forward dedup: when `$previous_snapshot_id` points at a stored row whose
	 * payload and schema version equal the new ones, that id is returned and no new
	 * row is written; otherwise a fresh snapshot row is inserted and its id
	 * returned.
	 *
	 * The match is a strict comparison against the JSON-decoded stored payload, so
	 * payload values must be JSON-round-trip-safe scalars: integers, and strings
	 * (money as decimal strings, never PHP floats - a float would decode back as a
	 * float and may not compare identical, defeating the reuse). Today's plan/items
	 * payloads satisfy this; when money enters plan snapshots it must be carried as
	 * a decimal string.
	 *
	 * @param int                      $contract_id          Owning contract id.
	 * @param string                   $snapshot_type        Snapshot type (`plan` | `items`).
	 * @param int|null                 $parent_id            Weak link to the source, or null.
	 * @param array<int|string, mixed> $payload              The new snapshot payload.
	 * @param int                      $schema_version       Payload-format version.
	 * @param int|null                 $previous_snapshot_id The previous cycle's snapshot id, or null.
	 * @return int The reused or newly-inserted snapshot id.
	 */
	private function copy_forward_or_insert( int $contract_id, string $snapshot_type, ?int $parent_id, array $payload, int $schema_version, ?int $previous_snapshot_id ): int {
		if ( null !== $previous_snapshot_id ) {
			$previous = $this->find_snapshot_payload( $previous_snapshot_id );
			if ( null !== $previous && $previous['schema_version'] === $schema_version && $previous['payload'] === $payload ) {
				return $previous_snapshot_id;
			}
		}

		return $this->snapshots->insert( $contract_id, $snapshot_type, $parent_id, $payload, $schema_version );
	}

	/**
	 * Hydrate a cycle row, decoding its snapshot ids into value objects in flight.
	 *
	 * The typed snapshot value objects are attached only for an in-flight
	 * (non-terminal) cycle - a `pending` or `failed` record still being worked. A
	 * settled record (`billed` / `cancelled`) is returned with its snapshot ids but
	 * without the extra reads to decode their payloads; a caller that needs them can
	 * read the snapshot rows explicitly.
	 *
	 * @param array<string, mixed> $row Cycle row.
	 * @return Cycle The hydrated cycle.
	 */
	private function hydrate_cycle( array $row ): Cycle {
		$cycle = Cycle::from_storage( $row );

		if ( CycleStatus::is_terminal( $cycle->get_status()->get_value() ) ) {
			return $cycle;
		}

		$plan = $this->find_plan_snapshot( $cycle->get_plan_snapshot_id() );
		if ( $plan instanceof PlanSnapshot ) {
			$cycle->set_plan_snapshot( $plan );
		}

		$items = $this->find_items_snapshot( $cycle->get_items_snapshot_id() );
		if ( $items instanceof ItemsSnapshot ) {
			$cycle->set_items_snapshot( $items );
		}

		return $cycle;
	}

	/**
	 * Insert a cycle row and stamp the generated id back onto the entity.
	 *
	 * @param Cycle $cycle Cycle to insert. Carries its contract id and kind.
	 * @throws \RuntimeException If the cycle insert fails.
	 */
	private function insert_cycle( Cycle $cycle ): void {
		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES ),
			array_merge(
				$cycle->to_storage(),
				array(
					'date_created_gmt' => $now,
					'date_updated_gmt' => $now,
				)
			)
		);

		if ( false === $inserted ) {
			throw new \RuntimeException( 'Failed to insert cycle.' );
		}

		$cycle->set_id( (int) $wpdb->insert_id );
	}

	/**
	 * Decode a stored plan snapshot row into a typed value object.
	 *
	 * @param int|null $snapshot_id Snapshot row id, or null.
	 * @return PlanSnapshot|null The decoded value object, or null.
	 */
	private function find_plan_snapshot( ?int $snapshot_id ): ?PlanSnapshot {
		$decoded = $this->find_snapshot_payload( $snapshot_id );
		if ( null === $decoded ) {
			return null;
		}

		return PlanSnapshot::from_payload( self::as_string_keyed( $decoded['payload'] ), $decoded['schema_version'] );
	}

	/**
	 * Decode a stored items snapshot row into a typed value object.
	 *
	 * @param int|null $snapshot_id Snapshot row id, or null.
	 * @return ItemsSnapshot|null The decoded value object, or null.
	 */
	private function find_items_snapshot( ?int $snapshot_id ): ?ItemsSnapshot {
		$decoded = $this->find_snapshot_payload( $snapshot_id );
		if ( null === $decoded ) {
			return null;
		}

		return ItemsSnapshot::from_payload( self::as_item_rows( $decoded['payload'] ), $decoded['schema_version'] );
	}

	/**
	 * Read and JSON-decode a snapshot row's payload and schema version.
	 *
	 * @param int|null $snapshot_id Snapshot row id, or null.
	 * @return array{payload: array<int|string, mixed>, schema_version: int}|null
	 */
	private function find_snapshot_payload( ?int $snapshot_id ): ?array {
		global $wpdb;

		if ( null === $snapshot_id ) {
			return null;
		}

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT payload, schema_version FROM {$table} WHERE id = %d", $snapshot_id ), ARRAY_A );

		if ( null === $row ) {
			return null;
		}

		$payload = json_decode( (string) $row['payload'], true );

		return array(
			'payload'        => is_array( $payload ) ? $payload : array(),
			'schema_version' => (int) $row['schema_version'],
		);
	}

	/**
	 * Re-key a decoded payload as a string-keyed map.
	 *
	 * A JSON-decoded payload is statically an `array<int|string, mixed>` (its key
	 * type is erased through json_decode), while a plan snapshot's payload is a
	 * string-keyed map. The keys of a decoded JSON object are already strings, so
	 * this re-key is a no-op for a real plan payload and exists to recover the
	 * string-keyed type the value object models.
	 *
	 * @param array<int|string, mixed> $payload Decoded payload.
	 * @return array<string, mixed>
	 */
	private static function as_string_keyed( array $payload ): array {
		$result = array();
		foreach ( $payload as $key => $value ) {
			$result[ (string) $key ] = $value;
		}

		return $result;
	}

	/**
	 * Shape a decoded payload as an ordered list of item rows.
	 *
	 * An items snapshot's payload is a list of associative item arrays, but the
	 * decoded JSON is statically an `array<int|string, mixed>`. Each element that
	 * is itself an array is re-keyed as a string-keyed row (its keys are already
	 * strings); non-array elements are skipped. Recovers the value object's
	 * modelled shape without trusting the erased JSON types.
	 *
	 * @param array<int|string, mixed> $payload Decoded payload.
	 * @return array<int, array<string, mixed>>
	 */
	private static function as_item_rows( array $payload ): array {
		$rows = array();
		foreach ( $payload as $row ) {
			if ( is_array( $row ) ) {
				$rows[] = self::as_string_keyed( $row );
			}
		}

		return $rows;
	}

	/**
	 * Read the contract row by id.
	 *
	 * @param int $id Contract id.
	 * @return array<string, mixed>|null
	 */
	private function find_contract_row( int $id ): ?array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		return null === $row ? null : $row;
	}

	/**
	 * Update the contract row in place (no child rows).
	 *
	 * @param Contract $contract Contract whose row to update. Must have an id.
	 * @throws \RuntimeException If the update fails.
	 */
	private function update_contract_row( Contract $contract ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS ),
			array_merge(
				$contract->to_storage(),
				array( 'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ) )
			),
			array( 'id' => (int) $contract->get_id() )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update contract.' );
		}
	}

	/**
	 * Guard against a stale id / concurrent delete before touching any table.
	 *
	 * `$wpdb->update()` returns 0 for both "matched, nothing changed" and "no
	 * row matched", so on its own it cannot tell that the parent is gone - and
	 * a child reconcile would then write orphan rows, since no foreign key
	 * enforces the relation. This narrows but does not fully close the race; the
	 * complete fix wraps the write in a transaction with SELECT ... FOR UPDATE,
	 * the operation-scoped seam a later transaction-handling change wraps (the
	 * integration suite's transaction-based isolation needs a test-safe approach
	 * first).
	 *
	 * @param int $id Contract id.
	 * @throws \RuntimeException If the contract row no longer exists.
	 */
	private function assert_contract_exists( int $id ): void {
		if ( ! $this->exists( $id ) ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Cannot update contract %d: the contract row no longer exists (stale id or concurrent delete).', $id ) )
			);
		}
	}

	/**
	 * Reconcile a contract's items, addresses, and meta rows only when they differ.
	 *
	 * Each child set is compared against what is stored - via a normalized
	 * signature that both the loaded rows and the entity's arrays are projected
	 * through, so MySQL's column coercion (DECIMAL padding, INT-as-string) is
	 * applied to both sides and a no-op round-trip compares equal. An unchanged set
	 * is left untouched; only a changed set is rewritten (delete-then-reinsert for
	 * that one child table). This keeps the contract-row cache write (the common
	 * renewal path) from churning child rows, while still letting an item / address
	 * / meta edit propagate.
	 *
	 * @param Contract $contract Contract whose children to reconcile. Must have an id.
	 */
	private function sync_children( Contract $contract ): void {
		$id = (int) $contract->get_id();

		if ( $this->items_signature( $this->find_items( $id ) ) !== $this->items_signature( $contract->get_items() ) ) {
			$this->replace_items( $id, $contract->get_items() );
		}

		if ( $this->addresses_signature( $this->find_addresses( $id ) ) !== $this->addresses_signature( $contract->get_addresses() ) ) {
			$this->replace_addresses( $id, $contract->get_addresses() );
		}

		if ( $this->meta_signature( $this->find_meta( $id ) ) !== $this->meta_signature( $contract->get_meta() ) ) {
			$this->replace_meta( $id, $contract->get_meta() );
		}
	}

	/**
	 * Delete-then-reinsert a contract's item rows.
	 *
	 * @param int                              $contract_id Contract id.
	 * @param array<int, array<string, mixed>> $items       Item rows.
	 */
	private function replace_items( int $contract_id, array $items ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS ), array( 'contract_id' => $contract_id ) );
		$this->insert_items( $contract_id, $items );
	}

	/**
	 * Delete-then-reinsert a contract's address rows.
	 *
	 * @param int                                 $contract_id Contract id.
	 * @param array<string, array<string, mixed>> $addresses   Address rows keyed by type.
	 */
	private function replace_addresses( int $contract_id, array $addresses ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ADDRESSES ), array( 'contract_id' => $contract_id ) );
		$this->insert_addresses( $contract_id, $addresses );
	}

	/**
	 * Delete-then-reinsert a contract's meta rows.
	 *
	 * @param int                   $contract_id Contract id.
	 * @param array<string, string> $meta        Meta as key => value.
	 */
	private function replace_meta( int $contract_id, array $meta ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META ), array( 'contract_id' => $contract_id ) );
		$this->insert_meta( $contract_id, $meta );
	}

	/**
	 * Insert line items for a contract.
	 *
	 * @param int                              $contract_id Contract id.
	 * @param array<int, array<string, mixed>> $items       Item rows.
	 */
	private function insert_items( int $contract_id, array $items ): void {
		global $wpdb;

		foreach ( $items as $item ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS ),
				array(
					'contract_id'  => $contract_id,
					'item_name'    => self::coerce_string( $item['item_name'] ?? null ),
					'item_type'    => self::coerce_string( $item['item_type'] ?? null, 'line_item' ),
					'product_id'   => isset( $item['product_id'] ) ? self::coerce_int( $item['product_id'] ) : null,
					'variation_id' => isset( $item['variation_id'] ) ? self::coerce_int( $item['variation_id'] ) : null,
					'quantity'     => self::coerce_string( $item['quantity'] ?? null, '1' ),
					'subtotal'     => self::coerce_string( $item['subtotal'] ?? null, '0' ),
					'total'        => self::coerce_string( $item['total'] ?? null, '0' ),
					'taxes'        => isset( $item['taxes'] ) ? wp_json_encode( $item['taxes'] ) : null,
				)
			);
		}
	}

	/**
	 * Insert addresses for a contract.
	 *
	 * @param int                                 $contract_id Contract id.
	 * @param array<string, array<string, mixed>> $addresses   Address rows keyed by type.
	 */
	private function insert_addresses( int $contract_id, array $addresses ): void {
		global $wpdb;

		foreach ( $addresses as $type => $address ) {
			$record = array(
				'contract_id'  => $contract_id,
				'address_type' => (string) $type,
			);

			foreach ( self::ADDRESS_COLUMNS as $column ) {
				$record[ $column ] = isset( $address[ $column ] ) ? self::coerce_string( $address[ $column ] ) : null;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ADDRESSES ), $record );
		}
	}

	/**
	 * Insert meta for a contract.
	 *
	 * @param int                   $contract_id Contract id.
	 * @param array<string, string> $meta        Meta as key => value.
	 */
	private function insert_meta( int $contract_id, array $meta ): void {
		global $wpdb;

		foreach ( $meta as $key => $value ) {
			// These are the engine's own contract-meta columns, not post/order
			// meta; the slow-meta-query heuristic does not apply.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			$wpdb->insert(
				SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META ),
				array(
					'contract_id' => $contract_id,
					'meta_key'    => (string) $key,
					'meta_value'  => (string) $value,
				)
			);
		}
	}

	/**
	 * Load line items for a contract.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<int, array<string, mixed>>
	 */
	private function find_items( int $contract_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d ORDER BY id ASC", $contract_id ), ARRAY_A );

		return self::as_item_rows( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Load addresses for a contract, keyed by address type.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<string, array<string, mixed>>
	 */
	private function find_addresses( int $contract_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ADDRESSES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d", $contract_id ), ARRAY_A );

		$by_type = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$by_type[ self::coerce_string( $row['address_type'] ?? null ) ] = self::as_string_keyed( $row );
			}
		}

		return $by_type;
	}

	/**
	 * Load meta for a contract as key => value.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<string, string>
	 */
	private function find_meta( int $contract_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );

		// These are the engine's own contract-meta columns, not post/order meta;
		// the slow-meta-query heuristic does not apply.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE contract_id = %d", $contract_id ), ARRAY_A );

		$meta = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$meta[ self::coerce_string( $row['meta_key'] ?? null ) ] = self::coerce_string( $row['meta_value'] ?? null );
			}
		}

		return $meta;
	}

	/**
	 * A change-detection signature for an item set.
	 *
	 * Projects each item to the comparable columns and coerces money / count
	 * fields to the fixed shape MySQL stores them in (DECIMAL scale, INT-as-string).
	 * Applied to both the loaded rows and the entity's arrays in
	 * {@see self::sync_children()}, so a no-op round-trip yields an equal signature
	 * and the rows are not rewritten. The generated id, `contract_id`, and column
	 * order are intentionally ignored.
	 *
	 * @param array<int, array<string, mixed>> $items Item arrays (loaded rows or entity items).
	 * @return array<int, array<string, string|null>> Per-item comparable projection.
	 */
	private function items_signature( array $items ): array {
		$signature = array();

		foreach ( $items as $item ) {
			$signature[] = array(
				'item_name'    => self::coerce_string( $item['item_name'] ?? null ),
				'item_type'    => self::coerce_string( $item['item_type'] ?? null, 'line_item' ),
				'product_id'   => isset( $item['product_id'] ) ? (string) self::coerce_int( $item['product_id'] ) : null,
				'variation_id' => isset( $item['variation_id'] ) ? (string) self::coerce_int( $item['variation_id'] ) : null,
				'quantity'     => number_format( self::coerce_float( $item['quantity'] ?? 1 ), 4, '.', '' ),
				'subtotal'     => number_format( self::coerce_float( $item['subtotal'] ?? 0 ), 8, '.', '' ),
				'total'        => number_format( self::coerce_float( $item['total'] ?? 0 ), 8, '.', '' ),
				'taxes'        => $this->taxes_signature( $item['taxes'] ?? null ),
			);
		}

		return $signature;
	}

	/**
	 * A change-detection signature for an address set, keyed by type.
	 *
	 * Projects each address to the persisted columns with the same string coercion
	 * the load path returns, so an unchanged set compares equal in
	 * {@see self::sync_children()}.
	 *
	 * @param array<string, array<string, mixed>> $addresses Address arrays keyed by type.
	 * @return array<string, array<string, string|null>> Per-type comparable projection.
	 */
	private function addresses_signature( array $addresses ): array {
		$signature = array();

		foreach ( $addresses as $type => $address ) {
			$record = array();
			foreach ( self::ADDRESS_COLUMNS as $column ) {
				$value             = isset( $address[ $column ] ) ? self::coerce_string( $address[ $column ] ) : '';
				$record[ $column ] = '' !== $value ? $value : null;
			}

			$signature[ (string) $type ] = $record;
		}

		ksort( $signature );

		return $signature;
	}

	/**
	 * A change-detection signature for a meta set.
	 *
	 * @param array<string, string> $meta Meta as key => value.
	 * @return array<string, string> Comparable projection (key-sorted).
	 */
	private function meta_signature( array $meta ): array {
		$signature = array();

		foreach ( $meta as $key => $value ) {
			$signature[ (string) $key ] = (string) $value;
		}

		ksort( $signature );

		return $signature;
	}

	/**
	 * Normalize a taxes value to a comparable JSON string (or null).
	 *
	 * The taxes column stores JSON; the load path returns that JSON string while
	 * the entity carries the decoded array. Re-encoding both to canonical JSON lets
	 * the signatures compare regardless of which side a value came from.
	 *
	 * @param mixed $taxes A JSON string (loaded) or an array (entity), or null.
	 * @return string|null Canonical JSON, or null when there is nothing to compare.
	 */
	private function taxes_signature( $taxes ): ?string {
		if ( null === $taxes ) {
			return null;
		}

		$decoded = is_string( $taxes ) ? json_decode( $taxes, true ) : $taxes;
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		$encoded = wp_json_encode( $decoded );

		return false === $encoded ? null : $encoded;
	}
}
