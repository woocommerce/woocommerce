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
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\JobManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\CycleStateService;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailTemplatesController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\MenusController;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;
use Automattic\WooCommerce\Internal\Utilities\DatabaseUtil;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\EligibilityService;
use Automattic\WooCommerce\Internal\StockNotifications\Utilities\StockManagementHelper;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;

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
		JobManager::class,
		NotificationsProcessor::class,
		CycleStateService::class,
		StockSyncController::class,
		StockManagementHelper::class,
		EligibilityService::class,
		EmailManager::class,
		EmailTemplatesController::class,
		AdminManager::class,
		SettingsController::class,
		MenusController::class,
		NotificationsPage::class,
		PrivacyEraser::class,
	);

	/**
	 * Register the classes.
	 */
	public function register() {

		// Main.
		$this->share( StockNotifications::class );

		// Data stores.
		$this->share( StockNotificationsDataStore::class )->addArguments(
			array(
				StockNotificationsMetaDataStore::class,
				DatabaseUtil::class,
			)
		);

		// Email.
		$this->share( EmailManager::class );
		$this->share( EmailTemplatesController::class );

		// Stock management.
		$this->share( EligibilityService::class )->addArguments( array( StockManagementHelper::class ) );
		$this->share( StockSyncController::class )->addArguments(
			array(
				EligibilityService::class,
				JobManager::class,
			)
		);
		$this->share( NotificationsProcessor::class )->addArguments(
			array(
				EligibilityService::class,
				JobManager::class,
				CycleStateService::class,
				EmailManager::class,
			)
		);

		// Admin.
		$this->share( AdminManager::class );
		$this->share( SettingsController::class );
		$this->share( MenusController::class )->addArguments( array( NotificationsPage::class ) );
		$this->share( PrivacyEraser::class );
	}
}
