<?php
/**
 * WooCommerce Legacy API. Was deprecated with 2.6.0.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce\RestApi
 * @since    2.6
 */

use Automattic\WooCommerce\Internal\Traits\AccessiblePrivateMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy API.
 */
class WC_Legacy_API {

	use AccessiblePrivateMethods;

	/**
	 * This is the major version for the REST API and takes
	 * first-order position in endpoint URLs.
	 *
	 * @deprecated 2.6.0
	 * @var string
	 */
	const VERSION = '3.1.0';

	/**
	 * The REST API server.
	 *
	 * @deprecated 2.6.0
	 * @var WC_API_Server
	 */
	public $server;

	/**
	 * REST API authentication class instance.
	 *
	 * @deprecated 2.6.0
	 * @var WC_API_Authentication
	 */
	public $authentication;

	/**
	 * Init the legacy API.
	 */
	public function init() {
		add_action( 'parse_request', array( $this, 'handle_rest_api_requests' ), 0 );
		self:$this->mark_method_as_accessible( 'display_legacy_wc_api_usage_notice' );
		self::add_action('admin_notices', array( $this, 'display_legacy_wc_api_usage_notice' ), 0);
	}

	/**
	 * Add new query vars.
	 *
	 * @since 2.0
	 * @param array $vars Vars.
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc-api-version'; // Deprecated since 2.6.0.
		$vars[] = 'wc-api-route'; // Deprecated since 2.6.0.
		return $vars;
	}

	/**
	 * Write a log entry, and/or update the last usage options, for a Legacy REST API request
	 * if the appropriate settings dictate so.
	 *
	 * @param string $route The Legacy REST API route requested.
	 * @param string|null $user_agent The content of the user agent HTTP header in the request, null if not available.
	 */
	private function maybe_log_rest_api_request(string $route, ?string $user_agent) {
		$user_agent = $user_agent ?? 'unknown';

		$write_log_entry = 'yes' === get_option('woocommerce_legacy_api_log_enabled') && function_exists('wc_get_logger');
		if(!$write_log_entry && 'yes' !== get_option('woocommerce_legacy_api_usage_notice_enabled')) {
			return;
		}

		$stored_api_accesses = get_option('wc_legacy_rest_usages', []);
		$stored_api_accesses_for_user_agent_and_route = $stored_api_accesses[$user_agent][$route] ?? null;
		$current_date = wp_date( 'Y-m-d H:i:s' );
		if(is_null($stored_api_accesses_for_user_agent_and_route)) {
			$stored_api_accesses[$user_agent][$route] = [
				'version'    => WC_API_REQUEST_VERSION,
				'first_date' => $current_date,
				'last_date'   => $current_date,
				'count' => 1,
				'logged' => $write_log_entry
			];
		}
		else {
			$stored_api_accesses[$user_agent][$route]['version'] = WC_API_REQUEST_VERSION;
			$stored_api_accesses[$user_agent][$route]['count']++;
			$previous_last_date = $stored_api_accesses[$user_agent][$route]['last_date'];
			$stored_api_accesses[$user_agent][$route]['last_date'] = $current_date;
			$was_logged = $stored_api_accesses[$user_agent][$route]['logged'];
			$write_log_entry =
				$write_log_entry &&
				(!$stored_api_accesses[$user_agent][$route]['logged'] || (substr($current_date, 0, 10) !== substr($previous_last_date, 0, 10)));

			// If not logged in the past and we log it now, update 'logged' to true.
			// But if it was logged in the past and we don't log it now, keep 'logged' as true.
			if(!$was_logged && $write_log_entry) {
				$stored_api_accesses[$user_agent][$route]['logged'] = true;
			}
		}

		if($write_log_entry) {
			wc_get_logger()->info( 'LEGACY REST API USAGE DETECTED (version ' . WC_API_REQUEST_VERSION . "): $route (User agent: $user_agent)", ['source' => 'legacy_rest_api_usages'] );
		}

		update_option('wc_legacy_rest_usages', $stored_api_accesses);
		update_option('wc_legacy_rest_last_usage', array_merge($stored_api_accesses[$user_agent][$route], ['user_agent' => $user_agent, 'route' => $route]));
	}

	/**
	 * Add new endpoints.
	 *
	 * @since 2.0
	 */
	public static function add_endpoint() {
		// REST API, deprecated since 2.6.0.
		add_rewrite_rule( '^wc-api/v([1-3]{1})/?$', 'index.php?wc-api-version=$matches[1]&wc-api-route=/', 'top' );
		add_rewrite_rule( '^wc-api/v([1-3]{1})(.*)?', 'index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]', 'top' );
	}

	/**
	 * Handle REST API requests.
	 *
	 * @since 2.2
	 * @deprecated 2.6.0
	 */
	public function handle_rest_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-api-version'] ) ) {
			$wp->query_vars['wc-api-version'] = $_GET['wc-api-version'];
		}

		if ( ! empty( $_GET['wc-api-route'] ) ) {
			$wp->query_vars['wc-api-route'] = $_GET['wc-api-route'];
		}

		if ( empty( $wp->query_vars['wc-api-version'] ) || empty( $wp->query_vars['wc-api-route'] ) ) {
			return;
		}

		// REST API request.

		wc_maybe_define_constant( 'WC_API_REQUEST', true );
		wc_maybe_define_constant( 'WC_API_REQUEST_VERSION', absint( $wp->query_vars['wc-api-version'] ) );

		$route = $wp->query_vars['wc-api-route'];
		$this->maybe_log_rest_api_request($route, $_SERVER['HTTP_USER_AGENT'] ?? null);

		// Legacy v1 API request.
		if ( 1 === WC_API_REQUEST_VERSION ) {
			$this->handle_v1_rest_api_request();
		} elseif ( 2 === WC_API_REQUEST_VERSION ) {
			$this->handle_v2_rest_api_request();
		} else {
			$this->includes();

			$this->server = new WC_API_Server( $route );

			// load API resource classes.
			$this->register_resources( $this->server );

			// Fire off the request.
			$this->server->serve_request();
		}
	}

	/**
	 * Display an admin notice with information about the last Legacy REST API usage,
	 * if available and if the appropriate setting dictates so.
	 */
	private function display_legacy_wc_api_usage_notice(): void {
		if('yes' !== get_option('woocommerce_legacy_api_usage_notice_enabled')) {
			return;
		}

		$legacy_usage = get_option( 'wc_legacy_rest_last_usage' );

		if ( ! is_array( $legacy_usage ) ) {
			return;
		}

		$usage = array_map( 'esc_html', $legacy_usage );

		printf(
			/* translators: 1: API version number, 2: request route, 3: user agent string, 4: ISO-formatted date and time, 5: request route, 6: user agent string, 7: usages count */
			__(
				"<div class='notice'>
                    <p><strong>ⓘ LEGACY REST API USAGE DETECTED</strong></p>
                    <p>Last usage recorded:</p>
                    <p>
                        API version: <kbd>%1\$s</kbd> |
                        Route: <kbd>%2\$s</kbd> |
                        Agent: <kbd>%3\$s</kbd> |
                        Date and time: <kbd>%4\$s</kbd>
                    </p>
                    <p>Total usages of <kbd>%5\$s</kbd> by <kbd>%6\$s</kbd> recorded: <strong>%7\$s</strong>
                </p></div>",
				'woocommerce'
			),
			$usage['version'],
			$usage['route'],
			$usage['user_agent'],
			$usage['last_date'],
			$usage['route'],
			$usage['user_agent'],
			$usage['count']
		);
	}

	/**
	 * Include required files for REST API request.
	 *
	 * @since 2.1
	 * @deprecated 2.6.0
	 */
	public function includes() {

		// API server / response handlers.
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-exception.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-server.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/interface-wc-api-handler.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-json-handler.php' );

		// Authentication.
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-resource.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-coupons.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-customers.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-orders.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-products.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-reports.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-taxes.php' );
		include_once( dirname( __FILE__ ) . '/api/v3/class-wc-api-webhooks.php' );

		// Allow plugins to load other response handlers or resource classes.
		do_action( 'woocommerce_api_loaded' );
	}

	/**
	 * Register available API resources.
	 *
	 * @since 2.1
	 * @deprecated 2.6.0
	 * @param WC_API_Server $server the REST server.
	 */
	public function register_resources( $server ) {

		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Coupons',
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Reports',
				'WC_API_Taxes',
				'WC_API_Webhooks',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $server );
		}
	}


	/**
	 * Handle legacy v1 REST API requests.
	 *
	 * @since 2.2
	 * @deprecated 2.6.0
	 */
	private function handle_v1_rest_api_request() {

		// Include legacy required files for v1 REST API request.
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-server.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/interface-wc-api-handler.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-json-handler.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-xml-handler.php' );

		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-resource.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-coupons.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-customers.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-orders.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-products.php' );
		include_once( dirname( __FILE__ ) . '/api/v1/class-wc-api-reports.php' );

		// Allow plugins to load other response handlers or resource classes.
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v1 REST API request.
		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Coupons',
				'WC_API_Reports',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $this->server );
		}

		// Fire off the request.
		$this->server->serve_request();
	}

	/**
	 * Handle legacy v2 REST API requests.
	 *
	 * @since 2.4
	 * @deprecated 2.6.0
	 */
	private function handle_v2_rest_api_request() {
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-exception.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-server.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/interface-wc-api-handler.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-json-handler.php' );

		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-resource.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-coupons.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-customers.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-orders.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-products.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-reports.php' );
		include_once( dirname( __FILE__ ) . '/api/v2/class-wc-api-webhooks.php' );

		// allow plugins to load other response handlers or resource classes.
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v2 REST API request.
		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Coupons',
				'WC_API_Reports',
				'WC_API_Webhooks',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $this->server );
		}

		// Fire off the request.
		$this->server->serve_request();
	}

	/**
	 * Rest API Init.
	 *
	 * @deprecated 3.7.0 - REST API classes autoload.
	 */
	public function rest_api_init() {}

	/**
	 * Include REST API classes.
	 *
	 * @deprecated 3.7.0 - REST API classes autoload.
	 */
	public function rest_api_includes() {
		$this->rest_api_init();
	}
	/**
	 * Register REST API routes.
	 *
	 * @deprecated 3.7.0
	 */
	public function register_rest_routes() {
		wc_deprecated_function( 'WC_Legacy_API::register_rest_routes', '3.7.0', '' );
		$this->register_wp_admin_settings();
	}
}
