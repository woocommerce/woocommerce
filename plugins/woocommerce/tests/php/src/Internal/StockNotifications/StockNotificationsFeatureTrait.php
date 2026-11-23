<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;

/**
 * Enables the Back In Stock Notifications feature for a test's lifetime.
 *
 * The feature is off by default in the test suite (see WOOPLUG-7704), so any
 * test that reads or writes a `Notification` must enable it itself, following
 * the Order Withdrawal set_up()/tear_down() pattern. Tests build their own
 * subject under test; only tests of the wiring itself call
 * `init_stock_notifications_services()`.
 */
trait StockNotificationsFeatureTrait {

	/**
	 * The feature option's value before the test enabled it.
	 *
	 * @var mixed
	 */
	private $stock_notifications_original_feature_option;

	/**
	 * Enable the feature and register its data store, enough to read and write a Notification.
	 *
	 * Deliberately stops short of `maybe_init_services()`: hooking the container's
	 * services would run real instances alongside the mocks a test injects into its own.
	 */
	private function enable_stock_notifications_feature(): void {
		$this->stock_notifications_original_feature_option = get_option( StockNotifications::ENABLE_OPTION_NAME, false );
		update_option( StockNotifications::ENABLE_OPTION_NAME, 'yes' );
		add_filter( 'woocommerce_data_stores', array( wc_get_container()->get( StockNotifications::class ), 'register_data_stores' ) );
	}

	/**
	 * Wire up every service `maybe_init_services()` gates. Only for tests of the wiring itself.
	 *
	 * Resets the container first: it caches each service, and the hooks a cached instance
	 * added in an earlier test are gone once that test tore down, so only a fresh
	 * resolution actually re-hooks.
	 */
	private function init_stock_notifications_services(): void {
		$container = wc_get_container();
		$container->reset_all_resolved();
		$container->get( StockNotifications::class )->maybe_init_services();
	}

	/**
	 * Restore the feature option to its pre-test value.
	 */
	private function restore_stock_notifications_feature_option(): void {
		if ( false === $this->stock_notifications_original_feature_option ) {
			delete_option( StockNotifications::ENABLE_OPTION_NAME );
		} else {
			update_option( StockNotifications::ENABLE_OPTION_NAME, $this->stock_notifications_original_feature_option );
		}
	}
}
