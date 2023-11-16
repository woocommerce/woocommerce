<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;
use WC_Unit_Test_Case;

// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose

/**
 * FileTest class.
 */
class FileTest extends WC_Unit_Test_Case {
	/**
	 * Tear down after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Delete all created files and directories.
		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*' );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				rmdir( $file );
			} else {
				unlink( $file );
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a filename in the standard format.
	 */
	public function test_initialize_file_standard() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$this->assertEquals( 'test-Source_1-1-2023-10-23-' . wp_hash( 'cheddar' ) . '.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1-2023-10-23', $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1', $file->get_source() );
		$this->assertNull( $file->get_rotation() );
		$this->assertEquals( strtotime( '2023-10-23' ), $file->get_created_timestamp() );
		$this->assertEquals( wp_hash( 'cheddar' ), $file->get_hash() );
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a rotated filename in the standard format.
	 */
	public function test_initialize_file_standard_rotated() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1.3-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$this->assertEquals( 'test-Source_1-1.3-2023-10-23-' . wp_hash( 'cheddar' ) . '.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1.3-2023-10-23', $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1', $file->get_source() );
		$this->assertEquals( 3, $file->get_rotation() );
		$this->assertEquals( strtotime( '2023-10-23' ), $file->get_created_timestamp() );
		$this->assertEquals( wp_hash( 'cheddar' ), $file->get_hash() );
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a filename in a non-standard format.
	 */
	public function test_initialize_file_non_standard() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_source() );
		$this->assertNull( $file->get_rotation() );
		$this->assertEquals( filemtime( $filename ), $file->get_created_timestamp() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_hash() );
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a rotated filename in a non-standard format.
	 */
	public function test_initialize_file_non_standard_rotated() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5', $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_source() );
		$this->assertEquals( 5, $file->get_rotation() );
		$this->assertEquals( filemtime( $filename ), $file->get_created_timestamp() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_hash() );
	}

	/**
	 * @testdox Check that is_readable and is_writable only return true for files.
	 */
	public function test_is_readable_writable() {
		global $wp_filesystem;

		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$this->assertTrue( $file->is_readable() );
		$this->assertTrue( $file->is_writable() );

		$nonfilename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test_dir';
		$wp_filesystem->mkdir( $nonfilename );
		$nonfile = new File( $nonfilename );

		$this->assertFalse( $nonfile->is_readable() );
		$this->assertFalse( $nonfile->is_writable() );
	}

	/**
	 * @testdox Check that get_stream returns a PHP resource representation of the file.
	 */
	public function test_get_stream() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$resource = fopen( $filename, 'a' );
		fclose( $resource );
		$file = new File( $filename );

		$stream = $file->get_stream();

		$this->assertTrue( is_resource( $stream ) );
	}

	/**
	 * @testdox The delete method should delete the file from the filesystem.
	 */
	public function test_delete() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log';
		$resource = fopen( $filename, 'a' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		fclose( $resource );
		$file = new File( $filename );

		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*.log' );
		$this->assertCount( 1, $files );

		$file->delete();

		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*.log' );
		$this->assertCount( 0, $files );
	}
}

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose
