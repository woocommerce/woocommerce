<?php
/**
 * WooCommerce API class loader.
 *
 * This handles APIs in WooCommerce. These include:
 * - wc-api endpoint - Commonly used by Payment gateways for callbacks.
 * - Legacy REST API - Deprecated in 2.6.0. @see class-wc-legacy-api.php
 * - WP REST API - The main REST API in WooCommerce which is built on top of the WP REST API.
 *
 * @package WooCommerce/API
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_API class.
 */
class WC_API extends WC_Legacy_API {

	/**
	 * Init the API by setting up action and filter hooks.
	 */
	public function init() {
		parent::init();
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		add_action( 'init', array( $this, 'rest_api_init' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
		add_action( 'rest_api_init', array( $this, 'register_wp_admin_settings' ) );
	}

	/**
	 * Call the Server class from the Rest API package.
	 *
	 * @see \Automattic\WooCommerce\RestApi\Server
	 */
	public function rest_api_init() {
		if ( $this->is_rest_api_loaded() || version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			return;
		}
		\Automattic\WooCommerce\RestApi\Server::instance()->init();
	}

	/**
	 * Get the version of the REST API package being ran.
	 *
	 * @since 3.7.0
	 * @return int|null
	 */
	public function get_rest_api_package_version() {
		return null;
	}

	/**
	 * Get the version of the REST API package being ran.
	 *
	 * @since 3.7.0
	 * @return string
	 */
	public function get_rest_api_package_path() {
		return '';
	}

	/**
	 * Return if the rest API classes were already loaded.
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	protected function is_rest_api_loaded() {
		return class_exists( '\Automattic\WooCommerce\RestApi\Server', false );
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

			// Make sure gateways are available for request.
			WC()->payment_gateways();

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
