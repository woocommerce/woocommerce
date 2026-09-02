<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin;

use Automattic\WooCommerce\Admin\ReportExporter;
use WC_Unit_Test_Case;

/**
 * Tests for the ReportExporter class.
 */
class ReportExporterTest extends WC_Unit_Test_Case {

	/**
	 * @testdox download_export_file() should ignore a request whose filename is not a string.
	 */
	public function test_download_export_file_ignores_array_filename(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_GET = array(
			'action'   => ReportExporter::DOWNLOAD_EXPORT_ACTION,
			'filename' => array( 'x' ),
		);

		try {
			ob_start();
			ReportExporter::download_export_file();
			$output = ob_get_clean();
		} finally {
			$_GET = array();
		}

		$this->assertSame(
			'',
			$output,
			'A non-string filename should be ignored rather than reaching the exporter, which would raise an array to string conversion error.'
		);
	}
}
