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

		$excluded_columns = array_keys( $this->order_table_migrator->get_meta_column_config() );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->billing_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->shipping_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns = array_merge( $excluded_columns, array_keys( $this->operation_data_table_migrator->get_meta_column_config() ) );

		$this->meta_table_migrator             = new WPPostMetaToOrderMetaMigrator( $excluded_columns );
		$this->error_logger                    = new MigrationErrorLogger();
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
		$this->meta_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		// TODO: Return merged error array.
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
