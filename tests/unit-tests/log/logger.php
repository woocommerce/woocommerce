<?php

/**
 * Class WC_Tests_Logger
 * @package WooCommerce\Tests\Log
 * @since 2.3
 */
class WC_Tests_Logger extends WC_Unit_Test_Case {

	public function tearDown() {
		$log_files = array(
			wc_get_log_file_path( 'unit-tests' ),
			wc_get_log_file_path( 'log' ),
		);

		foreach ( $log_files as $file ) {
			if ( file_exists( $file ) && is_writable( $file ) ) {
				unlink( $file );
			}
		}
		parent::tearDown();
	}

	public function read_content( $handle ) {
		return file_get_contents( wc_get_log_file_path( $handle ) );
	}

	/**
	 * Test add().
	 *
	 * @since 2.4
	 */
	public function test_add() {
		$time = time();
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler
			->expects( $this->once() )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'notice' ),
				$this->equalTo( 'this is a message' ),
				$this->equalTo( array( 'tag' => 'unit-tests', '_legacy' => true ) )
			);
		$log = new WC_Logger( array( $handler ), 'debug' );

		$log->add( 'unit-tests', 'this is a message' );

		$this->setExpectedDeprecated( 'WC_Logger::add' );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 */
	public function test_clear() {
		$log = new WC_Logger( null, 'debug' );
		$log->clear( 'log' );
		$this->setExpectedDeprecated( 'WC_Logger::clear' );
	}

	/**
	 * Test log() complains for bad levels.
	 *
	 * @since 2.7.0
	 */
	public function test_bad_level() {
		$log = new WC_Logger( null, 'debug' );
		$log->log( 'this-is-an-invalid-level', '' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );
	}

	/**
	 * Test log().
	 *
	 * @since 2.7.0
	 */
	public function test_log() {
		$handler = $this->create_mock_handler();
		$log = new WC_Logger( array( $handler ), 'debug' );
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
	 * @since 2.7.0
	 */
	public function test_log_handlers() {
		$false_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( false ) );

		$true_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( true ) );

		$final_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
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
	 * @since 2.7.0
	 */
	public function test_level_methods() {
		$handler = $this->create_mock_handler();
		$log = new WC_Logger( array( $handler ), 'debug' );
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
	 * @since 2.7.0
	 */
	public function test_woocommerce_register_log_handlers_filter() {
		add_filter( 'woocommerce_register_log_handlers', array( $this, 'return_assertion_handlers' ) );
		$log = new WC_Logger( null, 'debug' );
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
	 * Test default threshold 'notice' or read from option.
	 *
	 * @since 2.7.0
	 */
	public function test_threshold_defaults() {
		$time = time();

		// Test option setting.
		update_option( 'woocommerce_log_threshold', 'alert' );
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler
			->expects( $this->once() )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'alert' ),
				$this->equalTo( 'alert message' ),
				$this->equalTo( array() )
			);
		$log = new WC_Logger( array( $handler ) );
		$log->critical( 'critical message' );
		$log->alert( 'alert message' );

		// Test 'notice' default when option is not set.
		delete_option( 'woocommerce_log_threshold' );
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler
			->expects( $this->once() )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'notice' ),
				$this->equalTo( 'notice message' ),
				$this->equalTo( array() )
			);
		$log = new WC_Logger( array( $handler ) );
		$log->info( 'info message' );
		$log->notice( 'notice message' );
	}

	/**
	 * Helper for 'woocommerce_register_log_handlers' filter test.
	 *
	 * Returns an array of 1 mocked handler.
	 * The handler expects to receive exactly 8 messages (1 for each level).
	 *
	 * @since 2.7.0
	 *
	 * @return WC_Log_Handler[] array of mocked handlers.
	 */
	public function return_assertion_handlers() {
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler->expects( $this->exactly( 8 ) )->method( 'handle' );

		return array( $handler );
	}

	/**
	 * Mock handler that expects sequential calls to each level.
	 *
	 * Calls should have the message '[level] message'
	 *
	 * @since 2.7.0
	 *
	 * @return WC_Log_Handler mock object
	 */
	public function create_mock_handler() {
		$time = time();
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$handler
			->expects( $this->at( 0 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'debug' ),
				$this->equalTo( 'debug message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 1 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'info' ),
				$this->equalTo( 'info message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 2 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'notice' ),
				$this->equalTo( 'notice message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 3 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'warning' ),
				$this->equalTo( 'warning message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 4 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'error' ),
				$this->equalTo( 'error message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 5 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'critical' ),
				$this->equalTo( 'critical message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 6 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'alert' ),
				$this->equalTo( 'alert message' ),
				$this->equalTo( array() )
			);
		$handler
			->expects( $this->at( 7 ) )
			->method( 'handle' )
			->with(
				$this->greaterThanOrEqual( $time ),
				$this->equalTo( 'emergency' ),
				$this->equalTo( 'emergency message' ),
				$this->equalTo( array() )
			);

		return $handler;
	}
}
