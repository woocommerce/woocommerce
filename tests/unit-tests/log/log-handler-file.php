<?php

/**
 * Class WC_Tests_Log_Handler_File
 * @package WooCommerce\Tests\Log
 * @since 2.8
 */

if ( ! class_exists( 'WC_Log_Handler_File' ) ) {
	include_once(
		dirname( dirname( dirname( dirname( __FILE__ ) ) ) )
		. '/includes/log-handlers/class-wc-log-handler-file.php'
	);
}

class WC_Tests_Log_Handler_File extends WC_Unit_Test_Case {

	public function tearDown() {
		$log_files = array(
			'unit-tests',
			'log',
			'A',
			 '_cleartestfile',
			 '_clearremovefile',
		);

		foreach ( $log_files as $file ) {
			$file_path = wc_get_log_file_path( $file );
			if ( file_exists( $file_path ) && is_writable( $file_path ) ) {
				unlink( $file_path );
			}
		}
		parent::tearDown();
	}

	public function read_content( $handle ) {
		return file_get_contents( wc_get_log_file_path( $handle ) );
	}

	/**
	 * Test _legacy format.
	 *
	 * @since 2.8
	 */
	public function test_legacy_format() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );

		$handler->handle( 'info', time(), 'this is a message', array( 'tag' => 'unit-tests', '_legacy' => true ) );

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.8
	 */
	public function test_clear() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );
		$log_name = '_cleartestfile';
		$handler->handle( 'debug', time(), 'debug', array( 'tag' => $log_name ) );
		$handler->clear( $log_name );
		$this->assertEquals( '', $this->read_content( $log_name ) );
	}

	/**
	 * Test remove().
	 *
	 * @since 2.8
	 */
	public function test_remove() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );
		$log_name = '_clearremovefile';
		$handler->handle( 'debug', time(), 'debug', array( 'tag' => $log_name ) );
		$handler->remove( $log_name );
		$this->assertFileNotExists( wc_get_log_file_path( $log_name ) );
	}

	/**
	 * Test handle writes to default file correctly.
	 *
	 * @since 2.8
	 */
	public function test_writes_file() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );
		$time = time();

		$handler->handle( 'debug', $time, 'debug', array() );
		$handler->handle( 'info', $time, 'info', array() );
		$handler->handle( 'notice', $time, 'notice', array() );
		$handler->handle( 'warning', $time, 'warning', array() );
		$handler->handle( 'error', $time, 'error', array() );
		$handler->handle( 'critical', $time, 'critical', array() );
		$handler->handle( 'alert', $time, 'alert', array() );
		$handler->handle( 'emergency', $time, 'emergency', array() );

		$log_content = $this->read_content( 'log' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test 'tag' context determines log file.
	 *
	 * @since 2.8
	 */
	public function test_log_file_tag() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );
		$time = time();
		$context_tag = array( 'tag' => 'A' );

		$handler->handle( 'debug', $time, 'debug', $context_tag );
		$handler->handle( 'info', $time, 'info', $context_tag );
		$handler->handle( 'notice', $time, 'notice', $context_tag );
		$handler->handle( 'warning', $time, 'warning', $context_tag );
		$handler->handle( 'error', $time, 'error', $context_tag );
		$handler->handle( 'critical', $time, 'critical', $context_tag );
		$handler->handle( 'alert', $time, 'alert', $context_tag );
		$handler->handle( 'emergency', $time, 'emergency', $context_tag );

		$log_content = $this->read_content( 'A' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

}
