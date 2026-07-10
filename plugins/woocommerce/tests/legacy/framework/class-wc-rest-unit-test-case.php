<?php
/**
 * WC REST API Unit Test Case (for WP-API Endpoints).
 *
 * Provides REST API specific methods and setup/teardown.
 *
 * @package WooCommerce\Tests
 * @since 3.0
 */

/**
 * Base class for REST related unit test classes.
 */
class WC_Lazy_REST_Server extends WP_Test_Spy_REST_Server {

	/**
	 * Route namespaces initialized on this server.
	 *
	 * @var array<string, bool>
	 */
	private $initialized_namespaces = array();

	/**
	 * Whether every REST route has been initialized.
	 *
	 * @var bool
	 */
	private $all_routes_initialized = false;

	/**
	 * Whether route initialization is currently running.
	 *
	 * @var bool
	 */
	private $initializing = false;

	/**
	 * Number of requests currently being dispatched.
	 *
	 * @var int
	 */
	private $dispatch_depth = 0;

	/**
	 * Initialize only the namespace needed by a request before dispatching it.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response
	 */
	public function dispatch( $request ) {
		$route     = $request->get_route();
		$namespace = $this->get_route_namespace( $route );

		if ( ! $this->initializing && ! $this->all_routes_initialized && ! isset( $this->initialized_namespaces[ $namespace ] ) && ! $this->has_route( $route ) ) {
			$initialized_all_routes = $this->initialize_routes( $route );

			if ( $initialized_all_routes || '' === $namespace ) {
				$this->all_routes_initialized = true;
			} else {
				$this->initialized_namespaces[ $namespace ] = true;
			}
		}

		++$this->dispatch_depth;
		try {
			return parent::dispatch( $request );
		} finally {
			--$this->dispatch_depth;
		}
	}

	/**
	 * Get registered routes, initializing all routes for direct inspection.
	 *
	 * @param string $route_namespace Optionally limit results to a namespace.
	 * @return array
	 */
	public function get_routes( $route_namespace = '' ) {
		if ( 0 === $this->dispatch_depth && ! $this->initializing && ! $this->all_routes_initialized && ! $this->has_registered_routes() ) {
			$this->initialize_all_routes();
		}

		return parent::get_routes( $route_namespace );
	}

	/**
	 * Initialize every REST route on this server.
	 */
	public function initialize_all_routes(): void {
		if ( ! $this->all_routes_initialized ) {
			$this->initialize_routes();
			$this->all_routes_initialized = true;
		}
	}

	/**
	 * Run REST route registration with an optional production route context.
	 *
	 * @param string|null $route REST route being requested, or null to initialize all routes.
	 * @return bool Whether every route was initialized.
	 */
	private function initialize_routes( $route = null ): bool {
		$wp             = $GLOBALS['wp'] ?? null;
		$has_wp_context = is_object( $wp );
		$had_rest_route = $has_wp_context && array_key_exists( 'rest_route', $wp->query_vars );
		$rest_route     = $has_wp_context ? ( $wp->query_vars['rest_route'] ?? null ) : null;

		if ( $has_wp_context ) {
			if ( null === $route ) {
				unset( $wp->query_vars['rest_route'] );
			} else {
				$wp->query_vars['rest_route'] = $route;
			}
		}

		$this->initializing = true;
		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'rest_api_init' );
		} finally {
			$this->initializing = false;
			if ( $has_wp_context ) {
				if ( $had_rest_route ) {
					$wp->query_vars['rest_route'] = $rest_route;
				} else {
					unset( $wp->query_vars['rest_route'] );
				}
			}
		}

		return ! $has_wp_context || null === $route;
	}

	/**
	 * Check whether a non-core route has already been registered.
	 *
	 * @return bool
	 */
	private function has_registered_routes(): bool {
		return count( $this->endpoints ) > 2;
	}

	/**
	 * Check whether a request can already be served without global initialization.
	 *
	 * @param string $request_route REST request route.
	 * @return bool
	 */
	private function has_route( string $request_route ): bool {
		if ( '/' === $request_route || '/batch/v1' === $request_route ) {
			return false;
		}

		foreach ( array_keys( $this->endpoints ) as $route ) {
			if ( preg_match( '@^' . $route . '$@i', $request_route ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a stable namespace key from a REST route.
	 *
	 * @param string $route REST route.
	 * @return string
	 */
	private function get_route_namespace( string $route ): string {
		$segments = explode( '/', trim( $route, '/' ) );

		return implode( '/', array_slice( $segments, 0, 2 ) );
	}
}

/**
 * Base class for REST related unit test classes.
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test server and its base case are intentionally colocated.
class WC_REST_Unit_Test_Case extends WC_Unit_Test_Case {

	/**
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Setup our test server.
	 */
	public function setUp(): void {
		parent::setUp();
		global $wp_rest_server;
		$wp_rest_server = new WC_Lazy_REST_Server();
		$this->server   = $wp_rest_server;
		$this->initialize_rest_api_defaults();

		// Reset payment gateways.
		$gateways                   = WC_Payment_Gateways::instance();
		$gateways->payment_gateways = array();
		$gateways->init();
	}

	/**
	 * Initialize all REST routes for tests that depend on eager registration timing.
	 */
	protected function initialize_rest_api_routes(): void {
		$this->server->initialize_all_routes();
	}

	/**
	 * Establish WordPress REST defaults without registering WooCommerce routes.
	 */
	private function initialize_rest_api_defaults(): void {
		global $wp_filter;

		$rest_api_init_hook         = $wp_filter['rest_api_init'] ?? null;
		$wp_filter['rest_api_init'] = new WP_Hook(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The original hook is restored below.
		add_action( 'rest_api_init', 'rest_api_default_filters' );

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
			do_action( 'rest_api_init' );
		} finally {
			if ( null === $rest_api_init_hook ) {
				unset( $wp_filter['rest_api_init'] );
			} else {
				$wp_filter['rest_api_init'] = $rest_api_init_hook; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the original hook.
			}
		}
	}

	/**
	 * Unset the server.
	 */
	public function tearDown(): void {
		parent::tearDown();
		global $wp_rest_server;
		unset( $this->server );
		$wp_rest_server = null;
	}

	/**
	 * Perform a REST request.
	 *
	 * @param string     $url The endpopint url, if it doesn't start with '/' it'll be prepended with '/wc/v3/'.
	 * @param string     $verb HTTP verb for the request, default is GET.
	 * @param array|null $body_params Body parameters for the request, null if none are required.
	 * @param array|null $query_params Query string parameters for the request, null if none are required.
	 * @return array Result from the request.
	 */
	public function do_rest_request( $url, $verb = 'GET', $body_params = null, $query_params = null ) {
		if ( '/' !== $url[0] ) {
			$url = '/wc/v3/' . $url;
		}

		$request = new WP_REST_Request( $verb, $url );
		if ( ! is_null( $query_params ) ) {
			$request->set_query_params( $query_params );
		}
		if ( ! is_null( $body_params ) ) {
			$request->set_body_params( $body_params );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Perform a GET REST request.
	 *
	 * @param string     $url The endpopint url, if it doesn't start with '/' it'll be prepended with '/wc/v3/'.
	 * @param array|null $query_params Query string parameters for the request, null if none are required.
	 * @return WP_REST_Response The response for the request.
	 */
	public function do_rest_get_request( $url, $query_params = null ) {
		return $this->do_rest_request( $url, 'GET', null, $query_params );
	}

	/**
	 * Perform a POST REST request.
	 *
	 * @param string     $url The endpopint url, if it doesn't start with '/' it'll be prepended with '/wc/v3/'.
	 * @param array|null $body_params Body parameters for the request, null if none are required.
	 * @param array|null $query_params Query string parameters for the request, null if none are required.
	 * @return array Result from the request.
	 */
	public function do_rest_post_request( $url, $body_params = null, $query_params = null ) {
		return $this->do_rest_request( $url, 'POST', $body_params, $query_params );
	}

	/**
	 * Perform a PUT REST request.
	 *
	 * @param string     $url The endpopint url, if it doesn't start with '/' it'll be prepended with '/wc/v3/'.
	 * @param array|null $body_params Body parameters for the request, null if none are required.
	 * @param array|null $query_params Query string parameters for the request, null if none are required.
	 * @return array Result from the request.
	 */
	public function do_rest_put_request( $url, $body_params = null, $query_params = null ) {
		return $this->do_rest_request( $url, 'PUT', $body_params, $query_params );
	}
}
