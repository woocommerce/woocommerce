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
	 * Rest API packages.
	 *
	 * @var array
	 */
	protected $packages = array();

	/**
	 * Setup class.
	 */
	public function init() {
		parent::init();
		$this->register_core_api();
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );
		add_action( 'init', array( $this, 'rest_api_init' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );
		add_action( 'rest_api_init', array( $this, 'register_wp_admin_settings' ) );
	}

	/**
	 * Register a WC Rest API package.
	 *
	 * This is used to ensure we load the latest version of the REST API, if for example using a feature plugin version.
	 *
	 * @since 3.7.0
	 * @param string $version Version of the REST API being registered.
	 * @param mixed  $callback Callback function to load the REST API.
	 * @param string $path Path to package.
	 * @return bool
	 */
	public function register( $version, $callback, $path ) {
		if ( isset( $this->packages[ $version ] ) ) {
			return false;
		}
		$this->packages[ $version ] = array(
			'version'  => $version,
			'callback' => $callback,
			'path'     => $path,
		);
		return true;
	}

	/**
	 * Get REST API packages sorted by version (oldest to newest).
	 *
	 * @since 3.7.0
	 * @return array
	 */
	public function get_rest_api_packages() {
		uksort( $this->packages, 'version_compare' );
		return $this->packages;
	}

	/**
	 * Get latest version number of the registered REST API packages.
	 *
	 * @since 3.7.0
	 * @return string|bool
	 */
	public function get_latest_package_version() {
		$packages = $this->get_rest_api_packages();
		$versions = array_keys( $packages );

		return end( $versions );
	}

	/**
	 * Get path to the registered REST API package.
	 *
	 * @since 3.7.0
	 * @return string|bool
	 */
	public function get_latest_package_path() {
		$packages = $this->get_rest_api_packages();
		$latest   = end( $packages );

		return $latest['path'];
	}

	/**
	 * Get callback of the latest version of the REST API package.
	 *
	 * @since 3.7.0
	 * @return string|bool
	 */
	public function get_latest_package_callback() {
		$packages = $this->get_rest_api_packages();
		$latest   = end( $packages );

		return $latest['callback'];
	}

	/**
	 * Register the REST API package included in core.
	 *
	 * @since 3.7.0
	 */
	protected function register_core_api() {
		if ( file_exists( WC_ABSPATH . 'packages/rest-api/init.php' ) ) {
			$version       = include WC_ABSPATH . 'packages/rest-api/version.php';
			$init_callback = include WC_ABSPATH . 'packages/rest-api/init.php';
		} else {
			// Build step required.
			$version       = 0;
			$init_callback = function() {
				add_action(
					'admin_notices',
					function() {
						echo '<div class="error"><p>';
						printf(
							/* Translators: %1$s WooCommerce plugin directory, %2$s is the install command, %3$s is the build command. */
							esc_html__( 'The development version of WooCommerce requires files to be built before it can function properly. From the plugin directory (%1$s), run %2$s to install dependencies and %3$s to build assets.', 'woocommerce' ),
							'<code>' . esc_html( str_replace( ABSPATH, '', WC_ABSPATH ) ) . '</code>',
							'<code>npm install</code>',
							'<code>npm run build</code>'
						);
						echo '</p></div>';
					}
				);
			};
		}
		$this->register( $version, $init_callback, WC_ABSPATH . 'packages/rest-api' );
	}

	/**
	 * Look though registered REST API packages and load the latest one.
	 * Once loaded, it's class autoloader will be available for use.
	 *
	 * Packages should be registered during plugins_loaded/woocommerce_loaded hook.
	 *
	 * @see WC_API::register().
	 *
	 * @since 3.7.0
	 */
	public function rest_api_init() {
		if ( $this->is_rest_api_loaded() ) {
			return;
		}
		call_user_func( $this->get_latest_package_callback() );
	}

	/**
	 * Get data from a WooCommerce API endpoint.
	 *
	 * @since 3.7.0
	 * @param string $endpoint Endpoint.
	 * @param array  $params Params to passwith request.
	 * @return array|WP_Error
	 */
	public function get_endpoint_data( $endpoint, $params = array() ) {
		if ( ! $this->is_rest_api_loaded() ) {
			$this->rest_api_init();
		}

		$request = new \WP_REST_Request( 'GET', $endpoint );

		if ( $params ) {
			$request->set_query_params( $params );
		}

		$response = \rest_do_request( $request );
		$server   = \rest_get_server();
		$json     = wp_json_encode( $server->response_to_data( $response, false ) );

		return json_decode( $json, true );
	}

	/**
	 * Return if the rest API classes were already loaded.
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	protected function is_rest_api_loaded() {
		return class_exists( '\WooCommerce\RestApi\Server', false );
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
