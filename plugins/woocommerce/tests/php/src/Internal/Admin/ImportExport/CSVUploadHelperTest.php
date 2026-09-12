<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\ImportExport;

use Automattic\WooCommerce\Internal\Admin\ImportExport\CSVUploadHelper;
use WC_Unit_Test_Case;

/**
 * Tests for the CSVUploadHelper class.
 */
class CSVUploadHelperTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var CSVUploadHelper
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new CSVUploadHelper();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$import_dir = $this->sut->get_import_dir( false );
		if ( is_dir( $import_dir ) ) {
			\Automattic\WooCommerce\Internal\Utilities\FilesystemUtil::get_wp_filesystem_direct()->delete( $import_dir, true );
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should return the expected import directory path and create the directory when create is true.
	 */
	public function test_get_import_dir_returns_path_and_creates_directory(): void {
		$wp_upload_dir = wp_upload_dir();
		$expected_dir  = trailingslashit( $wp_upload_dir['basedir'] ) . 'wc-imports';

		$actual_dir = $this->sut->get_import_dir( true );

		$this->assertSame( $expected_dir, $actual_dir, 'get_import_dir should return path ending in wc-imports' );
		$this->assertDirectoryExists( $actual_dir, 'get_import_dir(true) should ensure directory exists on disk' );
	}

	/**
	 * @testdox Should return the expected import directory path without creating it when create is false.
	 */
	public function test_get_import_dir_returns_path_without_creation_when_false(): void {
		$wp_upload_dir = wp_upload_dir();
		$expected_dir  = trailingslashit( $wp_upload_dir['basedir'] ) . 'wc-imports';

		$actual_dir = $this->sut->get_import_dir( false );

		$this->assertSame( $expected_dir, $actual_dir, 'get_import_dir should match expected upload subdirectory path' );
	}

	/**
	 * @testdox Should modify path, url, and subdir in upload_dir array to use wc-imports.
	 */
	public function test_override_upload_dir_modifies_path_url_and_subdir(): void {
		$uploads = array(
			'path'    => '/var/www/uploads/2026/09',
			'url'     => 'https://example.com/wp-content/uploads/2026/09',
			'subdir'  => '/2026/09',
			'basedir' => '/var/www/uploads',
			'baseurl' => 'https://example.com/wp-content/uploads',
			'error'   => false,
		);

		$result = $this->sut->override_upload_dir( $uploads );

		$this->assertSame( '/var/www/uploads/wc-imports', $result['path'], 'Upload path should point to wc-imports' );
		$this->assertSame( 'https://example.com/wp-content/uploads/wc-imports', $result['url'], 'Upload URL should point to wc-imports' );
		$this->assertSame( '/wc-imports', $result['subdir'], 'Upload subdir should be /wc-imports' );
	}

	/**
	 * @testdox Should append a unique random suffix before the file extension.
	 */
	public function test_override_unique_filename_appends_random_suffix(): void {
		$filename = 'sample.csv';
		$ext      = '.csv';

		$unique_filename1 = $this->sut->override_unique_filename( $filename, $ext );
		$unique_filename2 = $this->sut->override_unique_filename( $filename, $ext );

		$this->assertStringStartsWith( 'sample-', $unique_filename1, 'Filename should start with original base name followed by hyphen' );
		$this->assertStringEndsWith( '.csv', $unique_filename1, 'Filename should retain original extension' );
		$this->assertNotSame( $unique_filename1, $unique_filename2, 'Two consecutive calls should generate distinct randomized filenames' );
	}

	/**
	 * @testdox Should strip trailing .txt extension added by WordPress import upload handler.
	 */
	public function test_remove_txt_from_uploaded_file_strips_txt_extension(): void {
		$file = array(
			'name'     => 'products-export.csv.txt',
			'type'     => 'text/plain',
			'tmp_name' => '/tmp/php123456',
			'error'    => 0,
			'size'     => 1024,
		);

		$result = $this->sut->remove_txt_from_uploaded_file( $file );

		$this->assertSame( 'products-export.csv', $result['name'], 'remove_txt_from_uploaded_file should strip .txt from file name' );
	}

	/**
	 * @testdox Should correct filetype and extension to csv when PHP misidentifies a CSV as text/html.
	 */
	public function test_filter_woocommerce_check_filetype_for_csv_corrects_misidentified_html(): void {
		$data  = array(
			'ext'             => 'txt',
			'type'            => 'text/plain',
			'proper_filename' => false,
		);
		$mimes = array(
			'csv' => 'text/csv',
		);

		$filtered = $this->sut->filter_woocommerce_check_filetype_for_csv(
			$data,
			'/tmp/test.csv',
			'test.csv',
			$mimes,
			'text/html'
		);

		$this->assertSame( 'csv', $filtered['ext'], 'Extension should be corrected to csv' );
		$this->assertSame( 'text/csv', $filtered['type'], 'MIME type should be corrected to text/csv' );
	}

	/**
	 * @testdox Should not modify filetype data when real mime is not text/html.
	 */
	public function test_filter_woocommerce_check_filetype_for_csv_ignores_non_html_mime(): void {
		$data  = array(
			'ext'             => 'csv',
			'type'            => 'text/csv',
			'proper_filename' => false,
		);
		$mimes = array(
			'csv' => 'text/csv',
		);

		$filtered = $this->sut->filter_woocommerce_check_filetype_for_csv(
			$data,
			'/tmp/test.csv',
			'test.csv',
			$mimes,
			'text/csv'
		);

		$this->assertSame( 'csv', $filtered['ext'], 'Extension should remain unchanged' );
		$this->assertSame( 'text/csv', $filtered['type'], 'MIME type should remain unchanged' );
	}

	/**
	 * @testdox Should throw an exception when import_type is empty or invalid.
	 */
	public function test_handle_csv_upload_throws_exception_for_invalid_import_type(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Import type is invalid.' );

		$this->sut->handle_csv_upload( '   ' );
	}

	/**
	 * @testdox Should throw an exception when the upload file entry is missing from $_FILES.
	 */
	public function test_handle_csv_upload_throws_exception_for_missing_file(): void {
		unset( $_FILES['import'] );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'File is empty.' );

		$this->sut->handle_csv_upload( 'products', 'import' );
	}
}
