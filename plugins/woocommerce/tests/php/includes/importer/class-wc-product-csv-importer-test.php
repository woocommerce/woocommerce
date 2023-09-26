<?php
/**
 * Unit tests for the WC_Product_CSV_Importer_Test class.
 *
 * @package WooCommerce\Tests\Importer.
 */

/**
 * Class WC_Product_CSV_Importer_Test
 */
class WC_Product_CSV_Importer_Test extends \WC_Unit_Test_Case {

	/**
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/import/class-wc-product-csv-importer.php';
		require_once $bootstrap->plugin_dir . '/includes/admin/importers/class-wc-product-csv-importer-controller.php';
	}

	/**
	 * @testdox variations need to set the status back to published if parent product is a draft
	 */
	public function test_expand_data_with_draft_variable() {
		$csv_file = dirname( __FILE__ ) . '/sample.csv';
		$raw_data = array(
			array(
				'type'      => 'variable',
				'published' => -1,
			),
			array(
				'type'      => 'variation',
				'published' => -1,
			),
		);

		$reflected_importer = new ReflectionClass( WC_Product_CSV_Importer::class );
		$expand_data        = $reflected_importer->getMethod( 'expand_data' );
		$expand_data->setAccessible( true );

		$importer  = new WC_Product_CSV_Importer( $csv_file );
		$variable  = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( 'variable' ),
				'published' => -1,
			)
		);
		$variation = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( 'variation' ),
				'published' => -1,
			)
		);

		$this->assertEquals( 'draft', $variable['status'] );
		$this->assertEquals( 'publish', $variation['status'] );
	}

	/**
	 * @testdox Test that the importer calculates the percent complete as 99 when it's >= 99.5% through the file.
	 */
	public function test_import_completion_issue_36618_lines_remaining() {
		$csv_file = dirname( __FILE__ ) . '/sample2.csv';
		$args     = array(
			'lines' => 200,
		);

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );

		$this->assertEquals( 99, $importer->get_percent_complete() );
	}

	/**
	 * @testdox Test that the importer calculates the percent complete as 100 when it's at the end of the file.
	 */
	public function test_import_completion_issue_36618_end_of_file() {
		$csv_file = dirname( __FILE__ ) . '/sample2.csv';
		$args     = array(
			'lines' => 201,
		);

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );

		$this->assertEquals( 100, $importer->get_percent_complete() );
	}
}
