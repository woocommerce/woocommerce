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
	 * @return array{product: WC_Product_Variable, category_slug: string}
	 */
	private function create_categorized_variation_product(): array {
		$term = wp_insert_term( 'Export Test Category', 'product_cat' );
		$this->assertIsArray( $term, 'Failed to create product category for export test.' );

		$product = WC_Helper_Product::create_variation_product();
		$product->set_category_ids( array( $term['term_id'] ) );
		$product->save();

		$category = get_term( $term['term_id'], 'product_cat' );

		return array(
			'product'       => $product,
			'category_slug' => $category->slug,
		);
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
}
