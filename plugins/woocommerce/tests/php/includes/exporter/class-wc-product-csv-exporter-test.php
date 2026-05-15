<?php
/**
 * Unit tests for the WC_Product_CSV_Exporter_Test class.
 *
 * @package WooCommerce\Tests\Exporter.
 */

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

/**
 * Class WC_Product_CSV_Exporter_Test
 */
class WC_Product_CSV_Exporter_Test extends \WC_Unit_Test_Case {

	/**
	 * Product IDs.
	 *
	 * @var array
	 */
	public $product_ids = array();

	/**
	 * Load up the exporter classes since they aren't loaded by default.
	 */
	public function setUp(): void {
		parent::setUp();

		$bootstrap = \WC_Unit_Tests_Bootstrap::instance();
		require_once $bootstrap->plugin_dir . '/includes/export/class-wc-product-csv-exporter.php';
	}

	/**
	 * Helper to set product export query args.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public function set_export_product_query_args( $args ) {
		$args['include'] = $this->product_ids;
		return $args;
	}

	/**
	 * @testdox variations should use draft status from parent product
	 */
	public function test_get_column_value_published() {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );

		$this->product_ids = array_merge( array( $product->get_id() ), $product->get_children( 'edit' ) );

		add_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );

		// Required for brands to be registered because wc-admin-brands.php adds a filter that depends on it.
		WC_Brands::init_taxonomy();

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->prepare_data_to_export();
		$data = $get_data_to_export->invoke( $exporter );

		foreach ( $data as $row ) {
			$this->assertEquals( -1, $row['published'] );
		}
		remove_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );
	}

	/**
	 * Helper: build a variable product with two variations assigned to a category.
	 *
	 * @param string $category_slug Category slug.
	 * @return WC_Product_Variable
	 */
	private function create_variable_product_in_category( $category_slug ) {
		$term    = wp_insert_term( $category_slug, 'product_cat' );
		$cat_id  = is_wp_error( $term ) ? $term->error_data['term_exists'] : $term['term_id'];
		$product = WC_Helper_Product::create_variation_product();
		$product->set_category_ids( array( $cat_id ) );
		$product->save();
		return $product;
	}

	/**
	 * @testdox category filter combined with the variable type filter must not leak variations into the export
	 */
	public function test_category_with_variable_type_filter_excludes_variations() {
		$product = $this->create_variable_product_in_category( 'csv-export-variable-only' );

		WC_Brands::init_taxonomy();

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_types_to_export( array( ProductType::VARIABLE ) );
		$exporter->set_product_category_to_export( array( 'csv-export-variable-only' ) );
		$exporter->prepare_data_to_export();

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );
		$data = $get_data_to_export->invoke( $exporter );

		$types = wp_list_pluck( $data, 'type' );
		$this->assertContains( ProductType::VARIABLE, $types, 'The variable parent should be exported.' );
		$this->assertNotContains( ProductType::VARIATION, $types, 'Variations must not be exported when the user filters by variable type only.' );
	}

	/**
	 * @testdox category filter without a type filter still includes variations of matching variable products
	 */
	public function test_category_filter_with_default_types_still_includes_variations() {
		$product = $this->create_variable_product_in_category( 'csv-export-default-types' );

		WC_Brands::init_taxonomy();

		// Default types: constructor seeds all known product types including 'variation'.
		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_category_to_export( array( 'csv-export-default-types' ) );
		$exporter->prepare_data_to_export();

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );
		$data = $get_data_to_export->invoke( $exporter );

		$types = wp_list_pluck( $data, 'type' );
		$this->assertContains( ProductType::VARIABLE, $types, 'The variable parent should be exported.' );
		$this->assertContains( ProductType::VARIATION, $types, 'Variations should still be exported when the variation type is part of the type filter.' );
	}

	/**
	 * @testdox category filter combined with explicit variation type still includes variations
	 */
	public function test_category_filter_with_variation_type_includes_variations() {
		$product = $this->create_variable_product_in_category( 'csv-export-variation-type' );

		WC_Brands::init_taxonomy();

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_types_to_export( array( ProductType::VARIABLE, ProductType::VARIATION ) );
		$exporter->set_product_category_to_export( array( 'csv-export-variation-type' ) );
		$exporter->prepare_data_to_export();

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );
		$data = $get_data_to_export->invoke( $exporter );

		$types = wp_list_pluck( $data, 'type' );
		$this->assertContains( ProductType::VARIABLE, $types );
		$this->assertContains( ProductType::VARIATION, $types );
	}
}
