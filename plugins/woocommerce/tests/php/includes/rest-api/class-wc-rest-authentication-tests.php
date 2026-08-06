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
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = WC_REST_Authentication::instance();
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

	/**
	 * @testdox API key query credentials over HTTP should return an HTTPS-required error.
	 */
	public function test_query_string_auth_over_http_returns_https_required_error(): void {
		$this->reset_auth_state();

		$_SERVER['REQUEST_URI']  = '/wp-json/wc/v3/products';
		$_GET['consumer_key']    = 'ck_test';
		$_GET['consumer_secret'] = 'cs_test';
		$_SERVER['HTTPS']        = 'off';
		add_filter( 'is_ssl', '__return_false' );

		$response = $this->do_rest_get_request( '/wc/v3/products' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'woocommerce_rest_authentication_error', $response->get_error_code() );
		$this->assertStringContainsString( 'HTTPS is required', $response->get_error_message() );

		remove_filter( 'is_ssl', '__return_false' );
		unset( $_SERVER['REQUEST_URI'], $_GET['consumer_key'], $_GET['consumer_secret'], $_SERVER['HTTPS'] );
	}

	/**
	 * @testdox Basic auth credentials over HTTP should return an HTTPS-required error.
	 */
	public function test_basic_auth_over_http_returns_https_required_error(): void {
		$this->reset_auth_state();

		$_SERVER['REQUEST_URI']   = '/wp-json/wc/v3/products';
		$_SERVER['PHP_AUTH_USER'] = 'ck_test';
		$_SERVER['PHP_AUTH_PW']   = 'cs_test';
		$_SERVER['HTTPS']         = 'off';
		add_filter( 'is_ssl', '__return_false' );

		$response = $this->do_rest_get_request( '/wc/v3/products' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'woocommerce_rest_authentication_error', $response->get_error_code() );
		$this->assertStringContainsString( 'HTTPS is required', $response->get_error_message() );

		remove_filter( 'is_ssl', '__return_false' );
		unset( $_SERVER['REQUEST_URI'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['HTTPS'] );
	}

	/**
	 * @testdox Invalid consumer key over HTTPS should return a consumer-key-invalid error.
	 */
	public function test_invalid_consumer_key_over_https_returns_error(): void {
		$this->reset_auth_state();

		$_SERVER['REQUEST_URI']   = '/wp-json/wc/v3/products';
		$_SERVER['PHP_AUTH_USER'] = 'ck_nonexistent';
		$_SERVER['PHP_AUTH_PW']   = 'cs_nonexistent';
		$_SERVER['HTTPS']         = 'on';
		add_filter( 'is_ssl', '__return_true' );

		$response = $this->do_rest_get_request( '/wc/v3/products' );

		$this->assertInstanceOf( WP_Error::class, $response );
		$this->assertSame( 'woocommerce_rest_authentication_error', $response->get_error_code() );
		$this->assertSame( 'Consumer key is invalid.', $response->get_error_message() );

		remove_filter( 'is_ssl', '__return_true' );
		unset( $_SERVER['REQUEST_URI'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['HTTPS'] );
	}

	/**
	 * Reset authentication state to prevent cross-test pollution.
	 */
	private function reset_auth_state(): void {
		$error_prop = new ReflectionProperty( $this->sut, 'error' );
		$error_prop->setAccessible( true );
		$error_prop->setValue( $this->sut, null );

		$user_prop = new ReflectionProperty( $this->sut, 'user' );
		$user_prop->setAccessible( true );
		$user_prop->setValue( $this->sut, null );

		$auth_method_prop = new ReflectionProperty( $this->sut, 'auth_method' );
		$auth_method_prop->setAccessible( true );
		$auth_method_prop->setValue( $this->sut, '' );

		$error_prop->setAccessible( false );
		$user_prop->setAccessible( false );
		$auth_method_prop->setAccessible( false );
	}
}
