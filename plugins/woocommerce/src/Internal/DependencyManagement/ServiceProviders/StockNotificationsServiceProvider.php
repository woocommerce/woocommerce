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
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\FormHandlerService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\SignupService;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
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
		SettingsController::class,
		ProductPageIntegration::class,
		FormHandlerService::class,
		SignupService::class,
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
		$this->share( SettingsController::class );
		$this->share( ProductPageIntegration::class );
		$this->share( FormHandlerService::class )->addArguments( array( SignupService::class ) );
	}
}
