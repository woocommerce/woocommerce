<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Logging;

use Automattic\WooCommerce\Internal\Logging\RemoteLogger;
use WC_Rate_Limiter;
use WC_Cache_Helper;

/**
 * Class RemoteLoggerTest.
 */
class RemoteLoggerTest extends \WC_Unit_Test_Case {

	/**
	 * System under test.
	 *
	 * @var RemoteLogger;
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

		WC()->version = '9.2.0';
	}

	/**
	 * Tear down.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->cleanup_filters();

		delete_option( 'woocommerce_feature_remote_logging_enabled' );
		delete_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT );

		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->prefix}wc_rate_limits"
		);

		WC_Cache_Helper::invalidate_cache_group( WC_Rate_Limiter::CACHE_GROUP );
	}

	/**
	 * Clean up filters.
	 *
	 * @return void
	 */
	private function cleanup_filters() {
		remove_all_filters( 'option_woocommerce_admin_remote_feature_enabled' );
		remove_all_filters( 'option_woocommerce_allow_tracking' );
		remove_all_filters( 'option_woocommerce_version' );
		remove_all_filters( 'option_woocommerce_remote_variant_assignment' );
		remove_all_filters( 'plugins_api' );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'woocommerce_remote_logger_formatted_log_data' );
	}

	/**
	 * @testdox Test that remote logging is allowed when all conditions are met.
	 */
	public function test_remote_logging_allowed() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		add_filter(
			'plugins_api',
			function ( $result, $action, $args ) {
				if ( 'plugin_information' === $action && 'woocommerce' === $args->slug ) {
					return (object) array(
						'version' => '9.2.0',
					);
				}
				return $result;
			},
			10,
			3
		);

		$this->assertTrue( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the feature flag is disabled.
	 */
	public function test_remote_logging_not_allowed_feature_flag_disabled() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'no' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when user tracking is not opted in.
	 */
	public function test_remote_logging_not_allowed_tracking_opted_out() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'no';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the WooCommerce version is outdated.
	 */
	public function test_remote_logging_not_allowed_outdated_version() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );
		WC()->version = '9.0.0';

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that remote logging is not allowed when the variant assignment is high.
	 */
	public function test_remote_logging_not_allowed_high_variant_assignment() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );
		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_version',
			function () {
				return '9.2.0';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 15;
			}
		);

		set_transient( RemoteLogger::WC_LATEST_VERSION_TRANSIENT, '9.2.0', DAY_IN_SECONDS );

		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	/**
	 * @testdox Test that the fetch_latest_woocommerce_version method retries fetching the latest WooCommerce version when the API call fails.
	 */
	public function test_fetch_latest_woocommerce_version_retry() {
		update_option( 'woocommerce_feature_remote_logging_enabled', 'yes' );

		add_filter(
			'option_woocommerce_allow_tracking',
			function () {
				return 'yes';
			}
		);
		add_filter(
			'option_woocommerce_remote_variant_assignment',
			function () {
				return 5;
			}
		);

		add_filter(
			'plugins_api', // phpcs:ignore PEAR.Functions.FunctionCallSignature.MultipleArguments
			function ( $result, $action, $args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
				return new \WP_Error();
			},
			10,
			3
		);

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 1, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 2, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 3, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );

		// After 3 retries, the transient should not be updated.
		$this->sut->is_remote_logging_allowed();
		$this->assertEquals( 3, get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY ) );
	}

	/**
	 * @testdox Test get_formatted_log method with basic log data returns expected array.
	 */
	public function test_get_formatted_log_basic() {
		$level   = 'error';
		$message = 'Fatal error occurred at line 123 in ' . ABSPATH . 'wp-content/file.php';
		$context = array( 'tags' => array( 'tag1', 'tag2' ) );

		$formatted_log = $this->sut->get_formatted_log( $level, $message, $context );

		$this->assertArrayHasKey( 'feature', $formatted_log );
		$this->assertArrayHasKey( 'severity', $formatted_log );
		$this->assertArrayHasKey( 'message', $formatted_log );
		$this->assertArrayHasKey( 'host', $formatted_log );
		$this->assertArrayHasKey( 'tags', $formatted_log );
		$this->assertArrayHasKey( 'properties', $formatted_log );

		$this->assertEquals( 'woocommerce_core', $formatted_log['feature'] );
		$this->assertEquals( 'error', $formatted_log['severity'] );
		$this->assertEquals( 'Fatal error occurred at line 123 in **/wp-content/file.php', $formatted_log['message'] );
		$this->assertEquals( wp_parse_url( home_url(), PHP_URL_HOST ), $formatted_log['host'] );

		// Tags.
		$this->assertArrayHasKey( 'tags', $formatted_log );
		$this->assertEquals( array( 'woocommerce', 'php', 'tag1', 'tag2' ), $formatted_log['tags'] );

		// Properties.
		$this->assertEquals( WC()->version, $formatted_log['properties']['wc_version'] );
		$this->assertEquals( get_bloginfo( 'version' ), $formatted_log['properties']['wp_version'] );
		$this->assertEquals( phpversion(), $formatted_log['properties']['php_version'] );
	}

	/**
	 * @testdox Test get_formatted_log method sanitizes backtrace.
	 */
	public function test_get_formatted_log_with_backtrace() {
		$level   = 'error';
		$message = 'Test error message';
		$context = array( 'backtrace' => ABSPATH . 'wp-content/file.php' );

		$result = $this->sut->get_formatted_log( $level, $message, $context );
		$this->assertEquals( '**/wp-content/file.php', $result['trace'] );

		$context = array( 'backtrace' => ABSPATH . 'wp-content/plugins/woocommerce/file.php' );
		$result  = $this->sut->get_formatted_log( $level, $message, $context );
		$this->assertEquals( '**/woocommerce/file.php', $result['trace'] );

		$context = array( 'backtrace' => true );
		$result  = $this->sut->get_formatted_log( $level, $message, $context );

		$this->assertIsString( $result['trace'] );
		$this->assertStringContainsString( '**/woocommerce/tests/php/src/Internal/Logging/RemoteLoggerTest.php', $result['trace'] );
	}


	/**
	 * @testdox Test get_formatted_log method log extra attributes.
	 */
	public function test_get_formatted_log_with_extra() {
		$level   = 'error';
		$message = 'Test error message';
		$context = array(
			'extra' => array(
				'key1' => 'value1',
				'key2' => 'value2',
			),
		);

		$result = $this->sut->get_formatted_log( $level, $message, $context );

		$this->assertArrayHasKey( 'extra', $result );
		$this->assertEquals( 'value1', $result['extra']['key1'] );
		$this->assertEquals( 'value2', $result['extra']['key2'] );
	}


	/**
	 * @testdox Test handle() method when throttled.
	 *
	 * @return void
	 */
	public function test_handle_returns_false_when_throttled() {
		$mock_local_logger = $this->createMock( \WC_Logger::class );
		$mock_local_logger->expects( $this->once() )
			->method( 'info' )
			->with( 'Remote logging throttled.', array( 'source' => 'wc-remote-logger' ) );

		$this->sut = $this->getMockBuilder( RemoteLogger::class )
			->setConstructorArgs( array( $mock_local_logger ) )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

		$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

		// Set rate limit to simulate exceeded limit.
		WC_Rate_Limiter::set_rate_limit( RemoteLogger::RATE_LIMIT_ID, 10 );
		$result = $this->sut->handle( time(), 'error', 'Test message', array() );
		$this->assertFalse( $result );
	}


	/**
	 * @testdox Test handle() method applies filter.
	 *
	 * @return void
	 */
	public function test_handle_filtered_log_null() {
		$this->sut = $this->getMockBuilder( RemoteLogger::class )
							->onlyMethods( array( 'is_remote_logging_allowed' ) )
							->getMock();

		$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

		add_filter(
			'woocommerce_remote_logger_formatted_log_data',
			function ( $log_data, $level, $message, $context ) {
				$this->assertEquals( 'Test message', $log_data['message'] );
				$this->assertEquals( 'error', $level );
				$this->assertEquals( 'Test message', $message );
				$this->assertEquals( array(), $context );

				return null;
			},
			10,
			4
		);

		// Mock wp_safe_remote_post using pre_http_request filter.
		add_filter(
			'pre_http_request',
			function () {
				// assert not called.
				$this->assertFalse( true );
			},
			10,
			3
		);

		$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array() ) );
	}

	/**
	 * @testdox Test handle() method successfully sends log.
	 */
	public function test_handle_successful() {
		$this->sut = $this->getMockBuilder( RemoteLogger::class )
							->onlyMethods( array( 'is_remote_logging_allowed' ) )
							->getMock();

		$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

		// Mock wp_safe_remote_post using pre_http_request filter.
		add_filter(
			'pre_http_request',
			function ( $preempt, $args, $url ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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

		$this->assertTrue( $this->sut->handle( time(), 'error', 'Test message', array() ) );
		// Verify that rate limit was set.
		$this->assertTrue( WC_Rate_Limiter::retried_too_soon( RemoteLogger::RATE_LIMIT_ID ) );
	}

	/**
	 * @testdox Test handle method when remote logging fails.
	 **/
	public function test_handle_remote_logging_failure() {
		$mock_local_logger = $this->createMock( \WC_Logger::class );
		$mock_local_logger->expects( $this->once() )
			->method( 'error' );

		$this->sut = $this->getMockBuilder( RemoteLogger::class )
			->setConstructorArgs( array( $mock_local_logger ) )
			->onlyMethods( array( 'is_remote_logging_allowed' ) )
			->getMock();

		$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );

		// Mock wp_safe_remote_post to throw an exception using pre_http_request filter.
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

		$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array() ) );
		// Verify that rate limit was set.
		$this->assertTrue( WC_Rate_Limiter::retried_too_soon( RemoteLogger::RATE_LIMIT_ID ) );
	}

	/**
	 * @testdox Test is_third_party_error method.
	 *
	 * @dataProvider data_provider_for_is_third_party_error
	 *
	 * @param string $message         Error message.
	 * @param array  $context         Context.
	 * @param bool   $expected_result  Expected result.
	 */
	public function test_is_third_party_error( $message, $context, $expected_result ) {
		$result = $this->invoke_private_method( $this->sut, 'is_third_party_error', array( $message, $context ) );

		$this->assertEquals( $expected_result, $result );
	}

	/**
	 * Data provider for third party error test.
	 *
	 * @return array
	 */
	public function data_provider_for_is_third_party_error() {
		return array(
			array(
				'Fatal error occurred at line 123 in' . WC_ABSPATH . 'file.php',
				array(),
				false,
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/file.php',
				array(),
				false,  // source is not.
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/file.php',
				array( 'source' => 'fatal-errors' ),
				false, // backtrace is not set.
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array(
						'/home/user/path/wp-content/plugins/3rd-plugin/file.php',
						WC_ABSPATH . 'file.php',
					),
				),
				false,
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/plugins/woocommerce-3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array(
						WP_PLUGIN_DIR . 'woocommerce-3rd-plugin/file.php',
					),
				),
				true,
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array(
						WP_PLUGIN_DIR . '3rd-plugin/file.php',
					),
				),
				true,
			),
			array(
				'Fatal error occurred at line 123 in /home/user/path/wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array(
						array(
							'file' => WP_PLUGIN_DIR . '3rd-plugin/file.php',
						),
					),
				),
				true,
			),
		);
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
