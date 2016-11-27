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
			 '_test_clear',
			 '_test_remove',
			 '_test_log_rotate',
			 '_test_log_rotate.0',
			 '_test_log_rotate.1',
			 '_test_log_rotate.2',
			 '_test_log_rotate.3',
			 '_test_log_rotate.4',
			 '_test_log_rotate.5',
			 '_test_log_rotate.6',
			 '_test_log_rotate.7',
			 '_test_log_rotate.8',
			 '_test_log_rotate.9',
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

		$handler->handle( time(), 'info', 'this is a message', array( 'tag' => 'unit-tests', '_legacy' => true ) );

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
		$log_name = '_test_clear';
		$handler->handle( time(), 'debug', 'debug', array( 'tag' => $log_name ) );
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
		$log_name = '_test_remove';
		$handler->handle( time(), 'debug', 'debug', array( 'tag' => $log_name ) );
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

		$handler->handle( $time, 'debug', 'debug', array() );
		$handler->handle( $time, 'info', 'info', array() );
		$handler->handle( $time, 'notice', 'notice', array() );
		$handler->handle( $time, 'warning', 'warning', array() );
		$handler->handle( $time, 'error', 'error', array() );
		$handler->handle( $time, 'critical', 'critical', array() );
		$handler->handle( $time, 'alert', 'alert', array() );
		$handler->handle( $time, 'emergency', 'emergency', array() );

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
		$context_tag = array( 'tag' => 'unit-tests' );

		$handler->handle( $time, 'debug', 'debug', $context_tag );
		$handler->handle( $time, 'info', 'info', $context_tag );
		$handler->handle( $time, 'notice', 'notice', $context_tag );
		$handler->handle( $time, 'warning', 'warning', $context_tag );
		$handler->handle( $time, 'error', 'error', $context_tag );
		$handler->handle( $time, 'critical', 'critical', $context_tag );
		$handler->handle( $time, 'alert', 'alert', $context_tag );
		$handler->handle( $time, 'emergency', 'emergency', $context_tag );

		$log_content = $this->read_content( 'A' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test log_rotate()
	 *
	 * @since 2.8
	 */
	public function test_log_rotate() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );
		$time = time();
		$log_name = '_test_log_rotate';
		$base_log_file = wc_get_log_file_path( $log_name );

		// Create log file larger than limit (5mb) to ensure log is rotated
		$handle = fopen( $base_log_file, 'w' );
		fseek( $handle, 6 * 1024 * 1024 );
		fwrite( $handle, '_base_log_file_contents_' );
		fclose( $handle );

		for ( $i = 0; $i < 10; $i++ ) {
			file_put_contents( wc_get_log_file_path( $log_name . ".{$i}" ), $i );
		}

		$context_tag = array( 'tag' => $log_name );

		$handler->handle( $time, 'emergency', 'emergency', $context_tag );

		$this->assertFileExists( wc_get_log_file_path( $log_name ) );
		$this->assertStringEndsWith( 'EMERGENCY emergency' . PHP_EOL, $this->read_content( $log_name ) );

		$this->assertEquals( '_base_log_file_contents_', trim( $this->read_content( $log_name . '.0' ) ) );
		for ( $i = 1; $i < 10; $i++ ) {
			$this->assertEquals( $i - 1, $this->read_content( $log_name . ".{$i}" ) );
		}
	}

}
