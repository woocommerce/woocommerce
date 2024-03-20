<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Logging\FileV2;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Admin\Logging\FileV2\File;
use Automattic\WooCommerce\Internal\Admin\Logging\Settings;
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
		$files = glob( Settings::get_log_directory() . '*' );
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
	 * Data provider for test_parse_path.
	 *
	 * @return iterable
	 */
	public function provide_path_data(): iterable {
		$hash = wp_hash( 'cheddar' );

		yield 'standard filename, no rotation' => array(
			Settings::get_log_directory() . 'test-Source_1-1-2023-10-23-' . $hash . '.log',
			array(
				'basename' => 'test-Source_1-1-2023-10-23-' . $hash . '.log',
				'source'   => 'test-Source_1-1',
				'rotation' => null,
				'created'  => strtotime( '2023-10-23' ),
				'hash'     => $hash,
				'file_id'  => 'test-Source_1-1-2023-10-23',
			),
		);
		yield 'standard filename, with rotation' => array(
			Settings::get_log_directory() . 'test-Source_1-1.3-2023-10-23-' . $hash . '.log',
			array(
				'basename' => 'test-Source_1-1.3-2023-10-23-' . $hash . '.log',
				'source'   => 'test-Source_1-1',
				'rotation' => 3,
				'created'  => strtotime( '2023-10-23' ),
				'hash'     => $hash,
				'file_id'  => 'test-Source_1-1.3-2023-10-23',
			),
		);
		yield 'non-standard filename, no rotation' => array(
			Settings::get_log_directory() . 'test-Source_1-1-' . $hash . '.log',
			array(
				'basename' => 'test-Source_1-1-' . $hash . '.log',
				'source'   => 'test-Source_1-1-' . $hash,
				'rotation' => null,
				'created'  => 0,
				'hash'     => '',
				'file_id'  => 'test-Source_1-1-' . $hash,
			),
		);
		yield 'non-standard filename, with rotation' => array(
			Settings::get_log_directory() . 'test-Source_1-1-' . $hash . '.5.log',
			array(
				'basename' => 'test-Source_1-1-' . $hash . '.5.log',
				'source'   => 'test-Source_1-1-' . $hash,
				'rotation' => 5,
				'created'  => 0,
				'hash'     => '',
				'file_id'  => 'test-Source_1-1-' . $hash . '.5',
			),
		);
	}

	/**
	 * @testdox Check that the parse_path method correctly parses log file properties from the path.
	 *
	 * @dataProvider provide_path_data
	 *
	 * @param string $path     The path to test the method with.
	 * @param array  $expected The expected data returned from the method.
	 */
	public function test_parse_path( string $path, array $expected ): void {
		$parsed = File::parse_path( $path );

		foreach ( array_keys( $expected ) as $key ) {
			$this->assertEquals( $expected[ $key ], $parsed[ $key ] );
		}
	}

	/**
	 * @testdox Check that the generate_file_id method concatenates strings in the correct way.
	 */
	public function test_generate_file_id(): void {
		$this->assertEquals(
			'unit-testing-' . gmdate( 'Y-m-d' ),
			File::generate_file_id( 'unit-testing', null, time() )
		);
		$this->assertEquals(
			'unit-testing.4-' . gmdate( 'Y-m-d' ),
			File::generate_file_id( 'unit-testing', 4, time() )
		);
		$this->assertEquals(
			'unit-testing-2022-09-25',
			File::generate_file_id( 'unit-testing', null, strtotime( '2022-09-25' ) )
		);
		$this->assertEquals(
			'asdf.4-2033-01-01',
			File::generate_file_id( 'asdf.4-2033-01-01' )
		);
	}

	/**
	 * @testdox Check that the generate_hash method generates the correct hash.
	 */
	public function test_generate_hash(): void {
		$key = Constants::get_constant( 'AUTH_SALT' ) ?? 'wc-logs';

		$this->assertEquals(
			hash_hmac( 'md5', 'unit-testing.3-2023-04-02', $key ),
			File::generate_hash( 'unit-testing.3-2023-04-02' )
		);
		$this->assertEquals(
			hash_hmac( 'md5', 'abc', $key ),
			File::generate_hash( 'abc' )
		);
	}

	/**
	 * @testdox Check that the readable and writable methods correctly detect whether the file exists in the filesystem,
	 *          and whether the file is readable and writable.
	 */
	public function test_file_readable_writable() {
		$filename = Settings::get_log_directory() . 'test-file.log';
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
		$filename = Settings::get_log_directory() . 'test-file.log';
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
		$filename = Settings::get_log_directory() . 'test-file.log';
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
	 * @testdox Check that the has_standard_filename method correctly identifies standard and nonstandard filenames.
	 */
	public function test_has_standard_filename() {
		$standard    = Settings::get_log_directory() . 'test-Source_1-1.3-2023-10-23-' . wp_hash( 'cheddar' ) . '.log';
		$nonstandard = Settings::get_log_directory() . 'test-Source_1-1-' . wp_hash( 'cheddar' ) . '.5.log';

		$file = new File( $standard );
		$this->assertTrue( $file->has_standard_filename() );

		$file = new File( $nonstandard );
		$this->assertFalse( $file->has_standard_filename() );
	}

	/**
	 * @testdox Check that get_stream returns a PHP resource representation of the file.
	 */
	public function test_get_and_close_stream() {
		$filename = Settings::get_log_directory() . 'test-file.log';
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
		$filename = Settings::get_log_directory() . 'test-file.log';
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
		$filename = Settings::get_log_directory() . 'test-file.2.log';
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
		$filename = Settings::get_log_directory() . 'test-file.log';
		$file     = new File( $filename );
		$file->write( 'test' );

		$this->assertTrue( $file->is_readable() );
		$files = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 1, $files );

		$result = $file->delete();

		$this->assertTrue( $result );
		$this->assertFalse( $file->is_readable() );
		$files = glob( Settings::get_log_directory() . '*.log' );
		$this->assertCount( 0, $files );
	}
}

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_read_fopen, WordPress.WP.AlternativeFunctions.file_system_read_fclose
