<?php
/**
 * Contract_Repository - persistence for {@see Contract} entities.
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

defined( 'ABSPATH' ) || exit;

/**
 * Contract repository.
 */
final class Contract_Repository {

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
			Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACTS ),
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
	 * Fetch a contract by id, including its items, addresses, and meta.
	 *
	 * @param int $id Contract id.
	 * @return Contract|null Hydrated contract, or null if not found.
	 */
	public function find( int $id ): ?Contract {
		global $wpdb;

		$contracts = Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACTS );

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
			Schema_Installer::TABLE_CONTRACT_ITEMS,
			Schema_Installer::TABLE_CONTRACT_ADDRESSES,
			Schema_Installer::TABLE_CONTRACT_META,
		) as $child ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( Schema_Installer::get_table_name( $child ), array( 'contract_id' => $id ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACTS ), array( 'id' => $id ) );

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
				Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_ITEMS ),
				array(
					'contract_id'  => $contract_id,
					'item_name'    => (string) ( $item['item_name'] ?? '' ),
					'item_type'    => (string) ( $item['item_type'] ?? 'line_item' ),
					'product_id'   => isset( $item['product_id'] ) ? (int) $item['product_id'] : null,
					'variation_id' => isset( $item['variation_id'] ) ? (int) $item['variation_id'] : null,
					'quantity'     => (string) ( $item['quantity'] ?? '1' ),
					'subtotal'     => (string) ( $item['subtotal'] ?? '0' ),
					'total'        => (string) ( $item['total'] ?? '0' ),
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
				$record[ $column ] = isset( $address[ $column ] ) ? (string) $address[ $column ] : null;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert( Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_ADDRESSES ), $record );
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
				Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_META ),
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

		$table = Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_ITEMS );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d ORDER BY id ASC", $contract_id ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Load addresses for a contract, keyed by address type.
	 *
	 * @param int $contract_id Contract id.
	 * @return array<string, array<string, mixed>>
	 */
	private function find_addresses( int $contract_id ): array {
		global $wpdb;

		$table = Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_ADDRESSES );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE contract_id = %d", $contract_id ), ARRAY_A );

		$by_type = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$by_type[ (string) $row['address_type'] ] = $row;
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

		$table = Schema_Installer::get_table_name( Schema_Installer::TABLE_CONTRACT_META );

		// These are the engine's own contract-meta columns, not post/order meta;
		// the slow-meta-query heuristic does not apply.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$table} WHERE contract_id = %d", $contract_id ), ARRAY_A );

		$meta = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$meta[ (string) $row['meta_key'] ] = (string) $row['meta_value'];
		}

		return $meta;
	}
}
