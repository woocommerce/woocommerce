<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\ProductPageIntegration;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\FormHandlerService;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\NotificationManagementService;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * The controller for the stock notifications.
 */
class StockNotifications implements RegisterHooksInterface {

	/**
	 * The feature that gates the whole Back in Stock Notifications experience.
	 */
	public const FEATURE_NAME = 'customer_stock_notifications';

	/**
	 * Register the hooks that must exist regardless of the feature's state.
	 *
	 * Only the feature-independent listeners live here. The services themselves are
	 * wired up in `maybe_init_services()`, behind the feature check, following the same
	 * shape as `ShopperListsController` and `OrderWithdrawalController`.
	 *
	 * Listening for the feature change has to happen outside the feature check:
	 * during the request that turns the feature on it is still off, so a listener
	 * registered behind the gate would never observe its own activation.
	 *
	 * @internal
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'maybe_init_services' ), 1 );
		add_action( 'woocommerce_installed', array( $this, 'on_install_or_update' ) );
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'on_feature_enabled_changed' ), 10, 2 );
	}

	/**
	 * Handle the WooCommerce installation event.
	 *
	 * This method is called when WooCommerce is installed or updated. When the
	 * feature is enabled, it initializes the data retention controller to set up
	 * necessary tasks; otherwise it does nothing.
	 */
	public function on_install_or_update() {
		if ( ! FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) ) {
			return;
		}

		wc_get_container()->get( DataRetentionController::class )->on_woo_install_or_update();
	}

	/**
	 * Set up or tear down the feature's side effects when it is toggled.
	 *
	 * Enabling the feature from the Features screen fires no install event, so the
	 * daily data retention task and the My Account endpoint rewrite rules have to be
	 * taken care of here. Disabling it has to undo both.
	 *
	 * Left untyped, and coerced in the body, because this is a public hook callback:
	 * third-party code firing the action may pass fewer or differently-typed arguments.
	 *
	 * @param mixed $feature_id The feature that changed.
	 * @param mixed $enabled    Whether the feature is now enabled.
	 *
	 * @internal
	 */
	public function on_feature_enabled_changed( $feature_id, $enabled = false ): void {
		if ( self::FEATURE_NAME !== $feature_id ) {
			return;
		}

		$enabled = filter_var( $enabled, FILTER_VALIDATE_BOOLEAN );

		// The My Account endpoint appears or disappears with the feature.
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );

		$data_retention_controller = wc_get_container()->get( DataRetentionController::class );

		if ( $enabled ) {
			$data_retention_controller->on_woo_install_or_update();
		} else {
			$data_retention_controller->clear_daily_task();
		}
	}

	/**
	 * Wire up the services, unless the feature is disabled.
	 *
	 * Hooked to `init` priority 1 so the feature check runs after the textdomain is
	 * loaded, and before `WC_Install::check_version()` (priority 5) fires
	 * `woocommerce_installed`.
	 *
	 * @internal
	 */
	public function maybe_init_services(): void {
		if ( ! FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) ) {
			return;
		}

		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		$container = wc_get_container();
		$container->get( EmailManager::class );
		$container->get( StockSyncController::class );
		$container->get( NotificationsProcessor::class );
		$container->get( PrivacyEraser::class );
		$container->get( DataRetentionController::class );
		$container->get( EmailActionController::class );

		$container->get( ProductPageIntegration::class );
		$container->get( FormHandlerService::class );
		$container->get( NotificationManagementService::class );

		if ( is_admin() ) {
			$container->get( AdminManager::class );
		}
	}

	/**
	 * Register the data stores, unless the feature is disabled.
	 *
	 * @param array $data_stores Data stores.
	 * @return array
	 */
	public function register_data_stores( $data_stores ) {
		// Check the option directly instead of using FeaturesController::feature_is_enabled()
		// because the woocommerce_data_stores filter can fire before the 'init' action, and
		// feature_is_enabled() would trigger translation loading too early, causing
		// _load_textdomain_just_in_time warnings.
		if ( 'yes' !== get_option( 'woocommerce_feature_customer_stock_notifications_enabled', 'no' ) ) {
			return $data_stores;
		}

		if ( ! is_array( $data_stores ) ) {
			return $data_stores;
		}

		$data_stores['stock_notification'] = wc_get_container()->get( StockNotificationsDataStore::class );
		return $data_stores;
	}
}
