<?php
/**
 * Class WC_Auth_Test file.
 *
 * @package WooCommerce\Tests\WC_Auth.
 */

/**
 * Class WC_Auth_Test file.
 */
class WC_Auth_Test extends \WC_Unit_Test_Case {

	/**
	 * @testdox Should keep application values from injecting authorization parameters.
	 * @testWith ["user_id"]
	 *           ["app_name"]
	 *
	 * @param string $parameter Parameter carrying the injection attempt.
	 */
	public function test_build_url_encodes_application_values( string $parameter ): void {
		$sut                = new WC_Auth();
		$build_url          = new ReflectionMethod( WC_Auth::class, 'build_url' );
		$data               = $this->get_auth_request_data();
		$data[ $parameter ] = 'Žluťoučký kôň العربية 日本語 🛒&callback_url=https://attacker.example/collect&scope=read_write#';
		$expected           = $data;
		$build_url->setAccessible( true );

		foreach ( array( 'authorize', 'access_granted' ) as $endpoint ) {
			$url = $build_url->invoke( $sut, $data, $endpoint );
			$this->assertNull( wp_parse_url( $url, PHP_URL_FRAGMENT ), 'Application values must not create a URL fragment.' );
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $data );
			foreach ( $expected as $key => $value ) {
				$this->assertSame( $value, $data[ $key ] ?? null, 'Each authorization parameter must remain a single value.' );
			}
		}
	}

	/**
	 * @testdox Should validate and use the same callback URL throughout the authorization flow.
	 * @testWith ["%2523"]
	 *           ["%252F"]
	 *           ["%253F"]
	 *
	 * @param string $encoded_delimiter Nested URL encoding in the callback user info.
	 */
	public function test_build_url_preserves_encoded_callback( string $encoded_delimiter ): void {
		$sut                  = new WC_Auth();
		$build_url            = new ReflectionMethod( WC_Auth::class, 'build_url' );
		$format_url           = new ReflectionMethod( WC_Auth::class, 'get_formatted_url' );
		$data                 = $this->get_auth_request_data();
		$data['callback_url'] = 'https://attacker.example' . $encoded_delimiter . '@trusted-app.example/collect';
		$build_url->setAccessible( true );
		$format_url->setAccessible( true );
		$displayed_callback = $format_url->invoke( $sut, $data['callback_url'] );

		$this->assertSame( 'trusted-app.example', wp_parse_url( $displayed_callback, PHP_URL_HOST ) );
		foreach ( array( 'authorize', 'access_granted' ) as $endpoint ) {
			$url = $build_url->invoke( $sut, $data, $endpoint );
			parse_str( wp_parse_url( $url, PHP_URL_QUERY ), $data );
			$this->assertSame( $displayed_callback, $format_url->invoke( $sut, $data['callback_url'] ), 'The recipient must not change between authorization steps.' );
		}
	}

	/**
	 * @testdox Should preserve supported URLs throughout every authorization step.
	 * @testWith ["https://trusted-app.example/callback?one=1&two=2#section"]
	 *           ["trusted-app.example/callback"]
	 *           ["https://trusted-app.example/callback?next=%2Fdashboard"]
	 *           ["https://xn--caf-dma.example/%25C5%25BE/%25E6%2597%25A5%25E6%259C%25AC?name=%25D8%25B9%25D8%25B1%25D8%25A8%25D9%258A&cart=%25F0%259F%259B%2592#caf%25C3%25A9"]
	 *
	 * @param string $url Application URL to forward.
	 */
	public function test_build_url_preserves_supported_urls( string $url ): void {
		$sut        = new WC_Auth();
		$build_url  = new ReflectionMethod( WC_Auth::class, 'build_url' );
		$format_url = new ReflectionMethod( WC_Auth::class, 'get_formatted_url' );
		$build_url->setAccessible( true );
		$format_url->setAccessible( true );
		$data                 = $this->get_auth_request_data();
		$data['return_url']   = $url;
		$data['callback_url'] = $url;
		$expected_url         = $format_url->invoke( $sut, $url );

		$request_backup = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the request after validating each authorization step.
		try {
			foreach ( array( 'login', 'authorize', 'access_granted' ) as $endpoint ) {
				$_REQUEST = wp_slash( $data );
				$this->get_make_validation_method()->invoke( $sut );
				$next_url = $build_url->invoke( $sut, $data, $endpoint );
				parse_str( wp_parse_url( $next_url, PHP_URL_QUERY ), $data );
				foreach ( array( 'return_url', 'callback_url' ) as $parameter ) {
					$this->assertSame( $url, $data[ $parameter ], 'Transport must preserve the submitted URL.' );
					$this->assertSame( $expected_url, $format_url->invoke( $sut, $data[ $parameter ] ), 'Every step must use the same formatted URL.' );
				}
			}
		} finally {
			$_REQUEST = $request_backup;
		}
	}

	/**
	 * @testdox Should preserve the application user ID in login, denial and successful authorization responses.
	 * @testWith ["login"]
	 *           ["authorize"]
	 *           ["access_granted"]
	 *
	 * @param string $route Authorization route.
	 */
	public function test_auth_responses_preserve_application_user_id( string $route ): void {
		global $wpdb;

		$sut             = new WC_Auth();
		$data            = $this->get_auth_request_data();
		$data['user_id'] = 'Žluťoučký kôň العربية 日本語 🛒&success=unexpected#fragment';
		$request_backup  = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the request after exercising the auth endpoint.
		$get_backup      = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the request after exercising the auth endpoint.
		$buffer_level    = ob_get_level();
		$return_url      = null;
		$consumer_data   = null;
		$stop_response   = new Error( 'Fixture response captured.' );

		wp_set_current_user( 'login' === $route ? 0 : $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$_REQUEST              = wp_slash( $data );
		$_GET['wc_auth_nonce'] = wp_create_nonce( 'wc_auth_grant_access' );

		add_action(
			'woocommerce_before_template_part',
			static function ( $template_name, $template_path, $located, $args ) use ( &$return_url, $stop_response ) {
				unset( $template_name, $template_path, $located );
				$return_url = $args['return_url'];
				throw $stop_response;
			},
			10,
			4
		);
		add_filter(
			'pre_http_request',
			static function ( $response, $args, $url ) use ( &$consumer_data ) {
				unset( $response );
				if ( 'https://trusted-app.example/callback' !== $url ) {
					throw new Error( 'Unexpected credential recipient.' );
				}
				$consumer_data = json_decode( $args['body'], true );
				return array( 'response' => array( 'code' => 200 ) );
			},
			10,
			3
		);
		add_filter(
			'wp_redirect',
			static function ( $url ) use ( &$return_url, $stop_response ) {
				$return_url = $url;
				throw $stop_response;
			}
		);

		$auth_endpoint = new ReflectionMethod( WC_Auth::class, 'auth_endpoint' );
		$auth_endpoint->setAccessible( true );
		try {
			$auth_endpoint->invoke( $sut, $route );
			$this->fail( 'The endpoint should produce a template or redirect.' );
		} catch ( Error $error ) {
			$this->assertSame( $stop_response, $error, 'Only the captured response should interrupt the endpoint.' );
		} finally {
			$_REQUEST = $request_backup;
			$_GET     = $get_backup;
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}

		$this->assertNull( wp_parse_url( $return_url, PHP_URL_FRAGMENT ), 'The user ID must not inject a return URL fragment.' );
		parse_str( wp_parse_url( $return_url, PHP_URL_QUERY ), $query );
		$this->assertSame( $data['user_id'], $query['user_id'], 'The application must receive its original user ID.' );
		$this->assertSame( 'access_granted' === $route ? '1' : '0', $query['success'], 'The user ID must not override the authorization result.' );
		if ( 'access_granted' === $route ) {
			$this->assertSame( 'read', $consumer_data['key_permissions'], 'A legitimate approval must still create the requested read-only key.' );
			$this->assertSame( $data['user_id'], $consumer_data['user_id'], 'The callback must preserve the multilingual application user ID.' );
			$description = $wpdb->get_var( $wpdb->prepare( "SELECT description FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d", $consumer_data['key_id'] ) );
			$this->assertStringStartsWith( $data['app_name'] . ' - API (', $description, 'The API key must store the multilingual application name without corruption.' );
		}
	}

	/**
	 * @testdox Should preserve consent when following the Approve link and delete keys for unsafe callbacks.
	 * @testWith ["1&callback_url=https://attacker.example/collect&scope=read_write#", "https://trusted-app.example/callback", false]
	 *           ["123", "https://attacker.example%2523@127.0.0.1/collect", true]
	 *
	 * @param string $user_id         Application user ID.
	 * @param string $callback_url    Requested credential recipient.
	 * @param bool   $reject_callback Whether WordPress should reject the callback.
	 */
	public function test_approval_preserves_consent_and_rejects_unsafe_callbacks( string $user_id, string $callback_url, bool $reject_callback ): void {
		global $wpdb;

		$sut                  = new WC_Auth();
		$data                 = $this->get_auth_request_data();
		$data['user_id']      = $user_id;
		$data['callback_url'] = $callback_url;
		$data['scope']        = $reject_callback ? 'read_write' : 'read';
		$request_backup       = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the request after following the generated approval URL.
		$get_backup           = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restore the request after following the generated approval URL.
		$buffer_level         = ob_get_level();
		$template_args        = array();
		$consumer_data        = array();
		$created_key          = null;
		$return_url           = null;
		$http_requests        = 0;
		$stop_response        = new Error( 'Fixture response captured.' );
		$auth_endpoint        = new ReflectionMethod( WC_Auth::class, 'auth_endpoint' );
		$auth_endpoint->setAccessible( true );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$_REQUEST = wp_slash( $data );
		$_GET     = array();

		$capture_template = static function ( $template_name, $template_path, $located, $args ) use ( &$template_args, $stop_response ) {
			unset( $template_name, $template_path, $located );
			$template_args = $args;
			throw $stop_response;
		};
		add_action( 'woocommerce_before_template_part', $capture_template, 10, 4 );
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( $reject_callback, &$consumer_data, &$created_key, &$http_requests, $wpdb ) {
				unset( $response );
				++$http_requests;
				$this->assertSame( $reject_callback ? 'https://attacker.example%23@127.0.0.1/collect' : 'https://trusted-app.example/callback', $url, 'Approval must use the callback shown during consent.' );
				$this->assertTrue( $args['reject_unsafe_urls'], 'Callback delivery must retain WordPress URL safety checks.' );
				$consumer_data = json_decode( $args['body'], true );
				$created_key   = $wpdb->get_row( $wpdb->prepare( "SELECT user_id, permissions FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d", $consumer_data['key_id'] ), ARRAY_A );

				// Returning a mock response here would bypass WordPress URL validation.
				return $reject_callback ? false : array( 'response' => array( 'code' => 200 ) );
			},
			10,
			3
		);
		add_filter(
			'wp_redirect',
			static function ( $url ) use ( &$return_url, $stop_response ) {
				$return_url = $url;
				throw $stop_response;
			}
		);

		try {
			try {
				$auth_endpoint->invoke( $sut, 'authorize' );
				$this->fail( 'Authorization should display the consent template.' );
			} catch ( Error $error ) {
				$this->assertSame( $stop_response, $error, 'Only the captured consent template should interrupt authorization.' );
			}
			remove_action( 'woocommerce_before_template_part', $capture_template, 10 );

			$this->assertSame( $reject_callback ? '127.0.0.1' : 'trusted-app.example', wp_parse_url( $template_args['callback_url'], PHP_URL_HOST ), 'Consent must identify the callback host.' );
			$this->assertSame( $reject_callback ? __( 'Read/Write', 'woocommerce' ) : __( 'Read', 'woocommerce' ), $template_args['scope'], 'Consent must show the requested permissions.' );
			$granted_url = html_entity_decode( esc_url( $template_args['granted_url'] ), ENT_QUOTES, 'UTF-8' );
			$this->assertNull( wp_parse_url( $granted_url, PHP_URL_FRAGMENT ), 'The application user ID must not inject a fragment into the Approve link.' );
			parse_str( wp_parse_url( $granted_url, PHP_URL_QUERY ), $query );
			$this->assertSame( $data['scope'], $query['scope'], 'The Approve link must retain the displayed permissions.' );
			$this->assertSame( $callback_url, $query['callback_url'], 'The Approve link must preserve the submitted callback.' );
			$this->assertSame( $user_id, $query['user_id'], 'The application user ID must remain one query value.' );
			$this->assertNotEmpty( $query['wc_auth_nonce'], 'Approval must use the nonce generated by the consent page.' );
			$_GET     = wp_slash( $query );
			$_REQUEST = wp_slash( $query );

			try {
				$auth_endpoint->invoke( $sut, 'access_granted' );
				$this->fail( 'Approval should redirect or deny access.' );
			} catch ( WPDieException $error ) {
				$this->assertTrue( $reject_callback, 'The injection payload must still allow the displayed read-only approval.' );
				$this->assertSame( 401, $error->getCode(), 'Unsafe callback URLs must deny access.' );
				$this->assertStringContainsString( 'A valid URL was not provided.', $error->getMessage(), 'WordPress must reject the callback URL before sending credentials.' );
			} catch ( Error $error ) {
				$this->assertSame( $stop_response, $error, 'Only the captured success redirect should interrupt approval.' );
				$this->assertFalse( $reject_callback, 'Unsafe callback URLs must not complete authorization.' );
			}

			$this->assertSame( 1, $http_requests, 'Approval should attempt exactly one callback.' );
			$this->assertSame( $user_id, $consumer_data['user_id'], 'The callback must receive the application user ID as one value.' );
			$this->assertSame( $data['scope'], $consumer_data['key_permissions'], 'The callback must receive only the approved permissions.' );
			$this->assertSame( (string) get_current_user_id(), $created_key['user_id'], 'The key must belong to the consenting shop manager.' );
			$this->assertSame( $data['scope'], $created_key['permissions'], 'The persisted key must match the displayed permissions.' );
			$key_id = $wpdb->get_var( $wpdb->prepare( "SELECT key_id FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d", $consumer_data['key_id'] ) );
			if ( $reject_callback ) {
				$this->assertNull( $return_url, 'Rejected callbacks must not produce a success redirect.' );
				$this->assertNull( $key_id, 'The key created before callback rejection must be deleted.' );
			} else {
				$this->assertSame( (string) $consumer_data['key_id'], $key_id, 'Successful approval must retain the issued key.' );
				parse_str( wp_parse_url( $return_url, PHP_URL_QUERY ), $query );
				$this->assertSame( '1', $query['success'], 'Successful callback delivery must complete authorization.' );
				$this->assertSame( $user_id, $query['user_id'], 'The success redirect must preserve the application user ID.' );
			}
		} finally {
			$_REQUEST = $request_backup;
			$_GET     = $get_backup;
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Build a valid authorization request.
	 *
	 * @return array
	 */
	private function get_auth_request_data(): array {
		return array(
			'app_name'     => 'Test app Žluťoučký kôň العربية 日本語 🛒',
			'user_id'      => '123',
			'return_url'   => 'https://trusted-app.example/return',
			'callback_url' => 'https://trusted-app.example/callback',
			'scope'        => 'read',
		);
	}

	/**
	 * @testdox Should save long ASCII and multilingual API key descriptions.
	 * @see https://github.com/woocommerce/woocommerce/issues/30594
	 * @testWith ["This_app_name_is_very_long_and_meant_to_exceed_the_column_length_of_200_characters_"]
	 *           ["Žluťoučký kôň العربية 日本語 🛒"]
	 *
	 * @param string $app_name_fragment Text to repeat beyond the description limit.
	 */
	public function test_api_key_long_description( string $app_name_fragment ): void {
		global $wpdb;

		$wc_auth        = new WC_Auth();
		$reflected_auth = new ReflectionClass( WC_Auth::class );
		$create_keys    = $reflected_auth->getMethod( 'create_keys' );
		$create_keys->setAccessible( true );

		$app_name    = str_repeat( $app_name_fragment, 20 );
		$app_user_id = 1;
		$scope       = 'read_write';

		$key_data = $create_keys->invoke( $wc_auth, $app_name, $app_user_id, $scope );

		// Verify the key was inserted successfully.
		$this->assertNotEquals( 0, $key_data['key_id'], 'API Key with long description was not written to database.' );
		$description = $wpdb->get_var( $wpdb->prepare( "SELECT description FROM {$wpdb->prefix}woocommerce_api_keys WHERE key_id = %d", $key_data['key_id'] ) );
		$this->assertStringStartsWith( $app_name_fragment, $description, 'The stored description must preserve the original text.' );
		$this->assertTrue( mb_check_encoding( $description, 'UTF-8' ), 'Trimming must not split a multibyte character.' );

		// Clean up.
		$maybe_delete_key = $reflected_auth->getMethod( 'maybe_delete_key' );
		$maybe_delete_key->setAccessible( true );
		$maybe_delete_key->invoke( $wc_auth, $key_data );
	}

	/**
	 * Get the protected WC_Auth::make_validation() method.
	 *
	 * @return ReflectionMethod
	 */
	private function get_make_validation_method() {
		$make_validation = ( new ReflectionClass( WC_Auth::class ) )->getMethod( 'make_validation' );
		$make_validation->setAccessible( true );

		return $make_validation;
	}

	/**
	 * An array submitted for a handshake parameter is reported as invalid rather than missing, and never
	 * reaches a string-only function.
	 */
	public function test_make_validation_rejects_array_parameters() {
		$request_backup = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Saved and restored so the test can drive make_validation() with a known request.
		$_REQUEST       = array(
			'app_name'     => 'Test app',
			'user_id'      => '123',
			'return_url'   => array( 'https://example.com/return' ),
			'callback_url' => 'https://example.com/callback',
			'scope'        => 'read',
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid parameter return_url' );

		try {
			$this->get_make_validation_method()->invoke( new WC_Auth() );
		} finally {
			$_REQUEST = $request_backup;
		}
	}
}
