<?php
/**
 * DataSynchronizer class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Utilities\DBUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the data migration/synchronization for the custom orders table. Its responsibilites are:
 *
 * - Performing the initial table creation and filling (triggered by initiate_regeneration)
 * - Synchronizing changes between the custom orders table and the posts table whenever changes in orders happen.
 */
class DataSynchronizer {

	const CUSTOM_ORDERS_TABLE_DATA_REGENERATION_IN_PROGRESS = 'woocommerce_custom_orders_table_data_regeneration_in_progress';
	const CUSTOM_ORDERS_TABLE_DATA_REGENERATION_DONE_COUNT  = 'woocommerce_custom_orders_table_data_regeneration_done_count';

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	// TODO: Add a constructor to handle hooks as appropriate.

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore $data_store The data store to use.
	 */
	final public function init( OrdersTableDataStore $data_store ) {
		$this->data_store = $data_store;
	}

	/**
	 * Does the custom orders table exist in the database?
	 *
	 * @return bool True if the custom orders table exist in the database.
	 */
	public function check_orders_table_exists(): bool {
		$missing_tables = DBUtil::verify_database_tables_exist( $this->data_store->get_schema() );
		if ( count( $missing_tables ) > 0 ) {
			delete_option( self::CUSTOM_ORDERS_TABLE_DATA_REGENERATION_IN_PROGRESS );
		}
		return count( $missing_tables ) === 0;
	}

	/**
	 * Is a table regeneration in progress?
	 *
	 * @return bool True if a table regeneration in currently progress
	 */
	public function data_regeneration_is_in_progress(): bool {
		return 'yes' === get_option( self::CUSTOM_ORDERS_TABLE_DATA_REGENERATION_IN_PROGRESS );
	}

	/**
	 * Initiate a table regeneration process.
	 */
	public function initiate_regeneration() {
		update_option( self::CUSTOM_ORDERS_TABLE_DATA_REGENERATION_IN_PROGRESS, 'yes' );
		update_option( self::CUSTOM_ORDERS_TABLE_DATA_REGENERATION_DONE_COUNT, 0 );
		DBUtil::create_database_tables( $this->data_store->get_schema() );
	}

	/**
	 * How many orders have been processed as part of the custom orders table regeneration?
	 *
	 * @return int Number of orders already processed, 0 if no regeneration is in progress.
	 */
	public function get_regeneration_processed_orders_count(): int {
		return (int)get_option( self::CUSTOM_ORDERS_TABLE_DATA_REGENERATION_DONE_COUNT, 0 );
	}

	/**
	 * Delete the custom orders table and the associated information.
	 */
	public function delete_custom_orders_table() {
		// TODO: Delete the tables and any associated data (e.g. options).
	}
}
