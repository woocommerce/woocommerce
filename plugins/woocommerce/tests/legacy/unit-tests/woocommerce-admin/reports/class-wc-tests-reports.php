<?php
/**
 * Report exporter tests.
 *
 * @package WooCommerce\Admin\Tests\Reports
 */

use Automattic\WooCommerce\Admin\ReportCSVExporter;

/**
 * Class WC_Admin_Tests_Reports
 */
class WC_Admin_Tests_Reports extends WC_Unit_Test_Case {

	/**
	 * @var Directory
	 */
	private $directory;

	/**
	 * setUp
	 */
	public function setUp(): void {
		parent::setUp();

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		global $wp_filesystem;

		$upload_dir       = wp_upload_dir();
		$this->directory  = ReportCSVExporter::get_reports_directory();
		$this->filesystem = $wp_filesystem;
	}

	/**
	 * Test that directory is created.
	 */
	public function test_directory_creation() {
		$this->filesystem->delete( $this->directory );
		ReportCSVExporter::maybe_create_directory();
		$this->assertDirectoryExists( $this->directory );
	}

	/**
	 * Test that files are created to prevent indexing.
	 */
	public function test_indexing_files_creation() {
		$this->filesystem->delete( $this->directory . 'index.html' );
		$this->filesystem->delete( $this->directory . '.htaccess' );
		ReportCSVExporter::maybe_create_directory();
		$this->assertFileExists( $this->directory . 'index.html' );
		$this->assertFileExists( $this->directory . '.htaccess' );
	}


}
