<?php
/**
 * Persistence for the live {@see Contract} (row + items / addresses / meta) plus
 * targeted cycle access. Owns the $wpdb access across the contract-side tables.
 *
 * The contract is the live source of truth. A chain is NOT a stored entity: it is
 * the pair `(contract_id, kind)`, with its head and counters derived from the cycle
 * rows. The entity never carries a cycle graph in memory, so cycles are reached
 * through purpose-built reads ({@see self::find_chain_head()}, {@see self::max_count()},
 * etc.) and written one at a time ({@see self::append_cycle()}, {@see self::update_cycle()}).
 * There is no whole-graph `save()`. Snapshots are deduped by copy-forward (reuse the
 * previous cycle's snapshot id when plan / items are unchanged), via {@see SnapshotStore}.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use DateTimeImmutable;
use DateTimeZone;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
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
	 * Logger source tag.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

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
	 * Durable-intent-first (parent row, then children) and the seam a later
	 * transaction-handling change wraps; it does not open a transaction now (a naive
	 * one would commit the integration suite's outer test transaction, and bare
	 * savepoints error in production without an ambient transaction).
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
	 * Insert a contract together with its signup cycle (cycle 1) - the checkout
	 * create path.
	 *
	 * Durable-intent-first: insert the contract -> freeze the signup cycle's snapshots
	 * (which need the contract id) -> record those ids on the contract and update its
	 * row -> insert cycle 1 (which carries the same snapshot ids by construction). The
	 * cycle is taken as built by the caller; this only stamps its contract id, resolves
	 * its snapshots, and inserts it. The seam a later transaction-handling change wraps.
	 *
	 * @param Contract $contract The contract to insert.
	 * @param Cycle    $cycle    The signup cycle (cycle 1), carrying its snapshot value objects.
	 * @return int The new contract id.
	 * @throws \RuntimeException If a contract, snapshot, or cycle write fails.
	 */
	public function insert_with_origin_cycle( Contract $contract, Cycle $cycle ): int {
		$contract_id = $this->insert( $contract );

		// First cycle in its chain: no previous to copy-forward from, so its snapshots
		// are inserted fresh and their ids stamped onto it.
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
	 * Updates the contract row in place, then reconciles items / addresses / meta only
	 * when they differ - so the common renewal-cache write (status, next_payment_gmt)
	 * does not churn child rows. The write seam a later transaction-handling change
	 * wraps; no transaction now (see {@see self::insert()}).
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
	 * Fetch a contract by id, hydrating the live entity with its items / addresses /
	 * meta. Cycles are NOT hydrated - they are reached on demand through the targeted
	 * cycle reads. For list / guard paths that do not need the children, use
	 * {@see self::find_summary()}.
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
	 * Lightweight read: the contract row only, no children. The row IS the live state,
	 * so list screens and guards that need only identity + schedule avoid loading children.
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
	 * Query a window of contracts for list screens, newest first (id DESC). Hydrated
	 * lightweight (row only, no children) like {@see self::find_summary()}.
	 *
	 * Takes a WooCommerce-style args array (cf. `wc_get_orders()`) rather than positional
	 * paging args, so the shape can widen to status / search / sort without a signature
	 * change. Only the paging args are honoured for now.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type int $limit  Maximum contracts to return. Default 20.
	 *     @type int $offset Rows to skip (for paging). Default 0.
	 * }
	 * @return array<int, Contract> Contracts newest first.
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		$limit  = isset( $args['limit'] ) && is_numeric( $args['limit'] ) ? (int) $args['limit'] : 20;
		$offset = isset( $args['offset'] ) && is_numeric( $args['offset'] ) ? (int) $args['offset'] : 0;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ), ARRAY_A );

		$contracts = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$contracts[] = Contract::from_storage( self::as_string_keyed( $row ) );
			}
		}

		return $contracts;
	}

	/**
	 * Contracts actionable for renewal at `$now`, oldest-due first - the batch dispatcher's scan.
	 * Active, primitive-scheduled contracts whose `next_payment_gmt` has arrived, joined to their
	 * head cycle so the scan can filter to the ones actually chargeable now:
	 *
	 * - head `billed`/`cancelled` and its period has ended (`ends_at_gmt <= now`) -> advance-ready;
	 * - head `pending` with an expired crash-recovery lease (`claimed_until <= now`) -> reclaim-ready.
	 *
	 * A head that is `failed` (awaits dunning), `processing` (awaits its gateway), or `pending`
	 * with a live lease is deliberately excluded. Because that filter is in SQL, `LIMIT` counts
	 * only actionable rows, so a cluster of non-actionable heads (a stuck gateway, a backlog of
	 * declines) cannot occupy the batch and starve healthy renewals behind them. Gateway-scheduled
	 * contracts are excluded (the gateway owns their renewal); a null `next_payment_gmt` never
	 * matches the `<=` comparison. Driven by the `due_contract (status, next_payment_gmt)` index;
	 * the head cycle is joined per candidate via the `chain_seq` UNIQUE index. Returns the head
	 * fields selection needs, so the dispatcher does not re-load the head to decide what to bill.
	 *
	 * @param DateTimeImmutable $now   The cutoff moment; contracts due at or before it.
	 * @param int               $limit Maximum rows to return (the batch size).
	 * @return array<int, RenewalCandidate> Actionable renewal candidates, oldest-due first.
	 */
	public function find_due( DateTimeImmutable $now, int $limit ): array {
		if ( $limit < 1 ) {
			return array();
		}

		global $wpdb;

		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$cycles    = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$cutoff    = $now->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );

		// Table names cannot be bound, so they are interpolated; every value is a placeholder.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.id AS contract_id, cy.count AS head_count, cy.status AS head_status, cy.ends_at_gmt AS head_ends_at_gmt
				FROM {$contracts} c
				JOIN {$cycles} cy
				  ON cy.contract_id = c.id AND cy.kind = %s
				 AND cy.sequence_no = ( SELECT MAX(s.sequence_no) FROM {$cycles} s WHERE s.contract_id = c.id AND s.kind = %s )
				WHERE c.status = %s AND c.schedule_source <> %s AND c.next_payment_gmt IS NOT NULL AND c.next_payment_gmt <= %s
				  AND (
				        ( cy.status = %s AND cy.ends_at_gmt <= %s )
				     OR ( cy.status = %s AND cy.claimed_until IS NOT NULL AND cy.claimed_until <= %s )
				      )
				ORDER BY c.next_payment_gmt ASC, c.id ASC
				LIMIT %d",
				Cycle::KIND_BILLING,
				Cycle::KIND_BILLING,
				ContractStatus::ACTIVE,
				Contract::SCHEDULE_SOURCE_GATEWAY,
				$cutoff,
				CycleStatus::BILLED,
				$cutoff,
				CycleStatus::PENDING,
				$cutoff,
				$limit
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// A failed scan otherwise reads exactly like "nothing due" and renewals stall
		// store-wide with no signal; the return stays empty either way.
		if ( '' !== $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'ContractRepository::find_due(): due-index scan failed - renewals may stall until this is fixed. %s', $wpdb->last_error ),
				array( 'source' => self::LOG_SOURCE )
			);
		}

		$result = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$head_count = $row['head_count'] ?? null;
			$result[]   = new RenewalCandidate(
				ScalarCoercion::coerce_int( $row['contract_id'] ?? 0 ),
				null === $head_count ? null : ScalarCoercion::coerce_int( $head_count ),
				ScalarCoercion::coerce_string( $row['head_status'] ?? '' ),
				ScalarCoercion::coerce_string( $row['head_ends_at_gmt'] ?? '' )
			);
		}

		return $result;
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
	 * Resolves the cycle's snapshots (reused from `$previous` when unchanged, else
	 * inserted fresh) then inserts the cycle row and stamps the generated id back onto
	 * the entity. The seam a later transaction-handling change wraps.
	 *
	 * @param Cycle      $cycle    Cycle to append. Carries its contract id and kind.
	 * @param Cycle|null $previous The chain's previous cycle, when copy-forward of its
	 *                             snapshot ids should be considered; null for the first cycle.
	 * @throws DuplicateCycleException If a chain UNIQUE index rejects the row (the
	 *                                 position is already claimed - the create-as-claim race).
	 * @throws \RuntimeException If a snapshot write or the cycle insert fails for any
	 *                          other reason (the database error is in the message).
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

		// date_updated_gmt always changes, so a matched row reports 1 affected row; 0
		// means no row matched (stale id or concurrent delete) and must fail loudly.
		if ( false === $updated || 0 === $updated ) {
			throw new \RuntimeException( 'Cycle row not found during update (concurrent delete or stale id).' );
		}
	}

	/**
	 * Atomically reclaim a stalled `pending` cycle whose crash-recovery lease has expired,
	 * extending the lease by `$lease_ttl_seconds`. The compare-and-set that makes reclaim
	 * race-safe: the predicate keys on `claimed_until <= now`, so among concurrent workers
	 * only the first to run the UPDATE matches a row - it writes the future lease, after
	 * which every other worker's `<=` predicate matches zero rows.
	 *
	 * Both the expiry predicate and the fresh lease anchor on the DATABASE clock
	 * (`UTC_TIMESTAMP()`) - the one clock every worker shares - so a worker whose PHP clock
	 * runs fast cannot take over a lease that is still live in real terms, and the lease it
	 * stamps means the same thing to every other worker.
	 *
	 * @param int $cycle_id          The stalled cycle's id.
	 * @param int $lease_ttl_seconds The lease window to stamp, measured from the DB's now.
	 * @return bool True when this caller won the reclaim (exactly one row updated); false
	 *              when another worker already reclaimed it (zero rows matched).
	 */
	public function reclaim_expired_cycle( int $cycle_id, int $lease_ttl_seconds ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET claimed_until = DATE_ADD( UTC_TIMESTAMP(), INTERVAL %d SECOND ), date_updated_gmt = UTC_TIMESTAMP() WHERE id = %d AND status = %s AND claimed_until IS NOT NULL AND claimed_until <= UTC_TIMESTAMP()", $lease_ttl_seconds, $cycle_id, CycleStatus::PENDING ) );

		// Exactly one row matched means this caller won the CAS; 0 means another worker
		// already extended the lease (or the cycle settled) between read and write.
		return false !== $result && 1 === $wpdb->rows_affected;
	}

	/**
	 * Re-claim a failed cycle for an admin-triggered retry, as an atomic compare-and-set:
	 * flip it back to `pending` and stamp a fresh lease only while it is still `failed`.
	 * Returns true when this caller won (exactly one row matched), false when it was already
	 * re-claimed or has since moved on.
	 *
	 * The fresh lease anchors on the DATABASE clock (`UTC_TIMESTAMP()`), the shared reference
	 * clock for all lease arbitration.
	 *
	 * @param int $cycle_id          The cycle to re-claim.
	 * @param int $lease_ttl_seconds The lease window to stamp, measured from the DB's now.
	 */
	public function reclaim_failed_cycle( int $cycle_id, int $lease_ttl_seconds ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, claimed_until = DATE_ADD( UTC_TIMESTAMP(), INTERVAL %d SECOND ), date_updated_gmt = UTC_TIMESTAMP() WHERE id = %d AND status = %s", CycleStatus::PENDING, $lease_ttl_seconds, $cycle_id, CycleStatus::FAILED ) );

		return false !== $result && 1 === $wpdb->rows_affected;
	}

	/**
	 * Settle a cycle's outcome as an atomic compare-and-set on its status: move it from
	 * `$from_status` to `$to_status`, stamp the settling order and reason, and clear the
	 * crash-recovery lease - all in one UPDATE gated on the row still being in `$from_status`.
	 * Among concurrent settlers (the post-charge reconciliation and the order-status listener
	 * can race across workers), exactly one caller matches the row and wins; the rest match
	 * zero rows, so status transitions - and the actions fired on them - happen exactly once.
	 *
	 * @param int         $cycle_id    The cycle to settle.
	 * @param string      $from_status The status the caller read; the CAS predicate.
	 * @param string      $to_status   The settled status to write.
	 * @param int         $order_id    The renewal order carrying the outcome.
	 * @param string|null $reason      Failure reason to record, or null to clear.
	 */
	public function transition_cycle_status( int $cycle_id, string $from_status, string $to_status, int $order_id, ?string $reason = null ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		if ( null === $reason ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, order_id = %d, reason = NULL, claimed_until = NULL, date_updated_gmt = %s WHERE id = %d AND status = %s", $to_status, $order_id, gmdate( 'Y-m-d H:i:s' ), $cycle_id, $from_status ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = %s, order_id = %d, reason = %s, claimed_until = NULL, date_updated_gmt = %s WHERE id = %d AND status = %s", $to_status, $order_id, $reason, gmdate( 'Y-m-d H:i:s' ), $cycle_id, $from_status ) );
		}

		return false !== $result && 1 === $wpdb->rows_affected;
	}

	/**
	 * The chain's head - its most-recent cycle (highest `sequence_no` in `(contract_id,
	 * kind)`) - or null when the chain is empty. The head is the chain's growth point, not
	 * necessarily the cycle current by date (a forced early renewal bills a head whose
	 * period lies ahead). Snapshots are decoded into typed value objects only for an
	 * in-flight cycle (see {@see self::hydrate_cycle()}).
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $kind        Chain kind. Defaults to billing.
	 * @return Cycle|null The head cycle, or null if the chain has none.
	 */
	public function find_chain_head( int $contract_id, string $kind = Cycle::KIND_BILLING ): ?Cycle {
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
	 * A window of a chain's cycle history, newest first - the paginated read for
	 * history screens. Snapshots are decoded only for any in-flight cycle in the window.
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
	 * The highest `count` in a chain `(contract_id, kind)` - the chargeable counter the
	 * dispatcher advances (next chargeable cycle is `MAX(count) + 1`). Returns null for a
	 * chain with no counting cycles (e.g. one holding only non-counting trial periods).
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
	 * All cycles linked to `$order_id`, across kinds and contracts. `order_id` is a
	 * non-1:1 reference (an aggregate order may serve many cycles), so this returns
	 * every match. Snapshots are decoded for any in-flight cycle.
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
	 * Resolve a cycle's in-memory snapshot value objects into stored row ids (write-once).
	 *
	 * Per snapshot type: when the cycle carries a fresh value object and no stored id
	 * yet, the id is copy-forwarded from `$previous` (when payload + schema version
	 * match) or freshly inserted, then stamped onto the cycle. A cycle that already
	 * carries an id is left as is - a frozen reference is never re-pointed.
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
	 * Reuse the previous cycle's snapshot id when its payload + schema version match,
	 * else insert a fresh row.
	 *
	 * The match is a strict comparison against the JSON-decoded stored payload, so
	 * payload values must be JSON-round-trip-safe scalars: ints and strings (money as
	 * decimal strings, never PHP floats - a float may not decode back identical,
	 * defeating the reuse).
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
	 * Hydrate a cycle row, attaching typed snapshot value objects only for an in-flight
	 * (non-terminal) cycle. A settled record keeps its snapshot ids but skips the extra
	 * reads to decode their payloads.
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
	 * @throws DuplicateCycleException If a chain UNIQUE index rejects the row.
	 * @throws \RuntimeException If the insert fails for any other reason.
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
			$db_error = $wpdb->last_error;
			if ( false !== stripos( $db_error, 'Duplicate entry' ) ) {
				// A chain UNIQUE index rejected the row: the position is already claimed.
				throw new DuplicateCycleException( esc_html( 'Cycle position already exists: ' . $db_error ) );
			}
			throw new \RuntimeException( esc_html( 'Failed to insert cycle. ' . $db_error ) );
		}

		$cycle->set_id( (int) $wpdb->insert_id );
	}

	/**
	 * Decode a stored plan snapshot row into a typed value object.
	 *
	 * Public so the renewal money-path can resolve a contract's own frozen plan terms
	 * (the cadence to bill the next cycle under) from its `plan_snapshot_id`.
	 *
	 * @param int|null $snapshot_id Snapshot row id, or null.
	 * @return PlanSnapshot|null The decoded value object, or null.
	 */
	public function find_plan_snapshot( ?int $snapshot_id ): ?PlanSnapshot {
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
	 * Re-key a decoded payload as a string-keyed map. A no-op at runtime (decoded JSON
	 * object keys are already strings); it recovers the string-keyed type that
	 * json_decode erases to `array<int|string, mixed>`.
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
	 * Shape a decoded payload as an ordered list of item rows: each array element is
	 * re-keyed as a string-keyed row, non-array elements skipped. Recovers the value
	 * object's modelled shape without trusting the erased JSON types.
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
	 * `$wpdb->update()` returns 0 for both "matched, nothing changed" and "no row
	 * matched", so on its own it cannot tell the parent is gone - and a child reconcile
	 * would then write orphan rows (no FK enforces the relation). This narrows but does
	 * not fully close the race; the complete fix (a transaction with SELECT ... FOR UPDATE)
	 * is the seam a later transaction-handling change wraps.
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
	 * Each child set is compared via a normalized signature both the loaded rows and
	 * the entity's arrays are projected through, so MySQL's column coercion (DECIMAL
	 * padding, INT-as-string) applies to both sides and a no-op round-trip compares
	 * equal. Only a changed set is rewritten (delete-then-reinsert for that one table).
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
					'item_name'    => ScalarCoercion::coerce_string( $item['item_name'] ?? null ),
					'item_type'    => ScalarCoercion::coerce_string( $item['item_type'] ?? null, 'line_item' ),
					'product_id'   => isset( $item['product_id'] ) ? ScalarCoercion::coerce_int( $item['product_id'] ) : null,
					'variation_id' => isset( $item['variation_id'] ) ? ScalarCoercion::coerce_int( $item['variation_id'] ) : null,
					'quantity'     => ScalarCoercion::coerce_string( $item['quantity'] ?? null, '1' ),
					'subtotal'     => ScalarCoercion::coerce_string( $item['subtotal'] ?? null, '0' ),
					'total'        => ScalarCoercion::coerce_string( $item['total'] ?? null, '0' ),
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
				$record[ $column ] = isset( $address[ $column ] ) ? ScalarCoercion::coerce_string( $address[ $column ] ) : null;
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
			// The engine's own contract-meta columns, not post/order meta; the
			// slow-meta-query heuristic does not apply.
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
				$by_type[ ScalarCoercion::coerce_string( $row['address_type'] ?? null ) ] = self::as_string_keyed( $row );
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

		// The engine's own contract-meta columns, not post/order meta; the
		// slow-meta-query heuristic does not apply.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE contract_id = %d", $contract_id ), ARRAY_A );

		$meta = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$meta[ ScalarCoercion::coerce_string( $row['meta_key'] ?? null ) ] = ScalarCoercion::coerce_string( $row['meta_value'] ?? null );
			}
		}

		return $meta;
	}

	/**
	 * A change-detection signature for an item set: each item projected to the
	 * comparable columns, with money / count fields coerced to the fixed shape MySQL
	 * stores (DECIMAL scale, INT-as-string) so a no-op round-trip compares equal in
	 * {@see self::sync_children()}. The id, `contract_id`, and column order are ignored.
	 *
	 * @param array<int, array<string, mixed>> $items Item arrays (loaded rows or entity items).
	 * @return array<int, array<string, string|null>> Per-item comparable projection.
	 */
	private function items_signature( array $items ): array {
		$signature = array();

		foreach ( $items as $item ) {
			$signature[] = array(
				'item_name'    => ScalarCoercion::coerce_string( $item['item_name'] ?? null ),
				'item_type'    => ScalarCoercion::coerce_string( $item['item_type'] ?? null, 'line_item' ),
				'product_id'   => isset( $item['product_id'] ) ? (string) ScalarCoercion::coerce_int( $item['product_id'] ) : null,
				'variation_id' => isset( $item['variation_id'] ) ? (string) ScalarCoercion::coerce_int( $item['variation_id'] ) : null,
				'quantity'     => number_format( ScalarCoercion::coerce_float( $item['quantity'] ?? 1 ), 4, '.', '' ),
				'subtotal'     => number_format( ScalarCoercion::coerce_float( $item['subtotal'] ?? 0 ), 8, '.', '' ),
				'total'        => number_format( ScalarCoercion::coerce_float( $item['total'] ?? 0 ), 8, '.', '' ),
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
				$value             = isset( $address[ $column ] ) ? ScalarCoercion::coerce_string( $address[ $column ] ) : '';
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
	 * Normalize a taxes value to canonical JSON (or null), so a loaded JSON string and
	 * the entity's decoded array compare equal regardless of which side it came from.
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
