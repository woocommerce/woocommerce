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
	 * @testdox Check that the readable and writable methods correctly detect whether the file exists in the filesystem,
	 *          and whether the file is readable and writable.
	 */
	public function test_file_readable_writable() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$file     = new File( $filename );

		$this->assertFalse( $file->is_readable() );
		$this->assertFalse( $file->is_writable() );

		$resource = fopen( $filename, 'a' );
		fclose( $resource );

		$this->assertTrue( $file->is_readable() );
		$this->assertTrue( $file->is_writable() );
	}

	/**
	 * @testdox Check that writing to a file that doesn't exist yet creates that file.
	 */
	public function test_write_new_file() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$content  = "You can't take the sky from me";
		$file     = new File( $filename );

		$this->assertFalse( $file->is_writable() );

		$result = $file->write( $content );

		$this->assertTrue( $result );
		$this->assertTrue( $file->is_writable() );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content = file_get_contents( $filename );

		$this->assertEquals( $content . "\n", $actual_content );
	}

	/**
	 * @testdox Check that writing to a file that already exists appends the new content to the existing content.
	 */
	public function test_write_existing_file() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$content1 = 'Shiny';
		$content2 = 'Scratch';

		$resource = fopen( $filename, 'a' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fwrite( $resource, $content1 . "\n" );
		fclose( $resource );
		$file = new File( $filename );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content = file_get_contents( $filename );

		$this->assertEquals( $content1 . "\n", $actual_content );

		$result = $file->write( $content2 );

		$this->assertTrue( $result );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$actual_content = file_get_contents( $filename );

		$this->assertEquals( $content1 . "\n" . $content2 . "\n", $actual_content );
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a filename in the standard format.
	 */
	public function test_initialize_existing_file_standard() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$file     = new File( $filename );

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
	public function test_initialize_existing_file_standard_rotated() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1.3-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$file     = new File( $filename );

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
	public function test_initialize_existing_file_non_standard() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.log';
		$file     = new File( $filename );

		// For non-standard files, file must exist to get the created timestamp.
		$file->write( 'test' );

		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_source() );
		$this->assertNull( $file->get_rotation() );
		$this->assertEquals( filectime( $filename ), $file->get_created_timestamp() );
		// On non-standard filenames, we can't determine what part of the name might be a hash.
		$this->assertEquals( '', $file->get_hash() );
	}

	/**
	 * @testdox Check that all properties are populated correctly when a File instance receives a rotated filename in a non-standard format.
	 */
	public function test_initialize_existing_file_non_standard_rotated() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log';
		$file     = new File( $filename );

		// For non-standard files, file must exist to get the created timestamp.
		$file->write( 'test' );

		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log', $file->get_basename() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5', $file->get_file_id() );
		$this->assertEquals( 'test-Source_1-1-' . wp_hash( 'cheddar' ), $file->get_source() );
		$this->assertEquals( 5, $file->get_rotation() );
		$this->assertEquals( filectime( $filename ), $file->get_created_timestamp() );
		// On non-standard filenames, we can't determine what part of the name might be a hash.
		$this->assertEquals( '', $file->get_hash() );
	}

	/**
	 * @testdox Check that get_stream returns a PHP resource representation of the file.
	 */
	public function test_get_and_close_stream() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$file     = new File( $filename );

		// File doesn't exist yet.
		$this->assertFalse( $file->get_stream() );

		$file->write( 'test' );

		$stream = $file->get_stream();

		$this->assertTrue( is_resource( $stream ) );

		$result = $file->close_stream();

		$this->assertTrue( $result );
		$this->assertFalse( is_resource( $stream ) );
	}

	/**
	 * @testdox Check that rotating a file without a rotation market will rename it to include a rotation marker.
	 */
	public function test_rotate_current_file() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$file     = new File( $filename );
		$file->write( 'test' );

		$this->assertNull( $file->get_rotation() );

		$result = $file->rotate();

		$this->assertTrue( $result );

		$expected_filename = 'test-file.0.log';

		$this->assertEquals( $expected_filename, $file->get_basename() );
		$this->assertTrue( $file->is_readable() );

		$old_file = new File( $filename );

		$this->assertFalse( $old_file->is_readable() );
	}

	/**
	 * @testdox Check that rotating a file with a rotation market will rename it to increment the rotation marker.
	 */
	public function test_rotate_older_file() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.2.log';
		$file     = new File( $filename );
		$file->write( 'test' );

		$this->assertEquals( 2, $file->get_rotation() );

		$result = $file->rotate();

		$this->assertTrue( $result );

		$expected_filename = 'test-file.3.log';

		$this->assertEquals( $expected_filename, $file->get_basename() );
		$this->assertTrue( $file->is_readable() );

		$old_file = new File( $filename );

		$this->assertFalse( $old_file->is_readable() );
	}

	/**
	 * @testdox The delete method should delete the file from the filesystem.
	 */
	public function test_delete_existing_file() {
		$filename = Constants::get_constant( 'WC_LOG_DIR' ) . 'test-file.log';
		$file     = new File( $filename );
		$file->write( 'test' );

		$this->assertTrue( $file->is_readable() );
		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*.log' );
		$this->assertCount( 1, $files );

		$result = $file->delete();

		$this->assertTrue( $result );
		$this->assertFalse( $file->is_readable() );
		$files = glob( trailingslashit( realpath( Constants::get_constant( 'WC_LOG_DIR' ) ) ) . '*.log' );
		$this->assertCount( 0, $files );
	}
}

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose
