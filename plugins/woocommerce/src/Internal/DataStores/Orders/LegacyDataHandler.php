<?php
/**
 * LegacyDataHandler class file.
 */

namespace Automattic\WooCommerce\Internal\DataStores\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * This class provides functionality to clean up post data from the posts table when HPOS is authoritative.
 */
class LegacyDataHandler {

	/**
	 * Instance of the HPOS datastore.
	 *
	 * @var OrdersTableDataStore
	 */
	private OrdersTableDataStore $data_store;

	/**
	 * Instance of the DataSynchronizer class.
	 *
	 * @var DataSynchronizer
	 */
	private DataSynchronizer $data_synchronizer;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @param OrdersTableDataStore $data_store HPOS datastore instance to use.
	 * @param DataSynchronizer     $data_synchronizer DataSynchronizer instance to use.
	 *
	 * @internal
	 */
	final public function init( OrdersTableDataStore $data_store, DataSynchronizer $data_synchronizer ) {
		$this->data_store        = $data_store;
		$this->data_synchronizer = $data_synchronizer;
	}
}
