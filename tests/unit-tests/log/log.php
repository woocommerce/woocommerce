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
			wc_get_log_file_path( 'A' ),
			wc_get_log_file_path( 'B' ),
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
		$log = wc_get_logger();

		$log->add( 'unit-tests', 'this is a message' );

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
		$this->setExpectedDeprecated( 'WC_Logger::add' );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 */
	public function test_clear() {
		$log = wc_get_logger();
		$log->clear( 'log' );
		$this->setExpectedDeprecated( 'WC_Logger::clear' );
	}

	/**
	 * Test log() complains for bad levels.
	 *
	 * @since 2.8
	 */
	public function test_bad_level() {
		$log = wc_get_logger();
		$log->log( 'this-is-an-invalid-level', '' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );
	}

	/**
	 * Test log().
	 *
	 * @since 2.8
	 */
	public function test_log() {
		$log = wc_get_logger();
		$log->log( 'debug', 'debug' );
		$log->log( 'info', 'info' );
		$log->log( 'notice', 'notice' );
		$log->log( 'warning', 'warning' );
		$log->log( 'error', 'error' );
		$log->log( 'critical', 'critical' );
		$log->log( 'alert', 'alert' );
		$log->log( 'emergency', 'emergency' );

		$log_content = $this->read_content( 'log' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test consumed logs do not bubble.
	 *
	 * @since 2.8
	 */
	public function test_log_entry_is_consumed() {
		add_filter( 'woocommerce_register_log_handlers', array( $this, '_return_consume_error_handlers' ) );

		$log = wc_get_logger();

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
	 * Test unconsumed logs bubble.
	 *
	 * @since 2.8
	 */
	public function test_log_entry_bubbles() {
		add_filter( 'woocommerce_register_log_handlers', array( $this, '_return_bubble_required_handlers' ) );

		$log = wc_get_logger();

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
	 * @since 2.8
	 */
	public function test_level_methods() {
		$log = wc_get_logger();

		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );

		$log_content = $this->read_content( 'log' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test conversion to string level to integers.
	 *
	 * @since 2.8
	 */
	public function test_get_level_severity() {
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'debug' ), 0 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'info' ), 1 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'notice' ), 2 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'warning' ), 3 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'error' ), 4 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'critical' ), 5 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'alert' ), 6 );
		$this->assertEquals( WC_Log_Handler::get_level_severity( 'emergency' ), 7 );
	}

	/**
	 * Helper for log handler comsume test.
	 *
	 * Returns an array of 2 mocked log hanlders.
	 * The first handler always bubbles.
	 * The second handler expects to recieve exactly 8 messages (1 for each level).
	 *
	 * @since 2.8
	 *
	 * @return WC_Log_Handler[] array of mocked log handlers
	 */
	public function _return_bubble_required_handlers() {
		$bubble = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$bubble->expects( $this->any() )->method( 'handle' )->will( $this->returnValue( true ) );

		$required = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$required->expects( $this->exactly( 8 ) )->method( 'handle' );

		return array( $bubble, $required );
	}

	/**
	 * Helper for log handler comsume test.
	 *
	 * Returns an array of 2 mocked log hanlders.
	 * The first handler never bubbles.
	 * The second handler expects to never be called.
	 *
	 * @since 2.8
	 *
	 * @return WC_Log_Handler[] array of mocked log handlers
	 */
	public function _return_consume_error_handlers() {
		$consume = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$consume->expects( $this->any() )->method( 'handle' )->will( $this->returnValue( false ) );

		$error = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$error->expects( $this->never() )->method( 'handle' );

		return array( $consume, $error );
	}
}
