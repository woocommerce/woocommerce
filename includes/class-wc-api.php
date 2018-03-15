<?php
/**
 * WooCommerce API
 *
 * Handles WC-API endpoint requests.
 *
 * @package WooCommerce/API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * API class.
 */
class WC_API extends WC_Legacy_API {

	/**
	 * Setup class.
	 *
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
	 * @param array $vars Query vars.
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars   = parent::add_query_vars( $vars );
		$vars[] = 'wc-api';
		return $vars;
	}

	/**
	 * WC API for payment gateway IPNs, etc.
	 *
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

		if ( ! empty( $_GET['wc-api'] ) ) { // WPCS: input var okay, CSRF ok.
			$wp->query_vars['wc-api'] = sanitize_key( wp_unslash( $_GET['wc-api'] ) ); // WPCS: input var okay, CSRF ok.
		}

		// wc-api endpoint requests.
		if ( ! empty( $wp->query_vars['wc-api'] ) ) {

			// Buffer, we won't want any output here.
			ob_start();

			// No cache headers.
			wc_nocache_headers();

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
	 *
	 * @since 2.6.0
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->rest_api_includes();

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Include REST API classes.
	 *
	 * @since 2.6.0
	 */
	private function rest_api_includes() {
		// Exception handler.
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-exception.php';

		// Authentication.
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-authentication.php';

		// Abstract controllers.
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-rest-controller.php';
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-rest-posts-controller.php';
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-rest-crud-controller.php';
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-rest-terms-controller.php';
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/abstracts/abstract-wc-settings-api.php';

		// REST API v1 controllers.
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-coupons-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-customer-downloads-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-customers-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-orders-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-order-notes-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-order-refunds-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-attribute-terms-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-attributes-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-categories-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-reviews-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-shipping-classes-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-product-tags-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-products-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-report-sales-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-report-top-sellers-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-reports-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-tax-classes-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-taxes-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-webhook-deliveries-controller.php';
		include_once dirname( __FILE__ ) . '/api/v1/class-wc-rest-webhooks-controller.php';

		// Legacy v2 code.
		include_once dirname( __FILE__ ) . '/api/legacy/class-wc-rest-legacy-coupons-controller.php';
		include_once dirname( __FILE__ ) . '/api/legacy/class-wc-rest-legacy-orders-controller.php';
		include_once dirname( __FILE__ ) . '/api/legacy/class-wc-rest-legacy-products-controller.php';

		// REST API v2 controllers.
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-coupons-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-customer-downloads-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-customers-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-orders-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-network-orders-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-order-notes-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-order-refunds-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-attribute-terms-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-attributes-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-categories-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-reviews-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-shipping-classes-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-tags-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-products-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-product-variations-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-report-sales-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-report-top-sellers-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-reports-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-settings-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-setting-options-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-shipping-zones-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-shipping-zone-locations-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-shipping-zone-methods-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-tax-classes-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-taxes-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-webhook-deliveries-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-webhooks-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-system-status-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-system-status-tools-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-shipping-methods-controller.php';
		include_once dirname( __FILE__ ) . '/api/class-wc-rest-payment-gateways-controller.php';
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.6.0
	 */
	public function register_rest_routes() {
		// Register settings to the REST API.
		$this->register_wp_admin_settings();

		$controllers = array(
			// v1 controllers.
			'WC_REST_Coupons_V1_Controller',
			'WC_REST_Customer_Downloads_V1_Controller',
			'WC_REST_Customers_V1_Controller',
			'WC_REST_Order_Notes_V1_Controller',
			'WC_REST_Order_Refunds_V1_Controller',
			'WC_REST_Orders_V1_Controller',
			'WC_REST_Product_Attribute_Terms_V1_Controller',
			'WC_REST_Product_Attributes_V1_Controller',
			'WC_REST_Product_Categories_V1_Controller',
			'WC_REST_Product_Reviews_V1_Controller',
			'WC_REST_Product_Shipping_Classes_V1_Controller',
			'WC_REST_Product_Tags_V1_Controller',
			'WC_REST_Products_V1_Controller',
			'WC_REST_Report_Sales_V1_Controller',
			'WC_REST_Report_Top_Sellers_V1_Controller',
			'WC_REST_Reports_V1_Controller',
			'WC_REST_Tax_Classes_V1_Controller',
			'WC_REST_Taxes_V1_Controller',
			'WC_REST_Webhook_Deliveries_V1_Controller',
			'WC_REST_Webhooks_V1_Controller',

			// v2 controllers.
			'WC_REST_Coupons_Controller',
			'WC_REST_Customer_Downloads_Controller',
			'WC_REST_Customers_Controller',
			'WC_REST_Network_Orders_Controller',
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
			'WC_REST_Product_Variations_Controller',
			'WC_REST_Report_Sales_Controller',
			'WC_REST_Report_Top_Sellers_Controller',
			'WC_REST_Reports_Controller',
			'WC_REST_Settings_Controller',
			'WC_REST_Setting_Options_Controller',
			'WC_REST_Shipping_Zones_Controller',
			'WC_REST_Shipping_Zone_Locations_Controller',
			'WC_REST_Shipping_Zone_Methods_Controller',
			'WC_REST_Tax_Classes_Controller',
			'WC_REST_Taxes_Controller',
			'WC_REST_Webhook_Deliveries_Controller',
			'WC_REST_Webhooks_Controller',
			'WC_REST_System_Status_Controller',
			'WC_REST_System_Status_Tools_Controller',
			'WC_REST_Shipping_Methods_Controller',
			'WC_REST_Payment_Gateways_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Register WC settings from WP-API to the REST API.
	 *
	 * @since  3.0.0
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

}
