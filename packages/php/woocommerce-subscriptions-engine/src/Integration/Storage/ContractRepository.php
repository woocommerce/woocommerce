<?php
/**
 * Persistence for the live {@see Contract} (row + items / addresses) plus contract
 * meta and targeted cycle access. Owns the $wpdb access across the contract-side tables.
 *
 * The contract is the live source of truth. A chain is NOT a stored entity: it is
 * the pair `(contract_id, kind)`, with its head and counters derived from the cycle
 * rows. The entity never carries a cycle graph in memory, so cycles are reached
 * through purpose-built reads ({@see self::find_cycle_history()}, etc.), appended one at a
 * time ({@see self::append_cycle()}) and changed only through compare-and-set writes
 * ({@see self::transition_cycle()}). Cycle snapshot ids are stored as given and their
 * value objects attached on read. Meta is read and written only through the meta methods.
 *
 * Every write is fenced by the per-contract lock ({@see ContractLock}): with a lock it lands
 * only while that lock lives, without one only while no lock lives. It opens no transactions
 * and keeps no object cache.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLock;
use Automattic\WooCommerce\SubscriptionsEngine\Api\ContractLockedException;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\ContractStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Cycle;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\CycleStatus;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\Coercion;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\ItemsSnapshot;
use Automattic\WooCommerce\SubscriptionsEngine\Core\ValueObject\PlanSnapshot;

defined( 'ABSPATH' ) || exit;

/**
 * Live contract repository with targeted cycle access.
 */
final class ContractRepository {

	/**
	 * Logger source tag.
	 */
	private const LOG_SOURCE = 'woocommerce-subscriptions-engine';

	/**
	 * Operation label of the lock the engine takes around a multi-statement write made without one.
	 */
	private const INTERNAL_LOCK_OPERATION = 'subscriptions_engine';

	/**
	 * The contract columns every contract read selects: the row plus whether its lock is live
	 * on the database clock. `lock_token` is stripped before hydration.
	 */
	private const CONTRACT_SELECT = '*, ( lock_token IS NOT NULL AND locked_until_gmt > UTC_TIMESTAMP() ) AS lock_live';

	/**
	 * SQL condition: the contract row carries the live lock with the bound token.
	 */
	private const HOLDS_LOCK = 'lock_token = %s AND locked_until_gmt > UTC_TIMESTAMP()';

	/**
	 * Insert a new, unlocked contract and its items and addresses (parent row first). When a
	 * child insert fails, the new contract is deleted again.
	 *
	 * @param Contract $contract Contract to insert, without a creation key (a keyed one goes through {@see self::insert_locked()}).
	 * @return int The new contract id.
	 * @throws \RuntimeException If the contract row or a child row insert fails.
	 */
	public function insert( Contract $contract ): int {
		$this->insert_row( $contract, null );
		$this->insert_children_or_discard( $contract );

		return (int) $contract->get_id();
	}

	/**
	 * Insert a new contract locked from birth: the row INSERT stamps the lock, then items and
	 * addresses follow under it. When a child insert fails, the new contract (and its lock) is
	 * deleted again, so a retry is not blocked by its own creation key.
	 *
	 * @param Contract $contract  Contract to insert.
	 * @param string   $operation Operation label, 1-64 of `[a-z0-9_-]`.
	 * @return ContractLock|null The lock on the new contract; null when the extension already holds its creation key.
	 * @throws \InvalidArgumentException If `$operation` is not a valid label.
	 * @throws \RuntimeException If the contract row or a child row insert fails.
	 */
	public function insert_locked( Contract $contract, string $operation ): ?ContractLock {
		self::assert_operation( $operation );

		$token    = bin2hex( random_bytes( 16 ) );
		$inserted = $this->insert_row(
			$contract,
			array(
				'token'     => $token,
				'operation' => $operation,
			)
		);
		if ( ! $inserted ) {
			return null;
		}

		$lock = self::new_lock( (int) $contract->get_id(), $operation, $token );

		$this->insert_children_or_discard( $contract );

		return $lock;
	}

	/**
	 * Insert a new contract's items and addresses; on a failure, best-effort delete the new
	 * contract with its children, then rethrow. A failed delete is logged, never thrown, so the
	 * child insert failure is what the caller sees.
	 *
	 * @param Contract $contract The new contract, carrying its id and children.
	 * @throws \Throwable The child insert failure, rethrown (a `RuntimeException` for a failed insert).
	 */
	private function insert_children_or_discard( Contract $contract ): void {
		global $wpdb;

		$contract_id = (int) $contract->get_id();

		try {
			$this->insert_items( $contract_id, $contract->get_items() );
			$this->insert_addresses( $contract_id, $contract->get_addresses() );
		} catch ( \Throwable $e ) {
			try {
				$discarded = $this->delete( $contract_id );
				$reason    = $wpdb->last_error;
			} catch ( \Throwable $cleanup ) {
				$discarded = false;
				$reason    = $cleanup->getMessage();
			}
			if ( ! $discarded ) {
				wc_get_logger()->error(
					sprintf( 'ContractRepository: could not discard contract %d after its items or addresses failed to insert; it stays without them. %s', $contract_id, $reason ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $contract_id,
					)
				);
			}
			throw $e;
		}
	}

	/**
	 * Insert the contract row, optionally stamping a lock on the database clock in the same
	 * statement, and set the new id on the entity. Hand-built, so strings are checked against
	 * their column lengths first, as `$wpdb->insert()` would.
	 *
	 * @param Contract                                     $contract Contract to insert.
	 * @param array{token: string, operation: string}|null $lock     Lock to stamp, or null.
	 * @return bool False when the `(extension_slug, creation_key)` pair is taken (nothing inserted).
	 * @throws \RuntimeException If the insert fails for any other reason.
	 */
	private function insert_row( Contract $contract, ?array $lock ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$now   = gmdate( 'Y-m-d H:i:s' );

		$columns                     = $contract->to_storage();
		$columns['date_created_gmt'] = $now;
		$columns['date_updated_gmt'] = $now;

		$names        = array();
		$placeholders = array();
		$values       = array();
		foreach ( $columns as $column => $value ) {
			$names[] = "`{$column}`";
			if ( null === $value ) {
				$placeholders[] = 'NULL';
				continue;
			}
			if ( is_string( $value ) ) {
				self::assert_fits_column( $table, $column, $value );
			}
			$placeholders[] = '%s';
			$values[]       = (string) $value;
		}

		if ( null !== $lock ) {
			array_push( $names, '`lock_token`', '`locked_until_gmt`', '`lock_operation`' );
			array_push( $placeholders, '%s', 'DATE_ADD( UTC_TIMESTAMP(), INTERVAL %d SECOND )', '%s' );
			array_push( $values, $lock['token'], ContractLock::TTL_SECONDS, $lock['operation'] );
		}

		$name_list        = implode( ', ', $names );
		$placeholder_list = implode( ', ', $placeholders );

		// A duplicate creation key is an expected outcome, so `$wpdb` must not print or log it;
		// every failure is still read from `last_error` and thrown below.
		$suppressed = $wpdb->suppress_errors( true );
		try {
			// Column names come from the entity's storage keys, never from input; every value is a placeholder.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$inserted = $wpdb->query( $wpdb->prepare( "INSERT INTO `{$table}` ( {$name_list} ) VALUES ( {$placeholder_list} )", $values ) );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		} finally {
			$wpdb->suppress_errors( $suppressed );
		}

		if ( false === $inserted ) {
			$db_error = $wpdb->last_error;
			// Only the `creation` UNIQUE index can reject a new row as a duplicate.
			if ( false !== stripos( $db_error, 'Duplicate entry' ) && false !== stripos( $db_error, "creation'" ) ) {
				return false;
			}
			throw new \RuntimeException( esc_html( 'Failed to insert contract. ' . $db_error ) );
		}

		$contract->set_id( (int) $wpdb->insert_id );

		return true;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Write the named contract columns from the entity in one statement, and replace items /
	 * addresses when named. With `$lock` the row write lands only while that lock lives; without
	 * one only while no lock lives, and items / addresses are replaced under an internal lock.
	 *
	 * @param Contract           $contract Contract carrying the values. Must have an id.
	 * @param array<int, string> $fields   Contract column names, plus `items` / `addresses`.
	 * @param ContractLock|null  $lock     The caller's lock on this contract, or null.
	 * @return bool False when the contract row no longer exists (nothing is written).
	 * @throws \InvalidArgumentException If a field is not a writable contract column, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \RuntimeException If the contract has no id or the write fails.
	 */
	public function update_fields( Contract $contract, array $fields, ?ContractLock $lock = null ): bool {
		$contract_id = $contract->get_id();
		if ( null === $contract_id ) {
			throw new \RuntimeException( 'Cannot update a contract that has no id. Use ContractRepository::insert() for a new contract.' );
		}

		if ( null !== $lock ) {
			self::assert_lock_for( $lock, $contract_id );
		}

		$storage = $contract->to_storage();
		foreach ( $fields as $field ) {
			if ( 'items' === $field || 'addresses' === $field ) {
				continue;
			}
			if ( 'extension_slug' === $field || 'creation_key' === $field || ! array_key_exists( $field, $storage ) ) {
				throw new \InvalidArgumentException( esc_html( sprintf( 'Cannot update contract field "%s".', $field ) ) );
			}
		}

		$replaces_children = in_array( 'items', $fields, true ) || in_array( 'addresses', $fields, true );
		if ( null !== $lock || ! $replaces_children ) {
			return $this->write_fields( $contract, $fields, $lock );
		}

		$internal_lock = $this->acquire_lock( $contract_id, self::INTERNAL_LOCK_OPERATION );
		if ( null === $internal_lock ) {
			$holder = $this->get_lock_holder( $contract_id );
			if ( ! $holder['exists'] ) {
				return false;
			}
			throw self::locked_exception( $contract_id, $holder ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the exception message is not output.
		}

		try {
			return $this->write_fields( $contract, $fields, $internal_lock );
		} finally {
			// A failed release must not replace the write's result or exception; the lock expires on its own.
			try {
				$this->release_lock( $internal_lock );
			} catch ( \RuntimeException $e ) {
				wc_get_logger()->warning(
					sprintf( 'ContractRepository::update_fields(): failed to release the internal lock on contract %d; it expires on its own. %s', $contract_id, $e->getMessage() ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $contract_id,
					)
				);
			}
		}
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * The fenced row write plus the named child replacements behind {@see self::update_fields()}.
	 *
	 * @param Contract           $contract Contract carrying its id and the values.
	 * @param array<int, string> $fields   Requested fields (validated).
	 * @param ContractLock|null  $lock     The lock, or null (row-only writes).
	 * @return bool False when the contract row no longer exists.
	 * @throws ContractLockedException If the fence refuses the write.
	 */
	private function write_fields( Contract $contract, array $fields, ?ContractLock $lock ): bool {
		$contract_id = (int) $contract->get_id();
		$columns     = array_intersect_key( $contract->to_storage(), array_flip( $fields ) );
		$updated     = $this->write_contract_row( $contract_id, $columns, $lock );

		// Every row write bumps `write_seq`, so zero rows means the fence refused the write or the row is gone.
		if ( 0 === $updated ) {
			$holder = $this->get_lock_holder( $contract_id );
			if ( ! $holder['exists'] ) {
				return false;
			}
			throw self::locked_exception( $contract_id, $holder ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the exception message is not output.
		}

		if ( null === $lock ) {
			return true;
		}

		if ( in_array( 'items', $fields, true ) ) {
			$this->fence_children( $lock );
			$this->replace_items( $contract_id, $contract->get_items() );
		}

		if ( in_array( 'addresses', $fields, true ) ) {
			$this->fence_children( $lock );
			$this->replace_addresses( $contract_id, $contract->get_addresses() );
		}

		return true;
	}

	/**
	 * Fetch a contract by id with its items, addresses and frozen plan terms
	 * ({@see Contract::get_plan_snapshot()}, no live {@see PlanRepository} join). Cycles are
	 * not hydrated: use the targeted cycle reads. Paths that do not need the children use
	 * {@see self::find_summary()}.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null Hydrated contract, or null if not found.
	 * @throws \RuntimeException If the contract row read fails.
	 */
	public function find( int $id ): ?Contract {
		$row = $this->find_contract_row( $id );
		if ( null === $row ) {
			return null;
		}

		return $this->hydrate_row( $row );
	}

	/**
	 * Fetch a contract by id when `$customer_id` owns it - the ownership-checked full
	 * read behind the customer portal.
	 *
	 * The ownership filter lives in the row query itself, so an unknown id and a
	 * contract owned by someone else are the SAME null - one read, nothing for a
	 * caller to distinguish (the anti-IDOR rule at the storage layer). The returned
	 * contract is hydrated exactly like {@see self::find()}.
	 *
	 * @param int $contract_id Contract id.
	 * @param int $customer_id Customer that must own the contract.
	 * @return Contract|null Hydrated contract when owned by `$customer_id`, else null.
	 */
	public function find_for_customer( int $contract_id, int $customer_id ): ?Contract {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$select = self::CONTRACT_SELECT;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT {$select} FROM {$table} WHERE id = %d AND customer_id = %d", $contract_id, $customer_id ), ARRAY_A );
		if ( ! is_array( $row ) ) {
			return null;
		}

		return $this->hydrate_row( self::strip_lock_token( Coercion::coerce_string_keyed( $row ) ) );
	}

	/**
	 * Hydrate a fetched contract row into the full live entity: frozen plan terms,
	 * items and addresses - the one full-read construction path.
	 *
	 * @param array<string, mixed> $row Contract row.
	 */
	private function hydrate_row( array $row ): Contract {
		$id = Coercion::coerce_int( $row['id'] ?? 0 );

		return Contract::from_storage(
			$row,
			$this->find_plan_snapshot( Coercion::coerce_nullable_int( $row['plan_snapshot_id'] ?? null ) ),
			$this->find_items( $id ),
			$this->find_addresses( $id )
		);
	}

	/**
	 * Lightweight read: the contract row only, no children. The row IS the live state,
	 * so list screens and guards that need only identity + schedule avoid loading children.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null The contract (row only), or null if not found.
	 * @throws \RuntimeException If the read fails.
	 */
	public function find_summary( int $id ): ?Contract {
		$row = $this->find_contract_row( $id );
		if ( null === $row ) {
			return null;
		}

		return Contract::from_storage( $row );
	}

	/**
	 * The columns a list query may order by, mapped from the public sort key to the
	 * stored column. A whitelist, never raw input, is the ORDER BY column - so an
	 * `orderby` arg can only ever name one of these four columns.
	 */
	private const ORDERBY_COLUMNS = array(
		'id'           => 'id',
		'next_payment' => 'next_payment_gmt',
		'total'        => 'billing_total',
		'start'        => 'start_gmt',
	);

	/**
	 * Query a window of contracts for list screens. Newest first (id DESC) by default,
	 * or by a whitelisted `orderby`/`order`. Hydrated lightweight (row only, no children)
	 * like {@see self::find_summary()}.
	 *
	 * Takes a WooCommerce-style args array (cf. `wc_get_orders()`) rather than positional
	 * paging args, so the shape widens to status / search / sort without a signature change.
	 * The status + search filter is shared with {@see self::count()} via
	 * {@see self::build_list_criteria()}, so a list page and its total count always agree.
	 *
	 * @param array<string, mixed> $args {
	 *     Optional. Query args.
	 *
	 *     @type int    $limit   Maximum contracts to return. Default 20.
	 *     @type int    $offset  Rows to skip (for paging). Default 0.
	 *     @type string $status  Filter to one status ({@see ContractStatus}); ignored when empty or invalid.
	 *     @type string $orderby One of the {@see self::ORDERBY_COLUMNS} keys (id, next_payment, total, start); default id.
	 *     @type string $order   ASC or DESC (case-insensitive); default DESC.
	 *     @type string $search  A numeric term matches contract id or origin order id; a text term matches the owning customer.
	 * }
	 * @return array<int, Contract> Contracts in the requested order.
	 */
	public function query( array $args = array() ): array {
		global $wpdb;

		// Clamp to non-negative so a negative arg cannot emit `LIMIT -n` / `OFFSET -n`
		// (invalid MySQL); mirrors the `$limit < 1` guard in find_due(). The public
		// facade methods are callable directly, so this is defence in depth beyond the
		// list table's own paging math.
		$limit  = isset( $args['limit'] ) && is_numeric( $args['limit'] ) ? max( 0, (int) $args['limit'] ) : 20;
		$offset = isset( $args['offset'] ) && is_numeric( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$select = self::CONTRACT_SELECT;

		list( $where_sql, $where_params ) = $this->build_list_criteria( $args );
		$order_by                         = $this->build_order_by( $args );

		$params   = $where_params;
		$params[] = $limit;
		$params[] = $offset;

		// The WHERE placeholders join dynamically, so the sniff cannot count them; the
		// ORDER BY column is from a fixed whitelist, never raw input (see build_order_by()).
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select}
				FROM {$table}
				WHERE {$where_sql}
				ORDER BY {$order_by}
				LIMIT %d OFFSET %d",
				$params
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return $this->contracts_from_rows( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Build the shared WHERE clause and its ordered placeholder params for a list query -
	 * the status + search filter that {@see self::query()} and {@see self::count()} apply
	 * identically, so a page and its total always describe the same set. Paging and sort
	 * are NOT part of this (they never change which rows match), so `count()` reuses it as is.
	 *
	 * The clause is built from placeholders following the {@see self::find_by_customer_id()}
	 * dynamic-placeholder pattern; the empty-filter case returns `1=1` so the caller can
	 * always interpolate `WHERE {$where}` unconditionally. A `status` is honoured only when
	 * a known {@see ContractStatus}; an empty or unknown status is dropped (never injected).
	 *
	 * A `search` term:
	 * - numeric  -> `( id = %d OR origin_order_id = %d )`;
	 * - non-empty text -> matched against the owning customer's email / display name / login via a
	 *   users-table subquery ({@see self::build_customer_search_clause()}), so a term matching no
	 *   customer naturally returns no rows (the subquery is empty) rather than everything, with no
	 *   cap on how many customers can match.
	 *
	 * @param array<string, mixed> $args Query args (only `status` and `search` are read).
	 * @return array{0: string, 1: array<int, int|string>} The WHERE SQL and its params in order.
	 */
	private function build_list_criteria( array $args ): array {
		$clauses = array();
		$params  = array();

		$status = isset( $args['status'] ) && is_string( $args['status'] ) ? $args['status'] : '';
		if ( '' !== $status && ContractStatus::is_registered( $status ) ) {
			$clauses[] = 'status = %s';
			$params[]  = $status;
		}

		$search = isset( $args['search'] ) && is_scalar( $args['search'] ) ? trim( (string) $args['search'] ) : '';
		if ( '' !== $search ) {
			if ( ctype_digit( $search ) ) {
				$clauses[] = '( id = %d OR origin_order_id = %d )';
				$params[]  = (int) $search;
				$params[]  = (int) $search;
			} else {
				list( $clause, $like_params ) = $this->build_customer_search_clause( $search );
				$clauses[]                    = $clause;
				foreach ( $like_params as $like_param ) {
					$params[] = $like_param;
				}
			}
		}

		$where_sql = array() === $clauses ? '1 = 1' : implode( ' AND ', $clauses );

		return array( $where_sql, $params );
	}

	/**
	 * Build the `ORDER BY` fragment from the whitelisted `orderby`/`order` args. The column
	 * is only ever one of {@see self::ORDERBY_COLUMNS} (default `id`) and the direction only
	 * `ASC` or `DESC` (default `DESC`), so raw input never reaches the SQL - the fragment is
	 * safe to interpolate. A stable `id` tiebreak keeps paging deterministic when the primary
	 * column has ties.
	 *
	 * @param array<string, mixed> $args Query args (only `orderby` and `order` are read).
	 * @return string The `ORDER BY` fragment (without the `ORDER BY` keyword).
	 */
	private function build_order_by( array $args ): string {
		$orderby_key = isset( $args['orderby'] ) && is_string( $args['orderby'] ) ? $args['orderby'] : 'id';
		$column      = self::ORDERBY_COLUMNS[ $orderby_key ] ?? 'id';

		$order = isset( $args['order'] ) && is_string( $args['order'] ) ? strtoupper( $args['order'] ) : 'DESC';
		$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';

		if ( 'id' === $column ) {
			return "id {$order}";
		}

		return "{$column} {$order}, id {$order}";
	}

	/**
	 * Build the customer-search clause for a non-numeric term - the text-search half of
	 * {@see self::build_list_criteria()}. Matches a contract whose owning customer's email,
	 * display name or login contains the term, via a subquery on the users table rather than a
	 * materialised id list, so the database bounds a broad term instead of a truncated
	 * `IN (...)` set (no matches are silently dropped, and `count()` stays accurate). The three
	 * `LIKE` params are the same escaped term. WP-native user-table access is Integration-layer
	 * work and stays out of `Core\`.
	 *
	 * @param string $term The non-empty, non-numeric search term.
	 * @return array{0: string, 1: array<int, string>} The clause SQL and its LIKE params in order.
	 */
	private function build_customer_search_clause( string $term ): array {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $term ) . '%';

		// $wpdb->users is a trusted core table name; the term is bound through the %s LIKE
		// placeholders when the caller runs this fragment through $wpdb->prepare().
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$clause = "customer_id IN ( SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s OR display_name LIKE %s OR user_login LIKE %s )";

		return array( $clause, array( $like, $like, $like ) );
	}

	/**
	 * The contract count per status - the views bar's read. One `GROUP BY status` scan,
	 * returned as a map keyed by EVERY registered contract status ({@see ContractStatus::get_all()}:
	 * the engine defaults plus extension registrations; absent statuses filled with 0) and in
	 * that order, so a consumer can render a fixed set of views without knowing which statuses
	 * currently have rows. A stored status that is not registered is not counted as a key. The
	 * `All` total is the caller's `array_sum()`. Independent of any search / paging (WC-style:
	 * the views count the whole store, not the current page).
	 *
	 * @return array<string, int> Status => count, every registered status present.
	 */
	public function count_by_status(): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A );

		// Seed every registered status at 0 so the map is complete and stably ordered.
		$counts = array();
		foreach ( ContractStatus::get_all() as $status ) {
			$counts[ $status ] = 0;
		}

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$status = Coercion::coerce_string( $row['status'] ?? '' );
			// A row whose stored status is not registered is ignored, not added as a
			// stray key - the map stays exactly ContractStatus::get_all().
			if ( array_key_exists( $status, $counts ) ) {
				$counts[ $status ] = Coercion::coerce_int( $row['total'] ?? 0 );
			}
		}

		return $counts;
	}

	/**
	 * The number of contracts matching a list filter - the total behind a list view's
	 * pagination. Applies the SAME status + search WHERE as {@see self::query()} (via
	 * {@see self::build_list_criteria()}), and ignores paging / sort, so the count always
	 * describes the full set the page is a window onto.
	 *
	 * @param array<string, mixed> $args Query args (only `status` and `search` are read).
	 * @return int The matching contract count.
	 */
	public function count( array $args = array() ): int {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		list( $where_sql, $params ) = $this->build_list_criteria( $args );

		if ( array() === $params ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}" );
		} else {
			// The WHERE placeholders join dynamically, so the sniff sees none in the literal.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}", $params ) );
		}

		return Coercion::coerce_int( $total );
	}

	/**
	 * The line-item count per contract - the list "Items" column's read. One
	 * `GROUP BY contract_id` scan of the items table over a page of contract ids,
	 * returned as a map keyed by EVERY requested id (ids with no items filled with
	 * 0), so the caller renders a value for every row without a per-row query. Ids
	 * are de-duplicated and cast to int before binding; an empty request is a no-op.
	 *
	 * @param array<int, int> $contract_ids Contract ids to count items for.
	 * @return array<int, int> Contract id => line-item count, one entry per requested id.
	 */
	public function count_items_by_contract( array $contract_ids ): array {
		// Keep only scalar, positive ids (this is a public entry point): casting an object or
		// array with intval would warn and collapse to a bogus 0. Array keys de-duplicate.
		$ids = array();
		foreach ( $contract_ids as $contract_id ) {
			if ( ! is_scalar( $contract_id ) ) {
				continue;
			}
			$id = (int) $contract_id;
			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}
		$ids = array_values( $ids );

		$counts = array_fill_keys( $ids, 0 );
		if ( array() === $ids ) {
			return $counts;
		}

		global $wpdb;

		$table        = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// The id placeholders join dynamically, so the sniff sees none in the literal.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT contract_id, COUNT(*) AS total FROM {$table} WHERE contract_id IN ({$placeholders}) GROUP BY contract_id", $ids ), ARRAY_A );

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$cid = Coercion::coerce_int( $row['contract_id'] ?? 0 );
			if ( array_key_exists( $cid, $counts ) ) {
				$counts[ $cid ] = Coercion::coerce_int( $row['total'] ?? 0 );
			}
		}

		return $counts;
	}

	/**
	 * Construct a page of list contracts from fetched rows: row-only children, with the
	 * frozen plan terms batch-loaded in ONE `IN()` read for the whole page - the one
	 * list construction path, so every list read carries the snapshot the same way.
	 *
	 * @param array<int, mixed> $rows Fetched rows (non-arrays are skipped).
	 * @return array<int, Contract> Contracts in row order.
	 */
	private function contracts_from_rows( array $rows ): array {
		$clean_rows = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$clean_rows[] = self::strip_lock_token( Coercion::coerce_string_keyed( $row ) );
			}
		}

		$snapshot_ids = array();
		foreach ( $clean_rows as $row ) {
			$snapshot_id = Coercion::coerce_nullable_int( $row['plan_snapshot_id'] ?? null );
			if ( null !== $snapshot_id ) {
				$snapshot_ids[ $snapshot_id ] = $snapshot_id;
			}
		}
		$snapshots = $this->find_plan_snapshots( array_values( $snapshot_ids ) );

		$contracts = array();
		foreach ( $clean_rows as $row ) {
			$snapshot_id = Coercion::coerce_nullable_int( $row['plan_snapshot_id'] ?? null );
			$contracts[] = Contract::from_storage( $row, null !== $snapshot_id ? ( $snapshots[ $snapshot_id ] ?? null ) : null );
		}

		return $contracts;
	}

	/**
	 * The due dispatcher's scan: contracts of `$owners` with an engine-kept schedule whose
	 * `next_payment_gmt` is at or before `$now` and that carry no live lock. Status is not read:
	 * whether a due contract acts is its owner's decision. Least recently locked first, so a
	 * contract left due by its handler cannot starve the others.
	 *
	 * @param DateTimeImmutable  $now    The cutoff moment; contracts due at or before it.
	 * @param int                $limit  Maximum rows to return (the batch size).
	 * @param array<int, string> $owners Owner slugs whose contracts may be selected.
	 * @return array<int, array{id: int, extension_slug: string, next_payment_gmt: string}> Due rows, least recently locked first, then oldest-due.
	 */
	public function find_due( DateTimeImmutable $now, int $limit, array $owners ): array {
		if ( $limit < 1 ) {
			return array();
		}

		$owners = array_values( array_unique( array_map( 'strval', $owners ) ) );
		if ( array() === $owners ) {
			return array();
		}

		global $wpdb;

		$contracts          = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$cutoff             = self::format_utc( $now );
		$owner_placeholders = implode( ', ', array_fill( 0, count( $owners ), '%s' ) );
		$no_live_lock       = self::no_live_lock();

		$args = array_merge( $owners, array( $cutoff, Contract::SCHEDULE_SOURCE_ENGINE, $limit ) );

		// Table names cannot be bound, so they are interpolated; every value is a placeholder.
		// The owner placeholder list is generated, one `%s` per owner in `$args`.
		// Driven by the `due_owner` index; schedule source and lock are residual filters, and lock
		// liveness is read on the database clock, not `$now`. NULL `locked_until_gmt` (never
		// locked) sorts first; the sort filesorts the owners' due backlog and `LIMIT` bounds it.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, extension_slug, next_payment_gmt
				FROM {$contracts}
				WHERE extension_slug IN ( {$owner_placeholders} ) AND next_payment_gmt IS NOT NULL AND next_payment_gmt <= %s
				  AND schedule_source = %s
				  AND {$no_live_lock}
				ORDER BY locked_until_gmt ASC, next_payment_gmt ASC, id ASC
				LIMIT %d",
				$args
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		// A failed scan otherwise reads exactly like "nothing due" and dispatch stalls
		// store-wide with no signal; the return stays empty either way.
		if ( '' !== $wpdb->last_error ) {
			wc_get_logger()->error(
				sprintf( 'ContractRepository::find_due(): due-index scan failed - due dispatch may stall until this is fixed. %s', $wpdb->last_error ),
				array( 'source' => self::LOG_SOURCE )
			);
		}

		$result = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$result[] = array(
				'id'               => Coercion::coerce_int( $row['id'] ?? 0 ),
				'extension_slug'   => Coercion::coerce_string( $row['extension_slug'] ?? '' ),
				'next_payment_gmt' => Coercion::coerce_string( $row['next_payment_gmt'] ?? '' ),
			);
		}

		return $result;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Take the contract's lock for `$operation`: one conditional UPDATE that lands only while
	 * no live lock is held (an expired one is taken over), stamped on the database clock. With
	 * `$seen_next_payment_gmt` it also requires the contract still to carry that due moment.
	 *
	 * @param int         $contract_id           Contract id.
	 * @param string      $operation             Operation label, 1-64 of `[a-z0-9_-]`.
	 * @param string|null $seen_next_payment_gmt The due moment the caller read, or null for no check.
	 * @return ContractLock|null The lock, or null when the contract is locked, missing or its due moment moved.
	 * @throws \InvalidArgumentException If `$operation` is not a valid label.
	 * @throws \RuntimeException If the write fails.
	 */
	public function acquire_lock( int $contract_id, string $operation, ?string $seen_next_payment_gmt = null ): ?ContractLock {
		global $wpdb;

		self::assert_operation( $operation );

		$table        = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$no_live_lock = self::no_live_lock();
		$token        = bin2hex( random_bytes( 16 ) );
		$values       = array( $token, ContractLock::TTL_SECONDS, $operation, $contract_id );
		$due          = '';
		if ( null !== $seen_next_payment_gmt ) {
			$due      = ' AND next_payment_gmt = %s';
			$values[] = $seen_next_payment_gmt;
		}

		$sql = "UPDATE {$table} SET lock_token = %s, locked_until_gmt = DATE_ADD( UTC_TIMESTAMP(), INTERVAL %d SECOND ), lock_operation = %s
			WHERE id = %d{$due} AND {$no_live_lock}";

		// Fixed SQL fragments only; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$updated = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $updated ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to lock contract %d: %s', $contract_id, $wpdb->last_error ) ) );
		}

		return 1 === $updated ? self::new_lock( $contract_id, $operation, $token ) : null;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Whether the contract exists and who holds its lock, read on the database clock. The
	 * operation and expiry are set only while the lock is live. Writes use it to shape errors
	 * only (it may lag on a replica), never to decide that a write landed.
	 *
	 * @param int $contract_id Contract id.
	 * @return array{exists: bool, operation: ?string, locked_until_gmt: ?string}
	 * @throws \RuntimeException If the read fails.
	 */
	public function get_lock_holder( int $contract_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$sql = "SELECT ( lock_token IS NOT NULL AND locked_until_gmt > UTC_TIMESTAMP() ) AS live, lock_operation, locked_until_gmt
			FROM {$table} WHERE id = %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $contract_id ), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to read the lock on contract %d: %s', $contract_id, $wpdb->last_error ) ) );
		}

		if ( ! is_array( $row ) ) {
			return array(
				'exists'           => false,
				'operation'        => null,
				'locked_until_gmt' => null,
			);
		}

		$live = ! empty( $row['live'] );

		return array(
			'exists'           => true,
			'operation'        => $live ? Coercion::coerce_nullable_string( $row['lock_operation'] ?? null ) : null,
			'locked_until_gmt' => $live ? Coercion::coerce_nullable_string( $row['locked_until_gmt'] ?? null ) : null,
		);
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Extend a held lock to a full TTL from the database's now, using its single extend
	 * allowance. Called by {@see self::fence_children()} only, after checking the allowance.
	 *
	 * @param ContractLock $lock The held lock.
	 * @throws ContractLockedException If the lock was lost.
	 * @throws \RuntimeException If the write fails.
	 */
	private function extend_lock( ContractLock $lock ): void {
		global $wpdb;

		$state = self::get_state_of( $lock );

		$table      = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$holds_lock = self::HOLDS_LOCK;

		$sql    = "UPDATE {$table} SET locked_until_gmt = DATE_ADD( UTC_TIMESTAMP(), INTERVAL %d SECOND ), write_seq = write_seq + 1 WHERE id = %d AND {$holds_lock}";
		$values = array( ContractLock::TTL_SECONDS, $lock->get_contract_id(), $state->get_token() );

		// Fixed SQL fragments only; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$updated = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $updated ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to extend the lock on contract %d: %s', $lock->get_contract_id(), $wpdb->last_error ) ) );
		}

		// The `write_seq` bump makes zero rows mean the lock is no longer held.
		if ( 0 === $updated ) {
			$this->throw_locked( $lock->get_contract_id() );
		}

		$state->stamp( ContractLock::TTL_SECONDS );
		$state->mark_extended();
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Release a lock. Idempotent, and guarded by the token, so it never clears a lock another
	 * operation took over. `locked_until_gmt` keeps the release moment (the dispatch fairness key).
	 *
	 * @param ContractLock $lock The lock.
	 * @throws \RuntimeException If the write fails (the lock is still marked released and expires on its own).
	 */
	public function release_lock( ContractLock $lock ): void {
		global $wpdb;

		$state = self::get_state_of( $lock );
		if ( $state->is_released() ) {
			return;
		}

		$token = $state->get_token();
		$state->mark_released();

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET lock_token = NULL, lock_operation = NULL, locked_until_gmt = UTC_TIMESTAMP() WHERE id = %d AND lock_token = %s",
				$lock->get_contract_id(),
				$token
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( false === $updated ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to release the lock on contract %d: %s', $lock->get_contract_id(), $wpdb->last_error ) ) );
		}
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Fence a child-table write (cycles, meta, items, addresses) made with `$lock`; within 60 s
	 * of the lock's last database stamp it costs no query. Past that, the lock is extended once when
	 * the local estimate gives 120 s or less left; otherwise it is re-verified on the database clock
	 * and trusted for up to 60 s more, extended once with 60 s or less left, and refused once the
	 * extend is used.
	 *
	 * @param ContractLock $lock The held lock.
	 * @throws ContractLockedException If the lock was released, lost or is about to expire.
	 * @throws \RuntimeException If the verify read or the extend write fails.
	 */
	public function fence_children( ContractLock $lock ): void {
		global $wpdb;

		$state = self::get_state_of( $lock );
		if ( $state->is_released() ) {
			$this->throw_locked( $lock->get_contract_id() );
		}
		if ( $state->is_trusted() ) {
			return;
		}
		if ( ! $state->has_extended() && $state->is_near_expiry() ) {
			$this->extend_lock( $lock );
			return;
		}

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		$sql = "SELECT TIMESTAMPDIFF( SECOND, UTC_TIMESTAMP(), locked_until_gmt ) FROM {$table} WHERE id = %d AND lock_token = %s";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$remaining = $wpdb->get_var( $wpdb->prepare( $sql, $lock->get_contract_id(), $state->get_token() ) );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to verify the lock on contract %d: %s', $lock->get_contract_id(), $wpdb->last_error ) ) );
		}

		// No row: the lock was lost.
		if ( null === $remaining ) {
			$this->throw_locked( $lock->get_contract_id() );
		}

		$state->stamp( Coercion::coerce_int( $remaining ) );
		if ( $state->is_trusted() ) {
			return;
		}
		if ( ! $state->has_extended() ) {
			$this->extend_lock( $lock );
			return;
		}

		$this->throw_locked( $lock->get_contract_id() );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Refuse a lock that belongs to another contract.
	 *
	 * @param ContractLock $lock        The lock.
	 * @param int          $contract_id The contract being written.
	 * @throws \InvalidArgumentException If the lock is for another contract.
	 */
	public static function assert_lock_for( ContractLock $lock, int $contract_id ): void {
		if ( $lock->get_contract_id() !== $contract_id ) {
			throw new \InvalidArgumentException( esc_html( sprintf( 'The lock belongs to contract %d, not contract %d.', $lock->get_contract_id(), $contract_id ) ) );
		}
	}

	/**
	 * Throw a conflict carrying the contract's current lock holder, if any.
	 *
	 * @param int $contract_id Contract id.
	 * @return never
	 * @throws ContractLockedException Always.
	 */
	private function throw_locked( int $contract_id ): void {
		throw self::locked_exception( $contract_id, $this->get_lock_holder( $contract_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the exception message is not output.
	}

	/**
	 * A conflict carrying the holder already read.
	 *
	 * @param int                                                                $contract_id Contract id.
	 * @param array{exists: bool, operation: ?string, locked_until_gmt: ?string} $holder      The lock holder read.
	 */
	private static function locked_exception( int $contract_id, array $holder ): ContractLockedException {
		$locked_until = null === $holder['locked_until_gmt'] ? null : new DateTimeImmutable( $holder['locked_until_gmt'], new DateTimeZone( 'UTC' ) );

		return new ContractLockedException( $contract_id, $holder['operation'], $locked_until );
	}

	/**
	 * Refuse an operation label that is not a slug of 1-64 lowercase letters, digits,
	 * underscores or hyphens (the schedule source rule).
	 *
	 * @param string $operation Operation label.
	 * @throws \InvalidArgumentException If the label is not a valid slug.
	 */
	private static function assert_operation( string $operation ): void {
		if ( 1 !== preg_match( '/^[a-z0-9_-]{1,64}$/D', $operation ) ) {
			throw new \InvalidArgumentException( 'A lock operation label must be 1-64 lowercase letters, digits, underscores or hyphens.' );
		}
	}

	/**
	 * Build a lock just stored with a full TTL. Scope-bound to the lock class, whose constructor is private.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $operation   Operation label.
	 * @param string $token       The stored token.
	 */
	private static function new_lock( int $contract_id, string $operation, string $token ): ContractLock {
		static $factory = null;

		if ( null === $factory ) {
			$factory = Closure::bind(
				static function ( int $id, string $label, ContractLockState $state ): ContractLock {
					return new ContractLock( $id, $label, $state );
				},
				null,
				ContractLock::class
			);
		}

		return $factory( $contract_id, $operation, new ContractLockState( $token ) );
	}

	/**
	 * A lock's token and trust state. Scope-bound to the lock class, so neither has a public accessor.
	 *
	 * @param ContractLock $lock The lock.
	 */
	private static function get_state_of( ContractLock $lock ): ContractLockState {
		static $reader = null;

		if ( null === $reader ) {
			$reader = Closure::bind(
				static function ( ContractLock $held ): ContractLockState {
					return $held->state;
				},
				null,
				ContractLock::class
			);
		}

		return $reader( $lock );
	}

	/**
	 * SQL condition: the contract row, under `$alias` when given, carries no live lock.
	 *
	 * @param string $alias Contract table alias, or '' for an unaliased table.
	 */
	private static function no_live_lock( string $alias = '' ): string {
		$column = '' === $alias ? '' : $alias . '.';

		return "( {$column}lock_token IS NULL OR {$column}locked_until_gmt <= UTC_TIMESTAMP() )";
	}

	/**
	 * Format a moment as a UTC `Y-m-d H:i:s` column value.
	 *
	 * @param DateTimeImmutable $moment Moment to format.
	 */
	private static function format_utc( DateTimeImmutable $moment ): string {
		return $moment->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Customer-scoped contract list, newest first - the customer portal's list read.
	 *
	 * Hits the `(customer_id, status)` index: scopes to the owner, applies the optional
	 * status filter, and pages. Returns lightweight (row only, no children) {@see Contract}
	 * entities like {@see self::query()} / {@see self::find_summary()} - enough for a list
	 * row without a per-contract child read. An empty result means "this customer has no
	 * matching contracts"; it is never null.
	 *
	 * Takes a WooCommerce-style args array (cf. {@see self::query()}) rather than positional
	 * args, so the shape can widen without a signature change. Ordered by id DESC (monotonic
	 * with creation) so the list is newest-first and stable for paging.
	 *
	 * Each row is row-only (no items / addresses), but its frozen plan terms are
	 * hydrated ({@see Contract::get_plan_snapshot()}) so the list rows carry the billing
	 * cadence off the snapshot - batch-loaded in ONE `IN()` read for the whole page, not
	 * one read per row.
	 *
	 * @param int                       $customer_id Owning customer id.
	 * @param array<string, mixed>|null $args        {
	 *     Optional. Query args.
	 *
	 *     @type int    $limit  Maximum contracts to return. Default 20.
	 *     @type int    $offset Rows to skip (for paging). Default 0.
	 *     @type string|string[] $status Optional status filter: one status or a list of them
	 *                                   ({@see ContractStatus}). Unregistered values are dropped;
	 *                                   ignored when none remain.
	 * }
	 * @return array<int, Contract> Contracts the customer owns, newest first.
	 */
	public function find_by_customer_id( int $customer_id, ?array $args = null ): array {
		global $wpdb;

		$args     = $args ?? array();
		$limit    = isset( $args['limit'] ) && is_numeric( $args['limit'] ) ? (int) $args['limit'] : 20;
		$offset   = isset( $args['offset'] ) && is_numeric( $args['offset'] ) ? (int) $args['offset'] : 0;
		$statuses = array_values(
			array_unique(
				array_filter(
					(array) ( $args['status'] ?? array() ),
					static function ( $status ): bool {
						return is_string( $status ) && ContractStatus::is_registered( $status );
					}
				)
			)
		);

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$select = self::CONTRACT_SELECT;

		// One query for both shapes: the optional status filter joins the WHERE, and
		// every value is a placeholder (table names cannot be bound, so the table is
		// interpolated).
		$where  = 'customer_id = %d';
		$params = array( $customer_id );
		if ( array() !== $statuses ) {
			$where .= ' AND status IN (' . implode( ', ', array_fill( 0, count( $statuses ), '%s' ) ) . ')';
			$params = array_merge( $params, $statuses );
		}
		$params[] = $limit;
		$params[] = $offset;

		// The WHERE placeholders join dynamically, so the sniff cannot count them.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$select}
				FROM {$table}
				WHERE {$where}
				ORDER BY id DESC
				LIMIT %d OFFSET %d",
				$params
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		return $this->contracts_from_rows( is_array( $rows ) ? $rows : array() );
	}

	/**
	 * Contracts whose `origin_order_id` is `$order_id`, oldest first. Row-only reads
	 * (no items / addresses) with their frozen plan terms batch-hydrated.
	 *
	 * @param int $order_id Origin order id.
	 * @return array<int, Contract>
	 */
	public function find_by_origin_order( int $order_id ): array {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$select = self::CONTRACT_SELECT;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$select} FROM {$table} WHERE origin_order_id = %d ORDER BY id ASC", $order_id ), ARRAY_A );

		return $this->contracts_from_rows( is_array( $rows ) ? $rows : array() );
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

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Add a meta row for a contract, like `add_post_meta()`. The insert selects from the contract
	 * row, so without `$lock` it lands only while no lock lives; with `$unique` the same statement
	 * checks the key is absent.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       Meta value; serialized when not scalar.
	 * @param bool              $unique      When true, add nothing if the key already exists.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return int|null The new meta row id, or null when `$unique` and the key exists.
	 * @throws \InvalidArgumentException If `$key` is empty, the contract does not exist, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \RuntimeException If the insert fails.
	 */
	public function add_meta( int $contract_id, string $key, $value, bool $unique = false, ?ContractLock $lock = null ): ?int {
		if ( '' === $key ) {
			throw new \InvalidArgumentException( 'Contract meta key must not be empty.' );
		}

		$this->fence_meta( $contract_id, $lock );

		$meta_id = $this->insert_meta_row( $contract_id, $key, $value, $unique, $lock );
		if ( null !== $meta_id ) {
			return $meta_id;
		}

		if ( $unique && $this->has_meta_key( $contract_id, $key ) ) {
			return null;
		}

		$this->throw_unfenced_insert( $contract_id );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Insert one meta row as `INSERT ... SELECT` from the contract row: without `$lock` only while
	 * no lock lives, with `$if_absent` only while the key has no row, both in the one statement.
	 * The absence check is a non-locking read, so an unlocked add-if-absent then keeps its row only
	 * when it is the key's lowest id; a later concurrent duplicate deletes itself.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       Meta value; serialized when not scalar.
	 * @param bool              $if_absent   Whether to insert only when the key has no row.
	 * @param ContractLock|null $lock        The caller's (fenced) lock, or null.
	 * @return int|null The new meta row id, or null when nothing was inserted (or the duplicate was removed).
	 * @throws \RuntimeException If the insert, or the removal of a duplicate, fails.
	 */
	private function insert_meta_row( int $contract_id, string $key, $value, bool $if_absent, ?ContractLock $lock ): ?int {
		global $wpdb;

		$meta      = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$values    = array( $key, maybe_serialize( $value ), $contract_id );
		$where     = 'c.id = %d';
		if ( null === $lock ) {
			$where .= ' AND ' . self::no_live_lock( 'c' );
		}
		if ( $if_absent ) {
			$where   .= " AND NOT EXISTS ( SELECT 1 FROM {$meta} m WHERE m.contract_id = %d AND m.meta_key = %s )";
			$values[] = $contract_id;
			$values[] = $key;
		}

		// LOCK IN SHARE MODE makes the fence read block on a concurrent lock write under READ COMMITTED too.
		$sql = "INSERT INTO {$meta} ( contract_id, meta_key, meta_value ) SELECT c.id, %s, %s FROM {$contracts} c WHERE {$where} LOCK IN SHARE MODE";

		// Fixed SQL fragments only; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$inserted = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $inserted ) {
			throw new \RuntimeException( sprintf( 'Failed to add contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}
		if ( 1 !== (int) $inserted ) {
			return null;
		}

		$meta_id = (int) $wpdb->insert_id;
		if ( ! $if_absent || null !== $lock ) {
			return $meta_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$first_id = $wpdb->get_var( $wpdb->prepare( "SELECT MIN( id ) FROM {$meta} WHERE contract_id = %d AND meta_key = %s LOCK IN SHARE MODE", $contract_id, $key ) );
		if ( null === $first_id ) {
			// The row landed; a failed check keeps it rather than report the add as failed.
			wc_get_logger()->warning(
				sprintf( 'ContractRepository: could not check contract meta "%s" for a concurrent duplicate. %s', $key, $wpdb->last_error ),
				array(
					'source'      => self::LOG_SOURCE,
					'contract_id' => $contract_id,
				)
			);
			return $meta_id;
		}
		if ( Coercion::coerce_int( $first_id ) >= $meta_id ) {
			return $meta_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false === $wpdb->delete( $meta, array( 'id' => $meta_id ) ) ) {
			throw new \RuntimeException( sprintf( 'Failed to remove duplicate contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		return null;
	}

	/**
	 * Whether the contract has a meta row for `$key`. Shapes errors only, so a lagging replica
	 * can at worst misname a failure.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $key         Meta key.
	 * @throws \RuntimeException If the read fails.
	 */
	private function has_meta_key( int $contract_id, string $key ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE contract_id = %d AND meta_key = %s LIMIT 1", $contract_id, $key ) );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( sprintf( 'Failed to read contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		return null !== $found;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Update a contract's meta rows for `$key`, like `update_post_meta()`: rewrites every row
	 * for the key, or only the rows holding `$prev_value`. Only when that changes no row does it
	 * add the key if absent (one insert that checks the absence itself), then classify the no-op.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       New value; serialized when not scalar.
	 * @param mixed             $prev_value  Only update rows holding this value; null updates all rows for the key.
	 *                                       Any other value ('' and false included) matches literally.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return bool True when a row was added or at least one row changed.
	 * @throws \InvalidArgumentException If `$key` is empty, the key is added to a missing contract, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \RuntimeException If a write fails.
	 */
	public function update_meta( int $contract_id, string $key, $value, $prev_value = null, ?ContractLock $lock = null ): bool {
		if ( '' === $key ) {
			throw new \InvalidArgumentException( 'Contract meta key must not be empty.' );
		}

		$this->fence_meta( $contract_id, $lock );

		if ( $this->update_meta_rows( $contract_id, $key, $value, $prev_value, $lock ) > 0 ) {
			return true;
		}
		if ( null !== $this->insert_meta_row( $contract_id, $key, $value, true, $lock ) ) {
			return true;
		}
		if ( ! $this->has_meta_key( $contract_id, $key ) ) {
			$this->throw_unfenced_insert( $contract_id );
		}
		if ( null !== $lock ) {
			return false;
		}

		// Unlocked, the key may have been added between the update and the insert: update it once more.
		if ( $this->update_meta_rows( $contract_id, $key, $value, $prev_value, null ) > 0 ) {
			return true;
		}
		$this->throw_if_meta_refused( $contract_id, $key, null === $prev_value ? null : maybe_serialize( $prev_value ), maybe_serialize( $value ) );

		return false;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Rewrite the meta rows for `$key` (only those holding `$prev_value`, when given). Without
	 * `$lock` the UPDATE joins the contract row and lands only while no lock lives.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       New value; serialized when not scalar.
	 * @param mixed             $prev_value  Only update rows holding this value; null updates all rows for the key.
	 * @param ContractLock|null $lock        The caller's (fenced) lock, or null.
	 * @return int The number of rows changed.
	 * @throws \RuntimeException If the write fails.
	 */
	private function update_meta_rows( int $contract_id, string $key, $value, $prev_value, ?ContractLock $lock ): int {
		global $wpdb;

		$meta      = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$values    = array( maybe_serialize( $value ), $contract_id, $key );
		$where     = 'm.contract_id = %d AND m.meta_key = %s';
		if ( null !== $prev_value ) {
			$where   .= ' AND m.meta_value = %s';
			$values[] = maybe_serialize( $prev_value );
		}

		if ( null !== $lock ) {
			$sql = "UPDATE {$meta} m SET m.meta_value = %s WHERE {$where}";
		} else {
			$sql = "UPDATE {$meta} m JOIN {$contracts} c ON c.id = m.contract_id SET m.meta_value = %s
				WHERE {$where} AND " . self::no_live_lock( 'c' );
		}

		// The WHERE clause is built from fixed fragments above; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$updated = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $updated ) {
			throw new \RuntimeException( sprintf( 'Failed to update contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		return (int) $updated;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Delete a contract's meta rows for `$key`, like `delete_post_meta()`.
	 *
	 * @param int               $contract_id Contract id.
	 * @param string            $key         Meta key.
	 * @param mixed             $value       Only delete rows holding this value; null deletes every row for the key.
	 *                                       Any other value ('' and false included) matches literally.
	 * @param ContractLock|null $lock        The caller's lock on this contract, or null.
	 * @return bool True when at least one row was deleted.
	 * @throws \InvalidArgumentException If `$key` is empty, or the lock is for another contract.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \RuntimeException If the delete fails.
	 */
	public function delete_meta( int $contract_id, string $key, $value = null, ?ContractLock $lock = null ): bool {
		global $wpdb;

		if ( '' === $key ) {
			throw new \InvalidArgumentException( 'Contract meta key must not be empty.' );
		}

		$this->fence_meta( $contract_id, $lock );

		$meta      = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$values    = array( $contract_id, $key );
		$where     = 'm.contract_id = %d AND m.meta_key = %s';
		if ( null !== $value ) {
			$where   .= ' AND m.meta_value = %s';
			$values[] = maybe_serialize( $value );
		}

		if ( null !== $lock ) {
			$sql = "DELETE m FROM {$meta} m WHERE {$where}";
		} else {
			$sql = "DELETE m FROM {$meta} m JOIN {$contracts} c ON c.id = m.contract_id
				WHERE {$where} AND " . self::no_live_lock( 'c' );
		}

		// The WHERE clause is built from fixed fragments above; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$deleted = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Failed to delete contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		// Unlocked and nothing deleted: refused when a matching row is still stored.
		if ( 0 === $deleted && null === $lock ) {
			$this->throw_if_meta_refused( $contract_id, $key, null === $value ? null : maybe_serialize( $value ), null );
		}

		return $deleted > 0;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Check a meta write's lock: it must be for this contract and still trusted or verifiable.
	 *
	 * @param int               $contract_id Contract id.
	 * @param ContractLock|null $lock        The caller's lock, or null (the statement is fenced instead).
	 * @throws \InvalidArgumentException If the lock is for another contract.
	 * @throws ContractLockedException If the lock was lost.
	 */
	private function fence_meta( int $contract_id, ?ContractLock $lock ): void {
		if ( null === $lock ) {
			return;
		}

		self::assert_lock_for( $lock, $contract_id );
		$this->fence_children( $lock );
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Classify an unlocked meta update or delete that changed no row from the stored rows, never
	 * from the lock state alone: a row for `$key` (holding `$matched`, when given) that does not hold
	 * `$target` (or any row, for a delete) means the fence refused the write.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $key         Meta key.
	 * @param mixed  $matched     Serialized value the write matched on, or null for every row.
	 * @param mixed  $target      Serialized value an update wrote, or null for a delete.
	 * @throws ContractLockedException If the write was refused and the contract exists.
	 * @throws \RuntimeException If the read fails.
	 */
	private function throw_if_meta_refused( int $contract_id, string $key, $matched, $target ): void {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );
		$where  = 'contract_id = %d AND meta_key = %s';
		$values = array( $contract_id, $key );
		if ( null !== $matched ) {
			$where   .= ' AND meta_value = %s';
			$values[] = $matched;
		}
		if ( null !== $target ) {
			$where   .= ' AND CAST( meta_value AS BINARY ) <> CAST( %s AS BINARY )';
			$values[] = $target;
		}

		// Fixed SQL fragments only; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$pending = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $values ) );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( sprintf( 'Failed to read contract meta "%s" for contract %d: %s', esc_html( $key ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		if ( Coercion::coerce_int( $pending ) < 1 ) {
			return;
		}

		$holder = $this->get_lock_holder( $contract_id );
		if ( $holder['exists'] ) {
			throw self::locked_exception( $contract_id, $holder ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the exception message is not output.
		}
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Read contract meta (WordPress `get_post_meta()` semantics), values unserialized,
	 * oldest row first.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $key         Meta key; empty for every key.
	 * @param bool   $single      With a key: return the first value only.
	 * @return mixed Empty key: `array<string, array<int, mixed>>` of all keys. Key + `$single`:
	 *               the first value, or '' when absent. Key only: the list of values (`[]` when absent).
	 * @throws \RuntimeException If the read fails.
	 */
	public function get_meta( int $contract_id, string $key = '', bool $single = false ) {
		if ( '' === $key ) {
			$all = array();
			foreach ( $this->find_meta_rows( $contract_id, null ) as $row ) {
				$all[ $row['meta_key'] ][] = maybe_unserialize( $row['meta_value'] );
			}

			return $all;
		}

		$values = $this->find_meta_values( $contract_id, $key );

		if ( $single ) {
			return array() === $values ? '' : $values[0];
		}

		return $values;
	}

	/**
	 * Append a cycle to its chain `(contract_id, kind)`. An unassigned `sequence_no` becomes the
	 * chain's highest plus one in the same INSERT, which selects from the contract row so without
	 * `$lock` it lands only while no lock lives. The id and position are stamped back on the entity
	 * (left unassigned, with a warning, when that read-back fails after the row landed).
	 *
	 * @param Cycle             $cycle Cycle to append. Carries its contract id and kind.
	 * @param ContractLock|null $lock  The caller's lock on the cycle's contract, or null.
	 * @throws DuplicateCycleException If a chain UNIQUE index rejects the row (the position is already taken).
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \InvalidArgumentException If the contract does not exist, or the lock is for another contract.
	 * @throws \RuntimeException If the cycle insert fails for any other reason (the database error is in the message).
	 */
	public function append_cycle( Cycle $cycle, ?ContractLock $lock = null ): void {
		if ( null !== $lock ) {
			self::assert_lock_for( $lock, $cycle->get_contract_id() );
			$this->fence_children( $lock );
		}

		$this->insert_cycle( $cycle, $lock );
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Move a cycle from `$from_status` to `$to_status` in one compare-and-set UPDATE that lands
	 * only while the row still carries `$from_status`. With `$lock` the cycle must belong to the
	 * lock's contract; without one the UPDATE joins the contract row and lands only while no lock
	 * lives. A same-status call returns true whenever the row already holds the values.
	 *
	 * @param int                  $cycle_id    Cycle id.
	 * @param string               $from_status The status the caller read; the compare-and-set predicate (not validated).
	 * @param string               $to_status   The status to write; must be a registered cycle status.
	 * @param array<string, mixed> $changes     Optional `order_id` (?int), `reason` (?string); present keys are written, null clears.
	 * @param ContractLock|null    $lock        The caller's lock on the cycle's contract, or null.
	 * @return bool True when this caller won the transition (for a same-status call: the row holds the values).
	 * @throws \DomainException If `$to_status` is not a registered cycle status.
	 * @throws \InvalidArgumentException If the cycle does not exist, or belongs to another contract than `$lock`.
	 * @throws ContractLockedException If another operation holds the lock, or `$lock` was lost.
	 * @throws \RuntimeException If the write fails.
	 */
	public function transition_cycle( int $cycle_id, string $from_status, string $to_status, array $changes, ?ContractLock $lock = null ): bool {
		global $wpdb;

		if ( ! CycleStatus::is_registered( $to_status ) ) {
			throw new \DomainException( esc_html( sprintf( 'ContractRepository::transition_cycle(): cycle status "%s" is not registered.', $to_status ) ) );
		}

		if ( null !== $lock ) {
			$this->fence_children( $lock );
		}

		// Without a lock the UPDATE joins the contract, so cycle columns are qualified.
		$prefix = null === $lock ? 'cy.' : '';
		$set    = array( "{$prefix}status = %s" );
		$values = array( $to_status );

		if ( array_key_exists( 'order_id', $changes ) ) {
			if ( null === $changes['order_id'] ) {
				$set[] = "{$prefix}order_id = NULL";
			} else {
				$set[]    = "{$prefix}order_id = %d";
				$values[] = Coercion::coerce_int( $changes['order_id'] );
			}
		}

		if ( array_key_exists( 'reason', $changes ) ) {
			if ( null === $changes['reason'] ) {
				$set[] = "{$prefix}reason = NULL";
			} else {
				$set[]    = "{$prefix}reason = %s";
				$values[] = Coercion::coerce_string( $changes['reason'] );
			}
		}

		$set[]    = "{$prefix}date_updated_gmt = %s";
		$values[] = gmdate( 'Y-m-d H:i:s' );
		$values[] = $cycle_id;
		$values[] = $from_status;

		$cycles     = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$contracts  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$set_clause = implode( ', ', $set );

		if ( null !== $lock ) {
			$values[] = $lock->get_contract_id();
			$sql      = "UPDATE {$cycles} SET {$set_clause} WHERE id = %d AND status = %s AND contract_id = %d";
		} else {
			$sql = "UPDATE {$cycles} cy JOIN {$contracts} c ON c.id = cy.contract_id SET {$set_clause}
				WHERE cy.id = %d AND cy.status = %s AND " . self::no_live_lock( 'c' );
		}

		// The SET clause is built from fixed fragments above; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $result ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to transition cycle %d: %s', $cycle_id, $wpdb->last_error ) ) );
		}

		if ( 1 === $wpdb->rows_affected ) {
			return true;
		}

		return $this->classify_lost_transition( $cycle_id, $from_status, $to_status, $changes, $lock );
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Tell apart why a cycle transition changed no row, from the stored cycle rather than the lock
	 * state alone. Unlocked, a row still in `$from_status` that is not already in the intended
	 * state means the fence refused the write. Otherwise a same-status call whose values are
	 * stored is a no-op win, and a moved status is a lost compare-and-set.
	 *
	 * @param int                  $cycle_id    Cycle id.
	 * @param string               $from_status The compare-and-set predicate.
	 * @param string               $to_status   The status written.
	 * @param array<string, mixed> $changes     The `order_id` / `reason` changes written.
	 * @param ContractLock|null    $lock        The caller's lock, or null.
	 * @return bool True for a same-status no-op, false when the status moved.
	 * @throws \InvalidArgumentException If the cycle does not exist, or belongs to another contract than `$lock`.
	 * @throws ContractLockedException If the fence refused the write.
	 * @throws \RuntimeException If the read fails.
	 */
	private function classify_lost_transition( int $cycle_id, string $from_status, string $to_status, array $changes, ?ContractLock $lock ): bool {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT contract_id, status, order_id, reason FROM {$table} WHERE id = %d", $cycle_id ), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to read cycle %d: %s', $cycle_id, $wpdb->last_error ) ) );
		}
		if ( ! is_array( $row ) ) {
			throw new \InvalidArgumentException( esc_html( sprintf( 'Cycle %d does not exist.', $cycle_id ) ) );
		}

		$contract_id = Coercion::coerce_int( $row['contract_id'] ?? 0 );
		if ( null !== $lock && $lock->get_contract_id() !== $contract_id ) {
			throw new \InvalidArgumentException( esc_html( sprintf( 'Cycle %d does not belong to contract %d.', $cycle_id, $lock->get_contract_id() ) ) );
		}

		$status = Coercion::coerce_string( $row['status'] ?? '' );
		$stored = $status === $to_status && self::has_changes( $row, $changes );

		if ( null === $lock && $status === $from_status && ! $stored ) {
			$this->throw_locked( $contract_id );
		}

		return $from_status === $to_status && $stored;
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	/**
	 * Whether a stored cycle row already holds the `order_id` / `reason` changes.
	 *
	 * @param array<string, mixed> $row     Cycle row (`order_id`, `reason`).
	 * @param array<string, mixed> $changes Changes: optional `order_id`, `reason`.
	 */
	private static function has_changes( array $row, array $changes ): bool {
		if ( array_key_exists( 'order_id', $changes ) ) {
			$intended = null === $changes['order_id'] ? null : Coercion::coerce_int( $changes['order_id'] );
			if ( Coercion::coerce_nullable_int( $row['order_id'] ?? null ) !== $intended ) {
				return false;
			}
		}

		if ( array_key_exists( 'reason', $changes ) ) {
			$intended = null === $changes['reason'] ? null : Coercion::coerce_string( $changes['reason'] );
			if ( Coercion::coerce_nullable_string( $row['reason'] ?? null ) !== $intended ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * A window of a chain's cycle history, newest first - the paginated read for
	 * history screens.
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
				$cycles[] = $this->hydrate_cycle( Coercion::coerce_string_keyed( $row ) );
			}
		}

		return $cycles;
	}

	/**
	 * All cycles linked to `$order_id`, across kinds and contracts. `order_id` is a
	 * non-1:1 reference (an aggregate order may serve many cycles), so this returns
	 * every match.
	 *
	 * @param int $order_id WooCommerce order id.
	 * @return array<int, Cycle> Cycles linked to the order, newest first.
	 */
	public function find_cycles_by_order_id( int $order_id ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d ORDER BY id DESC", $order_id ), ARRAY_A );

		$cycles = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$cycles[] = $this->hydrate_cycle( Coercion::coerce_string_keyed( $row ) );
			}
		}

		return $cycles;
	}

	/**
	 * Hydrate a cycle row, attaching typed snapshot value objects for the snapshot ids
	 * it carries (a cycle without snapshot ids costs no extra read).
	 *
	 * @param array<string, mixed> $row Cycle row.
	 * @return Cycle The hydrated cycle.
	 */
	private function hydrate_cycle( array $row ): Cycle {
		$cycle = Cycle::from_storage( $row );

		$plan_snapshot_id = $cycle->get_plan_snapshot_id();
		if ( null !== $plan_snapshot_id ) {
			$plan = $this->find_plan_snapshot( $plan_snapshot_id );
			if ( $plan instanceof PlanSnapshot ) {
				$cycle->set_plan_snapshot( $plan );
			}
		}

		$items_snapshot_id = $cycle->get_items_snapshot_id();
		if ( null !== $items_snapshot_id ) {
			$items = $this->find_items_snapshot( $items_snapshot_id );
			if ( $items instanceof ItemsSnapshot ) {
				$cycle->set_items_snapshot( $items );
			}
		}

		return $cycle;
	}

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Insert a cycle row as `INSERT ... SELECT` from the contract row and stamp the generated id
	 * (and an assigned `sequence_no`) back onto the entity. String values are checked against
	 * their column lengths first, as `$wpdb->insert()` would. A failed position read-back after
	 * the row landed logs a warning and leaves the entity's position unassigned.
	 *
	 * @param Cycle             $cycle Cycle to insert. Carries its contract id and kind.
	 * @param ContractLock|null $lock  The caller's (fenced) lock, or null: the insert then must find the contract with no live lock.
	 * @throws DuplicateCycleException If a chain UNIQUE index rejects the row.
	 * @throws ContractLockedException If unlocked and another operation holds the lock.
	 * @throws \InvalidArgumentException If the contract does not exist.
	 * @throws \RuntimeException If a value is longer than its column, or the insert fails for any other reason.
	 */
	private function insert_cycle( Cycle $cycle, ?ContractLock $lock ): void {
		global $wpdb;

		$table     = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CYCLES );
		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$now       = gmdate( 'Y-m-d H:i:s' );
		$assign    = $cycle->awaits_sequence_no();

		$columns                     = $cycle->to_storage();
		$columns['date_created_gmt'] = $now;
		$columns['date_updated_gmt'] = $now;

		$names        = array();
		$placeholders = array();
		$values       = array();
		foreach ( $columns as $column => $value ) {
			$names[] = "`{$column}`";
			if ( 'sequence_no' === $column && $assign ) {
				// The next position in the chain, read by the inserting statement itself.
				$placeholders[] = "( SELECT COALESCE( MAX( s.sequence_no ), 0 ) + 1 FROM `{$table}` s WHERE s.contract_id = %d AND s.kind = %s )";
				$values[]       = $cycle->get_contract_id();
				$values[]       = $cycle->get_kind();
				continue;
			}
			if ( null === $value ) {
				$placeholders[] = 'NULL';
				continue;
			}
			if ( is_string( $value ) ) {
				self::assert_fits_column( $table, $column, $value );
			}
			$placeholders[] = '%s';
			$values[]       = (string) $value;
		}

		$name_list        = implode( ', ', $names );
		$placeholder_list = implode( ', ', $placeholders );
		$values[]         = $cycle->get_contract_id();
		$fence            = null === $lock ? ' AND ' . self::no_live_lock( 'c' ) : '';

		// LOCK IN SHARE MODE makes the fence read block on a concurrent lock write under READ COMMITTED too.
		$sql = "INSERT INTO `{$table}` ( {$name_list} ) SELECT {$placeholder_list} FROM `{$contracts}` c WHERE c.id = %d{$fence} LOCK IN SHARE MODE";

		// Column names come from the entity's storage keys, never from input; every value is a placeholder.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		$inserted = $wpdb->query( $wpdb->prepare( $sql, $values ) );

		if ( false === $inserted ) {
			$db_error = $wpdb->last_error;
			if ( false !== stripos( $db_error, 'Duplicate entry' ) ) {
				// A chain UNIQUE index rejected the row: the position is already taken.
				throw new DuplicateCycleException( esc_html( 'Cycle position already exists: ' . $db_error ) );
			}
			throw new \RuntimeException( esc_html( 'Failed to insert cycle. ' . $db_error ) );
		}

		if ( 0 === $inserted ) {
			$this->throw_unfenced_insert( $cycle->get_contract_id() );
		}

		$id = (int) $wpdb->insert_id;
		$cycle->set_id( $id );

		if ( $assign ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sequence_no = $wpdb->get_var( $wpdb->prepare( "SELECT sequence_no FROM `{$table}` WHERE id = %d", $id ) );
			if ( null === $sequence_no ) {
				// The append landed: report it, with the position left unassigned.
				wc_get_logger()->warning(
					sprintf( 'ContractRepository: could not read the position of appended cycle %d. %s', $id, $wpdb->last_error ),
					array(
						'source'      => self::LOG_SOURCE,
						'contract_id' => $cycle->get_contract_id(),
						'cycle_id'    => $id,
					)
				);
				return;
			}
			$cycle->assign_sequence_no( Coercion::coerce_int( $sequence_no ) );
		}
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

	// phpcs:disable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber -- @throws also lists exceptions raised by the methods called.
	/**
	 * Explain a child insert that selected no contract row: the contract is missing, or another
	 * operation holds its lock. The lookup shapes the error only; a lagging replica can at
	 * worst misname it.
	 *
	 * @param int $contract_id Contract id.
	 * @return never
	 * @throws \InvalidArgumentException If the contract does not exist.
	 * @throws ContractLockedException Otherwise: the fence refused the insert (holder details best-effort).
	 */
	private function throw_unfenced_insert( int $contract_id ): void {
		$holder = $this->get_lock_holder( $contract_id );
		if ( ! $holder['exists'] ) {
			throw new \InvalidArgumentException( esc_html( sprintf( 'Contract %d does not exist.', $contract_id ) ) );
		}
		throw self::locked_exception( $contract_id, $holder ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- the exception message is not output.
	}
	// phpcs:enable Squiz.Commenting.FunctionCommentThrowTag.WrongNumber

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

		return PlanSnapshot::from_payload( Coercion::coerce_string_keyed( $decoded['payload'] ), $decoded['schema_version'] );
	}

	/**
	 * Decode a batch of stored plan snapshot rows in one read - the list pages'
	 * hydration path, so a page of contracts costs one snapshot query, not one
	 * per row.
	 *
	 * @param array<int, int> $snapshot_ids Snapshot row ids (deduplicated by the caller or not - the IN() dedupes).
	 * @return array<int, PlanSnapshot> Decoded value objects keyed by snapshot row id.
	 */
	private function find_plan_snapshots( array $snapshot_ids ): array {
		global $wpdb;

		$ids = array_values( array_unique( array_map( 'intval', $snapshot_ids ) ) );
		if ( array() === $ids ) {
			return array();
		}

		$table        = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_SNAPSHOTS );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// The IN() placeholders are built per id, so the sniff sees none in the literal.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, payload, schema_version
				FROM {$table}
				WHERE id IN ( {$placeholders} )",
				$ids
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$snapshots = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$payload = json_decode( Coercion::coerce_string( $row['payload'] ?? null ), true );

			$snapshots[ Coercion::coerce_int( $row['id'] ?? 0 ) ] = PlanSnapshot::from_payload(
				Coercion::coerce_string_keyed( is_array( $payload ) ? $payload : array() ),
				Coercion::coerce_int( $row['schema_version'] ?? 0 )
			);
		}

		return $snapshots;
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

		return ItemsSnapshot::from_payload( Coercion::coerce_list_of_arrays( $decoded['payload'] ), $decoded['schema_version'] );
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
	 * Read the contract row by id.
	 *
	 * @param int $id Contract id.
	 * @return array<string, mixed>|null
	 * @throws \RuntimeException If the read fails.
	 */
	private function find_contract_row( int $id ): ?array {
		global $wpdb;

		$table  = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$select = self::CONTRACT_SELECT;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT {$select} FROM {$table} WHERE id = %d", $id ), ARRAY_A );

		// A failed read must not look like a missing contract.
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( esc_html( sprintf( 'Failed to read contract %d: %s', $id, $wpdb->last_error ) ) );
		}

		return is_array( $row ) ? self::strip_lock_token( Coercion::coerce_string_keyed( $row ) ) : null;
	}

	/**
	 * Drop the lock token from a fetched contract row: it never leaves storage.
	 *
	 * @param array<string, mixed> $row Contract row.
	 * @return array<string, mixed>
	 */
	private static function strip_lock_token( array $row ): array {
		unset( $row['lock_token'] );

		return $row;
	}

	/**
	 * Write contract columns (plus `date_updated_gmt`, and a `write_seq` bump so a matched row
	 * always counts as changed) in one fenced UPDATE: with `$lock` it matches only while that
	 * lock lives, without one only while no lock lives. Strings are checked against their column
	 * lengths first, since WordPress runs without strict SQL modes and MySQL would shorten them.
	 *
	 * @param int                            $contract_id Contract id.
	 * @param array<string, int|string|null> $columns     Contract column values keyed by column name (from {@see Contract::to_storage()}).
	 * @param ContractLock|null              $lock        The caller's lock, or null.
	 * @return int The number of rows the statement changed.
	 * @throws \RuntimeException If a value is longer than its column, or the write fails.
	 */
	private function write_contract_row( int $contract_id, array $columns, ?ContractLock $lock ): int {
		global $wpdb;

		$table                       = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );
		$columns['date_updated_gmt'] = gmdate( 'Y-m-d H:i:s' );

		foreach ( $columns as $column => $value ) {
			if ( is_string( $value ) ) {
				self::assert_fits_column( $table, $column, $value );
			}
		}

		$assignments = array( '`write_seq` = `write_seq` + 1' );
		$values      = array();

		foreach ( $columns as $column => $value ) {
			if ( null === $value ) {
				$assignments[] = "`{$column}` = NULL";
				continue;
			}
			$assignments[] = "`{$column}` = %s";
			$values[]      = (string) $value;
		}

		$values[] = $contract_id;
		$set      = implode( ', ', $assignments );
		$fence    = self::no_live_lock();
		if ( null !== $lock ) {
			$fence    = self::HOLDS_LOCK;
			$values[] = self::get_state_of( $lock )->get_token();
		}

		// Column names come from the entity's storage keys, never from input; every value is a placeholder.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE `{$table}` SET {$set} WHERE `id` = %d AND {$fence}", $values ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update contract.' );
		}

		return (int) $updated;
	}

	/**
	 * Refuse a string longer than its column, measured the way `$wpdb` measures it
	 * (characters for CHAR/VARCHAR, bytes for TEXT/BLOB types). A column whose length
	 * `$wpdb` cannot read (non-MySQL, or a non-string type such as DATETIME) is not checked.
	 *
	 * @param string $table  Prefixed table name.
	 * @param string $column Column name.
	 * @param string $value  Value about to be written.
	 * @throws \RuntimeException If the value is longer than the column.
	 */
	private static function assert_fits_column( string $table, string $column, string $value ): void {
		global $wpdb;

		$length = $wpdb->get_col_length( $table, $column );
		if ( ! is_array( $length ) ) {
			return;
		}

		$max  = Coercion::coerce_int( $length['length'] ?? 0 );
		$size = 'byte' === ( $length['type'] ?? '' ) ? strlen( $value ) : mb_strlen( $value, 'UTF-8' );
		if ( $max > 0 && $size > $max ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Failed to write %s: the value for "%s" is longer than the column allows (%d).', $table, $column, $max ) )
			);
		}
	}

	/**
	 * Replace a contract's item rows: insert the new rows, then delete every other row of the
	 * contract. A failed insert removes the rows it added and keeps the old set; a failed delete
	 * leaves old and new rows side by side, never an empty set, and the next replacement repairs it.
	 *
	 * @param int                              $contract_id Contract id.
	 * @param array<int, array<string, mixed>> $items       Item rows.
	 * @throws \RuntimeException If an insert or the delete fails.
	 */
	private function replace_items( int $contract_id, array $items ): void {
		global $wpdb;

		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		$new_ids = $this->insert_items( $contract_id, $items );
		$values  = array_merge( array( $contract_id ), $new_ids );
		$keep    = array() === $new_ids ? '' : ' AND id NOT IN ( ' . implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) ) . ' )';

		// The NOT IN placeholders join dynamically, so the sniff cannot count them.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE contract_id = %d{$keep}", $values ) );
		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Failed to delete item rows for contract %d: %s', (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}
	}

	/**
	 * Replace a contract's address rows: upsert each given type in place, then delete the
	 * types no longer given. A failure in between leaves a stale type, never a missing one.
	 *
	 * @param int                                 $contract_id Contract id.
	 * @param array<string, array<string, mixed>> $addresses   Address rows keyed by type.
	 * @throws \RuntimeException If an upsert or the delete fails.
	 */
	private function replace_addresses( int $contract_id, array $addresses ): void {
		global $wpdb;

		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ADDRESSES );
		$columns = array_merge( array( 'contract_id', 'address_type' ), Contract::ADDRESS_FIELDS );

		$column_list = implode( ', ', $columns );
		$updates     = implode(
			', ',
			array_map(
				static function ( string $column ): string {
					return "{$column} = VALUES({$column})";
				},
				Contract::ADDRESS_FIELDS
			)
		);

		foreach ( $addresses as $type => $address ) {
			$record       = $this->address_record( $contract_id, (string) $type, $address );
			$placeholders = array();
			$values       = array();
			foreach ( $columns as $column ) {
				$value = $record[ $column ];
				if ( null === $value ) {
					$placeholders[] = 'NULL';
					continue;
				}
				if ( is_string( $value ) ) {
					self::assert_fits_column( $table, $column, $value );
				}
				$placeholders[] = '%s';
				$values[]       = (string) $value;
			}
			$placeholder_list = implode( ', ', $placeholders );

			// Column names come from Contract::ADDRESS_FIELDS, never from input; every value is a placeholder.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$upserted = $wpdb->query( $wpdb->prepare( "INSERT INTO {$table} ( {$column_list} ) VALUES ( {$placeholder_list} ) ON DUPLICATE KEY UPDATE {$updates}", $values ) );
			if ( false === $upserted ) {
				throw new \RuntimeException( sprintf( 'Failed to write %s address row for contract %d: %s', esc_html( (string) $type ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
			}
		}

		$types  = array_map( 'strval', array_keys( $addresses ) );
		$values = array_merge( array( $contract_id ), $types );
		$keep   = array() === $types ? '' : ' AND address_type NOT IN ( ' . implode( ', ', array_fill( 0, count( $types ), '%s' ) ) . ' )';

		// The NOT IN placeholders join dynamically, so the sniff cannot count them.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE contract_id = %d{$keep}", $values ) );
		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Failed to delete address rows for contract %d: %s', (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}
	}

	/**
	 * Insert line items for a contract. A failed insert best-effort deletes the rows this call
	 * added, then throws.
	 *
	 * @param int                              $contract_id Contract id.
	 * @param array<int, array<string, mixed>> $items       Item rows.
	 * @return array<int, int> The new row ids, in order.
	 * @throws \RuntimeException If an insert fails.
	 */
	private function insert_items( int $contract_id, array $items ): array {
		global $wpdb;

		$table   = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ITEMS );
		$new_ids = array();

		foreach ( $items as $item ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$inserted = $wpdb->insert(
				$table,
				array(
					'contract_id'  => $contract_id,
					'item_name'    => Coercion::coerce_string( $item['item_name'] ?? null ),
					'item_type'    => Coercion::coerce_string( $item['item_type'] ?? null, 'line_item' ),
					'product_id'   => isset( $item['product_id'] ) ? Coercion::coerce_int( $item['product_id'] ) : null,
					'variation_id' => isset( $item['variation_id'] ) ? Coercion::coerce_int( $item['variation_id'] ) : null,
					'quantity'     => Coercion::coerce_string( $item['quantity'] ?? null, '1' ),
					'subtotal'     => Coercion::coerce_string( $item['subtotal'] ?? null, '0' ),
					'total'        => Coercion::coerce_string( $item['total'] ?? null, '0' ),
					'taxes'        => isset( $item['taxes'] ) ? wp_json_encode( $item['taxes'] ) : null,
				)
			);

			if ( false === $inserted ) {
				$error = $wpdb->last_error;
				if ( array() !== $new_ids ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ( " . implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) ) . ' )', $new_ids ) );
				}
				throw new \RuntimeException( sprintf( 'Failed to insert item row for contract %d: %s', (int) $contract_id, esc_html( $error ) ) );
			}

			$new_ids[] = (int) $wpdb->insert_id;
		}

		return $new_ids;
	}

	/**
	 * Insert addresses for a contract.
	 *
	 * @param int                                 $contract_id Contract id.
	 * @param array<string, array<string, mixed>> $addresses   Address rows keyed by type.
	 * @throws \RuntimeException If an insert fails.
	 */
	private function insert_addresses( int $contract_id, array $addresses ): void {
		global $wpdb;

		foreach ( $addresses as $type => $address ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$inserted = $wpdb->insert( SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_ADDRESSES ), $this->address_record( $contract_id, (string) $type, $address ) );
			if ( false === $inserted ) {
				throw new \RuntimeException( sprintf( 'Failed to insert %s address row for contract %d: %s', esc_html( (string) $type ), (int) $contract_id, esc_html( $wpdb->last_error ) ) );
			}
		}
	}

	/**
	 * An address row as stored.
	 *
	 * @param int                  $contract_id Contract id.
	 * @param string               $type        Address type.
	 * @param array<string, mixed> $address     Address fields.
	 * @return array<string, int|string|null>
	 */
	private function address_record( int $contract_id, string $type, array $address ): array {
		$record = array(
			'contract_id'  => $contract_id,
			'address_type' => $type,
		);

		foreach ( Contract::ADDRESS_FIELDS as $column ) {
			$record[ $column ] = isset( $address[ $column ] ) ? Coercion::coerce_string( $address[ $column ] ) : null;
		}

		return $record;
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

		return Coercion::coerce_list_of_arrays( $rows );
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
				$by_type[ Coercion::coerce_string( $row['address_type'] ?? null ) ] = Coercion::coerce_string_keyed( $row );
			}
		}

		return $by_type;
	}

	/**
	 * Unserialized values stored under `$key` for a contract, oldest first.
	 *
	 * @param int    $contract_id Contract id.
	 * @param string $key         Meta key.
	 * @return array<int, mixed>
	 * @throws \RuntimeException If the read fails.
	 */
	private function find_meta_values( int $contract_id, string $key ): array {
		$values = array();
		foreach ( $this->find_meta_rows( $contract_id, $key ) as $row ) {
			$values[] = maybe_unserialize( $row['meta_value'] );
		}

		return $values;
	}

	/**
	 * Raw meta rows for a contract, optionally for one key, by id ascending.
	 *
	 * @param int         $contract_id Contract id.
	 * @param string|null $key         Meta key, or null for every key.
	 * @return array<int, array{meta_key: string, meta_value: string}>
	 * @throws \RuntimeException If the read fails.
	 */
	private function find_meta_rows( int $contract_id, ?string $key ): array {
		global $wpdb;

		$table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACT_META );

		if ( null === $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE contract_id = %d ORDER BY id ASC", $contract_id ), ARRAY_A );
		} else {
			// The engine's own contract-meta columns, not post/order meta; the
			// slow-meta-query heuristic does not apply.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE contract_id = %d AND meta_key = %s ORDER BY id ASC", $contract_id, $key ), ARRAY_A );
		}

		// Callers decide writes from meta; a failed read must not look like an absent key.
		if ( '' !== $wpdb->last_error ) {
			throw new \RuntimeException( sprintf( 'Failed to read contract meta for contract %d: %s', (int) $contract_id, esc_html( $wpdb->last_error ) ) );
		}

		$result = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) ) {
				$result[] = array(
					'meta_key'   => Coercion::coerce_string( $row['meta_key'] ?? null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value' => Coercion::coerce_string( $row['meta_value'] ?? null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				);
			}
		}

		return $result;
	}
}
