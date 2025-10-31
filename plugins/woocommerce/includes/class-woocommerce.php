<?php
/**
 * WooCommerce setup
 *
 * @package WooCommerce
 * @since   3.2.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\AddressProvider\AddressProviderController;
use Automattic\WooCommerce\Internal\AssignDefaultCategory;
use Automattic\WooCommerce\Internal\BatchProcessing\BatchProcessingController;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonAdminBarBadge;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonCacheInvalidator;
use Automattic\WooCommerce\Internal\ComingSoon\ComingSoonRequestHandler;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DownloadPermissionsAdjuster;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Internal\MCP\MCPAdapterProvider;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesRegistry;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\DataRegenerator;
use Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore;
use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register as ProductDownloadDirectories;
use Automattic\WooCommerce\Internal\ProductImage\MatchImageBySKU;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use Automattic\WooCommerce\Internal\RestockRefundedItemsAdjuster;
use Automattic\WooCommerce\Internal\Settings\OptionSanitizer;
use Automattic\WooCommerce\Internal\Utilities\LegacyRestApiStub;
use Automattic\WooCommerce\Internal\Utilities\WebhookUtil;
use Automattic\WooCommerce\Internal\Admin\EmailImprovements\EmailImprovements;
use Automattic\WooCommerce\Internal\Admin\Marketplace;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\{LoggingUtil, RestApiUtil, TimeUtil};
use Automattic\WooCommerce\Internal\Logging\RemoteLogger;
use Automattic\WooCommerce\Caches\OrderCountCacheService;
use Automattic\WooCommerce\Internal\StockNotifications\StockNotifications;
use Automattic\Jetpack\Constants;

/**
 * Main WooCommerce Class.
 *
 * @class WooCommerce
 */
final class WooCommerce {

	/**
	 * WooCommerce version.
	 *
	 * @var string
	 */
	public $version = '10.3.4';

	/**
	 * WooCommerce Schema version.
	 *
	 * @since 4.3 started with version string 430.
	 *
	 * @var string
	 */
	public $db_version = '920';

	/**
	 * The single instance of the class.
	 *
	 * @var WooCommerce
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var WC_Session|WC_Session_Handler
	 */
	public $session = null;

	/**
	 * Query instance.
	 *
	 * @var WC_Query
	 */
	public $query = null;

	/**
	 * API instance
	 *
	 * @deprecated 9.0.0 The Legacy REST API has been removed from WooCommerce core. Now this property points to a RestApiUtil instance, unless the Legacy REST API plugin is installed.
	 *
	 * @var WC_API
	 */
	private $api;

	/**
	 * Product factory instance.
	 *
	 * @var WC_Product_Factory
	 */
	public $product_factory = null;

	/**
	 * Countries instance.
	 *
	 * @var WC_Countries
	 */
	public $countries = null;

	/**
	 * Integrations instance.
	 *
	 * @var WC_Integrations
	 */
	public $integrations = null;

	/**
	 * Cart instance.
	 *
	 * @var WC_Cart
	 */
	public $cart = null;

	/**
	 * Customer instance.
	 *
	 * @var WC_Customer
	 */
	public $customer = null;

	/**
	 * Order factory instance.
	 *
	 * @var WC_Order_Factory
	 */
	public $order_factory = null;

	/**
	 * Structured data instance.
	 *
	 * @var WC_Structured_Data
	 */
	public $structured_data = null;

	/**
	 * Array of deprecated hook handlers.
	 *
	 * @var array of WC_Deprecated_Hooks
	 */
	public $deprecated_hook_handlers = array();

	/**
	 * Main WooCommerce Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see WC()
	 * @return WooCommerce - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce' ), '2.1' );
	}

	/**
	 * Autoload inaccessible or non-existing properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( 'api' === $key ) {
			// The Legacy REST API was removed from WooCommerce core as of version 9.0 (moved to a dedicated plugin),
			// but some plugins are still using wc()->api->get_endpoint_data. This method now lives in the RestApiUtil class,
			// but we expose it through LegacyRestApiStub to limit the scope of what can be done via WC()->api.
			//
			// On the other hand, if the dedicated plugin is installed it will set the $api property by itself
			// to an instance of the old WC_API class, which of course still has the get_endpoint_data method.
			if ( is_null( $this->api ) && ! $this->legacy_rest_api_is_available() ) {
				$this->api = wc_get_container()->get( LegacyRestApiStub::class );
			}

			return $this->api;
		}

		if ( in_array( $key, array( 'payment_gateways', 'shipping', 'mailer', 'checkout' ), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * Set the value of an inaccessible or non-existing property.
	 *
	 * @param string $key Property name.
	 * @param mixed  $value Property value.
	 * @throws Exception Attempt to access a property that's private or protected.
	 */
	public function __set( string $key, $value ) {
		if ( 'api' === $key ) {
			$this->api = $value;
		} elseif ( property_exists( $this, $key ) ) {
			throw new Exception( 'Cannot access private property ' . __CLASS__ . '::$' . esc_html( $key ) );
		} else {
			$this->$key = $value;
		}
	}

	/**
	 * Check if the Legacy REST API plugin is active (and thus the Legacy REST API is available).
	 *
	 * @return bool
	 */
	public function legacy_rest_api_is_available() {
		return class_exists( 'WC_Legacy_REST_API_Plugin', false );
	}

	/**
	 * Get the WooCommerce version.
	 *
	 * @since 10.3.0
	 *
	 * @return string The WooCommerce version.
	 */
	public function stable_version(): string {
		return explode( '-', $this->version, 2 )[0];
	}

	/**
	 * WooCommerce Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->define_tables();
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * When WP has loaded all plugins, trigger the `woocommerce_loaded` hook.
	 *
	 * This ensures `woocommerce_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order. See #21524 for details.
	 *
	 * @since 3.6.0
	 */
	public function on_plugins_loaded() {
		/**
		 * Action to signal that WooCommerce has finished loading.
		 *
		 * @since 3.6.0
		 */
		do_action( 'woocommerce_loaded' );
	}

	/**
	 * Initialize Jetpack Connection Config.
	 *
	 * @return void
	 */
	public function init_jetpack_connection_config() {
		$config = new Automattic\Jetpack\Config();
		$config->ensure(
			'connection',
			array(
				'slug' => 'woocommerce',
				// Cannot use __() here because it would cause translations to be loaded too early.
				// See https://github.com/woocommerce/woocommerce/pull/47113.
				'name' => 'WooCommerce',
			)
		);
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {
		register_activation_hook( WC_PLUGIN_FILE, array( 'WC_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ), -1 );
		add_action( 'plugins_loaded', array( $this, 'init_customizer' ) );
		add_action( 'plugins_loaded', array( $this, 'init_jetpack_connection_config' ), 1 );
		add_action( 'admin_notices', array( $this, 'build_dependencies_notice' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'load-post.php', array( $this, 'includes' ) );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'WC_Shortcodes', 'init' ) );
		add_action( 'init', array( 'WC_Emails', 'init_transactional_emails' ) );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
		add_action( 'init', array( $this, 'load_rest_api' ) );
		if ( $this->is_request( 'admin' ) || ( $this->is_rest_api_request() && ! $this->is_store_api_request() ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			add_action( 'init', array( 'WC_Site_Tracking', 'init' ) );
		}
		add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'activated_plugin', array( $this, 'activated_plugin' ) );
		add_action( 'deactivated_plugin', array( $this, 'deactivated_plugin' ) );
		add_action( 'woocommerce_installed', array( $this, 'add_woocommerce_inbox_variant' ) );
		add_action( 'woocommerce_updated', array( $this, 'add_woocommerce_inbox_variant' ) );
		add_action( 'rest_api_init', array( $this, 'register_wp_admin_settings' ) );
		add_action( 'woocommerce_installed', array( $this, 'add_woocommerce_remote_variant' ) );
		add_action( 'woocommerce_updated', array( $this, 'add_woocommerce_remote_variant' ) );
		add_action( 'woocommerce_newly_installed', 'wc_set_hooked_blocks_version', 10 );
		add_action( 'update_option_woocommerce_allow_tracking', array( $this, 'get_tracking_history' ), 10, 2 );
		add_action( 'update_option_woocommerce_allow_tracking', array( $this, 'handle_tracking_setting_change' ), 10, 2 );
		add_action( 'action_scheduler_ensure_recurring_actions', array( $this, 'register_recurring_actions' ) );
		add_action( 'action_scheduler_init', array( $this, 'add_recurring_action_wrappers' ) );

		add_filter( 'robots_txt', array( $this, 'robots_txt' ) );
		add_filter( 'wp_plugin_dependencies_slug', array( $this, 'convert_woocommerce_slug' ) );
		add_filter( 'woocommerce_register_log_handlers', array( $this, 'register_remote_log_handler' ) );

		// These classes set up hooks on instantiation.
		$container = wc_get_container();
		$container->get( ProductDownloadDirectories::class );
		$container->get( DownloadPermissionsAdjuster::class );
		$container->get( AssignDefaultCategory::class );
		$container->get( DataRegenerator::class );
		$container->get( LookupDataStore::class );
		$container->get( MatchImageBySKU::class );
		$container->get( RestockRefundedItemsAdjuster::class );
		$container->get( CustomOrdersTableController::class );
		$container->get( OptionSanitizer::class );
		$container->get( BatchProcessingController::class );
		$container->get( FeaturesController::class );
		$container->get( WebhookUtil::class );
		$container->get( Marketplace::class );
		$container->get( TimeUtil::class );
		$container->get( ComingSoonAdminBarBadge::class );
		$container->get( ComingSoonCacheInvalidator::class );
		$container->get( ComingSoonRequestHandler::class );
		$container->get( OrderCountCacheService::class );
		$container->get( EmailImprovements::class );
		$container->get( AddressProviderController::class );
		$container->get( AbilitiesRegistry::class );
		$container->get( MCPAdapterProvider::class );

		// Feature flags.
		if ( Constants::is_true( 'WOOCOMMERCE_BIS_ALPHA_ENABLED' ) ) {
			$container->get( StockNotifications::class );
		}

		/**
		 * These classes have a register method for attaching hooks.
		 */
		$container->get( Automattic\WooCommerce\Internal\Utilities\PluginInstaller::class )->register();
		$container->get( Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Orders\OrderAttributionController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Orders\OrderAttributionBlocksController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\Settings\PaymentsController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Utilities\LegacyRestApiStub::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Email\EmailStyleSync::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Fulfillments\FulfillmentsController::class )->register();

		// Classes inheriting from RestApiControllerBase.
		$container->get( Automattic\WooCommerce\Internal\ReceiptRendering\ReceiptRenderingRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Orders\OrderActionsRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Orders\OrderStatusRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\Settings\PaymentsRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders\WooPayments\WooPaymentsRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreviewRestController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\Admin\Emails\EmailListingRestController::class )->register();

		$container->get( Automattic\WooCommerce\Internal\ProductFilters\MainQueryController::class )->register();
		$container->get( Automattic\WooCommerce\Internal\ProductFilters\CacheController::class )->register();
	}

	/**
	 * Add woocommerce_inbox_variant for the Remote Inbox Notification.
	 *
	 * P2 post can be found at https://wp.me/paJDYF-1uJ.
	 *
	 * This will no longer be used. The more flexible add_woocommerce_remote_variant
	 * below will be used instead.
	 */
	public function add_woocommerce_inbox_variant() {
		$config_name = 'woocommerce_inbox_variant_assignment';
		if ( false === get_option( $config_name, false ) ) {
			update_option( $config_name, wp_rand( 1, 12 ) );
		}
	}

	/**
	 * Add woocommerce_remote_variant_assignment used to determine cohort
	 * or group assignment for Remote Spec Engines.
	 */
	public function add_woocommerce_remote_variant() {
		$config_name = 'woocommerce_remote_variant_assignment';
		if ( false === get_option( $config_name, false ) ) {
			update_option( $config_name, wp_rand( 1, 120 ) );
		}
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			$error_copy = $error;
			$message    = $error_copy['message'];
			unset( $error_copy['message'] );

			$context = array(
				'source'         => 'fatal-errors',
				'error'          => $error_copy,
				// Indicate that this error should be logged remotely if remote logging is enabled.
				'remote-logging' => true,
			);

			if ( false !== strpos( $message, 'Stack trace:' ) ) {
				$segments  = explode( 'Stack trace:', $message );
				$message   = str_replace( PHP_EOL, ' ', trim( $segments[0] ) );
				$backtrace = array_map(
					'trim',
					explode( PHP_EOL, $segments[1] )
				);

				$context['backtrace'] = $backtrace;
			} else {
				$context['backtrace'] = true;
			}

			$logger = wc_get_logger();
			$logger->critical(
				$message,
				$context
			);

			/**
			 * Action triggered when there are errors during shutdown.
			 *
			 * @since 3.2.0
			 */
			do_action( 'woocommerce_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'WC_ABSPATH', dirname( WC_PLUGIN_FILE ) . '/' );
		$this->define( 'WC_PLUGIN_BASENAME', plugin_basename( WC_PLUGIN_FILE ) );
		$this->define( 'WC_VERSION', $this->version );
		$this->define( 'WOOCOMMERCE_VERSION', $this->version );
		$this->define( 'WC_ROUNDING_PRECISION', 6 );
		$this->define( 'WC_DISCOUNT_ROUNDING_MODE', 2 );
		$this->define( 'WC_TAX_ROUNDING_MODE', 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ) ? 2 : 1 );
		$this->define( 'WC_DELIMITER', '|' );
		$this->define( 'WC_SESSION_CACHE_GROUP', 'wc_session_id' );
		$this->define( 'WC_TEMPLATE_DEBUG_MODE', false );

		/**
		 * As of 8.8.0, it is preferable to use the `woocommerce_log_directory` filter hook to change the log
		 * directory. WC_LOG_DIR_CUSTOM is a back-compatibility measure so we can tell if `WC_LOG_DIR` has been
		 * defined outside of WC Core.
		 */
		if ( defined( 'WC_LOG_DIR' ) ) {
			$this->define( 'WC_LOG_DIR_CUSTOM', true );
		} else {
			$this->define( 'WC_LOG_DIR', LoggingUtil::get_log_directory( false ) );
		}

		// These three are kept defined for compatibility, but are no longer used.
		$this->define( 'WC_NOTICE_MIN_PHP_VERSION', '7.2' );
		$this->define( 'WC_NOTICE_MIN_WP_VERSION', '5.2' );
		$this->define( 'WC_PHP_MIN_REQUIREMENTS_NOTICE', 'wp_php_min_requirements_' . WC_NOTICE_MIN_PHP_VERSION . '_' . WC_NOTICE_MIN_WP_VERSION );

		/** Define if we're checking against major, minor or no versions in the following places:
		 *   - plugin screen in WP Admin (displaying extra warning when updating to new major versions)
		 *   - System Status Report ('Installed version not tested with active version of WooCommerce' warning)
		 *   - core update screen in WP Admin (displaying extra warning when updating to new major versions)
		 *   - enable/disable automated updates in the plugin screen in WP Admin (if there are any plugins
		 *      that don't declare compatibility, the auto-update is disabled)
		 *
		 * We dropped SemVer before WC 5.0, so all versions are backwards compatible now, thus no more check needed.
		 * The SSR in the name is preserved for bw compatibility, as this was initially used in System Status Report.
		 */
		$this->define( 'WC_SSR_PLUGIN_UPDATE_RELEASE_VERSION_TYPE', 'none' );
	}

	/**
	 * Register custom tables within $wpdb object.
	 */
	private function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'payment_tokenmeta'      => 'woocommerce_payment_tokenmeta',
			'order_itemmeta'         => 'woocommerce_order_itemmeta',
			'wc_product_meta_lookup' => 'wc_product_meta_lookup',
			'wc_tax_rate_classes'    => 'wc_tax_rate_classes',
			'wc_reserved_stock'      => 'wc_reserved_stock',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 *
	 * Legacy REST requests should still run some extra code for backwards compatibility.
	 *
	 * @todo: replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	 *
	 * @return bool
	 */
	public function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		/**
		 * Whether this is a REST API request.
		 *
		 * @since 3.6.0
		 */
		return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
	}

	/**
	 * Returns true if the request is a store REST API request.
	 *
	 * @return bool
	 */
	public function is_store_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return false !== strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) . 'wc/store/' );
	}

	/**
	 * Load REST API.
	 */
	public function load_rest_api() {
		\Automattic\WooCommerce\RestApi\Server::instance()->init();
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once WC_ABSPATH . 'includes/class-wc-autoloader.php';

		/**
		 * Interfaces.
		 */
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-abstract-order-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-coupon-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-customer-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-customer-download-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-customer-download-log-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-object-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-order-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-order-item-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-order-item-product-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-order-item-type-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-order-refund-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-payment-token-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-product-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-product-variable-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-shipping-zone-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-logger-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-log-handler-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-webhooks-data-store-interface.php';
		include_once WC_ABSPATH . 'includes/interfaces/class-wc-queue-interface.php';

		/**
		 * Core traits.
		 */
		include_once WC_ABSPATH . 'includes/traits/trait-wc-item-totals.php';

		/**
		 * Abstract classes.
		 */
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-address-provider.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-data.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-object-query.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-payment-token.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-product.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-order.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-settings-api.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-shipping-method.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-payment-gateway.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-integration.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-log-handler.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-deprecated-hooks.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-session.php';
		include_once WC_ABSPATH . 'includes/abstracts/abstract-wc-privacy.php';

		/**
		 * Core classes.
		 */
		include_once WC_ABSPATH . 'includes/wc-core-functions.php';
		include_once WC_ABSPATH . 'includes/class-wc-datetime.php';
		include_once WC_ABSPATH . 'includes/class-wc-post-types.php';
		include_once WC_ABSPATH . 'includes/class-wc-install.php';
		include_once WC_ABSPATH . 'includes/class-wc-geolocation.php';
		include_once WC_ABSPATH . 'includes/class-wc-download-handler.php';
		include_once WC_ABSPATH . 'includes/class-wc-comments.php';
		include_once WC_ABSPATH . 'includes/class-wc-post-data.php';
		include_once WC_ABSPATH . 'includes/class-wc-ajax.php';
		include_once WC_ABSPATH . 'includes/class-wc-emails.php';
		include_once WC_ABSPATH . 'includes/class-wc-data-exception.php';
		include_once WC_ABSPATH . 'includes/class-wc-query.php';
		include_once WC_ABSPATH . 'includes/class-wc-meta-data.php';
		include_once WC_ABSPATH . 'includes/class-wc-order-factory.php';
		include_once WC_ABSPATH . 'includes/class-wc-order-query.php';
		include_once WC_ABSPATH . 'includes/class-wc-product-factory.php';
		include_once WC_ABSPATH . 'includes/class-wc-product-query.php';
		include_once WC_ABSPATH . 'includes/class-wc-payment-tokens.php';
		include_once WC_ABSPATH . 'includes/class-wc-shipping-zone.php';
		include_once WC_ABSPATH . 'includes/gateways/class-wc-payment-gateway-cc.php';
		include_once WC_ABSPATH . 'includes/gateways/class-wc-payment-gateway-echeck.php';
		include_once WC_ABSPATH . 'includes/class-wc-countries.php';
		include_once WC_ABSPATH . 'includes/class-wc-integrations.php';
		include_once WC_ABSPATH . 'includes/class-wc-cache-helper.php';
		include_once WC_ABSPATH . 'includes/class-wc-https.php';
		include_once WC_ABSPATH . 'includes/class-wc-deprecated-action-hooks.php';
		include_once WC_ABSPATH . 'includes/class-wc-deprecated-filter-hooks.php';
		include_once WC_ABSPATH . 'includes/class-wc-background-emailer.php';
		include_once WC_ABSPATH . 'includes/class-wc-discounts.php';
		include_once WC_ABSPATH . 'includes/class-wc-cart-totals.php';
		include_once WC_ABSPATH . 'includes/customizer/class-wc-shop-customizer.php';
		include_once WC_ABSPATH . 'includes/class-wc-regenerate-images.php';
		include_once WC_ABSPATH . 'includes/class-wc-privacy.php';
		include_once WC_ABSPATH . 'includes/class-wc-structured-data.php';
		include_once WC_ABSPATH . 'includes/class-wc-shortcodes.php';
		include_once WC_ABSPATH . 'includes/class-wc-logger.php';
		include_once WC_ABSPATH . 'includes/queue/class-wc-action-queue.php';
		include_once WC_ABSPATH . 'includes/queue/class-wc-queue.php';
		include_once WC_ABSPATH . 'includes/admin/marketplace-suggestions/class-wc-marketplace-updater.php';
		include_once WC_ABSPATH . 'includes/admin/class-wc-admin-marketplace-promotions.php';
		include_once WC_ABSPATH . 'includes/blocks/class-wc-blocks-utils.php';

		/**
		 * Data stores - used to store and retrieve CRUD object data from the database.
		 */
		include_once WC_ABSPATH . 'includes/class-wc-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-data-store-wp.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-coupon-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-product-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-product-grouped-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-product-variable-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-product-variation-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/abstract-wc-order-item-type-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-coupon-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-fee-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-product-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-shipping-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-item-tax-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-payment-token-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-customer-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-customer-data-store-session.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-customer-download-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-customer-download-log-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-shipping-zone-data-store.php';
		include_once WC_ABSPATH . 'includes/data-stores/abstract-wc-order-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-order-refund-data-store-cpt.php';
		include_once WC_ABSPATH . 'includes/data-stores/class-wc-webhook-data-store.php';

		/**
		 * REST API.
		 */
		include_once WC_ABSPATH . 'includes/class-wc-rest-authentication.php';
		include_once WC_ABSPATH . 'includes/class-wc-rest-exception.php';
		include_once WC_ABSPATH . 'includes/class-wc-auth.php';
		include_once WC_ABSPATH . 'includes/class-wc-register-wp-admin-settings.php';

		/**
		 * Tracks.
		 */
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-event.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-client.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-tracks-footer-pixel.php';
		include_once WC_ABSPATH . 'includes/tracks/class-wc-site-tracking.php';

		/**
		 * WCCOM Site.
		 */
		include_once WC_ABSPATH . 'includes/wccom-site/class-wc-wccom-site.php';

		/**
		 * Product Usage
		 */
		include_once WC_ABSPATH . 'includes/product-usage/class-wc-product-usage.php';

		/**
		 * Libraries and packages.
		 */
		include_once WC_ABSPATH . 'packages/action-scheduler/action-scheduler.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			include_once WC_ABSPATH . 'includes/class-wc-cli.php';
		}

		if ( $this->is_request( 'admin' ) ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin.php';
			// Simulate loading plugin for the legacy reports.
			// This will be removed after moving the legacy reports to a separate plugin.
			include_once WC_ABSPATH . 'includes/admin/woocommerce-legacy-reports.php';
		}

		// We load frontend includes in the post editor, because they may be invoked via pre-loading of blocks.
		$in_post_editor = doing_action( 'load-post.php' ) || doing_action( 'load-post-new.php' );

		if ( $this->is_request( 'frontend' ) || $this->is_rest_api_request() || $in_post_editor ) {
			$this->frontend_includes();
		}

		$this->theme_support_includes();
		$this->query = new WC_Query();
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
	private function theme_support_includes() {
		if ( wc_is_wp_default_theme_active() ) {
			switch ( get_template() ) {
				case 'twentyten':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-ten.php';
					break;
				case 'twentyeleven':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-eleven.php';
					break;
				case 'twentytwelve':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-twelve.php';
					break;
				case 'twentythirteen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-thirteen.php';
					break;
				case 'twentyfourteen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-fourteen.php';
					break;
				case 'twentyfifteen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-fifteen.php';
					break;
				case 'twentysixteen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-sixteen.php';
					break;
				case 'twentyseventeen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-seventeen.php';
					break;
				case 'twentynineteen':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-nineteen.php';
					break;
				case 'twentytwenty':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-twenty.php';
					break;
				case 'twentytwentyone':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-twenty-one.php';
					break;
				case 'twentytwentytwo':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-twenty-two.php';
					break;
				case 'twentytwentythree':
					include_once WC_ABSPATH . 'includes/theme-support/class-wc-twenty-twenty-three.php';
					break;
			}
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
		include_once WC_ABSPATH . 'includes/class-wc-template-loader.php';
		include_once WC_ABSPATH . 'includes/class-wc-frontend-scripts.php';
		include_once WC_ABSPATH . 'includes/class-wc-form-handler.php';
		include_once WC_ABSPATH . 'includes/class-wc-cart.php';
		include_once WC_ABSPATH . 'includes/class-wc-tax.php';
		include_once WC_ABSPATH . 'includes/class-wc-shipping-zones.php';
		include_once WC_ABSPATH . 'includes/class-wc-customer.php';
		include_once WC_ABSPATH . 'includes/class-wc-embed.php';
		include_once WC_ABSPATH . 'includes/class-wc-session-handler.php';
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once WC_ABSPATH . 'includes/wc-template-functions.php';
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// See the comment inside FeaturesController::__construct.
		wc_get_container()->get( FeaturesController::class )->register_additional_features();

		/**
		 * Action triggered before WooCommerce initialization begins.
		 */
		do_action( 'before_woocommerce_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
		$this->product_factory                     = new WC_Product_Factory();
		$this->order_factory                       = new WC_Order_Factory();
		$this->countries                           = new WC_Countries();
		$this->integrations                        = new WC_Integrations();
		$this->structured_data                     = new WC_Structured_Data();
		$this->deprecated_hook_handlers['actions'] = new WC_Deprecated_Action_Hooks();
		$this->deprecated_hook_handlers['filters'] = new WC_Deprecated_Filter_Hooks();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			wc_load_cart();
		}

		$this->load_webhooks();

		/**
		 * Action triggered after WooCommerce initialization finishes.
		 */
		do_action( 'woocommerce_init' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/woocommerce/woocommerce-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/woocommerce-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		/**
		 * Filter to adjust the WooCommerce locale to use for translations.
		 */
		$locale                  = apply_filters( 'plugin_locale', determine_locale(), 'woocommerce' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
		$custom_translation_path = WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo';
		$plugin_translation_path = WP_LANG_DIR . '/plugins/woocommerce-' . $locale . '.mo';

		// If a custom translation exists (by default it will not, as it is not a standard WordPress convention)
		// we unload the existing translation, then essentially layer the custom translation on top of the canonical
		// translation. Otherwise, we simply step back and let WP manage things.
		if ( is_readable( $custom_translation_path ) ) {
			unload_textdomain( 'woocommerce' );
			load_textdomain( 'woocommerce', $custom_translation_path );
			load_textdomain( 'woocommerce', $plugin_translation_path );
		}
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		/**
		 * WC_TEMPLATE_PATH constant.
		 *
		 * @deprecated 2.2 Use WC()->template_path() instead.
		 */
		$this->define( 'WC_TEMPLATE_PATH', $this->template_path() );

		$this->add_thumbnail_support();
	}

	/**
	 * Ensure post thumbnail support is turned on.
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( 'product', 'thumbnail' );
	}

	/**
	 * Add WC Image sizes to WP.
	 *
	 * As of 3.3, image sizes can be registered via themes using add_theme_support for woocommerce
	 * and defining an array of args. If these are not defined, we will use defaults. This is
	 * handled in wc_get_image_size function.
	 *
	 * 3.3 sizes:
	 *
	 * woocommerce_thumbnail - Used in product listings. We assume these work for a 3 column grid layout.
	 * woocommerce_single - Used on single product pages for the main image.
	 *
	 * @since 2.3
	 */
	public function add_image_sizes() {
		$thumbnail         = wc_get_image_size( 'thumbnail' );
		$single            = wc_get_image_size( 'single' );
		$gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );

		add_image_size( 'woocommerce_thumbnail', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'woocommerce_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'woocommerce_gallery_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WC_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		/**
		 * Filter to adjust the base templates path.
		 */
		return apply_filters( 'woocommerce_template_path', 'woocommerce/' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Return the WC API URL for a given request.
	 *
	 * @param string    $request Requested endpoint.
	 * @param bool|null $ssl     If should use SSL, null if should auto detect. Default: null.
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$api_request_url = trailingslashit( home_url( '/index.php/wc-api/' . $request, $scheme ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$api_request_url = trailingslashit( home_url( '/wc-api/' . $request, $scheme ) );
		} else {
			$api_request_url = add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) );
		}

		/**
		 * Filter to adjust the url of an incoming API request.
		 */
		return esc_url_raw( apply_filters( 'woocommerce_api_request_url', $api_request_url, $request, $ssl ) );  // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
	}

	/**
	 * Load & enqueue active webhooks.
	 *
	 * @since 2.2
	 */
	private function load_webhooks() {

		if ( ! is_blog_installed() ) {
			return;
		}

		/**
		 * Hook: woocommerce_load_webhooks_limit.
		 *
		 * @since 3.6.0
		 * @param int $limit Used to limit how many webhooks are loaded. Default: no limit.
		 */
		$limit = apply_filters( 'woocommerce_load_webhooks_limit', null );

		wc_load_webhooks( 'active', $limit );
	}

	/**
	 * Initialize the customer and cart objects and setup customer saving on shutdown.
	 *
	 * Note, wc()->customer is session based. Changes to customer data via this property are not persisted to the database automatically.
	 *
	 * @since 3.6.4
	 * @return void
	 */
	public function initialize_cart() {
		if ( is_null( $this->customer ) || ! $this->customer instanceof WC_Customer ) {
			$this->customer = new WC_Customer( get_current_user_id(), true );
			// Customer session should be saved during shutdown.
			add_action( 'shutdown', array( $this->customer, 'save' ), 10 );
		}
		if ( is_null( $this->cart ) || ! $this->cart instanceof WC_Cart ) {
			$this->cart = new WC_Cart();
		}
	}

	/**
	 * Initialize the session class.
	 *
	 * @since 3.6.4
	 * @return void
	 */
	public function initialize_session() {
		/**
		 * Filter to overwrite the session class that handles session data for users.
		 */
		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingSinceComment
		if ( is_null( $this->session ) || ! $this->session instanceof $session_class ) {
			$this->session = new $session_class();
			$this->session->init();
		}
	}

	/**
	 * Tell bots not to index some WooCommerce-created directories.
	 *
	 * We try to detect the default "User-agent: *" added by WordPress and add our rules to that group, because
	 * it's possible that some bots will only interpret the first group of rules if there are multiple groups with
	 * the same user agent.
	 *
	 * @param string $output The contents that WordPress will output in a robots.txt file.
	 *
	 * @return string
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function robots_txt( $output ) {
		$path = ( ! empty( $site_url['path'] ) ) ? $site_url['path'] : '';

		$lines       = preg_split( '/\r\n|\r|\n/', $output );
		$agent_index = array_search( 'User-agent: *', $lines, true );

		if ( false !== $agent_index ) {
			$above = array_slice( $lines, 0, $agent_index + 1 );
			$below = array_slice( $lines, $agent_index + 1 );
		} else {
			$above = $lines;
			$below = array();

			$above[] = '';
			$above[] = 'User-agent: *';
		}

		$above[] = "Disallow: $path/wp-content/uploads/wc-logs/";
		$above[] = "Disallow: $path/wp-content/uploads/woocommerce_transient_files/";
		$above[] = "Disallow: $path/wp-content/uploads/woocommerce_uploads/";
		$above[] = 'Disallow: /*?add-to-cart=';
		$above[] = 'Disallow: /*?*add-to-cart=';

		$lines = array_merge( $above, $below );

		return implode( PHP_EOL, $lines );
	}

	/**
	 * Set tablenames inside WPDB object.
	 */
	public function wpdb_table_fix() {
		$this->define_tables();
	}

	/**
	 * Ran when any plugin is activated.
	 *
	 * @since 3.6.0
	 * @param string $filename The filename of the activated plugin.
	 */
	public function activated_plugin( $filename ) {
		include_once __DIR__ . '/admin/helper/class-wc-helper.php';

		if ( '/woocommerce.php' === substr( $filename, -16 ) ) {
			set_transient( 'woocommerce_activated_plugin', $filename );
		}

		WC_Helper::activated_plugin( $filename );
	}

	/**
	 * Ran when any plugin is deactivated.
	 *
	 * @since 3.6.0
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public function deactivated_plugin( $filename ) {
		include_once __DIR__ . '/admin/helper/class-wc-helper.php';

		WC_Helper::deactivated_plugin( $filename );
	}

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public function queue() {
		return WC_Queue::instance();
	}

	/**
	 * Get Checkout Class.
	 *
	 * @return WC_Checkout
	 */
	public function checkout() {
		return WC_Checkout::instance();
	}

	/**
	 * Get gateways class.
	 *
	 * @return WC_Payment_Gateways
	 */
	public function payment_gateways() {
		return WC_Payment_Gateways::instance();
	}

	/**
	 * Get shipping class.
	 *
	 * @return WC_Shipping
	 */
	public function shipping() {
		return WC_Shipping::instance();
	}

	/**
	 * Email Class.
	 *
	 * @return WC_Emails
	 */
	public function mailer() {
		return WC_Emails::instance();
	}

	/**
	 * Check if plugin assets are built and minified
	 *
	 * @return bool
	 */
	public function build_dependencies_satisfied() {
		// Check if we have compiled CSS.
		if ( ! file_exists( WC()->plugin_path() . '/assets/css/admin.css' ) ) {
			return false;
		}

		// Check if we have minified JS.
		if ( ! file_exists( WC()->plugin_path() . '/assets/js/admin/woocommerce_admin.min.js' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Output a admin notice when build dependencies not met.
	 *
	 * @return void
	 */
	public function build_dependencies_notice() {
		if ( $this->build_dependencies_satisfied() ) {
			return;
		}

		$message_one = __( 'You have installed a development version of WooCommerce which requires files to be built and minified. From the plugin directory, run <code>pnpm install</code> and then <code>pnpm --filter=\'@woocommerce/plugin-woocommerce\' build</code> to build and minify assets.', 'woocommerce' );
		$message_two = sprintf(
			/* translators: 1: URL of WordPress.org Repository 2: URL of the GitHub Repository release page */
			__( 'Or you can download a pre-built version of the plugin from the <a href="%1$s">WordPress.org repository</a> or by visiting <a href="%2$s">the releases page in the GitHub repository</a>.', 'woocommerce' ),
			'https://wordpress.org/plugins/woocommerce/',
			'https://github.com/woocommerce/woocommerce/releases'
		);
		printf( '<div class="error"><p>%s %s</p></div>', $message_one, $message_two ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Is the WooCommerce Admin actively included in the WooCommerce core?
	 * Based on presence of a basic WC Admin function.
	 *
	 * @return boolean
	 */
	public function is_wc_admin_active() {
		return function_exists( 'wc_admin_url' );
	}

	/**
	 * Call a user function. This should be used to execute any non-idempotent function, especially
	 * those in the `includes` directory or provided by WordPress.
	 *
	 * This method can be useful for unit tests, since functions called using this method
	 * can be easily mocked by using WC_Unit_Test_Case::register_legacy_proxy_function_mocks.
	 *
	 * @param string $function_name The function to execute.
	 * @param mixed  ...$parameters The parameters to pass to the function.
	 *
	 * @return mixed The result from the function.
	 *
	 * @since 4.4
	 */
	public function call_function( $function_name, ...$parameters ) {
		return wc_get_container()->get( LegacyProxy::class )->call_function( $function_name, ...$parameters );
	}

	/**
	 * Call a static method in a class. This should be used to execute any non-idempotent method in classes
	 * from the `includes` directory.
	 *
	 * This method can be useful for unit tests, since methods called using this method
	 * can be easily mocked by using WC_Unit_Test_Case::register_legacy_proxy_static_mocks.
	 *
	 * @param string $class_name The name of the class containing the method.
	 * @param string $method_name The name of the method.
	 * @param mixed  ...$parameters The parameters to pass to the method.
	 *
	 * @return mixed The result from the method.
	 *
	 * @since 4.4
	 */
	public function call_static( $class_name, $method_name, ...$parameters ) {
		return wc_get_container()->get( LegacyProxy::class )->call_static( $class_name, $method_name, ...$parameters );
	}

	/**
	 * Gets an instance of a given legacy class.
	 * This must not be used to get instances of classes in the `src` directory.
	 *
	 * This method can be useful for unit tests, since objects obtained using this method
	 * can be easily mocked by using WC_Unit_Test_Case::register_legacy_proxy_class_mocks.
	 *
	 * @param string $class_name The name of the class to get an instance for.
	 * @param mixed  ...$args Parameters to be passed to the class constructor or to the appropriate internal 'get_instance_of_' method.
	 *
	 * @return object The instance of the class.
	 * @throws \Exception The requested class belongs to the `src` directory, or there was an error creating an instance of the class.
	 *
	 * @since 4.4
	 */
	public function get_instance_of( string $class_name, ...$args ) {
		return wc_get_container()->get( LegacyProxy::class )->get_instance_of( $class_name, ...$args );
	}

	/**
	 * Gets the value of a global.
	 *
	 * @param string $global_name The name of the global to get the value for.
	 * @return mixed The value of the global.
	 */
	public function get_global( string $global_name ) {
		return wc_get_container()->get( LegacyProxy::class )->get_global( $global_name );
	}

	/**
	 * Register WC settings from WP-API to the REST API.
	 *
	 * This method used to be part of the now removed Legacy REST API.
	 *
	 * @since 9.0.0
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function register_wp_admin_settings() {
		$pages = WC_Admin_Settings::get_settings_pages();
		foreach ( $pages as $page ) {
			new WC_Register_WP_Admin_Settings( $page, 'page' );
		}

		$emails = WC_Emails::instance();
		foreach ( $emails->get_emails() as $email ) {
			new WC_Register_WP_Admin_Settings( $email, 'email' );
		}
	}

	/**
	 * Converts the WooCommerce slug to the correct slug for the current version.
	 * This ensures that when the plugin is installed in a different folder name, the correct slug is used so that dependent plugins can be installed/activated.
	 *
	 * @since 9.0.0
	 * @param string $slug The plugin slug to convert.
	 *
	 * @return string
	 */
	public function convert_woocommerce_slug( $slug ) {
		if ( 'woocommerce' === $slug ) {
			$slug = dirname( WC_PLUGIN_BASENAME );
		}
		return $slug;
	}

	/**
	 * Register the remote log handler.
	 *
	 * @param \WC_Log_Handler[] $handlers The handlers to register.
	 *
	 * @return \WC_Log_Handler[]
	 *
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function register_remote_log_handler( $handlers ) {
		$handlers[] = wc_get_container()->get( RemoteLogger::class );
		return $handlers;
	}

	/**
	 * Tracks the history WooCommerce Allow Tracking option.
	 * - When the field was first set to allow tracking
	 * - Last time the option was changed
	 *
	 * @param string $old_value The old value for the woocommerce_allow_tracking option.
	 * @param string $value The current value for the woocommerce_allow_tracking option.
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function get_tracking_history( $old_value, $value ) {
		// If woocommerce_allow_tracking_first_optin is not set. It means is the first time it gets set.
		if ( ! get_option( 'woocommerce_allow_tracking_first_optin' ) && 'yes' === $value ) {
			update_option( 'woocommerce_allow_tracking_first_optin', time() );
		}

		// Always update the last change.
		update_option( 'woocommerce_allow_tracking_last_modified', time() );
	}

	/**
	 * For actions that may fail at execution time due to missing callbacks, register the recurring action in a wrapper
	 * to prevent errors, and load the classes where the callback is added.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_recurring_action_wrappers() {
		add_action( 'woocommerce_tracker_send_event_wrapper', array( $this, 'add_woocommerce_tracker_send_event_wrapper' ) );
		add_action( 'wc_admin_daily_wrapper', array( $this, 'add_wc_admin_daily_wrapper' ) );
		add_action( 'generate_category_lookup_table_wrapper', array( $this, 'add_generate_category_lookup_table_wrapper' ) );
		add_action( 'woocommerce_cleanup_rate_limits_wrapper', array( $this, 'add_woocommerce_cleanup_rate_limits_wrapper' ) );
	}

	/**
	 * Unschedule unwrapped actions that may have been added to the site.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function unschedule_unwrapped_actions() {
		// Unschedule the unwrapped actions.
		as_unschedule_all_actions( 'woocommerce_tracker_send_event' );
		as_unschedule_all_actions( 'wc_admin_daily' );
		as_unschedule_all_actions( 'generate_category_lookup_table' );
		as_unschedule_all_actions( 'woocommerce_cleanup_rate_limits' );
	}

	/**
	 * Wrapper for the `woocommerce_tracker_send_event` action. This prevents the event failing when the class is not loaded.
	 * It loads the class if it exists, and then calls the actual action.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_woocommerce_tracker_send_event_wrapper() {
		if ( true !== wc_string_to_bool( get_option( 'woocommerce_allow_tracking', 'no' ) ) ) {
			return;
		}
		try {
			include_once WC_ABSPATH . 'includes/class-wc-tracker.php';
			if ( class_exists( WC_Tracker::class ) ) {
				WC_Tracker::init();
			}
		} catch ( Throwable $e ) {
			wc_get_logger()->error( 'Error initializing WC_Tracker: ' . $e->getMessage(), array( 'source' => 'woocommerce-scheduled-actions' ) );
		}
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_tracker_send_event' );
	}

	/**
	 * Wrapper for the `wc_admin_daily` action. This prevents the event failing when the class is not loaded.
	 * It loads the class if it exists, and then calls the actual action.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_wc_admin_daily_wrapper() {
		try {
			if ( class_exists( \Automattic\WooCommerce\Internal\Admin\Events::class ) ) {
				\Automattic\WooCommerce\Internal\Admin\Events::instance();
			}
		} catch ( Throwable $e ) {
			wc_get_logger()->error( 'Error initializing wc_admin_daily: ' . $e->getMessage(), array( 'source' => 'woocommerce-scheduled-actions' ) );
		}
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'wc_admin_daily' );
	}

	/**
	 * Wrapper for the `generate_category_lookup_table` action. This prevents the event failing when the class is not loaded.
	 * It loads the class if it exists, and then calls the actual action.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_generate_category_lookup_table_wrapper() {
		try {
			if ( class_exists( \Automattic\WooCommerce\Internal\Admin\CategoryLookup::class ) ) {
				\Automattic\WooCommerce\Internal\Admin\CategoryLookup::instance();
			}
		} catch ( Throwable $e ) {
			wc_get_logger()->error( 'Error in category lookup wrapper: ' . $e->getMessage(), array( 'source' => 'woocommerce-scheduled-actions' ) );
		}
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'generate_category_lookup_table' );
	}

	/**
	 * Wrapper for the `woocommerce_cleanup_rate_limits` action. This prevents the event failing when the class is not loaded.
	 * It loads the class if it exists, and then calls the actual action.
	 *
	 * @return void
	 * @internal For exclusive usage of WooCommerce core, backwards compatibility not guaranteed.
	 */
	public function add_woocommerce_cleanup_rate_limits_wrapper() {
		try {
			include_once WC_ABSPATH . 'includes/class-wc-rate-limiter.php';
			if ( class_exists( WC_Rate_Limiter::class ) ) {
				WC_Rate_Limiter::init();
			}
		} catch ( Throwable $e ) {
			wc_get_logger()->error( 'Error in rate limiter cleanup wrapper: ' . $e->getMessage(), array( 'source' => 'woocommerce-scheduled-actions' ) );
		}
		// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'woocommerce_cleanup_rate_limits' );
	}

	/**
	 * Register recurring actions.
	 */
	public function register_recurring_actions() {
		// Remove any unwrapped actions that may have been scheduled before scheduling the new wrapped ones.
		$this->unschedule_unwrapped_actions();

		// Check if Action Scheduler is available.
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_schedule_single_action' ) ) {
			return;
		}

		$gmt_offset   = get_option( 'gmt_offset' );
		$offset_hours = ( $gmt_offset > 0 ? '-' : '+' ) . absint( $gmt_offset ) . ' hours';

		// Schedule daily sales event at midnight tomorrow.
		$scheduled_sales_time = strtotime( '00:00 tomorrow ' . $offset_hours );

		as_schedule_recurring_action( $scheduled_sales_time, DAY_IN_SECONDS, 'woocommerce_scheduled_sales', array(), 'woocommerce', true );

		$held_duration = get_option( 'woocommerce_hold_stock_minutes', '60' );

		if ( '' !== $held_duration ) {
			/**
			 * Determines the interval at which to cancel unpaid orders in minutes.
			 *
			 * @since 5.1.0
			 */
			$cancel_unpaid_interval = apply_filters( 'woocommerce_cancel_unpaid_orders_interval_minutes', absint( $held_duration ) );

			as_schedule_single_action( time() + ( absint( $cancel_unpaid_interval ) * 60 ), 'woocommerce_cancel_unpaid_orders', array(), 'woocommerce', true );

		}

		$tomorrow_3am = strtotime( 'tomorrow 03:00 am ' . $offset_hours );
		$tomorrow_6am = strtotime( 'tomorrow 06:00 am ' . $offset_hours );

		// Delay the first run of `woocommerce_cleanup_personal_data` by 10 seconds
		// so it doesn't occur in the same request. WooCommerce Admin also schedules
		// a daily cron that gets lost due to a race condition. WC_Privacy's background
		// processing instance updates the cron schedule from within a cron job.
		as_schedule_recurring_action( time() + 10, DAY_IN_SECONDS, 'woocommerce_cleanup_personal_data', array(), 'woocommerce', true );

		as_schedule_recurring_action( $tomorrow_3am, DAY_IN_SECONDS, 'woocommerce_cleanup_logs', array(), 'woocommerce', true );

		$next_run_timestamp = as_next_scheduled_action( 'woocommerce_cleanup_sessions', array(), 'woocommerce' );
		if ( $next_run_timestamp !== $tomorrow_6am ) {
			as_unschedule_all_actions( 'woocommerce_cleanup_sessions' );
			as_schedule_recurring_action( $tomorrow_6am, 12 * HOUR_IN_SECONDS, 'woocommerce_cleanup_sessions', array(), 'woocommerce', true );
		}

		as_schedule_recurring_action( $tomorrow_6am, 15 * DAY_IN_SECONDS, 'woocommerce_geoip_updater', array(), 'woocommerce', true );

		// Schedule the action to send tracking events if tracking is enabled.
		$this->schedule_tracking_action();

		as_schedule_recurring_action( $tomorrow_3am, DAY_IN_SECONDS, 'woocommerce_cleanup_rate_limits_wrapper', array(), 'woocommerce', true );

		as_schedule_recurring_action( time(), DAY_IN_SECONDS, 'wc_admin_daily_wrapper', array(), 'woocommerce', true );

		// Note: this is potentially redundant when the core package exists.
		as_schedule_single_action( time() + 10, 'generate_category_lookup_table_wrapper', array(), 'woocommerce', true );
	}

	/**
	 * Schedule the action send tracking events if tracking is enabled, or unregister it if tracking is disabled.
	 * This will be called when the `woocommerce_allow_tracking` option is updated.
	 *
	 * @param string $old_value The old value of the `woocommerce_allow_tracking` option.
	 * @param string $value     The new value of the `woocommerce_allow_tracking` option.
	 *
	 * @return void
	 */
	public function handle_tracking_setting_change( $old_value, $value ) {
		if ( $old_value === $value ) {
			return;
		}
		if ( false === wc_string_to_bool( $value ) ) {
			as_unschedule_all_actions( 'woocommerce_tracker_send_event_wrapper', array(), 'woocommerce' );
		} else {
			$this->schedule_tracking_action();
		}
	}

	/**
	 * Schedule the action to send tracking events if tracking is enabled.
	 *
	 * @return void
	 */
	public function schedule_tracking_action() {
		if ( false === wc_string_to_bool( get_option( 'woocommerce_allow_tracking', 'no' ) ) ) {
			return;
		}

		/**
		 * How frequent to schedule the tracker send event.
		 *
		 * @since 2.3.0
		 */
		$tracker_recurrence = apply_filters( 'woocommerce_tracker_event_recurrence', 'daily' );
		$core_internals     = wp_get_schedules();
		as_schedule_recurring_action( time() + 10, $core_internals[ $tracker_recurrence ]['interval'], 'woocommerce_tracker_send_event_wrapper', array(), 'woocommerce', true );
	}

	/**
	 * Initialize the customizer on the plugins_loaded action.
	 * If WooCommerce is network activated, wp_is_block_theme() will be called too early,
	 * which cause the warning in #58364. By initializing the customizer on plugins_loaded,
	 * we ensure that wp_is_block_theme() is called after theme directories registration.
	 *
	 * @internal
	 * @see https://github.com/woocommerce/woocommerce/issues/58364
	 */
	public function init_customizer() {
		global $pagenow;
		if (
			'customize.php' === $pagenow ||
			isset( $_REQUEST['customize_theme'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! wp_is_block_theme()
		) {
			new WC_Shop_Customizer();
		}
	}
}
