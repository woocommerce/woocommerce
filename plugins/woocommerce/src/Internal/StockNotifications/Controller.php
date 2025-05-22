<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\StockNotifications\TemplatesController;
use Automattic\WooCommerce\Internal\StockNotifications\EmailsController;
use Automattic\WooCommerce\Internal\StockNotifications\SettingsController;

/**
 * The controller for the stock notifications.
 */
class Controller {

	/**
	 * Initialize the controller.
	 *
	 * @internal
	 */
	final public function init() {
		add_action( 'plugins_loaded', array( $this, 'init_hooks' ) );
	}

	/**
	 * Regiter hooks and services.
	 *
	 * @internal
	 */
	public function init_hooks() {

		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		// Settings.
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'register_settings' ) );

		$container = wc_get_container();
		$container->get( TemplatesController::class );
		$container->get( EmailsController::class );
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
	 * Register the Customer Stock Notifications settings.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public function register_settings( $settings ) {

		$settings[] = wc_get_container()->get( SettingsController::class );

		return $settings;
	}
}
