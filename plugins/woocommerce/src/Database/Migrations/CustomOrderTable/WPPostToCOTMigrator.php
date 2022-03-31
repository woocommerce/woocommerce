<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable;

use Automattic\WooCommerce\DataBase\Migrations\MigrationErrorLogger;
use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\WPPostToOrderAddressTableMigrator;
use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\WPPostToOrderOpTableMigrator;
use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\WPPostToOrderTableMigrator;

/**
 * Class WPPostToCOTMigrator
 *
 * @package Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable
 */
class WPPostToCOTMigrator {

	/**
	 * Error logger for migration errors.
	 *
	 * @var MigrationErrorLogger $error_logger
	 */
	private $error_logger;

	/**
	 * Migrator instance to migrate data into wc_order table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $order_table_migrator;

	/**
	 * Migrator instance to migrate billing data into address table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $billing_address_table_migrator;

	/**
	 * Migrator instance to migrate shipping data into address table.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $shipping_address_table_migrator;

	/**
	 * Migrator instance to migrate operational data.
	 *
	 * @var MetaToCustomTableMigrator
	 */
	private $operation_data_table_migrator;

	/**
	 * Migrator instance to migrate meta data.
	 *
	 * @var MetaToMetaTableMigrator
	 */
	private $meta_table_migrator;

	/**
	 * WPPostToCOTMigrator constructor.
	 */
	public function __construct() {

		$this->order_table_migrator            = new WPPostToOrderTableMigrator();
		$this->billing_address_table_migrator  = new WPPostToOrderAddressTableMigrator( 'billing' );
		$this->shipping_address_table_migrator = new WPPostToOrderAddressTableMigrator( 'shipping' );
		$this->operation_data_table_migrator   = new WPPostToOrderOpTableMigrator();

		$meta_data_config = $this->get_config_for_meta_table();

		$this->meta_table_migrator = new MetaToMetaTableMigrator( $meta_data_config );

		$this->error_logger = new MigrationErrorLogger();
	}

	/**
	 * Generate config for meta data migration.
	 *
	 * @return array Meta data migration config.
	 */
	private function get_config_for_meta_table() {
		global $wpdb;
		// TODO: Remove hardcoding.
		$this->table_names = array(
			'orders'    => $wpdb->prefix . 'wc_orders',
			'addresses' => $wpdb->prefix . 'wc_order_addresses',
			'op_data'   => $wpdb->prefix . 'wc_order_operational_data',
			'meta'      => $wpdb->prefix . 'wc_orders_meta',
		);

		$excluded_columns = array_keys( $this->order_table_migrator->get_meta_column_config() );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->billing_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->shipping_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->operation_data_table_migrator->get_meta_column_config() ) );

		return array(
			'source'      => array(
				'meta'          => array(
					'table_name'        => $wpdb->postmeta,
					'entity_id_column'  => 'post_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
				),
				'excluded_keys' => $excluded_columns,
			),
			'destination' => array(
				'meta'   => array(
					'table_name'        => $this->table_names['meta'],
					'entity_id_column'  => 'order_id',
					'meta_key_column'   => 'meta_key',
					'meta_value_column' => 'meta_value',
					'entity_id_type'    => 'int',
				),
				'entity' => array(
					'table_name'       => $this->table_names['orders'],
					'source_id_column' => 'post_id',
					'id_column'        => 'id',
				),
			),
		);
	}

	/**
	 * @param MetaToCustomTableMigrator $migrator
	 * @param $type
	 * @param $order_post_ids
	 */
	private function process_next_address_batch( $migrator, $type, $order_post_ids ) {
		global $wpdb;

		if ( 0 === count( $order_post_ids ) ) {
			return;
		}

		$data = $migrator->fetch_data_for_migration_for_ids( $order_post_ids );

		foreach ( $data['errors'] as $order_id => $error ) {
			// TODO: Add name of the migrator in error message.
			$this->error_logger->log( 'info', "Error in importing data for Order ID $order_id: " . print_r( $error, true ) );
		}

		$to_insert = isset( $data['data']['insert'] ) ? $data['data']['insert'] : array();
		$to_update = isset( $data['data']['update'] ) ? $data['data']['update'] : array();

		if ( 0 === count( $to_insert ) && 0 === count( $to_update ) ) {
			return;
		}

		$order_post_ids_placeholders = implode( ',', array_fill( 0, count( $order_post_ids ), '%d' ) );

		$already_migrated = $wpdb->get_results(
			$wpdb->prepare(
				"
SELECT addresses.id, addresses.order_id, orders.post_id FROM {$this->table_names['addresses']} as addresses
JOIN {$this->table_names['orders']} orders ON addresses.order_id = orders.id
WHERE
      address_type = %s AND
      orders.post_id IN ( $order_post_ids_placeholders )
				",
				$type,
				$order_post_ids
			)
		);

		$order_post_ids_to_migrate = array_diff( $order_post_ids, array_column( $already_migrated, 'post_id' ) );
		$to_insert                 = array_intersect_key( $to_insert, array_flip( $order_post_ids_to_migrate ) );
		$this->execute_action_for_batch( $migrator, $to_insert, 'insert' );

		if ( 0 < count( $to_update ) ) {
			echo 'comes here';
		}
		$to_update_intersect = array();
		$ids_to_update       = array_flip( array_column( $already_migrated, 'id' ) );
		foreach ( $to_update as $post_id => $address_record ) {
			if ( isset( $ids_to_update[ $address_record['id'] ] ) ) {
				$to_update_intersect[ $post_id ] = $address_record;
			}
		}

		$this->execute_action_for_batch( $migrator, $to_update_intersect, 'update' );
	}

	/**
	 * Process next migration batch, uses option `wc_cot_migration` to checkpoints of what have been processed so far.
	 *
	 * @param int $batch_size Batch size of records to migrate.
	 *
	 * @return bool True if migration is completed, false if there are still records to process.
	 */
	public function process_next_migration_batch( $batch_size = 100 ) {
		$order_post_ids = $this->get_next_batch_ids( $batch_size );
		if ( 0 === count( $order_post_ids ) ) {
			return true;
		}

		$this->order_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->billing_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->shipping_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->operation_data_table_migrator->process_migration_batch_for_ids( $order_post_ids );

		$last_post_migrated = max( $order_post_ids );
		$this->update_checkpoint( $last_post_migrated );

		return false;
	}

	/**
	 * Process next batch for a given address type.
	 *
	 * @param MetaToCustomTableMigrator $migrator Migrator instance for address type.
	 * @param array $order_post_ids Array of post IDs for orders.
	 * @param string $order_by Order by clause.
	 */
	private function process_next_migrator_batch( $migrator, $order_post_ids ) {
		$data = $migrator->fetch_data_for_migration_for_ids( $order_post_ids );

		foreach ( $data['errors'] as $order_id => $error ) {
			// TODO: Add name of the migrator in error message.
			$this->error_logger->log( 'info', "Error in importing data for Order ID $order_id: " . print_r( $error, true ) );
		}

		$to_replace = isset( $data['data']['update'] ) ? $data['data']['update'] : array();
		$this->execute_action_for_batch( $migrator, $to_replace, 'update' );

		$to_insert = isset( $data['data']['insert'] ) ? $data['data']['insert'] : array();
		$this->execute_action_for_batch( $migrator, $to_insert, 'insert' );
	}

	/**
	 * Process migration for metadata for given post ids.\
	 *
	 * @param array $order_post_ids Post IDs.
	 */
	private function process_meta_migration( $order_post_ids ) {
		global $wpdb;
		$data_to_migrate = $this->meta_table_migrator->fetch_data_for_migration_for_ids( $order_post_ids );
		$insert_queries  = $this->meta_table_migrator->generate_insert_sql_for_batch( $data_to_migrate['data'], 'insert' );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $insert_queries should already be escaped in the generating function.
		$result = $wpdb->query( $insert_queries );
		if ( count( $data_to_migrate['data'] ) !== $result ) {
			// TODO: Find and log entity ids that were not inserted.
			echo 'error';
		}
	}

	/**
	 * Method to migrate single record.
	 *
	 * @param int $post_id Post ID of record to migrate.
	 *
	 * @return bool|\WP_Error
	 */
	public function process_single( $post_id ) {
		global $wpdb;

		$where_clause = $wpdb->prepare( 'ID = %d', $post_id );
		$data         = $this->order_table_migrator->fetch_data_for_migration_for_ids( $where_clause, 1, 'ID ASC' );
		if ( isset( $data['errors'][ $post_id ] ) ) {
			return new \WP_Error( $data['errors'][ $post_id ] );
		}

		$queries = $this->order_table_migrator->generate_insert_sql_for_batch( $data['data'], 'replace' );
		$result  = $wpdb->query( $queries ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $queries is already prepared.
		if ( 1 !== $result ) {
			// TODO: Fetch and return last error.
			echo 'error';

			return new \WP_Error( 'error' );
		}

		return true;
	}

	/**
	 * Helper function to get where clause to send to MetaToCustomTableMigrator instance.
	 *
	 * @return array Where clause.
	 */
	private function get_next_batch_ids( $batch_size ) {
		global $wpdb;

		$checkpoint = $this->get_checkpoint();
		$post_ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE ID > %d AND post_type = '%s' LIMIT %d",
				$checkpoint['id'],
				'shop_order',
				$batch_size
			)
		);

		return $post_ids;
	}

	/**
	 * Helper method to create `ID in (.., .., ...)` clauses.
	 *
	 * @param array $ids List of IDs.
	 * @param string $column_name Name of the ID column.
	 *
	 * @return string Prepared clause for where.
	 */
	private function get_where_id_clause( $ids, $column_name = 'ID' ) {
		global $wpdb;

		if ( 0 === count( $ids ) ) {
			return '';
		}

		$id_placeholder_array = '(' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';

		return $wpdb->prepare( "`$column_name` IN $id_placeholder_array", $ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Both $column_name and $id_placeholder_array should already be prepared.
	}

	/**
	 * Current checkpoint status.
	 *
	 * @return false|mixed|void
	 */
	private function get_checkpoint() {
		return get_option( 'wc_cot_migration', array( 'id' => 0 ) );
	}

	/**
	 * Updates current checkpoint
	 *
	 * @param int $id Order ID.
	 */
	public function update_checkpoint( $id ) {
		return update_option( 'wc_cot_migration', array( 'id' => $id ), false );
	}

	public function delete_checkpoint() {
		return delete_option( 'wp_cot_migration' );
	}
}
