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
		parent::setUp();

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
	 * Test format_data.
	 * @since 3.1.0
	 */
	public function test_format_data() {
		$exporter = new WC_Product_CSV_Exporter();

		$data = 'test';
		$this->assertEquals( 'test', $exporter->format_data( $data ) );

		$time = time();
		$data = new WC_DateTime( "@{$time}", new DateTimeZone( 'UTC' ) );
		$this->assertEquals( date( 'Y-m-d G:i:s', $time ), $exporter->format_data( $data ) );

		$data = true;
		$this->assertEquals( 1, $exporter->format_data( $data ) );

		$data = false;
		$this->assertEquals( 0, $exporter->format_data( $data ) );
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

		$expected = 'cat1, cat2, cat3';
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );

		wp_insert_category(
			array(
				'cat_ID'          => $term2,
				'cat_name'        => 'cat2',
				'category_parent' => $term1,
			)
		);

		$expected = 'cat1, cat1 > cat2, cat3';
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );

		wp_insert_category(
			array(
				'cat_ID'          => $term3,
				'cat_name'        => 'cat3',
				'category_parent' => $term2,
			)
		);
		$expected = 'cat1, cat1 > cat2, cat1 > cat2 > cat3';
		$this->assertEquals( $expected, $exporter->format_term_ids( array( $term1, $term2, $term3 ), 'category' ) );
	}

	/**
	 * Test prepare_data_to_export.
	 * @since 3.1.0
	 */
	public function test_prepare_data_to_export() {
		add_filter( 'woocommerce_product_export_row_data', array( $this, 'verify_exported_data' ), 10, 2 );
		$exporter = new WC_Product_CSV_Exporter();

		$product = WC_Helper_Product::create_simple_product();
		$product->set_description( 'Test description' );
		$product->set_short_description( 'Test short description' );
		$product->set_weight( 12.5 );
		$product->set_height( 10 );
		$product->set_length( 20 );
		$product->set_width( 1 );

		$sale_start = time();
		$sale_end   = $sale_start + DAY_IN_SECONDS;
		$product->set_date_on_sale_from( $sale_start );
		$product->set_date_on_sale_to( $sale_end );

		$product->save();
		WC_Helper_Product::create_external_product();
		WC_Helper_Product::create_grouped_product();
		WC_Helper_Product::create_variation_product();

		$exporter->set_product_category_to_export( array() );
		$exporter->prepare_data_to_export();
	}

	/**
	 * Verify one product for test_perpare_data_to_export.
	 * @since 3.1.0
	 */
	public function verify_exported_data( $row, $product ) {
		$this->assertEquals( $product->get_id(), $row['id'] );
		$this->assertEquals( $product->get_type(), $row['type'] );
		$this->assertEquals( $product->get_sku(), $row['sku'] );
		$this->assertEquals( $product->get_name(), $row['name'] );
		$this->assertEquals( $product->get_short_description(), $row['short_description'] );
		$this->assertEquals( $product->get_description(), $row['description'] );
		$this->assertEquals( $product->get_tax_status(), $row['tax_status'] );
		$this->assertEquals( $product->get_width(), $row['width'] );
		$this->assertEquals( $product->get_height(), $row['height'] );
		$this->assertEquals( $product->get_length(), $row['length'] );
		$this->assertEquals( $product->get_weight(), $row['weight'] );
		$this->assertEquals( $product->get_featured(), $row['featured'] );
		$this->assertEquals( $product->get_sold_individually(), $row['sold_individually'] );
		$this->assertEquals( $product->get_date_on_sale_from(), $row['date_on_sale_from'] );
		$this->assertEquals( $product->get_date_on_sale_to(), $row['date_on_sale_to'] );
		$this->assertEquals( 'publish' === $product->get_status(), $row['published'] );
		$this->assertEquals( 'instock' === $product->get_stock_status(), $row['stock_status'] );
		$this->assertEquals( $product->get_menu_order(), $row['menu_order'] );

		$this->assertContains( $row['catalog_visibility'], array( 'visible', 'catalog', 'search', 'hidden' ) );
		$this->assertContains( $row['backorders'], array( 1, 0, 'notify' ) );

		$expected_parent = '';
		$parent_id       = $product->get_parent_id();
		if ( $parent_id ) {
			$parent          = wc_get_product( $parent_id );
			$expected_parent = $parent->get_sku() ? $parent->get_sku() : 'id:' . $parent->get_id();
		}
		$this->assertEquals( $expected_parent, $row['parent_id'] );

		return $row;
	}
}
