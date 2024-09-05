<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\{ LogHandlerFileV2, Settings };
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\{ File, FileController };
use WC_Unit_Test_Case;

// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose

/**
 * FileControllerTest class.
 */
class FileControllerTest extends WC_Unit_Test_Case {
	/**
	 * Instance of the logging class.
	 *
	 * @var LogHandlerFileV2|null
	 */
	private $handler;

	/**
	 * "System Under Test", an instance of the class to be tested.
	 *
	 * @var FileController
	 */
	private $sut;

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
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->handler = new LogHandlerFileV2();
		$this->sut     = new FileController();
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
	 * @testdox The write_to_file method should create a new file with the proper filename when it doesn't exist yet.
	 */
	public function test_write_to_file_new() {
		$source  = 'unit-testing';
		$content = 'test';

		$result = $this->sut->write_to_file( $source, $content );
		$this->assertTrue( $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		$path = reset( $paths );
		$file = new File( $path );

		$this->assertStringStartsWith( $source, $file->get_basename() );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content = file_get_contents( $path );
		$this->assertEquals( $content . "\n", $actual_content );
	}

	/**
	 * @testdox The write_to_file method should append content to an existing file of the correct source that isn't rotated.
	 */
	public function test_write_to_file_existing() {
		$time = time();
		$hash = wp_hash( 'cheddar' );

		$existing_files = array(
			'target' => 'unit-testing-' . gmdate( 'Y-m-d', $time ) . '-' . $hash . '.log',
			'other1' => 'unit-testing.0-' . gmdate( 'Y-m-d', $time ) . '-' . $hash . '.log',
			'other2' => 'unit-testing-' . gmdate( 'Y-m-d', strtotime( '-2 days' ) ) . '-' . $hash . '.log',
		);
		foreach ( $existing_files as $filename ) {
			$path     = Settings::get_log_directory() . $filename;
			$resource = fopen( $path, 'a' );
			fclose( $resource );
		}

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 3, $paths );

		$source  = 'unit-testing';
		$content = 'test';

		$result = $this->sut->write_to_file( $source, $content, $time );
		$this->assertTrue( $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 3, $paths );

		$target_path = Settings::get_log_directory() . $existing_files['target'];
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content = file_get_contents( $target_path );
		$this->assertEquals( $content . "\n", $actual_content );
	}

	/**
	 * @testdox The write_to_file method should rotate a file that has reached the size limit and then write the content to a fresh file.
	 */
	public function test_write_to_file_needs_rotation() {
		$time = time();
		$path = Settings::get_log_directory() . 'unit-testing-' . gmdate( 'Y-m-d', $time ) . '-' . wp_hash( 'cheddar' ) . '.log';

		$resource         = fopen( $path, 'a' );
		$existing_content = random_bytes( 200 ) . "\n";
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $resource, $existing_content );
		fclose( $resource );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $paths );

		// Set the file size low to induce log rotation.
		$filter_callback = fn() => 100;
		add_filter( 'woocommerce_log_file_size_limit', $filter_callback );

		$source      = 'unit-testing';
		$new_content = 'test';

		$result = $this->sut->write_to_file( $source, $new_content, $time );
		$this->assertTrue( $result );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 2, $paths );

		foreach ( $paths as $path ) {
			$file = new File( $path );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$actual_content = file_get_contents( $file->get_path() );

			switch ( true ) {
				case is_null( $file->get_rotation() ):
					$this->assertEquals( $new_content . "\n", $actual_content );
					break;
				case 0 === $file->get_rotation():
					$this->assertEquals( $existing_content, $actual_content );
					break;
				default:
					$this->fail();
					break;
			}
		}

		remove_filter( 'woocommerce_log_file_size_limit', $filter_callback );
	}

	/**
	 * @testdox The get_files method should retrieve log files as File instances, in a specified order.
	 */
	public function test_get_files_with_files(): void {
		$this->handler->handle( strtotime( '-5 days' ), 'debug', '1', array() ); // No source defaults to "plugin-woocommerce" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- Unit test.
		$this->handler->handle( time(), 'debug', random_bytes( 100 ), array( 'source' => 'unit-testing' ) ); // Increase file size.

		$files = $this->sut->get_files();
		$this->assertCount( 2, $files );

		$first_file = array_shift( $files );
		$this->assertInstanceOf( 'Automattic\\WooCommerce\\Internal\\Admin\\Logging\\FileV2\\File', $first_file );
		$second_file = array_shift( $files );
		$this->assertInstanceOf( 'Automattic\\WooCommerce\\Internal\\Admin\\Logging\\FileV2\\File', $second_file );

		$files      = $this->sut->get_files(
			array(
				'orderby' => 'source',
				'order'   => 'asc',
			)
		);
		$first_file = array_shift( $files );
		$this->assertEquals( 'plugin-woocommerce', $first_file->get_source() );
		$second_file = array_shift( $files );
		$this->assertEquals( 'unit-testing', $second_file->get_source() );

		$files      = $this->sut->get_files(
			array(
				'orderby' => 'size',
				'order'   => 'desc',
			)
		);
		$first_file = array_shift( $files );
		$this->assertEquals( 'unit-testing', $first_file->get_source() );

		$files = $this->sut->get_files(
			array(
				'date_filter' => 'created',
				'date_start'  => strtotime( '-6 days' ),
				'date_end'    => strtotime( '-4 days' ),
			)
		);
		$this->assertCount( 1, $files );
		$first_file = array_shift( $files );
		$this->assertEquals( 'plugin-woocommerce', $first_file->get_source() );
	}

	/**
	 * @testdox The get_files method should return a count of files that meet the filtering criteria.
	 */
	public function test_get_files_count_only(): void {
		$this->handler->handle( time(), 'debug', '1', array() ); // No source defaults to "log" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );

		$count = $this->sut->get_files( array( 'source' => 'unit-testing' ), true );
		$this->assertEquals( 1, $count );
	}

	/**
	 * @testdox The get_files_by_id method should return an array of File instances given an array of valid file IDs.
	 */
	public function test_get_files_by_id() {
		// Create a log file with an accompanying rotation.
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );
		$file1_path = glob( Settings::get_log_directory() . '*.log' );
		$file1      = new File( reset( $file1_path ) );
		$file1->rotate();
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );

		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing2' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'unit-testing3' ) );

		$file_id_1 = 'unit-testing1-' . gmdate( 'Y-m-d', time() );
		$file_id_2 = 'unit-testing2-' . gmdate( 'Y-m-d', time() );
		$files     = $this->sut->get_files_by_id( array( $file_id_1, $file_id_2 ) );

		// The retrieved files should not include the rotation.
		$this->assertCount( 2, $files );

		$sources = array_map(
			function( $file ) {
				return $file->get_source();
			},
			$files
		);
		$this->assertContains( 'unit-testing1', $sources );
		$this->assertContains( 'unit-testing2', $sources );

		$files = $this->sut->get_files_by_id( array( 'pop tart' ) );
		$this->assertCount( 0, $files );
	}

	/**
	 * @testdox The get_file_by_id method should return a File instance given a valid file ID, or a WP_Error.
	 */
	public function test_get_file_by_id() {
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing2' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'unit-testing3' ) );
		$file_id = 'unit-testing2-' . gmdate( 'Y-m-d', time() );

		$file = $this->sut->get_file_by_id( $file_id );
		$this->assertInstanceOf( 'Automattic\\WooCommerce\\Internal\\Admin\\Logging\\FileV2\\File', $file );
		$this->assertEquals( 'unit-testing2', $file->get_source() );

		$file = $this->sut->get_file_by_id( 'zucchini' );
		$this->assertWPError( $file );
	}

	/**
	 * @testdox The get_file_rotations method should return an associative array of File instances.
	 */
	public function test_get_file_rotations() {
		$target_file_path = Settings::get_log_directory() . 'test-file.log';
		$other_file_path  = Settings::get_log_directory() . 'other-test-file.log';

		$oldest_file = new File( $target_file_path );
		$oldest_file->write( 'test' );
		$oldest_file->rotate();
		$oldest_file->rotate();

		$middle_file = new File( $target_file_path );
		$middle_file->write( 'test' );
		$middle_file->rotate();

		$newest_file = new File( $target_file_path );
		$newest_file->write( 'test' );

		$other_file = new File( $other_file_path );
		$other_file->write( 'test' );

		$paths = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 4, $paths );

		$file_id   = 'test-file';
		$rotations = $this->sut->get_file_rotations( $file_id );

		$this->assertCount( 3, $rotations );
		$this->assertArrayHasKey( 'current', $rotations );
		$this->assertNull( $rotations['current']->get_rotation() );
		$this->assertEquals( 'test-file', $rotations['current']->get_source() );
		$this->assertArrayHasKey( 0, $rotations );
		$this->assertEquals( 0, $rotations[0]->get_rotation() );
		$this->assertEquals( 'test-file', $rotations[0]->get_source() );
		$this->assertArrayHasKey( 1, $rotations );
		$this->assertEquals( 1, $rotations[1]->get_rotation() );
		$this->assertEquals( 'test-file', $rotations[1]->get_source() );
	}

	/**
	 * @testdox The get_file_sources method should return a unique array of sources.
	 */
	public function test_get_file_sources(): void {
		$this->handler->handle( time(), 'debug', '1', array() ); // No source defaults to "plugin-woocommerce" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'unit-testing' ) );

		$sources = $this->sut->get_file_sources();
		$this->assertCount( 2, $sources );
		$this->assertContains( 'plugin-woocommerce', $sources );
		$this->assertContains( 'unit-testing', $sources );
	}

	/**
	 * @testdox The delete_files method should delete files from the filesystem and return the number of deleted files.
	 */
	public function test_delete_files(): void {
		$this->handler->handle( time(), 'debug', '1', array() ); // No source defaults to "log" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'raspberry' ) );

		$this->assertEquals( 3, $this->sut->get_files( array(), true ) );

		$files   = $this->sut->get_files( array( 'source' => 'plugin-woocommerce' ) );
		$file_id = $files[0]->get_file_id();
		$deleted = $this->sut->delete_files( array( $file_id ) );
		$this->assertEquals( 1, $deleted );
		$this->assertEquals( 2, $this->sut->get_files( array(), true ) );
		$this->assertEquals( 0, $this->sut->get_files( array( 'source' => 'log' ), true ) );

		$files    = $this->sut->get_files();
		$file_ids = array_map( fn( $file ) => $file->get_file_id(), $files );
		$deleted  = $this->sut->delete_files( $file_ids );
		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 0, $this->sut->get_files( array(), true ) );
	}

	/**
	 * @testdox The search_within_files method should return an associative array of case-insensitive search results.
	 */
	public function test_search_within_files(): void {
		$log_time = time();

		$this->handler->handle( $log_time, 'debug', 'Foo', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( $log_time, 'debug', 'Bar', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( $log_time, 'debug', 'foobar', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( $log_time, 'debug', 'A trip to the food bar', array( 'source' => 'unit-testing2' ) );
		$this->handler->handle( $log_time, 'debug', 'Hello world', array( 'source' => 'unit-testing2' ) );

		$this->assertEquals( 2, $this->sut->get_files( array(), true ) );

		$file_args = array(
			'order'   => 'asc',
			'orderby' => 'source',
		);

		$results = $this->sut->search_within_files( 'foo', array(), $file_args );
		$this->assertCount( 3, $results );

		$match = array_shift( $results );
		$this->assertArrayHasKey( 'file_id', $match );
		$this->assertArrayHasKey( 'line_number', $match );
		$this->assertArrayHasKey( 'line', $match );
		$this->assertEquals( 'unit-testing1-' . gmdate( 'Y-m-d', $log_time ), $match['file_id'] );
		$this->assertEquals( 1, $match['line_number'] );
		$this->assertStringContainsString( 'Foo', $match['line'] );

		$match = array_shift( $results );
		$this->assertEquals( 3, $match['line_number'] );
		$this->assertStringContainsString( 'foobar', $match['line'] );

		$match = array_shift( $results );
		$this->assertEquals( 1, $match['line_number'] );
		$this->assertStringContainsString( 'A trip to the food bar', $match['line'] );
	}

	/**
	 * @testdox The get_log_directory_size method should return an accurate size of the directory in bytes.
	 */
	public function test_get_log_directory_size(): void {
		// Non-log files that should be in the log directory.
		$htaccess = wp_filesize( Settings::get_log_directory() . '.htaccess' );
		$index    = wp_filesize( Settings::get_log_directory() . 'index.html' );

		$path             = Settings::get_log_directory() . 'unit-testing-1.log';
		$resource         = fopen( $path, 'a' );
		$existing_content = random_bytes( 200 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $resource, $existing_content );
		fclose( $resource );

		$path             = Settings::get_log_directory() . 'unit-testing-2.log';
		$resource         = fopen( $path, 'a' );
		$existing_content = random_bytes( 300 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $resource, $existing_content );
		fclose( $resource );

		$expected_size = $htaccess + $index + 200 + 300;

		$size = $this->sut->get_log_directory_size();
		$this->assertEquals( $expected_size, $size );
	}
}

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose
