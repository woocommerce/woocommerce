<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\StockNotifications;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\DataStores\StockNotifications\StockNotificationsDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailActionController;
use Automattic\WooCommerce\Internal\StockNotifications\StockSyncController;
use Automattic\WooCommerce\Internal\StockNotifications\Privacy\PrivacyEraser;
use Automattic\WooCommerce\Internal\StockNotifications\Emails\EmailManager;
use Automattic\WooCommerce\Internal\StockNotifications\AsyncTasks\NotificationsProcessor;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\AdminManager;
use Automattic\WooCommerce\Internal\StockNotifications\Admin\SettingsController;
use Automattic\WooCommerce\Internal\StockNotifications\Frontend\MyAccountEndpoint;
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
	 * The option that stores the feature's on/off state.
	 *
	 * Declared as `option_key` in the feature definition, so this constant is the
	 * single source of truth rather than a copy of a derived name.
	 */
	public const ENABLE_OPTION_NAME = 'woocommerce_feature_customer_stock_notifications_enabled';

	/**
	 * Register the hooks that must exist regardless of the feature's state.
	 *
	 * The services are wired up in `maybe_init_services()`, behind the feature check.
	 * The feature-change listener cannot live there: during the request that turns the
	 * feature on it is still off, so it would never observe its own activation.
	 *
	 * @internal
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'maybe_init_services' ), 1 );
		add_action( 'woocommerce_installed', array( $this, 'on_install_or_update' ) );
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'on_feature_enabled_changed' ), 10, 2 );
	}

	/**
	 * Set up the data retention tasks when WooCommerce is installed or updated.
	 */
	public function on_install_or_update() {
		if ( ! self::is_enabled() ) {
			return;
		}

		wc_get_container()->get( DataRetentionController::class )->on_woo_install_or_update();
	}

	/**
	 * Set up or tear down the feature's side effects when it is toggled.
	 *
	 * Toggling from the Features screen fires no install event, so the daily data
	 * retention task and the rewrite rules have to be handled here.
	 *
	 * Params are untyped and coerced in the body because any third-party code can
	 * fire this action with fewer or differently-typed arguments.
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
	 * Runs on `init` priority 1: after the textdomain is loaded, and before
	 * `WC_Install::check_version()` (priority 5) fires `woocommerce_installed`.
	 *
	 * @internal
	 */
	public function maybe_init_services(): void {
		if ( ! self::is_enabled() ) {
			return;
		}

		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		$container = wc_get_container();
		$container->get( EmailManager::class );
		$container->get( StockSyncController::class );
		$container->get( NotificationsProcessor::class );
		$container->get( PrivacyEraser::class );
		$container->get( DataRetentionController::class );
		$container->get( Telemetry::class );

		$container->get( MyAccountEndpoint::class );

		// The settings filters must attach outside admin too, or the REST settings
		// API (`is_admin()` is false there) never sees the feature's settings.
		$container->get( SettingsController::class );

		if ( is_admin() ) {
			$container->get( AdminManager::class );
			return;
		}

		// Frontend-only: `template_redirect` and the add-to-cart hooks never fire in admin.
		$container->get( EmailActionController::class );
		$container->get( ProductPageIntegration::class );
		$container->get( FormHandlerService::class );
		$container->get( NotificationManagementService::class );
	}

	/**
	 * Register hooks and services.
	 *
	 * Declared without a native return type on purpose: the method it restores had
	 * none, and adding one would fatal any subclass that overrides it.
	 *
	 * @deprecated 11.2.0 Replaced by maybe_init_services().
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function init_hooks() {
		wc_deprecated_function( __METHOD__, '11.2.0', __CLASS__ . '::maybe_init_services()' );

		if ( did_action( 'init' ) ) {
			$this->maybe_init_services();
			return;
		}

		// Alpha-era callers ran this on plugins_loaded. Defer so the feature check
		// runs after the textdomain is loaded, or the feature definitions would be
		// built untranslated and WordPress would flag the early translation call.
		add_action( 'init', array( $this, 'maybe_init_services' ), 1 );
	}

	/**
	 * Register the data stores, unless the feature is disabled.
	 *
	 * @param array $data_stores Data stores.
	 * @return array
	 */
	public function register_data_stores( $data_stores ) {
		// WC_Data_Store::__construct() re-applies this filter on every data store load,
		// so re-check the feature: it can be switched off after this callback was
		// attached at `init`. Read the option directly rather than through
		// feature_is_enabled(): a data store can be loaded before `init`, and building
		// the translated feature definitions that early is not safe.
		if ( 'yes' !== get_option( self::ENABLE_OPTION_NAME, 'no' ) && ! self::is_alpha_enabled() ) {
			return $data_stores;
		}

		if ( ! is_array( $data_stores ) ) {
			return $data_stores;
		}

		$data_stores['stock_notification'] = wc_get_container()->get( StockNotificationsDataStore::class );
		return $data_stores;
	}

	/**
	 * Check whether the feature is enabled.
	 *
	 * Must not run before `init`: feature_is_enabled() builds the translated feature
	 * definitions on first use. Every caller is hooked at `init` or later.
	 *
	 * @return bool
	 */
	private static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_NAME ) || self::is_alpha_enabled();
	}

	/**
	 * Check whether the site opted in through the alpha constant.
	 *
	 * Only bridges the gap until wc_update_1120_migrate_stock_notifications_alpha_constant()
	 * has copied the constant into the feature option. The database version is bumped
	 * after every 11.2.0 callback has run, so once it reaches 11.2.0 the Features screen
	 * toggle is the only switch.
	 *
	 * @return bool
	 */
	private static function is_alpha_enabled(): bool {
		if ( ! Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' ) ) {
			return false;
		}

		return version_compare( (string) get_option( 'woocommerce_db_version', '0' ), '11.2.0', '<' );
	}
}
