<?php
/**
 * WooCommerce API
 *
 * Handles WC-API endpoint requests
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API {

	/**
	 * Setup class
	 *
	 * @access public
	 * @since 2.0
	 * @return WC_API
	 */
	public function __construct() {

		// add query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );

		// register API endpoints
		add_action( 'init', array( $this, 'add_endpoint'), 0 );

		// handle REST/legacy API request
		add_action( 'parse_request', array( $this, 'handle_api_requests'), 0 );

		// TODO: should this be done via this filter or in wp-json-server class?
		add_filter( 'json_endpoints', array( $this, 'remove_users_endpoint' ), 0 );
	}

	/**
	 * add_query_vars function.
	 *
	 * @access public
	 * @since 2.0
	 * @param $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc-api';
		$vars[] = 'wc-api-route';
		return $vars;
	}

	/**
	 * add_endpoint function.
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function add_endpoint() {

		// REST API
		add_rewrite_rule( '^wc-api\/v1/?$', 'index.php?wc-api-route=/', 'top' );
		add_rewrite_rule( '^wc-api\/v1(.*)?', 'index.php?wc-api-route=$matches[1]', 'top' );

		// legacy API for payment gateway IPNs
		add_rewrite_endpoint( 'wc-api', EP_ALL );
	}


	/**
	 * API request - Trigger any API requests
	 *
	 * @access public
	 * @since 2.0
	 * @return void
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-api'] ) )
			$wp->query_vars['wc-api'] = $_GET['wc-api'];

		if ( ! empty( $_GET['wc-api-route'] ) )
			$wp->query_vars['wc-api-route'] = $_GET['wc-api-route'];

		// REST API request
		if ( ! empty( $wp->query_vars['wc-api-route'] ) ) {

			// load required files
			$this->includes();

			define('XMLRPC_REQUEST', true);

			define('JSON_REQUEST', true);

			// TODO: should these filters/actions be renamed?
			$wp_json_server_class = apply_filters('wp_json_server_class', 'WP_JSON_Server');

			$this->server = new $wp_json_server_class;

			do_action('wp_json_server_before_serve', $this->server );

			$this->register_resources( $this->server );

			// Fire off the request
			$this->server->serve_request( $wp->query_vars['wc-api-route'] );

			exit;
		}

		// legacy API requests
		if ( ! empty( $wp->query_vars['wc-api'] ) ) {

			// Buffer, we won't want any output here
			ob_start();

			// Get API trigger
			$api = strtolower( esc_attr( $wp->query_vars['wc-api'] ) );

			// Load class if exists
			if ( class_exists( $api ) )
				$api_class = new $api();

			// Trigger actions
			do_action( 'woocommerce_api_' . $api );

			// Done, clear buffer and exit
			ob_end_clean();
			die('1');
		}
	}


	/**
	 * Include required files for REST API request
	 *
	 * @since 2.1
	 */
	private function includes() {

		// TODO: are all these required?
		include_once( ABSPATH . WPINC . '/class-IXR.php' );
		include_once( ABSPATH . WPINC . '/class-wp-xmlrpc-server.php' );

		include_once( 'libraries/wp-api/class-wp-json-responsehandler.php' );
		include_once( 'libraries/wp-api/class-wp-json-server.php' );

		include_once( 'api/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( 'api/class-wc-api-base.php' );
		include_once( 'api/class-wc-api-orders.php' );
		include_once( 'api/class-wc-api-products.php' );
		include_once( 'api/class-wc-api-coupons.php' );
		include_once( 'api/class-wc-api-customers.php' );
		include_once( 'api/class-wc-api-reports.php' );
	}

	/**
	 * Register API resources available
	 *
	 * @since 2.1
	 * @param object $server the REST server
	 */
	public function register_resources( $server ) {

		$api_classes = apply_filters( 'woocommerce_api_classes', array(
			'WC_API_Customers',
			'WC_API_Orders',
			'WC_API_Products',
			'WC_API_Coupons',
			'WC_API_Reports',
		) );

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $server );
		}
	}


	/**
	 * Remove the users endpoints added by the JSON server
	 *
	 * @since 2.1
	 * @param $endpoints
	 * @return array
	 */
	public function remove_users_endpoint( $endpoints ) {

		foreach ( $endpoints as $path => $endpoint ) {

			if ( ! strncmp( $path, '/user', 5 ) )
				unset( $endpoints[ $path ] );
		}

		return $endpoints;
	}

}
