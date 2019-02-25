<?php
/**
 * WooCommerce setup
 *
 * @package WooCommerce
 * @since   3.2.0
 */

defined( 'ABSPATH' ) || exit;

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
	public $version = '3.6.0';

	/**
	 * The single instance of the class.
	 *
	 * @var WooCommerce
	 * @since 2.1
	 */
	protected static $instance = null;

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
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
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
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'payment_gateways', 'shipping', 'mailer', 'checkout' ), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * WooCommerce Constructor.
	 */
	public function __construct() {
		$this->define_constants();
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
		do_action( 'woocommerce_loaded' );
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
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'WC_Shortcodes', 'init' ) );
		add_action( 'init', array( 'WC_Emails', 'init_transactional_emails' ) );
		add_action( 'init', array( 'WC_Regenerate_Images', 'init' ) );
		add_action( 'init', array( 'WC_Template_Loader', 'init' ) );
		add_action( 'init', array( $this, 'wpdb_table_fix' ), 0 );
		add_action( 'init', array( $this, 'add_image_sizes' ) );
		add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
	}

	/**
	 * Ensures fatal errors are logged so they can be picked up in the status report.
	 *
	 * @since 3.2.0
	 */
	public function log_errors() {
		$error = error_get_last();
		if ( in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			$logger = wc_get_logger();
			$logger->critical(
				/* translators: 1: error message 2: file name and path 3: line number */
				sprintf( __( '%1$s in %2$s on line %3$s', 'woocommerce' ), $error['message'], $error['file'], $error['line'] ) . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
			do_action( 'woocommerce_shutdown_error', $error );
		}
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'WC_ABSPATH', dirname( WC_PLUGIN_FILE ) . '/' );
		$this->define( 'WC_PLUGIN_BASENAME', plugin_basename( WC_PLUGIN_FILE ) );
		$this->define( 'WC_VERSION', $this->version );
		$this->define( 'WOOCOMMERCE_VERSION', $this->version );
		$this->define( 'WC_ROUNDING_PRECISION', 6 );
		$this->define( 'WC_DISCOUNT_ROUNDING_MODE', 2 );
		$this->define( 'WC_TAX_ROUNDING_MODE', 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ) ? 2 : 1 );
		$this->define( 'WC_DELIMITER', '|' );
		$this->define( 'WC_LOG_DIR', $upload_dir['basedir'] . '/wc-logs/' );
		$this->define( 'WC_SESSION_CACHE_GROUP', 'wc_session_id' );
		$this->define( 'WC_TEMPLATE_DEBUG_MODE', false );

		if ( ! empty( $_GET['wc-ajax'] ) ) {
			add_filter( 'wp_doing_ajax', '__return_true' );
			$this->define( 'WC_DOING_AJAX', true );
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

		// REST API prefix.
		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		// Check if this is a WC endpoint.
		$is_woocommerce_endpoint = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'wc/' ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return apply_filters( 'woocommerce_is_rest_api_request', $is_woocommerce_endpoint );
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
				return wp_doing_ajax();
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'cli':
				return defined( 'WP_CLI' ) && WP_CLI;
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! $this->is_rest_api_request();
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @throws Exception When composer install hasn't been ran.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		$loader = include_once WC_ABSPATH . 'vendor/autoload.php';

		if ( ! $loader ) {
			throw new Exception( 'vendor/autoload.php missing please run `composer install`' );
		}

		/**
		 * Libraries
		 */
		include_once WC_ABSPATH . 'includes/libraries/action-scheduler/action-scheduler.php';

		if ( $this->is_request( 'cli' ) ) {
			WC_CLI::init();
		}

		if ( $this->is_request( 'admin' ) ) {
			WC_Admin::instance()->init();
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		if ( $this->is_request( 'cron' ) ) {
			WC_Tracker::init();
		}

		if ( $this->is_request( 'ajax' ) ) {
			WC_AJAX::init();
		}

		$this->theme_support_includes();

		// Classes loaded before 'init'.
		$this->query = new WC_Query();
		$this->api   = new WC_API();
		$this->auth  = new WC_Auth();

		// Init static classes.
		WC_Cache_Helper::init();
		WC_Comments::init();
		WC_Download_Handler::init();
		WC_Geolocation::init();
		WC_HTTPS::init();
		WC_Install::init();
		WC_Post_Types::init();
		WC_Post_Data::init();
		WC_Shop_Customizer::init();
	}

	/**
	 * Include classes for theme support.
	 *
	 * @since 3.3.0
	 */
	private function theme_support_includes() {
		$support_classes = array(
			'twentyten'       => 'WC_Twenty_Ten',
			'twentyeleven'    => 'WC_Twenty_Eleven',
			'twentytwelve'    => 'WC_Twenty_Twelve',
			'twentythirteen'  => 'WC_Twenty_Thirteen',
			'twentyfourteen'  => 'WC_Twenty_Fourteen',
			'twentyfifteen'   => 'WC_Twenty_Fifteen',
			'twentysixteen'   => 'WC_Twenty_Sixteen',
			'twentyseventeen' => 'WC_Twenty_Seventeen',
			'twentynineteen'  => 'WC_Twenty_Nineteen',
		);
		if ( array_key_exists( get_template(), $support_classes ) ) {
			$support_classes[ get_template() ]::init();
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		WC_Embed::init();
		WC_Frontend_Scripts::init();
		WC_Form_Handler::init();
		WC_Tax::init();

		/**
		 * Template hooks hook in template functions.
		 */
		include_once WC_ABSPATH . 'includes/wc-template-hooks.php';
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
		// Before init action.
		do_action( 'before_woocommerce_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
		$this->product_factory                     = new WC_Product_Factory();
		$this->order_factory                       = new WC_Order_Factory();
		$this->countries                           = new WC_Countries();
		$this->integrations                        = new WC_Integrations();
		$this->structured_data                     = new WC_Structured_Data();
		$this->privacy                             = new WC_Privacy();
		$this->deprecated_hook_handlers['actions'] = new WC_Deprecated_Action_Hooks();
		$this->deprecated_hook_handlers['filters'] = new WC_Deprecated_Filter_Hooks();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			// Session class, handles session data for users - can be overwritten if custom handler is needed.
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
			$this->session = new $session_class();
			$this->session->init();

			$this->customer = new WC_Customer( get_current_user_id(), true );
			$this->cart     = new WC_Cart();

			// Customer should be saved during shutdown.
			add_action( 'shutdown', array( $this->customer, 'save' ), 10 );
		}

		$this->load_webhooks();

		// Init action.
		do_action( 'woocommerce_init' );
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
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce' );

		unload_textdomain( 'woocommerce' );
		load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( WC_PLUGIN_FILE ) ) . '/i18n/languages' );
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

		/**
		 * Legacy image sizes.
		 *
		 * @deprecated These sizes will be removed in 4.0.
		 */
		add_image_size( 'shop_catalog', $thumbnail['width'], $thumbnail['height'], $thumbnail['crop'] );
		add_image_size( 'shop_single', $single['width'], $single['height'], $single['crop'] );
		add_image_size( 'shop_thumbnail', $gallery_thumbnail['width'], $gallery_thumbnail['height'], $gallery_thumbnail['crop'] );
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
		return apply_filters( 'woocommerce_template_path', 'woocommerce/' );
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

		return esc_url_raw( apply_filters( 'woocommerce_api_request_url', $api_request_url, $request, $ssl ) );
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
	 * WooCommerce Payment Token Meta API and Term/Order item Meta - set table names.
	 */
	public function wpdb_table_fix() {
		global $wpdb;
		$wpdb->payment_tokenmeta = $wpdb->prefix . 'woocommerce_payment_tokenmeta';
		$wpdb->order_itemmeta    = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$wpdb->tables[]          = 'woocommerce_payment_tokenmeta';
		$wpdb->tables[]          = 'woocommerce_order_itemmeta';

		if ( get_option( 'db_version' ) < 34370 ) {
			$wpdb->woocommerce_termmeta = $wpdb->prefix . 'woocommerce_termmeta';
			$wpdb->tables[]             = 'woocommerce_termmeta';
		}
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
}
