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
	 * Rest API versions.
	 *
	 * @var array
	 */
	protected $versions = array();

	/**
	 * Setup class.
	 */
	public function init() {
		parent::init();
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
		add_action( 'rest_api_init', array( $this, 'register_wp_admin_settings' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ), 0 );
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

	/**
	 * Init WP REST API by hooking into `rest_api_init`.
	 *
	 * @since 2.6.0
	 */
	public function rest_api_init() {
		$callback = $this->get_latest_version_callback();

		if ( ! $callback ) {
			require_once dirname( __FILE__ ) . '/api/src/class-server.php';
			\WooCommerce\Rest_Api\Server::instance()->init();
			return;
		}

		call_user_func( $this->latest_version_callback() );
	}

	/**
	 * Include REST API classes.
	 */
	public function rest_api_includes() {
		// Just init latest REST API - it will autoload any REST classes.
		$this->rest_api_init();
	}

	/**
	 * Register the WC Rest API.
	 *
	 * This is used to ensure we load the latest version of the REST API, if for example using a feature plugin version.
	 *
	 * @param string $version Version of the REST API being registered.
	 * @param mixed  $callback Callback function to load the REST API.
	 * @return bool
	 */
	public function register( $version, $callback ) {
		if ( isset( $this->versions[ $version ] ) ) {
			return false;
		}
		$this->versions[ $version ] = $callback;
		return true;
	}

	/**
	 * Get registered versions of the REST API.
	 *
	 * @return array
	 */
	public function get_versions() {
		return $this->versions;
	}

	/**
	 * Get latest version number of the registered REST APIs.
	 *
	 * @return string|bool
	 */
	public function get_latest_version() {
		$keys = array_keys( $this->versions );
		if ( empty( $keys ) ) {
			return false;
		}
		uasort( $keys, 'version_compare' );
		return end( $keys );
	}

	/**
	 * Get the initialization callback for the latest registered version of the REST API.
	 *
	 * @return string
	 */
	public function get_latest_version_callback() {
		$latest = $this->get_latest_version();
		if ( empty( $latest ) || ! isset( $this->versions[ $latest ] ) ) {
			return '';
		}
		return $this->versions[ $latest ];
	}
}
