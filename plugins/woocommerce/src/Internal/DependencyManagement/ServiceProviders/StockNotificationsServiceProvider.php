<?php
/**
 * StockNotificationsServiceProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\DependencyManagement\ServiceProviders;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsMetaDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\MenusController;
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
		StockNotifications::class,
		StockNotificationsDataStore::class,
		StockNotificationsMetaDataStore::class,
		NotificationsProcessor::class,
		StockSyncController::class,
		EmailManager::class,
		EmailTemplatesController::class,
		AdminManager::class,
		SettingsController::class,
		MenusController::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( StockNotifications::class );
		$this->share( StockNotificationsDataStore::class )->addArguments( array( StockNotificationsMetaDataStore::class, DatabaseUtil::class ) );
		$this->share( EmailManager::class );
		$this->share( EmailTemplatesController::class );
		$this->share( StockSyncController::class );
		$this->share( NotificationsProcessor::class )->addArguments( array( EmailManager::class ) );
		$this->share( AdminManager::class );
		$this->share( SettingsController::class );
		$this->share( MenusController::class );
	}
}
