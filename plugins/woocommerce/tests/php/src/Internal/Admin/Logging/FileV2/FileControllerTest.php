<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\FileController;
use WC_Log_Handler_File;
use WC_Unit_Test_Case;

/**
 * FileControllerTest class.
 */
class FileControllerTest extends WC_Unit_Test_Case {
	/**
	 * Instance of the logging class.
	 *
	 * @var WC_Log_Handler_File|null
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

		$this->handler = new WC_Log_Handler_File();
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
		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*.log' );
		foreach ( $files as $file ) {
			unlink( $file );
		}
	}

	/**
	 * @testdox The get_files method should retrieve log files as File instances, in a specified order.
	 */
	public function test_get_files_with_files(): void {
		$this->handler->handle( time(), 'debug', '1', array() ); // No source defaults to "log" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- Unit test.
		$this->handler->handle( time(), 'debug', wp_debug_backtrace_summary(), array( 'source' => 'unit-testing' ) ); // Increase file size.

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
		$this->assertEquals( 'log', $first_file->get_source() );
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
	 * @testdox The get_file_sources method should return a unique array of sources.
	 */
	public function test_get_file_sources(): void {
		$this->handler->handle( time(), 'debug', '1', array() ); // No source defaults to "log" as source.
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'unit-testing' ) );

		$sources = $this->sut->get_file_sources();
		$this->assertCount( 2, $sources );
		$this->assertContains( 'log', $sources );
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

		$files    = $this->sut->get_files( array( 'source' => 'log' ) );
		$filename = $files[0]->get_basename();
		$deleted  = $this->sut->delete_files( array( $filename ) );
		$this->assertEquals( 1, $deleted );
		$this->assertEquals( 2, $this->sut->get_files( array(), true ) );
		$this->assertEquals( 0, $this->sut->get_files( array( 'source' => 'log' ), true ) );

		$files     = $this->sut->get_files();
		$filenames = array_map( fn( $file ) => $file->get_basename(), $files );
		$deleted   = $this->sut->delete_files( $filenames );
		$this->assertEquals( 2, $deleted );
		$this->assertEquals( 0, $this->sut->get_files( array(), true ) );
	}
}
