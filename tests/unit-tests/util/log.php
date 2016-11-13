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
		$log->clear();
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
	 * Test consumed logs do not bubble.
	 */
	public function test_log_entry_is_consumed() {
		$consumer = $this->createMock( WC_Log_Handler::class );
		$consumer->method( 'handle' )->willReturn( false );

		$error_thrower = $this->createMock( WC_Log_Handler::class );
		$error_thrower->method( 'handle' )->will( $this->throwException( new Exception( 'Log was not consumed.' ) ) );

		add_filter( 'woocommerce_register_log_handlers', function() use ( $consumer, $error_thrower ) {
			return array( $consumer, $error_thrower );
		} );

		$log = wc_get_logger();
		$log->log( 'emergency', '' );
	}

	/**
	 * Test unconsumed logs bubble.
	 */
	public function test_log_entry_bubbles() {
		$bubbler = $this->createMock( WC_Log_Handler::class );
		$bubbler->method( 'handle' )->willReturn( true );

		$should_be_reached = $this->createMock( WC_Log_Handler::class );
		$should_be_reached->expects( $this->once() )->method( 'handle' );

		add_filter( 'woocommerce_register_log_handlers', function() use ( $bubbler, $should_be_reached ) {
			return array( $bubbler, $should_be_reached );
		} );

		$log = wc_get_logger();
		$log->log( 'emergency', '' );
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
		$context_tag_A = array( 'tag' => 'A' );

		$log->info( 'info', $context_tag_A );
		$log->notice( 'notice', $context_tag_A );
		$log->warning( 'warning', $context_tag_A );
		$log->error( 'error', $context_tag_A );
		$log->critical( 'critical', $context_tag_A );
		$log->alert( 'alert', $context_tag_A );
		$log->emergency( 'emergency', $context_tag_A );

		$log_content = $this->read_content( 'A' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}
}
