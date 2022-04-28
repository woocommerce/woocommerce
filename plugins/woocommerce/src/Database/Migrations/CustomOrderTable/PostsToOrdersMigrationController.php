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
	 * PostsToOrdersMigrationController constructor.
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
	 * Migrates an order from the posts table to the custom orders tables.
	 *
	 * @param int $order_post_id Post ID of the order to migrate.
	 */
	public function migrate_order( int $order_post_id ): void {
		$this->migrate_orders( array( $order_post_id ) );
		// TODO: Return error.
	}
}
