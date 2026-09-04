<?php
/**
 * Tests for the Analytics report exporter.
 *
 * @package WooCommerce\Tests\Admin
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\ReportCSVExporter;
use Automattic\WooCommerce\Admin\ReportExporter;
use WC_Unit_Test_Case;

/**
 * Tests for retained report exports.
 */
class ReportExporterTest extends WC_Unit_Test_Case {

	/**
	 * Paths written by a test, removed in tear down.
	 *
	 * @var string[]
	 */
	private $paths = array();

	/**
	 * Remove files that the database transaction does not roll back.
	 */
	public function tearDown(): void {
		foreach ( $this->paths as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		$this->paths = array();

		parent::tearDown();
	}

	/**
	 * Write an export to the reports directory.
	 *
	 * @param string $filename Filename without the extension.
	 * @param string $body     Export body.
	 * @param int    $age      How long ago the export was written, in seconds.
	 * @return string Resolved filename, as the download handler and cleanup see it.
	 */
	private function create_export( string $filename, string $body = "1,2\n", int $age = 0 ): string {
		ReportCSVExporter::maybe_create_directory();

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );
		$resolved = $exporter->get_filename();
		$path     = ReportCSVExporter::get_reports_directory() . $resolved;

		$this->write_file( $path, $body, $age );
		$this->write_file( $path . '.headers', "id,total\n", $age );

		return $resolved;
	}

	/**
	 * Write one file and backdate it.
	 *
	 * @param string $path     File path.
	 * @param string $contents File contents.
	 * @param int    $age      How long ago the file was written, in seconds.
	 * @return void
	 */
	private function write_file( string $path, string $contents, int $age ): void {
		file_put_contents( $path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		touch( $path, time() - $age ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch

		$this->paths[] = $path;
	}

	/**
	 * @testdox Downloading an export streams it without consuming it, so the link can be reused.
	 */
	public function test_streaming_an_export_leaves_it_in_place(): void {
		$filename = $this->create_export( 'wc-orders-report-export-repeatable', "42,7\n" );
		$path     = ReportCSVExporter::get_reports_directory() . $filename;

		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		ob_start();
		$exporter->stream_export_file();
		$first = ob_get_clean();

		$this->assertStringContainsString( '42,7', $first, 'The export body should be streamed to the client.' );
		$this->assertStringContainsString( 'id,total', $first, 'The stored header row should be streamed ahead of the body.' );
		$this->assertFileExists( $path, 'Serving an export should not delete it.' );

		ob_start();
		$exporter->stream_export_file();
		$second = ob_get_clean();

		$this->assertSame( $first, $second, 'A second download should return the same export.' );
	}

	/**
	 * @testdox An export that is gone reports itself as missing instead of serving an empty report.
	 */
	public function test_missing_export_is_reported_as_missing(): void {
		$filename = $this->create_export( 'wc-orders-report-export-missing' );
		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		$this->assertTrue( $exporter->export_file_exists(), 'A generated export should be reported as available.' );

		wp_delete_file( ReportCSVExporter::get_reports_directory() . $filename );

		$this->assertFalse( $exporter->export_file_exists(), 'A deleted export should be reported as unavailable.' );
	}

	/**
	 * @testdox An export that never finished writing reports itself as missing.
	 */
	public function test_incomplete_export_is_reported_as_missing(): void {
		$filename = $this->create_export( 'wc-orders-report-export-incomplete' );
		$exporter = new ReportCSVExporter();
		$exporter->set_filename( $filename );

		// A body with no `.headers` companion is an export that stopped before reaching 100%.
		wp_delete_file( ReportCSVExporter::get_reports_directory() . $filename . '.headers' );

		$this->assertFalse( $exporter->export_file_exists(), 'An unfinished export should be reported as unavailable.' );
	}

	/**
	 * @testdox Daily cleanup deletes only exports past the retention period.
	 */
	public function test_cleanup_deletes_only_expired_exports(): void {
		$reports_dir = ReportCSVExporter::get_reports_directory();
		$expired     = $this->create_export( 'wc-orders-report-export-expired', "1,2\n", ReportExporter::EXPORT_RETENTION_PERIOD + HOUR_IN_SECONDS );
		$fresh       = $this->create_export( 'wc-orders-report-export-fresh', "3,4\n", ReportExporter::EXPORT_RETENTION_PERIOD - HOUR_IN_SECONDS );

		ReportExporter::delete_expired_exports();

		$this->assertFileDoesNotExist( $reports_dir . $expired, 'An expired export should be deleted.' );
		$this->assertFileDoesNotExist( $reports_dir . $expired . '.headers', 'An expired export header row should be deleted too.' );
		$this->assertFileExists( $reports_dir . $fresh, 'An export inside the retention period should be kept.' );
		$this->assertFileExists( $reports_dir . $fresh . '.headers', 'An export header row inside the retention period should be kept.' );
		$this->assertFileExists( $reports_dir . '.htaccess', 'Cleanup should not touch the directory guards.' );
		$this->assertFileExists( $reports_dir . 'index.html', 'Cleanup should not touch the directory guards.' );
	}

	/**
	 * @testdox Cleanup runs from the daily WooCommerce Admin event.
	 */
	public function test_cleanup_is_hooked_to_the_daily_event(): void {
		$this->assertSame(
			10,
			has_action( 'wc_admin_daily', array( ReportExporter::class, 'delete_expired_exports' ) ),
			'Expired exports should be cleaned up by the daily event rather than on each request.'
		);
		$this->assertSame(
			10,
			has_action( 'admin_init', array( ReportExporter::class, 'download_export_file' ) ),
			'The download handler should be registered.'
		);
	}
}
