<?php
/**
 * OrdersDataStoreServiceProvider class file.
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\DataBase\Migrations\CustomOrderTable\CLIRunner;
use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableRefundDataStore;
use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStoreMeta;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

/**
 * Service provider for the classes in the Internal\DataStores\Orders namespace.
 */
class OrdersDataStoreServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		DataSynchronizer::class,
		CustomOrdersTableController::class,
		OrdersTableDataStore::class,
		CLIRunner::class,
		OrdersTableDataStoreMeta::class,
		OrdersTableRefundDataStore::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( OrdersTableDataStoreMeta::class );

		$this->share( OrdersTableDataStore::class )->addArguments( array( OrdersTableDataStoreMeta::class, DatabaseUtil::class ) );
		$this->share( DataSynchronizer::class )->addArguments( array( OrdersTableDataStore::class, DatabaseUtil::class, PostsToOrdersMigrationController::class ) );
		$this->share( OrdersTableRefundDataStore::class )->addArguments( array( OrdersTableDataStoreMeta::class, DatabaseUtil::class ) );
		$this->share( CustomOrdersTableController::class )->addArguments(
			array(
				OrdersTableDataStore::class,
				DataSynchronizer::class,
				OrdersTableRefundDataStore::class,
				BatchProcessingController::class,
			)
		);
		if ( Constants::is_defined( 'WP_CLI' ) && WP_CLI ) {
			$this->share( CLIRunner::class )->addArguments( array( CustomOrdersTableController::class, DataSynchronizer::class, PostsToOrdersMigrationController::class ) );
		}
	}
}
