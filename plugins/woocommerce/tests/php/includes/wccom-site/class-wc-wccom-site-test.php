<?php
declare( strict_types = 1 );

/**
 * Tests for how WC_WCCOM_Site recognizes and authenticates WooCommerce.com REST requests.
 */
class WC_WCCOM_Site_Test extends WC_Unit_Test_Case {

	private const ACCESS_TOKEN = 'test-access-token';

	private const ACCESS_TOKEN_SECRET = 'test-access-token-secret';

	private const SERVER_KEYS = array( 'REQUEST_URI', 'REQUEST_METHOD', 'HTTP_HOST', 'HTTP_AUTHORIZATION', 'HTTP_X_WOO_SIGNATURE' );

	/**
	 * $_SERVER values the test overwrites, as they were before it ran.
	 *
	 * @var array<string, mixed>
	 */
	private $original_server = array();

	/**
	 * $_GET and $_REQUEST before the test ran.
	 *
	 * @var array<string, array>
	 */
	private $original_request_globals = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		foreach ( self::SERVER_KEYS as $key ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Restored verbatim in tearDown.
			$this->original_server[ $key ] = $_SERVER[ $key ] ?? null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restored verbatim in tearDown.
		$this->original_request_globals = array( $_GET, $_REQUEST );

		wp_set_current_user( 0 );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		foreach ( $this->original_server as $key => $value ) {
			if ( null === $value ) {
				unset( $_SERVER[ $key ] );
			} else {
				$_SERVER[ $key ] = $value;
			}
		}

		list( $_GET, $_REQUEST ) = $this->original_request_globals;

		parent::tearDown();
	}

	/**
	 * Set up a request the way PHP and WordPress would see it.
	 *
	 * @param string $request_uri Request URI.
	 * @param bool   $dispatching Whether WordPress is dispatching the request as REST.
	 */
	private function simulate_request( string $request_uri, bool $dispatching ): void {
		$_SERVER['REQUEST_URI'] = $request_uri;

		$query_params = array();
		parse_str( (string) wp_parse_url( $request_uri, PHP_URL_QUERY ), $query_params );
		$_GET     = $query_params;
		$_REQUEST = $query_params;

		add_filter( 'wp_is_rest_endpoint', $dispatching ? '__return_true' : '__return_false' );
	}

	/**
	 * Connect the site to WooCommerce.com and sign the current request with its credentials.
	 *
	 * @param int    $user_id     User the connection belongs to.
	 * @param string $request_uri Request URI to sign.
	 */
	private function sign_request_for_connected_site( int $user_id, string $request_uri ): void {
		WC_Helper_Options::update(
			'auth',
			array(
				'access_token'        => self::ACCESS_TOKEN,
				'access_token_secret' => self::ACCESS_TOKEN_SECRET,
				'user_id'             => $user_id,
			)
		);

		$_SERVER['HTTP_HOST']          = 'example.org';
		$_SERVER['REQUEST_METHOD']     = 'GET';
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . self::ACCESS_TOKEN;

		$signed_data = array(
			'host'        => 'example.org',
			'request_uri' => urldecode( remove_query_arg( array( 'token', 'signature' ), $request_uri ) ),
			'method'      => 'GET',
		);

		$_SERVER['HTTP_X_WOO_SIGNATURE'] = hash_hmac( 'sha256', wp_json_encode( $signed_data ), self::ACCESS_TOKEN_SECRET );
	}

	/**
	 * @testdox Should recognize a WooCommerce.com REST request only by the route WordPress dispatches.
	 *
	 * @testWith ["/wp-json/wccom-site/v3/status", false, null, true]
	 *           ["/blog/wp-json/wccom-site/v3/status", false, null, true]
	 *           ["/index.php/wp-json/wccom-site/v3/status", false, null, true]
	 *           ["/wp-json/wccom-site/v3/status", true, "/wccom-site/v3/status", true]
	 *           ["/wp-json/wccom-site/v3/status?rest_route=/wp/v2/users", false, null, false]
	 *           ["/wp-json/wc/v3/products", false, null, false]
	 *           ["/shop/?/wp-json/wccom-site/=1", false, null, false]
	 *           ["/wp-json/wccom-site/v3/status", true, "/wp/v2/users", false]
	 *           ["/wp-admin/admin-ajax.php?rest_route=/wccom-site/v3/status", false, null, false]
	 *           ["/wp-admin/admin-ajax.php?rest_route=/wccom-site/v3/status", false, "/wccom-site/v3/status", false]
	 *           ["/?rest_route=/wccom-site/v3/status", true, "/wccom-site/v3/status", true]
	 *           ["/?rest_route[]=/wccom-site/v3/status", true, null, false]
	 *
	 * @param string      $request_uri    Request URI.
	 * @param bool        $dispatching    Whether WordPress is dispatching the request as REST.
	 * @param string|null $resolved_route Route WordPress resolved.
	 * @param bool        $expected       Expected result.
	 */
	public function test_is_request_to_wccom_site_rest_api( string $request_uri, bool $dispatching, ?string $resolved_route, bool $expected ): void {
		$this->simulate_request( $request_uri, $dispatching );

		$method = new ReflectionMethod( WC_WCCOM_Site::class, 'is_request_to_wccom_site_rest_api' );
		$method->setAccessible( true );

		$this->assertSame( $expected, self::with_rest_route_context( $resolved_route, fn() => $method->invoke( null ) ) );
	}

	/**
	 * @testdox Should leave the current user alone on a request with an array rest_route parameter.
	 */
	public function test_authenticate_wccom_ignores_array_rest_route(): void {
		$this->simulate_request( '/?rest_route[]=x', false );

		$this->assertFalse( self::with_rest_route_context( null, fn() => WC_WCCOM_Site::authenticate_wccom( false ) ) );
	}

	/**
	 * @testdox Should authenticate a signed pretty-permalink request as the connected user before the route is resolved.
	 */
	public function test_authenticate_wccom_accepts_signed_pretty_permalink_request(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->simulate_request( '/wp-json/wccom-site/v3/status', false );
		$this->sign_request_for_connected_site( $user_id, '/wp-json/wccom-site/v3/status' );

		$user = self::with_rest_route_context( null, fn() => WC_WCCOM_Site::authenticate_wccom( false ) );

		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( $user_id, $user->ID );
	}

	/**
	 * @testdox Should authenticate a signed plain-permalink request as the connected user once the route is resolved.
	 */
	public function test_authentication_fallback_sets_connected_user(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->simulate_request( '/?rest_route=/wccom-site/v3/status', true );
		$this->sign_request_for_connected_site( $user_id, '/?rest_route=/wccom-site/v3/status' );

		$this->assertTrue( self::with_rest_route_context( '/wccom-site/v3/status', fn() => WC_WCCOM_Site::authentication_fallback( null ) ) );
		$this->assertSame( $user_id, get_current_user_id() );
	}

	/**
	 * @testdox Should pass the previous result through for a signed request to a route outside wccom-site.
	 */
	public function test_authentication_fallback_ignores_other_routes(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->simulate_request( '/?rest_route=/wc/v3/products', true );
		$this->sign_request_for_connected_site( $user_id, '/?rest_route=/wc/v3/products' );

		$this->assertNull( self::with_rest_route_context( '/wc/v3/products', fn() => WC_WCCOM_Site::authentication_fallback( null ) ) );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @testdox Should not authenticate a plain-permalink request with an invalid signature.
	 */
	public function test_authentication_fallback_rejects_invalid_signature(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->simulate_request( '/?rest_route=/wccom-site/v3/status', true );
		$this->sign_request_for_connected_site( $user_id, '/?rest_route=/wccom-site/v3/status' );
		$_SERVER['HTTP_X_WOO_SIGNATURE'] = 'invalid';

		$this->assertNull( self::with_rest_route_context( '/wccom-site/v3/status', fn() => WC_WCCOM_Site::authentication_fallback( null ) ) );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @testdox Should pass through a result another authentication handler already returned.
	 */
	public function test_authentication_fallback_keeps_earlier_result(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->simulate_request( '/?rest_route=/wccom-site/v3/status', true );
		$this->sign_request_for_connected_site( $user_id, '/?rest_route=/wccom-site/v3/status' );
		$error = new WP_Error( 'other_handler', 'Rejected elsewhere.' );

		$this->assertSame( $error, self::with_rest_route_context( '/wccom-site/v3/status', fn() => WC_WCCOM_Site::authentication_fallback( $error ) ) );
		$this->assertSame( 0, get_current_user_id() );
	}

	/**
	 * @testdox Should keep the current user when one is already set.
	 */
	public function test_authentication_fallback_keeps_existing_user(): void {
		$connected_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$current_user_id   = self::factory()->user->create( array( 'role' => 'customer' ) );
		$this->simulate_request( '/?rest_route=/wccom-site/v3/status', true );
		$this->sign_request_for_connected_site( $connected_user_id, '/?rest_route=/wccom-site/v3/status' );
		wp_set_current_user( $current_user_id );

		$this->assertNull( self::with_rest_route_context( '/wccom-site/v3/status', fn() => WC_WCCOM_Site::authentication_fallback( null ) ) );
		$this->assertSame( $current_user_id, get_current_user_id() );
	}
}
