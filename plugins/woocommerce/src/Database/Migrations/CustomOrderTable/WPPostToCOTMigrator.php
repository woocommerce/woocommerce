<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MigrationErrorLogger;

/**
 * Class WPPostToCOTMigrator
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
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
	 * @var WPPostToOrderTableMigrator
	 */
	private $order_table_migrator;

	/**
	 * Migrator instance to migrate billing data into address table.
	 *
	 * @var WPPostToOrderAddressTableMigrator
	 */
	private $billing_address_table_migrator;

	/**
	 * Migrator instance to migrate shipping data into address table.
	 *
	 * @var WPPostToOrderAddressTableMigrator
	 */
	private $shipping_address_table_migrator;

	/**
	 * Migrator instance to migrate operational data.
	 *
	 * @var WPPostToOrderOpTableMigrator
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
		$this->process_migration_for_ids( $order_post_ids );
		$last_post_migrated = max( $order_post_ids );
		$this->update_checkpoint( $last_post_migrated );
		return false;
	}

	/**
	 * Process migration for specific order post IDs.
	 *
	 * @param array $order_post_ids List of post IDs to migrate.
	 */
	public function process_migration_for_ids( $order_post_ids ) {
		$this->order_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->billing_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->shipping_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->operation_data_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		// TODO: Add resilience for meta migrations.
		// $this->process_meta_migration( $order_post_ids );
		// TODO: Return merged error array.
	}

	/**
	 * Process migration for metadata for given post ids.
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
	 */
	public function process_single( $post_id ) {
		$this->process_migration_for_ids( array( $post_id ) );
		// TODO: Return error.
	}

	/**
	 * Helper function to get where clause to send to MetaToCustomTableMigrator instance.
	 *
	 * @param int $batch_size Number of orders in batch.
	 *
	 * @return array List of IDs in the current patch.
	 */
	private function get_next_batch_ids( $batch_size ) {
		global $wpdb;

		$checkpoint = $this->get_checkpoint();
		$post_ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE ID > %d AND post_type = %s ORDER BY ID ASC LIMIT %d ",
				$checkpoint['id'],
				'shop_order',
				$batch_size
			)
		);

		return $post_ids;
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

	/**
	 * Remove checkpoint.
	 *
	 * @return bool Whether checkpoint was removed.
	 */
	public function delete_checkpoint() {
		return delete_option( 'wp_cot_migration' );
	}
}
