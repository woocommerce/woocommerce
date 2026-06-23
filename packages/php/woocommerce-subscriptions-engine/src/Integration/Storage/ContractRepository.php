<?php
/**
 * ContractRepository - persistence for {@see Contract} entities.
 *
 * Lives in the integration layer: it owns the $wpdb access and spans the four
 * contract tables (contract row, items, addresses, meta), hydrating the Core
 * entity from clean arrays.
 *
 * @package Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\SubscriptionsEngine\Integration\Storage;

use Automattic\WooCommerce\SubscriptionsEngine\Core\Entity\Contract;
use Automattic\WooCommerce\SubscriptionsEngine\Core\Support\ScalarCoercion;

defined( 'ABSPATH' ) || exit;

/**
 * Contract repository.
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
	 * Insert a new contract and its items, addresses, and meta.
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
	 * Persist changes to an existing contract and its child rows.
	 *
	 * Updates the contract row in place, then replaces the items, addresses,
	 * and meta rows wholesale (delete-then-reinsert). Replacement rather than a
	 * per-row diff keeps update() symmetric with insert() - the child tables
	 * always reflect the entity's current arrays - at the cost of churning rows
	 * that did not change. The renewal money-path mutates only contract-row
	 * scheduling fields (next_payment_gmt, cycle_count, status, the *_gmt
	 * stamps), so the child churn is rare in practice; revisit with a diffing
	 * upsert if a high-frequency child-row writer appears.
	 *
	 * @param Contract $contract Contract to update. Must have an id whose row still exists.
	 * @return bool True when the contract row was updated (or already current).
	 * @throws \RuntimeException If the contract has no id, or its row no longer exists.
	 */
	public function update( Contract $contract ): bool {
		global $wpdb;

		$id = $contract->get_id();
		if ( null === $id ) {
			throw new \RuntimeException( 'Cannot update a contract that has no id. Use ContractRepository::insert() for a new contract.' );
		}

		$contracts_table = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// Guard against a stale id / concurrent delete before touching any table.
		// `$wpdb->update()` returns 0 for both "matched, nothing changed" and "no
		// row matched", so on its own it cannot tell that the parent is gone - and
		// the child delete/reinsert below would then write orphan rows, since no
		// foreign key enforces the relation. This narrows but does not fully close
		// the race; the complete fix wraps the whole method in a transaction with
		// SELECT ... FOR UPDATE, tracked as separate hardening (the integration
		// suite's transaction-based isolation needs a test-safe approach first).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$contracts_table} WHERE id = %d", $id ) );
		if ( null === $exists ) {
			throw new \RuntimeException(
				esc_html( sprintf( 'Cannot update contract %d: the contract row no longer exists (stale id or concurrent delete).', $id ) )
			);
		}

		$data = $contract->to_storage();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->update(
			$contracts_table,
			array_merge(
				$data,
				array( 'date_updated_gmt' => gmdate( 'Y-m-d H:i:s' ) )
			),
			array( 'id' => $id )
		);

		if ( false === $updated ) {
			throw new \RuntimeException( 'Failed to update contract.' );
		}

		// Replace child rows so they mirror the entity's current arrays. Same
		// delete set as delete(), minus the contract row itself.
		foreach ( array(
			SchemaInstaller::TABLE_CONTRACT_ITEMS,
			SchemaInstaller::TABLE_CONTRACT_ADDRESSES,
			SchemaInstaller::TABLE_CONTRACT_META,
		) as $child ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( SchemaInstaller::get_table_name( $child ), array( 'contract_id' => $id ) );
		}

		$this->insert_items( $id, $contract->get_items() );
		$this->insert_addresses( $id, $contract->get_addresses() );
		$this->insert_meta( $id, $contract->get_meta() );

		// `$wpdb->update` returns 0 (int) when the row matched but no column
		// changed - a successful no-op, not a failure. Only `false` is an error,
		// and that path threw above; the "row no longer exists" case is ruled out
		// by the existence guard at the top of this method.
		return true;
	}

	/**
	 * Fetch a contract by id, including its items, addresses, and meta.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null Hydrated contract, or null if not found.
	 */
	public function find( int $id ): ?Contract {
		global $wpdb;

		$contracts = SchemaInstaller::get_table_name( SchemaInstaller::TABLE_CONTRACTS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$contracts} WHERE id = %d", $id ), ARRAY_A );

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
	 * Delete a contract and its child rows.
	 *
	 * @param int $id Contract id.
	 * @return bool True when the contract row was removed.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		foreach ( array(
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
				$record[ $column ] = self::coerce_nullable_string( $address[ $column ] ?? null );
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

		$items = array();
		foreach ( (array) $rows as $row ) {
			if ( is_array( $row ) ) {
				$items[] = $row;
			}
		}

		return $items;
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
		foreach ( (array) $rows as $row ) {
			if ( is_array( $row ) ) {
				$by_type[ self::coerce_string( $row['address_type'] ?? null ) ] = $row;
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
		foreach ( (array) $rows as $row ) {
			if ( is_array( $row ) ) {
				$meta[ self::coerce_string( $row['meta_key'] ?? null ) ] = self::coerce_string( $row['meta_value'] ?? null );
			}
		}

		return $meta;
	}
}
