<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;

/**
 * The controller for the stock notifications.
 */
class StockNotifications {

	/**
	 * Initialize the controller.
	 */

	public function __construct() {
		add_action( 'woocommerce_installed', array( SyncTasks::class, 'schedule_async_tasks' ) );
		add_action( 'customer_stock_notifications_daily', array( SyncTasks::class, 'do_wc_customer_stock_notifications_daily' ) );
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );

		register_deactivation_hook( WC_PLUGIN_FILE, array( $this, 'on_deactivation' ) );
	}

	/**
	 * Regiter hooks and services.
	 *
	 * @internal
	 */
	public function init_hooks() {
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		$container = wc_get_container();
		$container->get( EmailManager::class );
		$container->get( StockSyncController::class );
		$container->get( NotificationsProcessor::class );
		$container->get( PrivacyEraser::class );

		if ( is_admin() ) {
			$container->get( AdminManager::class );
		}
	}

	/**
	 * Register the data stores.
	 *
	 * @param array $data_stores Data stores.
	 * @return array
	 */
	public function register_data_stores( $data_stores ) {
		if ( ! is_array( $data_stores ) ) {
			return $data_stores;
		}

		$data_stores['stock_notification'] = wc_get_container()->get( StockNotificationsDataStore::class );
		return $data_stores;
	}

	/**
	 * Do any cleanup on plugin deactivation.
	 *
	 */
	public function on_deactivation() {
		SyncTasks::clear_async_tasks();
	}
}
