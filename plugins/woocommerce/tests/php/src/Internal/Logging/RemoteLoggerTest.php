<?php
declare( strict_types = 1 );

// phpcs:disable Universal.Namespaces.DisallowCurlyBraceSyntax.Forbidden -- need to override filter_input
// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- same
// phpcs:disable Universal.Namespaces.OneDeclarationPerFile.MultipleFound -- same
namespace Automattic\WooCommerce\Tests\Internal\Logging {

	use Automattic\Jetpack\Constants;
	use Automattic\WooCommerce\Internal\Logging\RemoteLogger;
	use Automattic\WooCommerce\Utilities\StringUtil;
	use WC_Rate_Limiter;
	use WC_Cache_Helper;
	/**
	 * Class RemoteLoggerTest.
	 */
	class RemoteLoggerTest extends \WC_Unit_Test_Case {
		/**
		 * System under test.
		 *
		 * @var RemoteLogger
		 */
		private $sut;

		/**
		 * Set up test
		 *
		 * @return void
		 */
		public function setUp(): void {
			parent::setUp();
			$this->sut = wc_get_container()->get( RemoteLogger::class );
		}

		/**
		 * Tear down.
		 *
		 * @return void
		 */
		public function tearDown(): void {
			$this->cleanup_filters();
			delete_option( 'woocommerce_feature_remote_logging_enabled' );
			delete_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT );
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_rate_limits" );
			WC_Cache_Helper::invalidate_cache_group( WC_Rate_Limiter::CACHE_GROUP );
			Constants::clear_constants();
		}

		/**
		 * Cleanup filters used in tests.
		 *
		 * @return void
		 */
		private function cleanup_filters() {
			$filters = array(
				'option_woocommerce_admin_remote_feature_enabled',
				'option_woocommerce_allow_tracking',
				'option_woocommerce_version',
				'plugins_api',
				'pre_http_request',
				'woocommerce_remote_logger_formatted_log_data',
				'pre_site_transient_update_plugins',
				'woocommerce_remote_logger_request_uri_whitelist',
			);
			foreach ( $filters as $filter ) {
				remove_all_filters( $filter );
			}
		}

		/**
		 * @testdox Remote logging is allowed when all conditions are met
		 */
		public function test_remote_logging_allowed() {
			$this->setup_remote_logging_conditions( true );
			$this->assertTrue( $this->sut->is_remote_logging_allowed() );
		}

		/**
		 * @testdox Remote logging is not allowed under various conditions
		 * @dataProvider remote_logging_disallowed_provider
		 *
		 * @param string   $condition      The condition being tested.
		 * @param callable $setup_callback Callback to set up the test condition.
		 */
		public function test_remote_logging_not_allowed( $condition, $setup_callback ) {
			$this->setup_remote_logging_conditions( true );
			$setup_callback( $this );
			$this->assertFalse( $this->sut->is_remote_logging_allowed() );
		}

		/**
		 * Data provider for test_remote_logging_not_allowed.
		 *
		 * @return array[] Test cases with conditions and setup callbacks.
		 */
		public function remote_logging_disallowed_provider() {
			return array(
				'feature flag disabled' => array(
					'condition' => 'feature flag disabled',
					'setup'     => fn() => update_option( 'woocommerce_feature_remote_logging_enabled', 'no' ),
				),
				'tracking opted out'    => array(
					'condition' => 'tracking opted out',
					'setup'     => fn() => add_filter( 'option_woocommerce_allow_tracking', fn() => 'no' ),
				),
				'outdated version'      => array(
					'condition' => 'outdated version',
					'setup'     => function () {
						$version = WC()->version;
						// Next major version. (e.g. 9.0.1 -> 10.0.0).
						$next_version = sprintf( '%d.0.0', explode( '.', $version, 2 )[0] + 1 );

						set_site_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT, $next_version, WEEK_IN_SECONDS );
					},
				),
			);
		}

		/**
		 * @testdox should_current_version_be_logged method behaves correctly
		 * @dataProvider should_current_version_be_logged_provider
		 *
		 * @param string $current_version The current WooCommerce version.
		 * @param string $new_version The new WooCommerce version.
		 * @param string $transient_value The value of the transient.
		 * @param bool   $expected The expected result.
		 */
		public function test_should_current_version_be_logged( $current_version, $new_version, $transient_value, $expected ) {
			Constants::set_constant( 'WC_VERSION', $current_version );

			// Set up the transient.
			if ( null !== $transient_value ) {
				set_site_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT, $transient_value, WEEK_IN_SECONDS );
			} else {
				delete_site_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT );

				$this->setup_mock_plugin_updates( $new_version );
			}

			$result = $this->invoke_private_method( $this->sut, 'should_current_version_be_logged', array() );
			$this->assertEquals( $expected, $result );

			// Clean up.
			delete_site_transient( RemoteLogger::WC_NEW_VERSION_TRANSIENT );
		}

		/**
		 * Data provider for test_should_current_version_be_logged.
		 */
		public function should_current_version_be_logged_provider() {
			return array(
				'current version is latest (transient set)' => array( '9.2.0', '9.2.0', '9.2.0', true ),
				'current version is newer (transient set)' => array( '9.3.0', '9.2.0', '9.2.0', true ),
				'current version is older (transient set)' => array( '9.1.0', '9.2.0', '9.2.0', false ),
				'new version is null (transient set)'      => array( '9.2.0', null, null, true ),
				'transient not set, current version is latest' => array( '9.2.0', '9.2.0', null, true ),
				'transient not set, current version is newer' => array( '9.3.0', '9.2.0', null, true ),
				'transient not set, current version is older' => array( '9.1.0', '9.2.0', null, false ),
				'transient not set, new version is null'   => array( '9.2.0', null, null, true ),
			);
		}

		/**
		 * @testdox fetch_new_woocommerce_version method returns correct version
		 */
		public function test_fetch_new_woocommerce_version() {
			$this->setup_mock_plugin_updates( '9.3.0' );

			$result = $this->invoke_private_method( $this->sut, 'fetch_new_woocommerce_version', array() );
			$this->assertEquals( '9.3.0', $result, 'The result should be the latest version when an update is available.' );
		}

		/**
		 * @testdox fetch_new_woocommerce_version method returns null when no update is available
		 */
		public function test_fetch_new_woocommerce_version_no_update() {
			add_filter( 'pre_site_transient_update_plugins', fn() => array() );

			$result = $this->invoke_private_method( $this->sut, 'fetch_new_woocommerce_version', array() );
			$this->assertNull( $result, 'The result should be null when no update is available.' );
		}


		/**
		 * @testdox get_formatted_log method returns expected array structure
		 * @dataProvider get_formatted_log_provider
		 *
		 * @param string $level    The log level.
		 * @param string $message  The log message.
		 * @param array  $context  The log context.
		 * @param array  $expected The expected formatted log array.
		 */
		public function test_get_formatted_log( $level, $message, $context, $expected ) {
			$formatted_log = $this->sut->get_formatted_log( $level, $message, $context );
			foreach ( $expected as $key => $value ) {
				$this->assertArrayHasKey( $key, $formatted_log );
				$this->assertEquals( $value, $formatted_log[ $key ] );
			}
		}

		/**
		 * Data provider for test_get_formatted_log.
		 *
		 * @return array[] Test cases with log data and expected formatted output.
		 */
		public function get_formatted_log_provider() {
			return array(
				'basic log data'            => array(
					'error',
					'Fatal error occurred at line 123 in ' . ABSPATH . 'wp-content/file.php',
					array( 'tags' => array( 'tag1', 'tag2' ) ),
					array(
						'feature'  => 'woocommerce_core',
						'severity' => 'error',
						'message'  => 'Fatal error occurred at line 123 in ./wp-content/file.php',
						'tags'     => array( 'woocommerce', 'php', 'tag1', 'tag2' ),
					),
				),
				'log with backtrace'        => array(
					'error',
					'Test error message',
					array( 'backtrace' => ABSPATH . 'wp-content/plugins/woocommerce/file.php' ),
					array( 'trace' => './woocommerce/file.php' ),
				),
				'log with extra attributes' => array(
					'error',
					'Test error message',
					array(
						'extra' => array(
							'key1' => 'value1',
							'key2' => 'value2',
						),
					),
					array(
						'extra' => array(
							'key1' => 'value1',
							'key2' => 'value2',
						),
					),
				),
				'log with error file'       => array(
					'error',
					'Test error message',
					array( 'error' => array( 'file' => WC_ABSPATH . 'includes/class-wc-test.php' ) ),
					array(
						'file' => './woocommerce/includes/class-wc-test.php',
					),
				),
			);
		}


		/**
		 * @testdox get_formatted_log method correctly sanitizes request URI
		 */
		public function test_get_formatted_log_sanitizes_request_uri() {
			global $mock_filter_input, $mock_return;
			$mock_filter_input = true;
			$mock_return       = '/shop?path=home&user=admin&token=abc123';

			$formatted_log = $this->sut->get_formatted_log( 'error', 'Test message', array() );

			$mock_filter_input = false;

			$this->assertArrayHasKey( 'properties', $formatted_log );
			$this->assertArrayHasKey( 'request_uri', $formatted_log['properties'] );
			$this->assertNotNull( $formatted_log['properties']['request_uri'], 'Request URI should not be null' );
			$this->assertStringContainsString( 'path=home', $formatted_log['properties']['request_uri'] );
			$this->assertStringContainsString( 'user=xxxxxx', $formatted_log['properties']['request_uri'] );
			$this->assertStringContainsString( 'token=xxxxxx', $formatted_log['properties']['request_uri'] );
		}

		/**
		 * @testdox sanitize_request_uri method respects whitelist filter
		 */
		public function test_sanitize_request_uri_respects_whitelist_filter() {
			add_filter(
				'woocommerce_remote_logger_request_uri_whitelist',
				function ( $whitelist ) {
					$whitelist[] = 'custom_param';
					return $whitelist;
				}
			);

			$request_uri   = '/shop?path=home&custom_param=value&token=abc123';
			$sanitized_uri = $this->invoke_private_method( $this->sut, 'sanitize_request_uri', array( $request_uri ) );
			$this->assertStringContainsString( 'path=home', $sanitized_uri );
			$this->assertStringContainsString( 'custom_param=value', $sanitized_uri );
			$this->assertStringContainsString( 'token=xxxxxx', $sanitized_uri );
		}

		/**
		 * @testdox sanitize_request_uri method correctly sanitizes request URIs
		 */
		public function test_sanitize_request_uri() {
			$reflection = new \ReflectionClass( $this->sut );
			$method     = $reflection->getMethod( 'sanitize_request_uri' );
			$method->setAccessible( true );

			// Test with whitelisted parameters.
			$request_uri   = '/shop?path=home&page=2&step=1&task=checkout';
			$sanitized_uri = $method->invokeArgs( $this->sut, array( $request_uri ) );
			$this->assertStringContainsString( 'path=home', $sanitized_uri );
			$this->assertStringContainsString( 'page=2', $sanitized_uri );
			$this->assertStringContainsString( 'step=1', $sanitized_uri );
			$this->assertStringContainsString( 'task=checkout', $sanitized_uri );

			// Test with non-whitelisted parameters.
			$request_uri   = '/shop?path=home&user=admin&token=abc123';
			$sanitized_uri = $method->invokeArgs( $this->sut, array( $request_uri ) );
			$this->assertStringContainsString( 'path=home', $sanitized_uri );
			$this->assertStringContainsString( 'user=xxxxxx', $sanitized_uri );
			$this->assertStringContainsString( 'token=xxxxxx', $sanitized_uri );

			// Test with mixed parameters.
			$request_uri   = '/shop?path=home&page=2&user=admin&step=1&token=abc123';
			$sanitized_uri = $method->invokeArgs( $this->sut, array( $request_uri ) );
			$this->assertStringContainsString( 'path=home', $sanitized_uri );
			$this->assertStringContainsString( 'page=2', $sanitized_uri );
			$this->assertStringContainsString( 'step=1', $sanitized_uri );
			$this->assertStringContainsString( 'user=xxxxxx', $sanitized_uri );
			$this->assertStringContainsString( 'token=xxxxxx', $sanitized_uri );
		}

		/**
		 * @testdox should_handle method behaves correctly under different conditions
		 * @dataProvider should_handle_provider
		 *
		 * @param callable $setup   Function to set up the test environment.
		 * @param string   $level   Log level to test.
		 * @param bool     $expected Expected result of should_handle method.
		 */
		public function test_should_handle( $setup, $level, $expected ) {
			$this->sut = $this->getMockBuilder( RemoteLogger::class )
			->onlyMethods( array( 'is_remote_logging_allowed', 'is_third_party_error' ) )
			->getMock();

			$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );
			$this->sut->method( 'is_third_party_error' )->willReturn( false );

			$setup( $this );

			$result = $this->invoke_private_method( $this->sut, 'should_handle', array( $level, 'Test message', array( 'remote-logging' => true ) ) );
			$this->assertEquals( $expected, $result );
		}

		/**
		 * Data provider for test_should_handle method.
		 *
		 * @return array Test cases for should_handle method.
		 */
		public function should_handle_provider() {
			return array(
				'throttled'                 => array(
					fn() => WC_Rate_Limiter::set_rate_limit( RemoteLogger::RATE_LIMIT_ID, 10 ),
					'critical',
					false,
				),
				'less severe than critical' => array(
					fn() => null,
					'error',
					true,
				),
				'critical level'            => array(
					fn() => null,
					'critical',
					true,
				),
			);
		}

		/**
		 * @testdox Test should_handle returns false without remote-logging context
		 */
		public function test_should_handle_no_remote_logging_context() {
			$result = $this->invoke_private_method( $this->sut, 'should_handle', array( 'error', 'Test message', array() ) );
			$this->assertFalse( $result, 'should_handle should return false without remote-logging context' );
		}

		/**
		 * @testdox handle method applies filter and doesn't send logs when filtered to null
		 */
		public function test_handle_filtered_log_null() {
			$this->sut = $this->getMockBuilder( RemoteLogger::class )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

			$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

			add_filter( 'woocommerce_remote_logger_formatted_log_data', fn() => null, 10, 4 );
			add_filter( 'pre_http_request', fn() => $this->fail( 'wp_safe_remote_post should not be called' ), 10, 3 );

			$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array( 'remote-logging' => true ) ) );
		}

		/**
		 * @testdox handle method does not send logs in dev environment
		 */
		public function test_handle_does_not_send_logs_in_dev_environment() {
			$this->sut = $this->getMockBuilder( RemoteLoggerWithEnvironmentOverride::class )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

			$this->sut->set_is_dev_or_local( true );
			$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

			$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array( 'remote-logging' => true ) ) );
		}

		/**
		 * @testdox handle method successfully sends log
		 */
		public function test_handle_successful() {
			$this->sut = $this->getMockBuilder( RemoteLoggerWithEnvironmentOverride::class )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

			$this->sut->set_is_dev_or_local( false );
			$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

			add_filter(
				'pre_http_request',
				function ( $preempt, $args ) {
					$this->assertArrayHasKey( 'body', $args );
					$this->assertArrayHasKey( 'headers', $args );
					return array(
						'response' => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'body'     => wp_json_encode( array( 'success' => true ) ),
					);
				},
				10,
				3
			);

			$this->assertTrue( $this->sut->handle( time(), 'critical', 'Test message', array( 'remote-logging' => true ) ) );
			$this->assertTrue( WC_Rate_Limiter::retried_too_soon( RemoteLogger::RATE_LIMIT_ID ) );
		}

		/**
		 * @testdox handle method handles remote logging failure
		 */
		public function test_handle_remote_logging_failure() {
			$this->sut = $this->getMockBuilder( RemoteLoggerWithEnvironmentOverride::class )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

			$this->sut->set_is_dev_or_local( false );
			$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

			add_filter(
				'pre_http_request',
				function ( $preempt, $args, $url ) {
					if ( 'https://public-api.wordpress.com/rest/v1.1/logstash' === $url ) {
						throw new \Exception( 'Remote logging failed: A valid URL was not provided.' );
					}
					return $preempt;
				},
				10,
				3
			);

			$this->assertFalse( $this->sut->handle( time(), 'critical', 'Test message', array( 'remote-logging' => true ) ) );
			$this->assertTrue( WC_Rate_Limiter::retried_too_soon( RemoteLogger::RATE_LIMIT_ID ) );
		}

		/**
		 * @testdox is_third_party_error method correctly identifies third-party errors
		 * @dataProvider is_third_party_error_provider
		 * @param string $message The error message to check.
		 * @param array  $context The context of the error.
		 * @param bool   $expected_result The expected result of the check.
		 */
		public function test_is_third_party_error( $message, $context, $expected_result ) {
			$result = $this->invoke_private_method( $this->sut, 'is_third_party_error', array( $message, $context ) );
			$this->assertEquals( $expected_result, $result );
		}

		/**
		 * Data provider for test_is_third_party_error.
		 *
		 * @return array[] Test cases.
		 */
		public function is_third_party_error_provider() {
			$wc_plugin_dir   = StringUtil::normalize_local_path_slashes( WC_ABSPATH );
			$wp_includes_dir = StringUtil::normalize_local_path_slashes( ABSPATH . WPINC );
			$wp_admin_dir    = StringUtil::normalize_local_path_slashes( ABSPATH . 'wp-admin' );

			return array(
				'WooCommerce error message' => array(
					'Error in ' . $wc_plugin_dir . 'includes/class-wc-cart.php',
					array(
						'source'    => 'fatal-errors',
						'backtrace' => array(),
					),
					false,
				),
				'Third-party error message' => array(
					'Error in /plugins/some-other-plugin/file.php',
					array(
						'source'    => 'fatal-errors',
						'backtrace' => array(),
					),
					true,
				),
				'WooCommerce backtrace'     => array(
					'Some error message',
					array(
						'source'    => 'fatal-errors',
						'backtrace' => array(
							$wp_includes_dir . 'functions.php',
							$wc_plugin_dir . 'includes/class-wc-cart.php',
							'/plugins/some-other-plugin/file.php',
						),
					),
					false,
				),
				'Third-party backtrace'     => array(
					'Some error message',
					array(
						'source'    => 'fatal-errors',
						'backtrace' => array(
							$wp_includes_dir . 'functions.php',
							$wp_admin_dir . 'admin.php',
							'/plugins/some-other-plugin/file.php',
						),
					),
					true,
				),
				'Non-fatal-errors source'   => array(
					'Some error message',
					array(
						'source'    => 'other-source',
						'backtrace' => array(),
					),
					false,
				),
				'Missing backtrace'         => array(
					'Some error message',
					array( 'source' => 'fatal-errors' ),
					true,
				),
			);
		}

		/**
		 * @testdox sanitize method correctly sanitizes paths
		 */
		public function test_sanitize() {
			$message  = WC_ABSPATH . 'includes/class-wc-test.php on line 123';
			$expected = './woocommerce/includes/class-wc-test.php on line 123';
			$result   = $this->invoke_private_method( $this->sut, 'sanitize', array( $message ) );
			$this->assertEquals( $expected, $result );
		}

		/**
		 * @testdox sanitize_trace method correctly sanitizes stack traces
		 */
		public function test_sanitize_trace() {
			$trace    = array(
				WC_ABSPATH . 'includes/class-wc-test.php:123',
				ABSPATH . 'wp-includes/plugin.php:456',
			);
			$expected = "./woocommerce/includes/class-wc-test.php:123\n./wp-includes/plugin.php:456";
			$result   = $this->invoke_private_method( $this->sut, 'sanitize_trace', array( $trace ) );
			$this->assertEquals( $expected, $result );
		}

		/**
		 * @testdox redact_user_data method correctly redacts sensitive information
		 * @dataProvider redact_user_data_provider
		 *
		 * @param string $input    The input string containing sensitive data.
		 * @param string $expected The expected output with redacted data.
		 */
		public function test_redact_user_data( $input, $expected ) {
			$result = $this->invoke_private_method( $this->sut, 'redact_user_data', array( $input ) );
			$this->assertEquals( $expected, $result );
		}

		/**
		 * Data provider for test_redact_user_data.
		 *
		 * @return array[] Test cases with input strings and expected redacted outputs.
		 */
		public function redact_user_data_provider() {
			return array(
				'email address'                        => array(
					'input'    => 'User email is john.doe@example.com',
					'expected' => 'User email is [redacted_email]',
				),
				'complex email address'                => array(
					'input'    => 'Email: test.user+label@sub-domain.example.co.uk',
					'expected' => 'Email: [redacted_email]',
				),
				'international phone with parentheses' => array(
					'input'    => 'Phone: +1 (123) 456 7890',
					'expected' => 'Phone: [redacted_phone]',
				),
				'international phone with dashes'      => array(
					'input'    => 'Contact at +44-123-456-7890',
					'expected' => 'Contact at [redacted_phone]',
				),
				'simple phone number'                  => array(
					'input'    => 'Call 1234567890',
					'expected' => 'Call [redacted_phone]',
				),
				'formatted phone number'               => array(
					'input'    => 'Phone: (123) 456-7890',
					'expected' => 'Phone: [redacted_phone]',
				),
				'should not match short number'        => array(
					'input'    => 'Order #123 status',
					'expected' => 'Order #123 status',
				),
				'should not match medium number'       => array(
					'input'    => 'Product 12345',
					'expected' => 'Product 12345',
				),
				'IP address'                           => array(
					'input'    => 'User IP: 192.168.1.1',
					'expected' => 'User IP: [redacted_ip]',
				),
				'credit card number spaced'            => array(
					'input'    => 'Card: 4111 1111 1111 1111',
					'expected' => 'Card: [redacted_credit_card]',
				),
				'mixed sensitive data'                 => array(
					'input'    => 'Contact: user@example.com, Tel: +1-234-567-8900, IP: 192.168.0.1, Card: 4111 1111 1111 1111',
					'expected' => 'Contact: [redacted_email], Tel: [redacted_phone], IP: [redacted_ip], Card: [redacted_credit_card]',
				),
				'numbers in text'                      => array(
					'input'    => 'Order #123 had 456 items costing $789',
					'expected' => 'Order #123 had 456 items costing $789',
				),
				'generic api key'                      => array(
					'input'    => 'API Key: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0',
					'expected' => 'API Key: [redacted_api_key]',
				),
				'hex api key'                          => array(
					'input'    => 'Key: 1234567890abcdef1234567890abcdef',
					'expected' => 'Key: [redacted_api_key]',
				),
				'segmented api key'                    => array(
					'input'    => 'Access Key: ABCD-1234-EFGH-5678',
					'expected' => 'Access Key: [redacted_api_key]',
				),
				'multiple api keys'                    => array(
					'input'    => 'Keys: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0, ABCD-1234-EFGH-5678, sk_fakekey0123456789abcdefg',
					'expected' => 'Keys: [redacted_api_key], [redacted_api_key], [redacted_api_key]',
				),
			);
		}

		/**
		 * @testdox sanitize method applies custom sanitization filter
		 */
		public function test_sanitize_with_custom_filter() {
			add_filter(
				'woocommerce_remote_logger_sanitized_content',
				function ( $sanitized ) {
					return str_replace( 'test', 'filtered', $sanitized );
				},
				10,
				2
			);

			$message  = WC_ABSPATH . 'includes/class-wc-test.php on line 123';
			$expected = './woocommerce/includes/class-wc-filtered.php on line 123';
			$result   = $this->invoke_private_method( $this->sut, 'sanitize', array( $message ) );
			$this->assertEquals( $expected, $result );
		}

		/**
		 * Setup common conditions for remote logging tests.
		 *
		 * @param bool $enabled Whether remote logging is enabled.
		 */
		private function setup_remote_logging_conditions( $enabled = true ) {
			update_option( 'woocommerce_feature_remote_logging_enabled', $enabled ? 'yes' : 'no' );
			add_filter( 'option_woocommerce_allow_tracking', fn() => 'yes' );
			$this->setup_mock_plugin_updates( $enabled ? WC()->version : '9.0.0' );
		}

			/**
			 * Set up mock plugin updates.
			 *
			 * @param string $new_version The new version of WooCommerce to simulate.
			 */
		private function setup_mock_plugin_updates( $new_version ) {
			$update_plugins = (object) array(
				'response' => array(
					WC_PLUGIN_BASENAME => (object) array(
						'new_version' => $new_version,
						'package'     => 'https://downloads.wordpress.org/plugin/woocommerce.zip',
						'slug'        => 'woocommerce',
					),
				),
			);
			add_filter( 'pre_site_transient_update_plugins', fn() => $update_plugins );
		}

		/**
		 * Helper method to invoke private methods.
		 *
		 * @param object $obj     Object instance.
		 * @param string $method_name Name of the private method.
		 * @param array  $parameters  Parameters to pass to the method.
		 * @return mixed
		 */
		private function invoke_private_method( $obj, $method_name, $parameters = array() ) {
			$reflection = new \ReflectionClass( get_class( $obj ) );
			$method     = $reflection->getMethod( $method_name );
			$method->setAccessible( true );
			return $method->invokeArgs( $obj, $parameters );
		}
	}


//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName
	/**
	 * Mock class that extends RemoteLogger to allow overriding is_dev_or_local_environment.
	 */
	class RemoteLoggerWithEnvironmentOverride extends RemoteLogger {
		/**
		 * The is_dev_or_local value.
		 *
		 * @var bool
		 */
		private $is_dev_or_local = false;

		/**
		 * Set the is_dev_or_local value.
		 *
		 * @param bool $value The value to set.
		 */
		public function set_is_dev_or_local( $value ) {
			$this->is_dev_or_local = $value;
		}

		/**
		 * @inheritDoc
		 */
		protected function is_dev_or_local_environment() {
			return $this->is_dev_or_local;
		}
	}
//phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName
}

/**
 * Mocks for global functions used in RemoteLogger.php
 */
namespace Automattic\WooCommerce\Internal\Logging {
	/**
	 * The filter_input function will return NULL if we change the $_SERVER variables at runtime, so we
	 * need to override it in RemoteLogger's namespace when we want it to return a specific value for testing.
	 *
	 * @return mixed
	 */
	function filter_input() {
		global $mock_filter_input, $mock_return;

		if ( true === $mock_filter_input ) {
			return $mock_return;
		} else {
			return call_user_func_array( '\filter_input', func_get_args() );
		}
	}
}
