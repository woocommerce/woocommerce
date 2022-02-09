<?php
/**
 * DataSynchronizer class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

defined( 'ABSPATH' ) || exit;

/**
 * This class handles the database structure creation and the data synchronization for the custom orders tables. Its responsibilites are:
 *
 * - Providing entry points for creating and deleting the required database tables.
 * - Synchronizing changes between the custom orders tables and the posts table whenever changes in orders happen.
 */
class DataSynchronizer {

	/**
	 * The data store object to use.
	 *
	 * @var OrdersTableDataStore
	 */
	private $data_store;

	/**
	 * The database util object to use.
	 *
	 * @var DatabaseUtil
	 */
	private $database_util;

	// TODO: Add a constructor to handle hooks as appropriate.

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrdersTableDataStore $data_store The data store to use.
	 * @param DatabaseUtil         $database_util The database util class to use.
	 */
	final public function init( OrdersTableDataStore $data_store, DatabaseUtil $database_util ) {
		$this->data_store    = $data_store;
		$this->database_util = $database_util;
	}

	/**
	 * Does the custom orders tables exist in the database?
	 *
	 * @return bool True if the custom orders tables exist in the database.
	 */
	public function check_orders_table_exists(): bool {
		$missing_tables = $this->database_util->get_missing_tables( $this->data_store->get_database_schema() );

		return count( $missing_tables ) === 0;
	}

	/**
	 * Create the custom orders database tables.
	 */
	public function create_database_tables() {
		$this->database_util->dbdelta( $this->data_store->get_database_schema() );
	}

	/**
	 * Delete the custom orders database tables.
	 */
	public function delete_database_tables() {
		$table_names = $this->data_store->get_all_table_names();

		foreach ( $table_names as $table_name ) {
			$this->database_util->drop_database_table( $table_name );
		}
	}


}
