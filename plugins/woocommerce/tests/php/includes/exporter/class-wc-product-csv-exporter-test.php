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
	 * Get prepared export row data via reflection.
	 *
	 * @param WC_Product_CSV_Exporter $exporter Exporter instance.
	 * @return array
	 */
	private function get_exported_data( WC_Product_CSV_Exporter $exporter ): array {
		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$get_data_to_export = $reflected_exporter->getMethod( 'get_data_to_export' );
		$get_data_to_export->setAccessible( true );

		return $get_data_to_export->invoke( $exporter );
	}

	/**
	 * Create a variable product assigned to a product category.
	 *
	 * @param string $category_name Product category name.
	 * @return array{product: WC_Product_Variable, category_slug: string, category_id: int}
	 */
	private function create_categorized_variation_product( string $category_name = 'Export Test Category' ): array {
		$term = wp_insert_term( $category_name, 'product_cat' );
		$this->assertIsArray( $term, 'Failed to create product category for export test.' );

		$product = WC_Helper_Product::create_variation_product();
		$product->set_category_ids( array( $term['term_id'] ) );
		$product->save();

		$category = get_term( $term['term_id'], 'product_cat' );

		return array(
			'product'       => $product,
			'category_slug' => $category->slug,
			'category_id'   => (int) $category->term_id,
		);
	}

	/**
	 * Export the current page and return the product IDs, sorted.
	 *
	 * @param WC_Product_CSV_Exporter $exporter Exporter instance.
	 * @return int[]
	 */
	private function get_sorted_exported_ids( WC_Product_CSV_Exporter $exporter ): array {
		$exported_ids = array_map( 'intval', wp_list_pluck( $this->get_exported_data( $exporter ), 'id' ) );
		sort( $exported_ids );

		return $exported_ids;
	}

	/**
	 * @testdox variations should use draft status from parent product
	 */
	public function test_get_column_value_published() {
		$product = WC_Helper_Product::create_variation_product();
		$product->set_status( ProductStatus::DRAFT );
		$product->save();

		$this->product_ids = array_merge( array( $product->get_id() ), $product->get_children( 'edit' ) );

		add_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );

		try {
			$exporter = new WC_Product_CSV_Exporter();
			$exporter->prepare_data_to_export();
			$data = $this->get_exported_data( $exporter );

			foreach ( $data as $row ) {
				$this->assertEquals( -1, $row['published'] );
			}
		} finally {
			remove_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );
		}
	}

	/**
	 * @testdox pending review products should export with a distinct published value.
	 */
	public function test_get_column_value_published_for_pending_product() {
		$product = new WC_Product_Simple();
		$product->set_status( ProductStatus::PENDING );
		$product->save();

		$this->product_ids = array( $product->get_id() );

		add_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );

		try {
			$exporter = new WC_Product_CSV_Exporter();
			$exporter->prepare_data_to_export();
			$data = $this->get_exported_data( $exporter );

			$this->assertNotEmpty( $data, 'Pending review product should be included in the export.' );
			foreach ( $data as $row ) {
				$this->assertEquals( 2, $row['published'], 'Pending review products should not export as draft (-1).' );
			}
		} finally {
			remove_filter( 'woocommerce_product_export_product_query_args', array( $this, 'set_export_product_query_args' ) );
		}
	}

	/**
	 * @testdox exporting variable products with a category filter should not auto-include variations.
	 *
	 * See: https://github.com/woocommerce/woocommerce/issues/53155
	 */
	public function test_category_export_with_variable_type_excludes_variations(): void {
		$fixture = $this->create_categorized_variation_product();
		$product = $fixture['product'];

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_types_to_export( array( ProductType::VARIABLE ) );
		$exporter->set_product_category_to_export( array( $fixture['category_slug'] ) );
		$exporter->prepare_data_to_export();

		$exported_ids = wp_list_pluck( $this->get_exported_data( $exporter ), 'id' );

		$this->assertContains(
			$product->get_id(),
			$exported_ids,
			'Variable parent should be included when exporting variable products by category.'
		);
		$this->assertCount(
			1,
			$exported_ids,
			'Only the variable parent should be exported when variation is excluded from the type filter.'
		);
		foreach ( $product->get_children( 'edit' ) as $variation_id ) {
			$this->assertNotContains(
				$variation_id,
				$exported_ids,
				'Variations should not be auto-included when the type filter is variable only.'
			);
		}
	}

	/**
	 * @testdox Exporting variable products and variations with a category filter includes both.
	 */
	public function test_category_export_with_variable_and_variation_types_includes_both(): void {
		$included = $this->create_categorized_variation_product( 'Export Variable And Variation Category' );
		$this->create_categorized_variation_product( 'Export Other Variable Category' );

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_types_to_export( array( ProductType::VARIABLE, ProductType::VARIATION ) );
		$exporter->set_product_category_to_export( array( $included['category_slug'] ) );
		$exporter->prepare_data_to_export();

		$expected_ids = array_merge(
			array( $included['product']->get_id() ),
			array_map( 'intval', $included['product']->get_children( 'edit' ) )
		);
		sort( $expected_ids );

		$this->assertSame(
			$expected_ids,
			$this->get_sorted_exported_ids( $exporter ),
			'Selecting variable products and variations should export categorized parents and their variations.'
		);
	}

	/**
	 * @testdox Exporting simple products and variations with a category filter skips the variable parent.
	 */
	public function test_category_export_with_simple_and_variation_types_skips_variable_parent(): void {
		$included = $this->create_categorized_variation_product( 'Export Simple And Variation Category' );
		$this->create_categorized_variation_product( 'Export Unrelated Simple Category' );

		$simple_product = WC_Helper_Product::create_simple_product();
		$simple_product->set_category_ids( array( $included['category_id'] ) );
		$simple_product->save();

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_product_types_to_export( array( ProductType::SIMPLE, ProductType::VARIATION ) );
		$exporter->set_product_category_to_export( array( $included['category_slug'] ) );
		$exporter->prepare_data_to_export();

		$expected_ids = array_merge(
			array( $simple_product->get_id() ),
			array_map( 'intval', $included['product']->get_children( 'edit' ) )
		);
		sort( $expected_ids );

		$this->assertSame(
			$expected_ids,
			$this->get_sorted_exported_ids( $exporter ),
			'Simple products in the category and variations of parents in the category should be exported, without the variable parent.'
		);
	}

	/**
	 * @testdox Variations matched by category are exported once, with the first page.
	 */
	public function test_category_export_does_not_repeat_variations_on_later_pages(): void {
		$fixture = $this->create_categorized_variation_product( 'Export Paged Category' );

		$first_simple = WC_Helper_Product::create_simple_product();
		$first_simple->set_category_ids( array( $fixture['category_id'] ) );
		$first_simple->save();

		$second_simple = WC_Helper_Product::create_simple_product();
		$second_simple->set_category_ids( array( $fixture['category_id'] ) );
		$second_simple->save();

		$variation_ids = array_map( 'intval', $fixture['product']->get_children( 'edit' ) );
		$simple_ids    = array( $first_simple->get_id(), $second_simple->get_id() );
		sort( $simple_ids );

		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_limit( 1 );
		$exporter->set_product_types_to_export( array( ProductType::SIMPLE, ProductType::VARIATION ) );
		$exporter->set_product_category_to_export( array( $fixture['category_slug'] ) );

		$exporter->set_page( 1 );
		$exporter->prepare_data_to_export();
		$page_one_ids = $this->get_sorted_exported_ids( $exporter );

		$exporter->set_page( 2 );
		$exporter->prepare_data_to_export();
		$page_two_ids = $this->get_sorted_exported_ids( $exporter );

		$expected_page_one = array_merge( array( $simple_ids[0] ), $variation_ids );
		sort( $expected_page_one );

		$this->assertSame( $expected_page_one, $page_one_ids, 'The first page should include the first simple product and every matching variation.' );
		$this->assertSame( array( $simple_ids[1] ), $page_two_ids, 'A later page should not export the variations again.' );
	}

	/**
	 * @testdox Selected product IDs restrict the export to those products and their variations.
	 */
	public function test_selected_product_ids_restrict_export_rows(): void {
		$simple_product_ids = array();
		$variable_product   = new WC_Product_Variable();

		try {
			$simple_product       = WC_Helper_Product::create_simple_product();
			$simple_product_ids[] = $simple_product->get_id();

			WC_Helper_Product::create_variation_product( $variable_product );
			$variation_ids = $variable_product->get_children( 'edit' );

			$unrelated_product    = WC_Helper_Product::create_simple_product();
			$simple_product_ids[] = $unrelated_product->get_id();

			$exporter = new WC_Product_CSV_Exporter();
			$exporter->set_product_ids_to_export( array( $simple_product->get_id(), $variable_product->get_id() ) );
			$exporter->prepare_data_to_export();

			$exported_ids = array_map( 'intval', wp_list_pluck( $this->get_exported_data( $exporter ), 'id' ) );
			$expected_ids = array_merge(
				array( $simple_product->get_id(), $variable_product->get_id() ),
				$variation_ids
			);
			sort( $exported_ids );
			sort( $expected_ids );

			$this->assertSame( $expected_ids, $exported_ids );
			$this->assertCount( count( $expected_ids ), $exported_ids );
			$this->assertNotContains( $unrelated_product->get_id(), $exported_ids );
		} finally {
			if ( $variable_product->get_id() ) {
				$variation_ids = (array) wc_get_products(
					array(
						'parent' => $variable_product->get_id(),
						'type'   => ProductType::VARIATION,
						'return' => 'ids',
						'limit'  => -1,
					)
				);
				foreach ( $variation_ids as $variation_id ) {
					WC_Helper_Product::delete_product( $variation_id );
				}
				WC_Helper_Product::delete_product( $variable_product->get_id() );
			}

			foreach ( array_reverse( $simple_product_ids ) as $product_id ) {
				WC_Helper_Product::delete_product( $product_id );
			}
		}
	}

	/**
	 * @testdox CSV data is written with an append-only fopen mode so write-only stream wrappers are supported.
	 */
	public function test_write_csv_data_uses_append_only_fopen_mode(): void {
		$exporter = new WC_Product_CSV_Exporter();
		$exporter->set_filename( 'wc-csv-exporter-fopen-mode-test' );

		// Creates the file so write_csv_data() gets past its writability check.
		$exporter->get_file();

		$captured_mode = null;
		$capture_mode  = function ( $fopen_mode ) use ( &$captured_mode ) {
			$captured_mode = $fopen_mode;
			return $fopen_mode;
		};

		add_filter( 'woocommerce_csv_exporter_fopen_mode', $capture_mode );

		$reflected_exporter = new ReflectionClass( WC_Product_CSV_Exporter::class );
		$write_csv_data     = $reflected_exporter->getMethod( 'write_csv_data' );
		$write_csv_data->setAccessible( true );
		$get_file_path = $reflected_exporter->getMethod( 'get_file_path' );
		$get_file_path->setAccessible( true );
		$get_headers_row_file_path = $reflected_exporter->getMethod( 'get_headers_row_file_path' );
		$get_headers_row_file_path->setAccessible( true );

		try {
			$write_csv_data->invoke( $exporter, "sku,name\n" );

			$this->assertSame( 'a', $captured_mode, 'The default fopen mode must be append-only; "a+" is rejected by stream wrappers that cannot seek.' );
			$this->assertStringContainsString( "sku,name\n", $exporter->get_file(), 'Data passed to write_csv_data() should reach the export file.' );
		} finally {
			remove_filter( 'woocommerce_csv_exporter_fopen_mode', $capture_mode );

			foreach ( array( $get_file_path->invoke( $exporter ), $get_headers_row_file_path->invoke( $exporter ) ) as $path ) {
				if ( file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}
		}
	}

	/**
	 * @testdox Exporting a product loaded before its global attribute is deleted leaves the value empty without a warning.
	 */
	public function test_export_row_for_product_loaded_before_its_global_attribute_is_deleted(): void {
		$attribute = WC_Helper_Product::create_product_attribute_object( 'Stale Finish', array( 'Matte' ) );
		$product   = new WC_Product_Simple();
		$product->set_attributes( array( $attribute ) );
		$product->save();
		$product = wc_get_product( $product->get_id() );

		wc_delete_attribute( $attribute->get_id() );

		$exporter          = new WC_Product_CSV_Exporter();
		$generate_row_data = ( new ReflectionClass( WC_Product_CSV_Exporter::class ) )->getMethod( 'generate_row_data' );
		$generate_row_data->setAccessible( true );
		$warnings = array();
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Capturing the warning is the assertion; PHPUnit would otherwise convert it to an exception.
		set_error_handler(
			static function ( int $errno, string $errstr ) use ( &$warnings ): bool {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Reads the level only, to skip warnings silenced with @.
				if ( error_reporting() & $errno ) {
					$warnings[] = $errstr;
				}
				return true;
			}
		);
		try {
			$row = $generate_row_data->invoke( $exporter, $product );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $warnings, 'Exporting the product should not raise warnings.' );
		$this->assertSame( '', $row['attributes:value1'] );
		$this->assertSame( 1, $row['attributes:taxonomy1'] );
	}
}
