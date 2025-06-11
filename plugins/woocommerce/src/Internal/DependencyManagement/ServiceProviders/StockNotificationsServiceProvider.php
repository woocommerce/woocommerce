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
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\NotificationEligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsBatchProcessor;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;

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
		StockSyncController::class,
		EmailManager::class,
		EmailTemplatesController::class,
		SettingsController::class,
		NotificationEligibilityService::class,
		NotificationsBatchProcessor::class,
		StockManagementHelper::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {
		$this->share( StockNotifications::class );
		$this->share( StockNotificationsDataStore::class )->addArguments( array( StockNotificationsMetaDataStore::class, DatabaseUtil::class ) );
		$this->share( EmailManager::class );
		$this->share( EmailTemplatesController::class );
		$this->share( StockManagementHelper::class );
		$this->share( NotificationEligibilityService::class )->addArguments( array( StockManagementHelper::class ) );
		$this->share( StockSyncController::class )->addArguments( array( NotificationEligibilityService::class ) );
		$this->share( NotificationsBatchProcessor::class )->addArguments( array( EmailManager::class, NotificationEligibilityService::class, BatchProcessingController::class ) );
		$this->share( SettingsController::class );
	}
}
