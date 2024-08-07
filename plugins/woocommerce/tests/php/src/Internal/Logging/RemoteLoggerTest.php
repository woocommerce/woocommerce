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
		$this->sut    = wc_get_container()->get( RemoteLogger::class );
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
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wc_rate_limits" );
		WC_Cache_Helper::invalidate_cache_group( WC_Rate_Limiter::CACHE_GROUP );
	}


	private function cleanup_filters() {
		$filters = array(
			'option_woocommerce_admin_remote_feature_enabled',
			'option_woocommerce_allow_tracking',
			'option_woocommerce_version',
			'option_woocommerce_remote_variant_assignment',
			'plugins_api',
			'pre_http_request',
			'woocommerce_remote_logger_formatted_log_data',
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
	 */
	public function test_remote_logging_not_allowed( $condition, $setup_callback ) {
		$this->setup_remote_logging_conditions( true );
		$setup_callback( $this );
		$this->assertFalse( $this->sut->is_remote_logging_allowed() );
	}

	public function remote_logging_disallowed_provider() {
		return array(
			'feature flag disabled'   => array(
				'condition' => 'feature flag disabled',
				'setup'     => fn( $test ) => update_option( 'woocommerce_feature_remote_logging_enabled', 'no' ),
			),
			'tracking opted out'      => array(
				'condition' => 'tracking opted out',
				'setup'     => fn( $test ) => add_filter( 'option_woocommerce_allow_tracking', fn() => 'no' ),
			),
			'outdated version'        => array(
				'condition' => 'outdated version',
				'setup'     => fn( $test ) => WC()->version = '9.0.0',
			),
			'high variant assignment' => array(
				'condition' => 'high variant assignment',
				'setup'     => fn( $test ) => add_filter( 'option_woocommerce_remote_variant_assignment', fn() => 15 ),
			),
		);
	}

	/**
	 * @testdox Fetch latest WooCommerce version retries on API failure
	 */
	public function test_fetch_latest_woocommerce_version_retry() {
		$this->setup_remote_logging_conditions( true );
		add_filter( 'plugins_api', fn() => new \WP_Error(), 10, 3 );

		for ( $i = 1; $i <= 4; $i++ ) {
			$this->sut->is_remote_logging_allowed();
			$retry_count = get_transient( RemoteLogger::FETCH_LATEST_VERSION_RETRY );
			$this->assertEquals( min( $i, 3 ), $retry_count );
		}
	}

	/**
	 * @testdox get_formatted_log method returns expected array structure
	 * @dataProvider get_formatted_log_provider
	 */
	public function test_get_formatted_log( $level, $message, $context, $expected ) {
		$formatted_log = $this->sut->get_formatted_log( $level, $message, $context );
		foreach ( $expected as $key => $value ) {
			$this->assertArrayHasKey( $key, $formatted_log );
			$this->assertEquals( $value, $formatted_log[ $key ] );
		}
	}

	public function get_formatted_log_provider() {
		return array(
			'basic log data'            => array(
				'error',
				'Fatal error occurred at line 123 in ' . ABSPATH . 'wp-content/file.php',
				array( 'tags' => array( 'tag1', 'tag2' ) ),
				array(
					'feature'  => 'woocommerce_core',
					'severity' => 'error',
					'message'  => 'Fatal error occurred at line 123 in **/wp-content/file.php',
					'tags'     => array( 'woocommerce', 'php', 'tag1', 'tag2' ),
				),
			),
			'log with backtrace'        => array(
				'error',
				'Test error message',
				array( 'backtrace' => ABSPATH . 'wp-content/plugins/woocommerce/file.php' ),
				array( 'trace' => '**/woocommerce/file.php' ),
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
		);
	}

	/**
	 * @testdox should_handle method behaves correctly under different conditions
	 * @dataProvider should_handle_provider
	 */
	public function test_should_handle( $setup, $level, $expected ) {
		$this->sut = $this->getMockBuilder( RemoteLogger::class )
			->onlyMethods( array( 'is_remote_logging_allowed', 'is_third_party_error' ) )
			->getMock();

		$this->sut->method( 'is_remote_logging_allowed' )->willReturn( true );
		$this->sut->method( 'is_third_party_error' )->willReturn( false );

		$setup( $this );

		$result = $this->invoke_private_method( $this->sut, 'should_handle', array( $level, 'Test message', array() ) );
		$this->assertEquals( $expected, $result );
	}

	public function should_handle_provider() {
		return array(
			'throttled'                 => array(
				fn( $test ) => WC_Rate_Limiter::set_rate_limit( RemoteLogger::RATE_LIMIT_ID, 10 ),
				'critical',
				false,
			),
			'less severe than critical' => array(
				fn( $test ) => null,
				'error',
				false,
			),
			'critical level'            => array(
				fn( $test ) => null,
				'critical',
				true,
			),
		);
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

		$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array() ) );
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

		$this->assertFalse( $this->sut->handle( time(), 'error', 'Test message', array() ) );
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
			function ( $preempt, $args, $url ) {
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

		$this->assertTrue( $this->sut->handle( time(), 'critical', 'Test message', array() ) );
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

		$this->assertFalse( $this->sut->handle( time(), 'critical', 'Test message', array() ) );
		$this->assertTrue( WC_Rate_Limiter::retried_too_soon( RemoteLogger::RATE_LIMIT_ID ) );
	}

	/**
	 * @testdox is_third_party_error method correctly identifies third-party errors
	 * @dataProvider is_third_party_error_provider
	 */
	public function test_is_third_party_error( $message, $context, $expected_result ) {
		$result = $this->invoke_private_method( $this->sut, 'is_third_party_error', array( $message, $context ) );
		$this->assertEquals( $expected_result, $result );
	}

	public function is_third_party_error_provider() {
		return array(
			array( 'Fatal error in ' . WC_ABSPATH . 'file.php', array(), false ),
			array( 'Fatal error in /wp-content/file.php', array(), false ),
			array( 'Fatal error in /wp-content/file.php', array( 'source' => 'fatal-errors' ), false ),
			array(
				'Fatal error in /wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array( '/wp-content/plugins/3rd-plugin/file.php', WC_ABSPATH . 'file.php' ),
				),
				false,
			),
			array(
				'Fatal error in /wp-content/plugins/woocommerce-3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array( WP_PLUGIN_DIR . 'woocommerce-3rd-plugin/file.php' ),
				),
				true,
			),
			array(
				'Fatal error in /wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array( WP_PLUGIN_DIR . '3rd-plugin/file.php' ),
				),
				true,
			),
			array(
				'Fatal error in /wp-content/plugins/3rd-plugin/file.php',
				array(
					'source'    => 'fatal-errors',
					'backtrace' => array( array( 'file' => WP_PLUGIN_DIR . '3rd-plugin/file.php' ) ),
				),
				true,
			),
		);
	}

	/**
	 * @testdox sanitize method correctly sanitizes paths
	 */
	public function test_sanitize() {
		$message  = WC_ABSPATH . 'includes/class-wc-test.php on line 123';
		$expected = '**/woocommerce/includes/class-wc-test.php on line 123';
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
		$expected = "**/woocommerce/includes/class-wc-test.php:123\n**/wp-includes/plugin.php:456";
		$result   = $this->invoke_private_method( $this->sut, 'sanitize_trace', array( $trace ) );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Setup common conditions for remote logging tests
	 */
	private function setup_remote_logging_conditions( $enabled = true ) {
		update_option( 'woocommerce_feature_remote_logging_enabled', $enabled ? 'yes' : 'no' );
		add_filter( 'option_woocommerce_allow_tracking', fn() => 'yes' );
		add_filter( 'option_woocommerce_remote_variant_assignment', fn() => 5 );
		add_filter(
			'plugins_api',
			function ( $result, $action, $args ) {
				if ( 'plugin_information' === $action && 'woocommerce' === $args->slug ) {
					return (object) array( 'version' => '9.2.0' );
				}
				return $result;
			},
			10,
			3
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


//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch
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
//phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch
