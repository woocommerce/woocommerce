<?php

/**
 * Class Log.
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class WC_Tests_Log extends WC_Unit_Test_Case {

	public function tearDown() {
		parent::tearDown();
		$log_files = array(
			wc_get_log_file_path( 'unit-tests' ),
			wc_get_log_file_path( 'log' ),
			wc_get_log_file_path( 'A' ),
		);

		foreach ( $log_files as $file ) {
			if ( file_exists( $file ) && is_writable( $file ) ) {
				unlink( $file );
			}
		}
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
	 */
	public function test_bad_level() {
		$log = wc_get_logger();
		$log->log( 'this-is-an-invalid-level', '' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );
	}

	/**
	 * Test log().
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
	 * Test log( 'level', ... ) === level( ... ).
	 */
	public function test_log_short_methods() {
		$log = wc_get_logger();

		$ctx_a = array( 'tag' => 'A' );
		$ctx_b = array( 'tag' => 'B' );

		$log->log( 'debug',     'debug',     $ctx_a );
		$log->log( 'info',      'info',      $ctx_a );
		$log->log( 'notice',    'notice',    $ctx_a );
		$log->log( 'warning',   'warning',   $ctx_a );
		$log->log( 'error',     'error',     $ctx_a );
		$log->log( 'critical',  'critical',  $ctx_a );
		$log->log( 'alert',     'alert',     $ctx_a );
		$log->log( 'emergency', 'emergency', $ctx_a );

		$log->debug(     'debug',     $ctx_b );
		$log->info(      'info',      $ctx_b );
		$log->notice(    'notice',    $ctx_b );
		$log->warning(   'warning',   $ctx_b );
		$log->error(     'error',     $ctx_b );
		$log->critical(  'critical',  $ctx_b );
		$log->alert(     'alert',     $ctx_b );
		$log->emergency( 'emergency', $ctx_b );

		$log_content_a = $this->read_content( 'A' );
		$log_content_b = $this->read_content( 'B' );

		$this->assertEquals( $log_content_a, $log_content_b );
	}

	/**
	 * Test consumed logs do not bubble.
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
	 * Test 'tag' context determines log file.
	 */
	public function test_log_file_tag() {
		$log = wc_get_logger();
		$context_tag = array( 'tag' => 'A' );

		$log->debug( 'debug', $context_tag );
		$log->info( 'info', $context_tag );
		$log->notice( 'notice', $context_tag );
		$log->warning( 'warning', $context_tag );
		$log->error( 'error', $context_tag );
		$log->critical( 'critical', $context_tag );
		$log->alert( 'alert', $context_tag );
		$log->emergency( 'emergency', $context_tag );

		$log_content = $this->read_content( 'A' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Helper for log handler comsume test.
	 *
	 * Returns an array of 2 mocked log hanlders.
	 * The first handler always bubbles.
	 * The second handler expects to recieve exactly 8 messages (1 for each level).
	 *
	 * @return WC_Log_Handler[] array of mocked log handlers
	 */
	public function _return_bubble_required_handlers() {
		$bubble = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$bubble->method( 'handle' )->willReturn( true );


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
	 * @return WC_Log_Handler[] array of mocked log handlers
	 */
	public function _return_consume_error_handlers() {
		$consume = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$consume->method( 'handle' )->willReturn( false );

		$error = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();

		$error->expects( $this->never() )->method( 'handle' );

		return array( $consume, $error );
	}
}
