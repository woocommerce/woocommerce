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
		$this->mark_method_as_accessible( 'maybe_display_legacy_wc_api_usage_notice' );
		self::add_action( 'admin_notices', array( $this, 'maybe_display_legacy_wc_api_usage_notice' ), 0 );
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
	 * Write a log entry and update the last usage options, for a Legacy REST API request.
	 *
	 * @param string      $route The Legacy REST API route requested.
	 * @param string|null $user_agent The content of the user agent HTTP header in the request, null if not available.
	 */
	private function maybe_log_rest_api_request( string $route, ?string $user_agent ) {
		if ( is_plugin_active( 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php' ) ) {
			return;
		}

		$user_agent = $user_agent ?? 'unknown';

		$current_date        = wp_date( 'Y-m-d H:i:s' );
		$stored_api_accesses = get_transient( 'wc_legacy_rest_api_usages' );
		if ( false === $stored_api_accesses ) {
			$stored_api_accesses = array(
				'user_agents' => array(),
				'first_usage' => $current_date,
				'total_count' => 0,
			);
		}

		$stored_api_accesses_for_user_agent = $stored_api_accesses['user_agents'][ $user_agent ] ?? null;
		if ( is_null( $stored_api_accesses_for_user_agent ) ) {
			$stored_api_accesses['user_agents'][ $user_agent ] = array(
				'first_date' => $current_date,
				'last_date'  => $current_date,
				'count'      => 1,
			);
		} else {
			$stored_api_accesses['user_agents'][ $user_agent ]['count']++;
			$stored_api_accesses['user_agents'][ $user_agent ]['last_date'] = $current_date;
		}
		$stored_api_accesses['total_count']++;

		set_transient( 'wc_legacy_rest_api_usages', $stored_api_accesses, time() + 2 * WEEK_IN_SECONDS );

		/**
		 * This filter allows disabling the logging of Legacy REST API usages.
		 *
		 * @param bool $do_logging True to enable the logging of all the Legacy REST API usages (default), false to disable.
		 *
		 * @since 8.5.0
		 */
		if ( apply_filters( 'woocommerce_log_legacy_rest_api_usages', true ) ) {
			wc_get_logger()->info( 'Version: ' . WC_API_REQUEST_VERSION . ", Route: $route, User agent: $user_agent", array( 'source' => 'legacy_rest_api_usages' ) );
		}
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
		$this->maybe_log_rest_api_request( $route, $_SERVER['HTTP_USER_AGENT'] ?? null );

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

		exit;
	}

	/**
	 * Display an admin notice with information about the last Legacy REST API usage,
	 * if the corresponding transient is available and unless the Legacy REST API
	 * extension is installed or the user has dismissed the notice.
	 */
	private function maybe_display_legacy_wc_api_usage_notice(): void {
		$legacy_api_usages = get_transient( 'wc_legacy_rest_api_usages' );
		if ( false === $legacy_api_usages || is_plugin_active( 'woocommerce-legacy-rest-api/woocommerce-legacy-rest-api.php' ) || 'yes' !== get_option( 'woocommerce_api_enabled' ) ) {
			if ( WC_Admin_Notices::has_notice( 'legacy_api_usages_detected' ) ) {
				WC_Admin_Notices::remove_notice( 'legacy_api_usages_detected' );
			}
		} elseif ( ! WC_Admin_Notices::user_has_dismissed_notice( 'legacy_api_usages_detected' ) ) {
			unset( $legacy_api_usages['user_agents']['unknown'] );
			$user_agents = array_keys( $legacy_api_usages['user_agents'] );

			WC_Admin_Notices::add_custom_notice(
				'legacy_api_usages_detected',
				sprintf(
					'%s%s',
					sprintf(
						'<h4>%s</h4>',
						esc_html__( 'WooCommerce Legacy REST API access detected', 'woocommerce' )
					),
					sprintf(
					// translators: %1$d = count of Legacy REST API usages recorded, %2$s = date and time of first access, %3$d = count of known user agents registered, %4$s = URL.
						wpautop( wp_kses_data( __( '<p>The WooCommerce Legacy REST API has been accessed <b>%1$d</b> time(s) since <b>%2$s</b>. There are <b>%3$d</b> known user agent(s) registered. There are more details in <b><a target="_blank" href="%4$s">the WooCommerce log files</a></b> (file names start with <code>legacy_rest_api_usages</code>).', 'woocommerce' ) ) ),
						$legacy_api_usages['total_count'],
						$legacy_api_usages['first_usage'],
						count( $user_agents ),
						admin_url( 'admin.php?page=wc-status&tab=logs' ),
					)
				)
			);
		}
	}

	/**
	 * Include required files for REST API request.
	 *
	 * @since 2.1
	 * @deprecated 2.6.0
	 */
	public function includes() {

		// API server / response handlers.
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-exception.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-server.php';
		include_once dirname( __FILE__ ) . '/api/v3/interface-wc-api-handler.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-json-handler.php';

		// Authentication.
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-authentication.php';
		$this->authentication = new WC_API_Authentication();

		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-resource.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-coupons.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-customers.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-orders.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-products.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-reports.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-taxes.php';
		include_once dirname( __FILE__ ) . '/api/v3/class-wc-api-webhooks.php';

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

		$api_classes = apply_filters(
			'woocommerce_api_classes',
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
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-server.php';
		include_once dirname( __FILE__ ) . '/api/v1/interface-wc-api-handler.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-json-handler.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-xml-handler.php';

		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-authentication.php';
		$this->authentication = new WC_API_Authentication();

		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-resource.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-coupons.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-customers.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-orders.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-products.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-api-reports.php';

		// Allow plugins to load other response handlers or resource classes.
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v1 REST API request.
		$api_classes = apply_filters(
			'woocommerce_api_classes',
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
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-exception.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-server.php';
		include_once dirname( __FILE__ ) . '/api/v2/interface-wc-api-handler.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-json-handler.php';

		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-authentication.php';
		$this->authentication = new WC_API_Authentication();

		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-resource.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-coupons.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-customers.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-orders.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-products.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-reports.php';
		include_once dirname( __FILE__ ) . '/api/v2/class-wc-api-webhooks.php';

		// allow plugins to load other response handlers or resource classes.
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v2 REST API request.
		$api_classes = apply_filters(
			'woocommerce_api_classes',
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
