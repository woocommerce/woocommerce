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
	 * @testdox The get_files_by_id method should return an array of File instances given an array of valid file IDs.
	 */
	public function test_get_files_by_id() {
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing2' ) );
		$this->handler->handle( time(), 'debug', '3', array( 'source' => 'unit-testing3' ) );
		$file_id_1 = 'unit-testing1-' . gmdate( 'Y-m-d', time() );
		$file_id_2 = 'unit-testing2-' . gmdate( 'Y-m-d', time() );

		$files = $this->sut->get_files_by_id( array( $file_id_1, $file_id_2 ) );
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
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );
		$this->handler->handle( time(), 'debug', '2', array( 'source' => 'unit-testing2' ) );
		$file_id = 'unit-testing1-' . gmdate( 'Y-m-d', time() );

		$reflection = new \ReflectionClass( get_class( $this->handler ) );
		$method     = $reflection->getMethod( 'log_rotate' );
		$method->setAccessible( true );

		// Handler has to be reset after rotating a log file because it caches handles.
		$method->invoke( $this->handler, 'unit-testing1' );
		$this->handler = new WC_Log_Handler_File();
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );
		$method->invoke( $this->handler, 'unit-testing1' );
		$this->handler = new WC_Log_Handler_File();
		$this->handler->handle( time(), 'debug', '1', array( 'source' => 'unit-testing1' ) );

		$rotations = $this->sut->get_file_rotations( $file_id );

		$this->assertCount( 3, $rotations );
		$this->assertArrayHasKey( 'current', $rotations );
		$this->assertNull( $rotations['current']->get_rotation() );
		$this->assertEquals( 'unit-testing1', $rotations['current']->get_source() );
		$this->assertArrayHasKey( 0, $rotations );
		$this->assertEquals( 0, $rotations[0]->get_rotation() );
		$this->assertEquals( 'unit-testing1', $rotations[0]->get_source() );
		$this->assertArrayHasKey( 1, $rotations );
		$this->assertEquals( 1, $rotations[1]->get_rotation() );
		$this->assertEquals( 'unit-testing1', $rotations[1]->get_source() );
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

		$files   = $this->sut->get_files( array( 'source' => 'log' ) );
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
}
