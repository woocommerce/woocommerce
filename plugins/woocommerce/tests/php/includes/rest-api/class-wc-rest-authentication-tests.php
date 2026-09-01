<?php

/**
 * Tests relating to our REST authentication logic.
 */
class WC_REST_Authentication_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * The System Under Test.
	 *
	 * @var WC_REST_Authentication
	 */
	private $sut;

	/**
	 * The route WordPress had resolved before the test ran, if any.
	 *
	 * @var string|null
	 */
	private $original_resolved_route;

	/**
	 * Whether REQUEST_URI was set before the test ran.
	 *
	 * @var bool
	 */
	private $had_request_uri;

	/**
	 * The request URI the test ran with, verbatim.
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * The authentication state WC_REST_Authentication held before the test ran.
	 *
	 * @var array<string, mixed>
	 */
	private $original_authentication_state = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		global $wp;

		$this->sut = WC_REST_Authentication::instance();

		$this->original_resolved_route = $wp->query_vars['rest_route'] ?? null;
		unset( $wp->query_vars['rest_route'] );

		$this->had_request_uri = array_key_exists( 'REQUEST_URI', $_SERVER );
		// Stored and restored verbatim, so tearDown leaves $_SERVER exactly as it found it. Sanitizing
		// would drop the characters these tests use to tell a WooCommerce route from one that only looks
		// like it, and unslashing would undo what wp_magic_quotes() put there.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Round-tripped between $_SERVER and $_SERVER, never output or stored.
		$this->original_request_uri = $this->had_request_uri ? $_SERVER['REQUEST_URI'] : null;

		foreach ( array( 'user', 'error', 'auth_method' ) as $name ) {
			$this->original_authentication_state[ $name ] = $this->authentication_property( $name )->getValue( $this->sut );
		}
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp;

		if ( null === $this->original_resolved_route ) {
			unset( $wp->query_vars['rest_route'] );
		} else {
			$wp->query_vars['rest_route'] = $this->original_resolved_route;
		}

		if ( $this->had_request_uri ) {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		} else {
			unset( $_SERVER['REQUEST_URI'] );
		}

		foreach ( $this->original_authentication_state as $name => $value ) {
			$this->authentication_property( $name )->setValue( $this->sut, $value );
		}

		parent::tearDown();
	}

	/**
	 * Accessor for one of WC_REST_Authentication's protected authentication properties.
	 *
	 * @param string $name Property name.
	 * @return ReflectionProperty
	 */
	private function authentication_property( string $name ): ReflectionProperty {
		$property = new ReflectionProperty( WC_REST_Authentication::class, $name );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * Call the protected is_request_to_rest_api() for the request the test has set up.
	 *
	 * @return bool
	 */
	private function is_request_to_rest_api(): bool {
		$method = new ReflectionMethod( $this->sut, 'is_request_to_rest_api' );
		$method->setAccessible( true );

		return $method->invoke( $this->sut );
	}

	/**
	 * Put WC_REST_Authentication into the state it reaches after an API key authenticates a request.
	 *
	 * @param int    $user_id     User the key belongs to.
	 * @param string $permissions Key permissions.
	 * @return void
	 */
	private function authenticate_as( int $user_id, string $permissions = 'read_write' ): void {
		$this->authentication_property( 'user' )->setValue(
			$this->sut,
			(object) array(
				'key_id'      => 1,
				'user_id'     => $user_id,
				'permissions' => $permissions,
			)
		);
	}

	/**
	 * @testdox Should identify WooCommerce REST requests by route only.
	 *
	 * @dataProvider provider_request_uris_for_rest_api_detection
	 *
	 * @param string $request_uri Request URI.
	 * @param bool   $expected    Expected result.
	 */
	public function test_is_request_to_rest_api_checks_path_only( string $request_uri, bool $expected ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;

		$this->assertSame( $expected, $this->is_request_to_rest_api() );
	}

	/**
	 * Data provider for REST API request detection.
	 *
	 * @return array[]
	 */
	public static function provider_request_uris_for_rest_api_detection(): array {
		return array(
			'woocommerce route'                           => array( '/wp-json/wc/v3/products', true ),
			'third-party woocommerce route'               => array( '/wp-json/wc-custom/v1/resource', true ),
			'plain permalink woocommerce route'           => array( '/?rest_route=/wc/v3/products', true ),
			'plain permalink third-party route'           => array( '/?rest_route=/wc-custom/v1/resource', true ),
			'index.php plain permalink woocommerce route' => array( '/index.php?rest_route=/wc/v3/products', true ),
			'index.php path permalink woocommerce route'  => array( '/index.php/wp-json/wc/v3/products', true ),
			'rest route overrides woocommerce-looking path' => array( '/wp-json/wc/v3/products?rest_route=/wp/v2/users', false ),
			'rest route overrides non-woocommerce path'   => array( '/wp-json/wp/v2/users?rest_route=/wc/v3/products', true ),
			'plain permalink non-woocommerce route'       => array( '/?rest_route=/wp/v2/users&x=wp-json/wc/', false ),
			'non-woocommerce route with query'            => array( '/wp-json/wp/v2/users?context=edit&x=wp-json/wc/', false ),
			'non-woocommerce path with substring'         => array( '/not-wp-json/wc/v3/products', false ),
			// A character esc_url_raw() strips must not be collapsed into a WooCommerce route prefix.
			'path with stripped character in prefix'      => array( '/wp-json/w^c/v3/products', false ),
			'plain route with stripped character in prefix' => array( '/?rest_route=/w^c/v3/products', false ),
			// PHP populates $_GET from the raw query string, so 'rest_rou^te' is a distinct parameter and
			// WordPress routes this request to /wp/v2/users. Sanitizing the URI first would drop the '^'
			// and change which parameter wins, so the route has to be read from the raw URI.
			'decoy parameter that sanitizing would merge' => array( '/?rest_route=/wp/v2/users&rest_rou^te=/wc/v3/products', false ),
		);
	}

	/**
	 * @testdox Should detect WooCommerce routes on a subdirectory install, matching how WordPress strips the home path.
	 *
	 * @dataProvider provider_subdirectory_request_uris
	 *
	 * @param string $request_uri Request URI.
	 * @param bool   $expected    Expected result.
	 */
	public function test_is_request_to_rest_api_strips_home_path( string $request_uri, bool $expected ): void {
		$home_filter = function () {
			return 'http://example.org/shop';
		};

		add_filter( 'option_home', $home_filter );
		$_SERVER['REQUEST_URI'] = $request_uri;

		try {
			$this->assertSame( $expected, $this->is_request_to_rest_api() );
		} finally {
			remove_filter( 'option_home', $home_filter );
		}
	}

	/**
	 * Data provider for subdirectory install detection, with the site's home path at '/shop'.
	 *
	 * @return array[]
	 */
	public static function provider_subdirectory_request_uris(): array {
		return array(
			'woocommerce route'                  => array( '/shop/wp-json/wc/v3/products', true ),
			// WP::parse_request() strips the home path case-insensitively, so this still reaches WooCommerce.
			'woocommerce route, home path cased' => array( '/Shop/wp-json/wc/v3/products', true ),
			'index.php permalink'                => array( '/shop/index.php/wp-json/wc/v3/products', true ),
			'non-woocommerce route'              => array( '/shop/wp-json/wp/v2/users', false ),
		);
	}

	/**
	 * @testdox Should only let an authenticated key through when the route WordPress resolved is a WooCommerce one.
	 *
	 * @dataProvider provider_resolved_routes
	 *
	 * @param string $resolved_route Route WordPress resolved for the request.
	 * @param bool   $in_scope       Whether a WooCommerce API key may authenticate it.
	 */
	public function test_reject_out_of_scope_route_checks_resolved_route( string $resolved_route, bool $in_scope ): void {
		global $wp;

		// The request URI stays a WooCommerce one throughout, since the route WordPress resolves does
		// not have to be the one the URI names. The scope check is what has to notice the difference.
		$_SERVER['REQUEST_URI']       = '/wp-json/wc/v3/products';
		$wp->query_vars['rest_route'] = $resolved_route;

		$this->authenticate_as( 1 );

		$result = $this->sut->reject_out_of_scope_route( null );

		if ( $in_scope ) {
			$this->assertNull( $result, 'A WooCommerce route must be left for the endpoint to serve.' );
			return;
		}

		$this->assertWPError( $result, 'A WooCommerce API key must not authenticate a route outside our namespaces.' );
		$this->assertSame( 'woocommerce_rest_authentication_error', $result->get_error_code() );
	}

	/**
	 * Data provider for the routes WordPress may resolve a request to.
	 *
	 * @return array[]
	 */
	public static function provider_resolved_routes(): array {
		return array(
			'woocommerce route'             => array( '/wc/v3/products', true ),
			'third-party woocommerce route' => array( '/wc-custom/v1/resource', true ),
			'no leading slash'              => array( 'wc/v3/products', true ),
			'core users route'              => array( '/wp/v2/users', false ),
			'route index'                   => array( '/', false ),
			'woocommerce route as a suffix' => array( '/wp/v2/users?x=wc/v3/products', false ),
		);
	}

	/**
	 * @testdox Should let an authenticated key through to a third-party route the request URI named.
	 */
	public function test_reject_out_of_scope_route_allows_matching_third_party_route(): void {
		global $wp;

		// A genuine request to a third-party namespace: the URI names the route WordPress resolved.
		$_SERVER['REQUEST_URI']       = '/wp-json/myplugin/v1/resource';
		$wp->query_vars['rest_route'] = '/myplugin/v1/resource';

		$this->authenticate_as( 1 );

		$this->assertNull(
			$this->sut->reject_out_of_scope_route( null ),
			'A third-party route the URI named must be left for the endpoint to serve.'
		);
	}

	/**
	 * @testdox Should treat a namespace opted in through the woocommerce_rest_is_request_to_rest_api filter as a WooCommerce request.
	 */
	public function test_is_request_to_rest_api_honours_scope_filter(): void {
		$_SERVER['REQUEST_URI'] = '/wp-json/myplugin/v1/resource';

		$this->assertFalse(
			$this->is_request_to_rest_api(),
			'A third-party namespace is not a WooCommerce request until it opts in.'
		);

		add_filter( 'woocommerce_rest_is_request_to_rest_api', '__return_true' );

		try {
			$this->assertTrue(
				$this->is_request_to_rest_api(),
				'A namespace opted in through the filter must be treated as a WooCommerce request.'
			);
		} finally {
			remove_filter( 'woocommerce_rest_is_request_to_rest_api', '__return_true' );
		}
	}

	/**
	 * @testdox Should reject a request whose resolved route is not the one the URI names.
	 */
	public function test_reject_out_of_scope_route_rejects_overridden_third_party_route(): void {
		global $wp;

		// Two different non-WooCommerce namespaces: one named by the URI, one resolved. Rejected.
		$_SERVER['REQUEST_URI']       = '/wp-json/myplugin/v1/resource';
		$wp->query_vars['rest_route'] = '/wp/v2/users';

		$this->authenticate_as( 1 );

		$result = $this->sut->reject_out_of_scope_route( null );

		$this->assertWPError( $result, 'A resolved route the URI never named must be rejected even when the URI names an opted-in namespace.' );
		$this->assertSame( 'woocommerce_rest_authentication_error', $result->get_error_code() );
	}

	/**
	 * @testdox Should reject a route the URI does not name even when the request overrides its method with _method.
	 */
	public function test_reject_out_of_scope_route_rejects_method_override_to_foreign_route(): void {
		global $wp;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Saved and restored verbatim, never output or stored.
		$original_method = $_SERVER['REQUEST_METHOD'] ?? null;

		// The scope check ignores the HTTP method, including a '_method' override in the query string,
		// and only compares the resolved route to the one the URI names. A resolved route the URI never
		// named is rejected either way.
		$_SERVER['REQUEST_METHOD']    = 'GET';
		$_SERVER['REQUEST_URI']       = '/wp-json/wc/v3/products?_method=POST';
		$wp->query_vars['rest_route'] = '/wp/v2/users';

		$this->authenticate_as( 1 );

		try {
			$result = $this->sut->reject_out_of_scope_route( null );

			$this->assertWPError( $result, 'A method override must not let a key reach a route the URI never named.' );
			$this->assertSame( 'woocommerce_rest_authentication_error', $result->get_error_code() );
		} finally {
			if ( null === $original_method ) {
				unset( $_SERVER['REQUEST_METHOD'] );
			} else {
				$_SERVER['REQUEST_METHOD'] = $original_method;
			}
		}
	}

	/**
	 * @testdox Should reject an out-of-scope route when the authentication fallback is what authenticated the key.
	 */
	public function test_rest_authentication_errors_rejects_out_of_scope_route_from_fallback(): void {
		global $wp, $wpdb;

		$consumer_key    = 'ck_' . wp_generate_password( 32, false );
		$consumer_secret = 'cs_' . wp_generate_password( 32, false );

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => 1,
				'description'     => 'Route scope test key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			)
		);

		// authentication_fallback() only authenticates when nothing did so during
		// 'determine_current_user', which is the state left by setUp. Basic auth needs SSL.
		$_SERVER['HTTPS']             = 'on';
		$_SERVER['PHP_AUTH_USER']     = $consumer_key;
		$_SERVER['PHP_AUTH_PW']       = $consumer_secret;
		$_SERVER['REQUEST_URI']       = '/wp-json/wc/v3/products';
		$wp->query_vars['rest_route'] = '/wp/v2/users';

		wp_set_current_user( 0 );

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Running WordPress core's filter so both of our callbacks fire in order; not defining a hook.
			$result = apply_filters( 'rest_authentication_errors', null );

			$this->assertWPError( $result, 'A key authenticated by the fallback must not reach a core REST route.' );
			$this->assertSame( 'woocommerce_rest_authentication_error', $result->get_error_code() );
			$this->assertSame( 401, $result->get_error_data()['status'] );
		} finally {
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( $consumer_key ) ) );
			unset( $_SERVER['HTTPS'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * @testdox Should let the authentication fallback through for a WooCommerce route.
	 */
	public function test_rest_authentication_errors_allows_in_scope_route_from_fallback(): void {
		global $wp, $wpdb;

		$consumer_key    = 'ck_' . wp_generate_password( 32, false );
		$consumer_secret = 'cs_' . wp_generate_password( 32, false );

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => 1,
				'description'     => 'Route scope test key',
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			)
		);

		$_SERVER['HTTPS']             = 'on';
		$_SERVER['PHP_AUTH_USER']     = $consumer_key;
		$_SERVER['PHP_AUTH_PW']       = $consumer_secret;
		$_SERVER['REQUEST_URI']       = '/wp-json/wc/v3/products';
		$wp->query_vars['rest_route'] = '/wc/v3/products';

		wp_set_current_user( 0 );

		try {
			// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Running WordPress core's filter so both of our callbacks fire in order; not defining a hook.
			$authenticated = apply_filters( 'rest_authentication_errors', null );

			$this->assertTrue( $authenticated, 'The fallback must still authenticate a genuine WooCommerce route.' );
		} finally {
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'consumer_key' => wc_api_hash( $consumer_key ) ) );
			unset( $_SERVER['HTTPS'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			wp_set_current_user( 0 );
		}
	}

	/**
	 * @testdox Should stay out of a request no WooCommerce API key authenticated.
	 */
	public function test_reject_out_of_scope_route_ignores_requests_it_did_not_authenticate(): void {
		global $wp;

		$wp->query_vars['rest_route'] = '/wp/v2/users';

		$this->assertNull(
			$this->sut->reject_out_of_scope_route( null ),
			'A request without WooCommerce API key credentials should be left alone.'
		);
	}

	/**
	 * The default behaviour is to record a last_access datetime only once per request.
	 *
	 * This should only be for the 'primary' REST API request, and not programmatically
	 * generated REST API requests used for internal purposes.
	 *
	 * @return void
	 */
	public function test_last_access(): void {
		global $wp;
		$original_request = $wp->request;

		// Prepare the WC_Rest_Authentication instance for testing.
		$wc_rest_authentication = WC_REST_Authentication::instance();
		$update_last_access     = new ReflectionMethod( $wc_rest_authentication, 'update_last_access' );
		$authenticated_user     = new ReflectionProperty( $wc_rest_authentication, 'user' );

		$update_last_access->setAccessible( true );
		$authenticated_user->setAccessible( true );
		$original_authenticated_user = $authenticated_user->getValue( $wc_rest_authentication );
		$authenticated_user->setValue(
			$wc_rest_authentication,
			(object) array(
				'key_id'      => 1,
				'user_id'     => 1,
				'permissions' => 'read_write',
			)
		);

		// Spy on decisions to log REST API access.
		$last_access_updated = false;
		$last_access_spy     = function ( $do_not_record ) use ( &$last_access_updated ) {
			$last_access_updated = ! $do_not_record;
		};
		add_filter( 'woocommerce_disable_rest_api_access_log', $last_access_spy );

		// Test if last_access is updated for programmatic API requests.
		$update_last_access->invoke( $wc_rest_authentication, new WP_REST_Request( 'GET', '/wc/v3/products' ) );
		$this->assertFalse(
			$last_access_updated,
			'If a REST API request is created programmatically, the default is to not update the corresponding last_access time.'
		);

		// Test if last_access is updated for 'real' API requests.
		$wp->request = '/wp-json/wc/v3/products';
		$update_last_access->invoke( $wc_rest_authentication, new WP_REST_Request( 'GET', '/wc/v3/products' ) );
		$this->assertTrue(
			$last_access_updated,
			'If a REST API request is received over HTTP, then by default the corresponding last_access time should be updated.'
		);

		// Clean-up.
		$wp->request = $original_request;
		$authenticated_user->setValue( $wc_rest_authentication, $original_authenticated_user );
		$authenticated_user->setAccessible( false );
		$update_last_access->setAccessible( false );
		add_filter( 'woocommerce_disable_rest_api_access_log', $last_access_spy );
	}
}
