<?php
/**
 * WooCommerce API
 *
 * Handles WC-API endpoint requests.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Legacy_API' ) ) {
	include_once( 'class-wc-legacy-api.php' );
}

class WC_API extends WC_Legacy_API {

	/**
	 * Setup class.
	 * @since 2.0
	 */
	public function __construct() {
		parent::__construct();

		// Add query vars.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register API endpoints.
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle wc-api endpoint requests.
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

		// Ensure payment gateways are initialized in time for API requests.
		add_action( 'woocommerce_api_request', array( 'WC_Payment_Gateways', 'instance' ), 0 );

		// WP REST API.
		$this->rest_api_init();
	}

	/**
	 * Add new query vars.
	 *
	 * @since 2.0
	 * @param array $vars
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars   = parent::add_query_vars( $vars );
		$vars[] = 'wc-api';
		return $vars;
	}

	/**
	 * WC API for payment gateway IPNs, etc.
	 * @since 2.0
	 */
	public static function add_endpoint() {
		parent::add_endpoint();
		add_rewrite_endpoint( 'wc-api', EP_ALL );
	}

	/**
	 * API request - Trigger any API requests.
	 *
	 * @since   2.0
	 * @version 2.4
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-api'] ) ) {
			$wp->query_vars['wc-api'] = $_GET['wc-api'];
		}

		// wc-api endpoint requests.
		if ( ! empty( $wp->query_vars['wc-api'] ) ) {

			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			nocache_headers();

			// Clean the API request.
			$api_request = strtolower( wc_clean( $wp->query_vars['wc-api'] ) );

			// Trigger generic action before request hook.
			do_action( 'woocommerce_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
			status_header( has_action( 'woocommerce_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request.
			do_action( 'woocommerce_api_' . $api_request );

			// Done, clear buffer and exit.
			ob_end_clean();
			die( '-1' );
		}
	}

	/**
	 * Init WP REST API.
	 * @since 2.6.0
	 */
	private function rest_api_init() {
		global $wp_version;

		// REST API was included starting WordPress 4.4.
		if ( version_compare( $wp_version, 4.4, '<' ) ) {
			return;
		}

		$this->rest_api_includes();

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Include REST API classes.
	 * @since 2.6.0
	 */
	private function rest_api_includes() {
		// Exception handler.
		include_once( 'api/class-wc-rest-exception.php' );

		// Authentication.
		include_once( 'api/class-wc-rest-authentication.php' );

		// WP-API classes and functions.
		include_once( 'vendor/wp-rest-functions.php' );
		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			include_once( 'vendor/class-wp-rest-controller.php' );
		}

		// Abstract controllers.
		include_once( 'abstracts/abstract-wc-rest-controller.php' );
		include_once( 'abstracts/abstract-wc-rest-posts-controller.php' );
		include_once( 'abstracts/abstract-wc-rest-terms-controller.php' );

		// REST API controllers.
		include_once( 'api/class-wc-rest-coupons-controller.php' );
		include_once( 'api/class-wc-rest-customer-downloads-controller.php' );
		include_once( 'api/class-wc-rest-customers-controller.php' );
		include_once( 'api/class-wc-rest-order-notes-controller.php' );
		include_once( 'api/class-wc-rest-order-refunds-controller.php' );
		include_once( 'api/class-wc-rest-orders-controller.php' );
		include_once( 'api/class-wc-rest-product-attribute-terms-controller.php' );
		include_once( 'api/class-wc-rest-product-attributes-controller.php' );
		include_once( 'api/class-wc-rest-product-categories-controller.php' );
		include_once( 'api/class-wc-rest-product-reviews-controller.php' );
		include_once( 'api/class-wc-rest-product-shipping-classes-controller.php' );
		include_once( 'api/class-wc-rest-product-tags-controller.php' );
		include_once( 'api/class-wc-rest-products-controller.php' );
		include_once( 'api/class-wc-rest-report-sales-controller.php' );
		include_once( 'api/class-wc-rest-report-top-sellers-controller.php' );
		include_once( 'api/class-wc-rest-reports-controller.php' );
		include_once( 'api/class-wc-rest-tax-classes-controller.php' );
		include_once( 'api/class-wc-rest-taxes-controller.php' );
		include_once( 'api/class-wc-rest-webhook-deliveries.php' );
		include_once( 'api/class-wc-rest-webhooks-controller.php' );
	}

	/**
	 * Register REST API routes.
	 * @since 2.6.0
	 */
	public function register_rest_routes() {
		$controllers = array(
			'WC_REST_Coupons_Controller',
			'WC_REST_Customer_Downloads_Controller',
			'WC_REST_Customers_Controller',
			'WC_REST_Order_Notes_Controller',
			'WC_REST_Order_Refunds_Controller',
			'WC_REST_Orders_Controller',
			'WC_REST_Product_Attribute_Terms_Controller',
			'WC_REST_Product_Attributes_Controller',
			'WC_REST_Product_Categories_Controller',
			'WC_REST_Product_Reviews_Controller',
			'WC_REST_Product_Shipping_Classes_Controller',
			'WC_REST_Product_Tags_Controller',
			'WC_REST_Products_Controller',
			'WC_REST_Report_Sales_Controller',
			'WC_REST_Report_Top_Sellers_Controller',
			'WC_REST_Reports_Controller',
			'WC_REST_Tax_Classes_Controller',
			'WC_REST_Taxes_Controller',
			'WC_REST_Webhook_Deliveries_Controller',
			'WC_REST_Webhooks_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}
}
