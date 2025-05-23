<?php
/**
 * StockNotificationsServiceProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsMetaDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\Controller;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;

/**
 * Service provider for Back in Stock Notification classes.
 */
class StockNotificationsServiceProvider extends AbstractServiceProvider {

	/**
	 * The classes/interfaces that are serviced by this service provider.
	 *
	 * @var array
	 */
	protected $provides = array(
		Controller::class,
		EmailManager::class,
		EmailTemplatesController::class,
		StockNotificationsDataStore::class,
		StockNotificationsMetaDataStore::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( StockNotificationsDataStore::class )->addArguments( array( StockNotificationsMetaDataStore::class, StockNotificationsActivityLogsDataStore::class, DatabaseUtil::class ) );
		$this->share( Controller::class );
		$this->share( EmailManager::class );
		$this->share( EmailTemplatesController::class );
	}
}
