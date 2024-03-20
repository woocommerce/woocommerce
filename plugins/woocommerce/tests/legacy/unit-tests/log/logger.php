<?php
/**
 * Class WC_Tests_Logger file.
 *
 * @package WooCommerce\Tests
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;

/**
 * Class WC_Tests_Logger
 * @package WooCommerce\Tests\Log
 * @since 2.3
 */
class WC_Tests_Logger extends WC_Unit_Test_Case {
	/**
	 * Set up to do before running any of these tests.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		self::delete_all_log_files();
	}

	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		self::delete_all_log_files();
		parent::tearDown();
	}

	/**
	 * Delete all existing log files.
	 *
	 * @return void
	 */
	private static function delete_all_log_files(): void {
		$files = glob( Settings::get_log_directory() . '*.log' );
		foreach ( $files as $file ) {
			unlink( $file );
		}
	}

	/**
	 * Test add().
	 *
	 * @since 2.4
	 */
	public function test_add() {
		$time    = time();
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler
			->expects( $this->once() )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'notice' ),
				$this->equalTo( 'this is a message' ),
				$this->equalTo(
					array(
						'source'  => 'unit-tests',
						'_legacy' => true,
					)
				)
			);
		$log = new WC_Logger( array( $handler ), 'debug' );

		$log->add( 'unit-tests', 'this is a message' );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 */
	public function test_clear() {
		$path = Settings::get_log_directory() . 'unit-tests.log';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $path, 'Test file content.' );
		$this->assertFileExists( $path );
		$log = new WC_Logger();
		$log->clear( 'unit-tests' );
		$this->assertFileDoesNotExist( $path );
	}

	/**
	 * Test log() complains for bad levels.
	 *
	 * @since 3.0.0
	 */
	public function test_bad_level() {
		$log = new WC_Logger( null, 'debug' );
		$log->log( 'this-is-an-invalid-level', '' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );
	}

	/**
	 * Test log().
	 *
	 * @since 3.0.0
	 */
	public function test_log() {
		$this->expectNotToPerformAssertions();

		$handler = $this->create_mock_handler();
		$log     = new WC_Logger( array( $handler ), 'debug' );
		$log->log( 'debug', 'debug message' );
		$log->log( 'info', 'info message' );
		$log->log( 'notice', 'notice message' );
		$log->log( 'warning', 'warning message' );
		$log->log( 'error', 'error message' );
		$log->log( 'critical', 'critical message' );
		$log->log( 'alert', 'alert message' );
		$log->log( 'emergency', 'emergency message' );
	}

	/**
	 * Test logs passed to all handlers.
	 *
	 * @since 3.0.0
	 */
	public function test_log_handlers() {
		$false_handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( false ) );

		$true_handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( true ) );

		$final_handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$final_handler->expects( $this->exactly( 8 ) )->method( 'handle' );

		$log = new WC_Logger( array( $false_handler, $true_handler, $final_handler ), 'debug' );

		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );
	}

	/**
	 * Test WC_Logger->[debug..emergency] methods
	 *
	 * @since 3.0.0
	 */
	public function test_level_methods() {
		$this->expectNotToPerformAssertions();

		$handler = $this->create_mock_handler();
		$log     = new WC_Logger( array( $handler ), 'debug' );
		$log->debug( 'debug message' );
		$log->info( 'info message' );
		$log->notice( 'notice message' );
		$log->warning( 'warning message' );
		$log->error( 'error message' );
		$log->critical( 'critical message' );
		$log->alert( 'alert message' );
		$log->emergency( 'emergency message' );
	}

	/**
	 * Test 'woocommerce_register_log_handlers' filter.
	 *
	 * @since 3.0.0
	 */
	public function test_woocommerce_register_log_handlers_filter() {
		add_filter( 'woocommerce_register_log_handlers', array( $this, 'return_assertion_handlers' ) );
		$log = new WC_Logger();
		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );
		remove_filter( 'woocommerce_register_log_handlers', array( $this, 'return_assertion_handlers' ) );
	}

	/**
	 * Test no filtering by default
	 *
	 * @since 3.0.0
	 */
	public function test_threshold_defaults() {
		$time = time();

		// Test no filtering by default.
		delete_option( 'woocommerce_log_threshold' );
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler
			->method( 'handle' )
			->withConsecutive(
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'bad-level' ),
					$this->equalTo( 'bad-level message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'debug' ),
					$this->equalTo( 'debug message' ),
					$this->equalTo( array() ),
				)
			);

		$log = new WC_Logger( array( $handler ) );

		// An invalid level has the minimum severity, but should not be filtered.
		$log->log( 'bad-level', 'bad-level message' );
		// Bad level also complains.
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );

		// Debug is lowest recognized level.
		$log->debug( 'debug message' );
	}

	/**
	 * Test that the logger validates handlers
	 *
	 * If a handler does not implement WC_Log_Handler_Interface, WC_Logger should complain
	 * (wc_doing_it_wrong) and not register the handler. This handler should receive no messages.
	 *
	 * @since 3.0.0
	 */
	public function test_validate_handler_interface() {
		$handler = $this
			->getMockBuilder( 'stdClass' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler->expects( $this->never() )->method( 'handle' );
		$logger = new WC_Logger( array( $handler ) );
		$logger->debug( 'debug' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::get_handlers' );
	}

	/**
	 * Helper for 'woocommerce_register_log_handlers' filter test.
	 *
	 * Returns an array of 1 mocked handler.
	 * The handler expects to receive exactly 8 messages (1 for each level).
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Log_Handler[] array of mocked handlers.
	 */
	public function return_assertion_handlers() {
		static $handler;

		if ( ! $handler instanceof WC_Log_Handler_Interface ) {
			$handler = $this
				->getMockBuilder( 'WC_Log_Handler_Interface' )
				->setMethods( array( 'handle' ) )
				->getMock();
			$handler->expects( $this->exactly( 8 ) )->method( 'handle' );
		}

		return array( $handler );
	}

	/**
	 * Mock handler that expects sequential calls to each level.
	 *
	 * Calls should have the message '[level] message'
	 *
	 * @since 3.0.0
	 *
	 * @return WC_Log_Handler mock object
	 */
	public function create_mock_handler() {
		$time    = time();
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler_Interface' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$handler
			->method( 'handle' )
			->withConsecutive(
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'debug' ),
					$this->equalTo( 'debug message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'info' ),
					$this->equalTo( 'info message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'notice' ),
					$this->equalTo( 'notice message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'warning' ),
					$this->equalTo( 'warning message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'error' ),
					$this->equalTo( 'error message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'critical' ),
					$this->equalTo( 'critical message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'alert' ),
					$this->equalTo( 'alert message' ),
					$this->equalTo( array() ),
				),
				array(
					$this->greaterThanOrEqual( $time ),
					$this->equalTo( 'emergency' ),
					$this->equalTo( 'emergency message' ),
					$this->equalTo( array() ),
				)
			);

		return $handler;
	}
}
