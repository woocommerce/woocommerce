<?php
/**
 * Class WC_Tests_Log_Handler_File file.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WC_Tests_Log_Handler_File
 * @package WooCommerce\Tests\Log
 * @since 3.0.0
 */
class WC_Tests_Log_Handler_File extends WC_Unit_Test_Case {

	/**
	 * Runs after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
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
			$file_path = WC_Log_Handler_File::get_log_file_path( $file );
			if ( file_exists( $file_path ) && is_writable( $file_path ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
				unlink( $file_path ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink
			}
		}
		parent::tearDown();
	}

	/**
	 * Get the entire contents of a file.
	 *
	 * @param string $handle File path.
	 * @return false|string Contents of the file, or false on error.
	 */
	public function read_content( $handle ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions
		return file_get_contents( WC_Log_Handler_File::get_log_file_path( $handle ) );
	}

	/**
	 * Test _legacy format.
	 *
	 * @since 3.0.0
	 */
	public function test_legacy_format() {
		$handler = new WC_Log_Handler_File( array( 'threshold' => 'debug' ) );

		$handler->handle(
			time(),
			'info',
			'this is a message',
			array(
				'source'  => 'unit-tests',
				'_legacy' => true,
			)
		);

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
	}

	/**
	 * Test clear().
	 *
	 * @since 3.0.0
	 */
	public function test_clear() {
		$handler  = new WC_Log_Handler_File();
		$log_name = '_test_clear';
		$handler->handle( time(), 'debug', 'debug', array( 'source' => $log_name ) );
		$handler->clear( $log_name );
		$this->assertEquals( '', $this->read_content( $log_name ) );
	}

	/**
	 * Test remove().
	 *
	 * @since 3.0.0
	 */
	public function test_remove() {
		$handler  = new WC_Log_Handler_File();
		$log_name = 'test_remove';
		$handler->handle( time(), 'debug', 'debug', array( 'source' => $log_name ) );
		$handler->remove( $handler::get_log_file_name( $log_name ) );
		$this->assertFileDoesNotExist( $handler::get_log_file_path( $log_name ) );
	}

	/**
	 * Test handle writes to default file correctly.
	 *
	 * @since 3.0.0
	 */
	public function test_writes_file() {
		$handler = new WC_Log_Handler_File();
		$time    = time();

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
	 * Test 'source' context determines log file.
	 *
	 * @since 3.0.0
	 */
	public function test_log_file_source() {
		$handler        = new WC_Log_Handler_File();
		$time           = time();
		$context_source = array( 'source' => 'unit-tests' );

		$handler->handle( $time, 'debug', 'debug', $context_source );
		$handler->handle( $time, 'info', 'info', $context_source );
		$handler->handle( $time, 'notice', 'notice', $context_source );
		$handler->handle( $time, 'warning', 'warning', $context_source );
		$handler->handle( $time, 'error', 'error', $context_source );
		$handler->handle( $time, 'critical', 'critical', $context_source );
		$handler->handle( $time, 'alert', 'alert', $context_source );
		$handler->handle( $time, 'emergency', 'emergency', $context_source );

		$log_content = $this->read_content( 'unit-tests' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test multiple handlers don't conflict on log writing.
	 *
	 * @since 3.0.0
	 */
	public function test_multiple_handlers() {
		$handler_a      = new WC_Log_Handler_File();
		$handler_b      = new WC_Log_Handler_File();
		$time           = time();
		$context_source = array( 'source' => 'unit-tests' );

		// Different loggers should not conflict.
		$handler_a->handle( $time, 'debug', 'debug', $context_source );
		$handler_b->handle( $time, 'info', 'info', $context_source );
		$handler_a->handle( $time, 'notice', 'notice', $context_source );
		$handler_b->handle( $time, 'warning', 'warning', $context_source );
		$handler_a->handle( $time, 'error', 'error', $context_source );
		$handler_b->handle( $time, 'critical', 'critical', $context_source );
		$handler_a->handle( $time, 'alert', 'alert', $context_source );
		$handler_b->handle( $time, 'emergency', 'emergency', $context_source );

		$log_content = $this->read_content( 'unit-tests' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test log_rotate()
	 *
	 * Ensure logs are rotated correctly when limit is surpassed.
	 *
	 * @since 3.0.0
	 */
	public function test_log_rotate() {
		// phpcs:disable WordPress.WP.AlternativeFunctions

		// Handler with log size limit of 5mb.
		$handler       = new WC_Log_Handler_File( 5 * 1024 * 1024 );
		$time          = time();
		$log_name      = '_test_log_rotate';
		$base_log_file = WC_Log_Handler_File::get_log_file_path( $log_name );

		// Create log file larger than 5mb to ensure log is rotated.
		$handle = fopen( $base_log_file, 'w' );
		fseek( $handle, 5 * 1024 * 1024 );
		fwrite( $handle, '_base_log_file_contents_' );
		fclose( $handle );

		// Write some files to ensure they've rotated correctly.
		for ( $i = 0; $i < 10; $i++ ) {
			file_put_contents( WC_Log_Handler_File::get_log_file_path( $log_name . ".{$i}" ), $i ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_file_put_contents
		}

		$context_source = array( 'source' => $log_name );
		$handler->handle( $time, 'emergency', 'emergency', $context_source );

		$this->assertFileExists( WC_Log_Handler_File::get_log_file_path( $log_name ) );

		// Ensure the handled log is correct.
		$this->assertStringEndsWith( 'EMERGENCY emergency' . PHP_EOL, $this->read_content( $log_name ) );

		// Ensure other logs have rotated correctly.
		$this->assertEquals( '_base_log_file_contents_', trim( $this->read_content( $log_name . '.0' ) ) );
		for ( $i = 1; $i < 10; $i++ ) {
			$this->assertEquals( $i - 1, $this->read_content( $log_name . ".{$i}" ) );
		}

		// phpcs:enable WordPress.WP.AlternativeFunctions
	}

	/**
	 * Test get_log_file_path().
	 *
	 * @since 3.0.0
	 */
	public function test_get_log_file_path() {
		$log_dir     = trailingslashit( WC_LOG_DIR );
		$date_suffix = gmdate( 'Y-m-d', time() );
		$hash_name   = sanitize_file_name( wp_hash( 'unit-tests' ) );
		$this->assertEquals( $log_dir . 'unit-tests-' . $date_suffix . '-' . $hash_name . '.log', WC_Log_Handler_File::get_log_file_path( 'unit-tests' ) );
	}

}
