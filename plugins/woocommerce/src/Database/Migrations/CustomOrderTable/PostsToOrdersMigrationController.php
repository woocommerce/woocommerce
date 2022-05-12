<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MigrationErrorLogger;

/**
 * This is the main class used to perform the complete migration of orders
 * from the posts table to the custom orders table.
 *
 * @package Automattic\WooCommerce\Database\Migrations\CustomOrderTable
 */
class PostsToOrdersMigrationController {

	/**
	 * Error logger for migration errors.
	 *
	 * @var MigrationErrorLogger $error_logger
	 */
	private $error_logger;

	/**
	 * Migrator instance to migrate data into wc_order table.
	 *
	 * @var PostToOrderTableMigrator
	 */
	private $order_table_migrator;

	/**
	 * Migrator instance to migrate billing data into address table.
	 *
	 * @var PostToOrderAddressTableMigrator
	 */
	private $billing_address_table_migrator;

	/**
	 * Migrator instance to migrate shipping data into address table.
	 *
	 * @var PostToOrderAddressTableMigrator
	 */
	private $shipping_address_table_migrator;

	/**
	 * Migrator instance to migrate operational data.
	 *
	 * @var PostToOrderOpTableMigrator
	 */
	private $operation_data_table_migrator;

	/**
	 * Migrator instance to migrate meta data.
	 *
	 * @var MetaToMetaTableMigrator
	 */
	private $meta_table_migrator;

	/**
	 * PostsToOrdersMigrationController constructor.
	 */
	public function __construct() {

		$this->order_table_migrator            = new PostToOrderTableMigrator();
		$this->billing_address_table_migrator  = new PostToOrderAddressTableMigrator( 'billing' );
		$this->shipping_address_table_migrator = new PostToOrderAddressTableMigrator( 'shipping' );
		$this->operation_data_table_migrator   = new PostToOrderOpTableMigrator();

		$excluded_columns = $this->get_migrated_meta_keys();

		$this->meta_table_migrator = new PostMetaToOrderMetaMigrator( $excluded_columns );
		$this->error_logger        = new MigrationErrorLogger();
	}

	/**
	 * Helper method to get keys to migrate for migrations.
	 *
	 * @return int[]|string[]
	 */
	public function get_migrated_meta_keys() : array {
		$migrated_keys = array_keys( $this->order_table_migrator->get_meta_column_config() );
		$migrated_keys = array_merge( $migrated_keys, array_keys( $this->billing_address_table_migrator->get_meta_column_config() ) );
		$migrated_keys = array_merge( $migrated_keys, array_keys( $this->shipping_address_table_migrator->get_meta_column_config() ) );
		$migrated_keys = array_merge( $migrated_keys, array_keys( $this->operation_data_table_migrator->get_meta_column_config() ) );
		return $migrated_keys;
	}

	/**
	 * Migrates a set of orders from the posts table to the custom orders tables.
	 *
	 * @param array $order_post_ids List of post IDs of the orders to migrate.
	 */
	public function migrate_orders( array $order_post_ids ): void {
		$this->order_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->billing_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->shipping_address_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->operation_data_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		$this->meta_table_migrator->process_migration_batch_for_ids( $order_post_ids );
		// TODO: Return merged error array.
	}

	/**
	 * Verify whether the given order IDs were migrated properly or not.
	 *
	 * @param array $order_post_ids Order IDs.
	 *
	 * @return array Array of failed IDs along with columns.
	 */
	public function verify_migrated_orders( array $order_post_ids ): array {
		return $this->order_table_migrator->verify_migrated_data( $order_post_ids ) +
			$this->billing_address_table_migrator->verify_migrated_data( $order_post_ids ) +
			$this->shipping_address_table_migrator->verify_migrated_data( $order_post_ids ) +
			$this->operation_data_table_migrator->verify_migrated_data( $order_post_ids );
	}

	/**
	 * Migrates an order from the posts table to the custom orders tables.
	 *
	 * @param int $order_post_id Post ID of the order to migrate.
	 */
	public function migrate_order( int $order_post_id ): void {
		$this->migrate_orders( array( $order_post_id ) );
		// TODO: Return error.
	}
}
