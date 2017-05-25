<?php

/**
 * Meta
 * @package WooCommerce\Tests\Exporter
 */
class WC_Tests_Product_CSV_Exporter extends WC_Unit_Test_Case {

	/**
	 * Load up the exporter classes since they aren't loaded by default.
	 */
	public function setUp() {
		$bootstrap = WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/export/class-wc-product-csv-exporter.php';
	}

	/**
	 * Test escape_data to prevent regressions that could open security holes.
	 * @since 3.1.0
	 */
	public function test_escape_data() {
		$exporter = new WC_Product_CSV_Exporter();

		$data = "=cmd|' /C calc'!A0";
		$this->assertEquals( "'=cmd|' /C calc'!A0", $exporter->escape_data( $data ) );

		$data = "+cmd|' /C calc'!A0";
		$this->assertEquals( "'+cmd|' /C calc'!A0", $exporter->escape_data( $data ) );

		$data = "-cmd|' /C calc'!A0";
		$this->assertEquals( "'-cmd|' /C calc'!A0", $exporter->escape_data( $data ) );

		$data = "@cmd|' /C calc'!A0";
		$this->assertEquals( "'@cmd|' /C calc'!A0", $exporter->escape_data( $data ) );
	}

	/**
	 * Test escape_data to prevent regressions that could open security holes.
	 * @since 3.1.0
	 */
	public function test_format_term_ids() {
		$exporter = new WC_Product_CSV_Exporter();

		$term1 = wp_insert_category( array( 'cat_name' => 'cat1' ) );
		$term2 = wp_insert_category( array( 'cat_name' => 'cat2' ) );
		$term3 = wp_insert_category( array( 'cat_name' => 'cat3' ) );

		$expected = "cat1, cat2, cat3";
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );

		wp_insert_category( array( 'cat_ID' => $term2, 'cat_name' => 'cat2', 'category_parent' => $term1 ) );

		$expected = "cat1, cat1 > cat2, cat3";
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );

		wp_insert_category( array( 'cat_ID' => $term3, 'cat_name' => 'cat3', 'category_parent' => $term2 ) );
		$expected = "cat1, cat1 > cat2, cat1 > cat2 > cat3";
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );
	}

	public function test_prepare_data_to_export() {
		add_filter( 'woocommerce_product_export_row_data', array( $this, 'verify_exported_data' ), 10, 2 );
		$exporter = new WC_Product_CSV_Exporter();

	}

	public function verify_exported_data( $row, $product ) {
	}
}
