<?php
/**
 * Class for implementing migration from wp_posts and wp_postmeta to custom order tables.
 */

namespace Automattic\WooCommerce\Database\Migrations\CustomOrderTable;

use Automattic\WooCommerce\Database\Migrations\MigrationErrorLogger;
use Automattic\WooCommerce\Utilities\ArrayUtil;

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
	 * @var WC_Logger
	 */
	private $error_logger;

	/**
	 * Array of objects used to perform the migration.
	 *
	 * @var array
	 */
	private $all_migrators;

	/**
	 * The source name to use for logs.
	 */
	private const LOGS_SOURCE_NAME = 'posts-to-orders-migration';

	/**
	 * PostsToOrdersMigrationController constructor.
	 */
	public function __construct() {

		$order_table_migrator            = new PostToOrderTableMigrator();
		$billing_address_table_migrator  = new PostToOrderAddressTableMigrator( 'billing' );
		$shipping_address_table_migrator = new PostToOrderAddressTableMigrator( 'shipping' );
		$operation_data_table_migrator   = new PostToOrderOpTableMigrator();

		$excluded_columns    = array_keys( $order_table_migrator->get_meta_column_config() );
		$excluded_columns    = array_merge( $excluded_columns, array_keys( $billing_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns    = array_merge( $excluded_columns, array_keys( $shipping_address_table_migrator->get_meta_column_config() ) );
		$excluded_columns    = array_merge( $excluded_columns, array_keys( $operation_data_table_migrator->get_meta_column_config() ) );
		$meta_table_migrator = new PostMetaToOrderMetaMigrator( $excluded_columns );

		$this->all_migrators = array(
			$order_table_migrator,
			$billing_address_table_migrator,
			$shipping_address_table_migrator,
			$operation_data_table_migrator,
			$meta_table_migrator,
		);

		$this->error_logger = wc_get_logger();
	}

	/**
	 * Migrates a set of orders from the posts table to the custom orders tables.
	 *
	 * @param array $order_post_ids List of post IDs of the orders to migrate.
	 */
	public function migrate_orders( array $order_post_ids ): void {
		foreach ( $this->all_migrators as $migrator ) {
			$this->do_orders_migration_step( $migrator, $order_post_ids );
		}
	}

	/**
	 * Performs one step of the migration for a set of order posts using one given migration class.
	 * All database errors and exceptions are logged.
	 *
	 * @param object $migration_class The migration class to use, must have a `process_migration_batch_for_ids(array of ids)` method.
	 * @param array  $order_post_ids List of post IDs of the orders to migrate.
	 * @return void
	 */
	private function do_orders_migration_step( object $migration_class, array $order_post_ids ): void {
		$result = $migration_class->process_migration_batch_for_ids( $order_post_ids );

		$errors    = $result['errors'];
		$exception = $result['exception'];
		if ( null === $exception && empty( $errors ) ) {
			return;
		}

		$migration_class_name = ( new \ReflectionClass( $migration_class ) )->getShortName();
		$batch                = ArrayUtil::to_ranges_string( $order_post_ids );

		if ( null !== $exception ) {
			$exception_class = get_class( $exception );
			$this->error_logger->error(
				"$migration_class_name: when processing ids $batch: ($exception_class) {$exception->getMessage()}, {$exception->getTraceAsString()}",
				array(
					'source'    => self::LOGS_SOURCE_NAME,
					'ids'       => $order_post_ids,
					'exception' => $exception,
				)
			);
		}

		foreach ( $errors as $error ) {
			$this->error_logger->error(
				"$migration_class_name: when processing ids $batch: $error",
				array(
					'source'    => self::LOGS_SOURCE_NAME,
					'ids'       => $order_post_ids,
					'exception' => $exception,
				)
			);
		}
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
	}
}
