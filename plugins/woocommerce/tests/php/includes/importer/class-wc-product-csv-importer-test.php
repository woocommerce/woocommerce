<?php
/**
 * Unit tests for the WC_Product_CSV_Importer_Test class.
 *
 * @package WooCommerce\Tests\Importer.
 */

declare( strict_types=1 );

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductType;

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
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wc_get_price_decimal_separator' );

		parent::tearDown();
	}

	/**
	 * @testdox variations need to set the status back to published if parent product is a draft
	 */
	public function test_expand_data_with_draft_variable() {
		$csv_file = dirname( __FILE__ ) . '/sample.csv';
		$raw_data = array(
			array(
				'type'      => ProductType::VARIABLE,
				'published' => -1,
			),
			array(
				'type'      => ProductType::VARIATION,
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
				'type'      => array( ProductType::VARIABLE ),
				'published' => -1,
			)
		);
		$variation = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( ProductType::VARIATION ),
				'published' => -1,
			)
		);

		$this->assertEquals( ProductStatus::DRAFT, $variable['status'] );
		$this->assertEquals( ProductStatus::PUBLISH, $variation['status'] );
	}

	/**
	 * @testdox published value of 2 maps to the pending review status on import.
	 */
	public function test_expand_data_maps_published_to_pending() {
		$csv_file = __DIR__ . '/sample.csv';

		$reflected_importer = new ReflectionClass( WC_Product_CSV_Importer::class );
		$expand_data        = $reflected_importer->getMethod( 'expand_data' );
		$expand_data->setAccessible( true );

		$importer = new WC_Product_CSV_Importer( $csv_file );
		$parsed   = $expand_data->invoke(
			$importer,
			array(
				'type'      => array( ProductType::SIMPLE ),
				'published' => 2,
			)
		);

		$this->assertEquals( ProductStatus::PENDING, $parsed['status'] );
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

	/**
	 * @testdox Test that the importer skips updating products with the same SKU.
	 */
	public function test_import_skipping_existing_product_sku_46505() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_sku( '46505-sku' );
		$product->save();

		$csv_file = __DIR__ . '/import-skipping-existing-products-46505-data.csv';
		$args     = array(
			'parse'   => true,
			'mapping' => array(
				'ID'  => 'id',
				'SKU' => 'sku',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		WC_Helper_Product::delete_product( $product->get_id() );
		$this->assertEmpty( $data['updated'], 'Expected 0 updated products, got ' . count( $data['updated'] ) );
		$this->assertEmpty( $data['imported'], 'Expected 0 imported products, got ' . count( $data['imported'] ) );
		$this->assertEmpty( $data['failed'], 'Expected 0 failed products, got ' . count( $data['failed'] ) );
		$this->assertEquals( 1, count( $data['skipped'] ), 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );

		$error = $data['skipped'][0];
		$this->assertInstanceOf( WP_Error::class, $error );
		$this->assertEquals( 'A product with this SKU already exists.', $error->get_error_message() );
	}

	/**
	 * @testdox Test that new variations of an existing variable product are created when updating existing products.
	 */
	public function test_import_creates_new_variations_of_existing_products_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$csv_file = __DIR__ . '/import-adding-variations-26256-data.csv';
		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'                 => 'type',
				'SKU'                  => 'sku',
				'Name'                 => 'name',
				'Parent'               => 'parent_id',
				'Attribute 1 name'     => 'attributes:name1',
				'Attribute 1 value(s)' => 'attributes:value1',
				'Attribute 1 global'   => 'attributes:taxonomy1',
				'Regular price'        => 'regular_price',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();

		$this->assertEquals( array( $product->get_id() ), $data['updated'], 'Expected the existing parent product to be updated' );
		$this->assertCount( 1, $data['imported_variations'], 'Expected 1 imported variation, got ' . count( $data['imported_variations'] ) );
		$this->assertEmpty( $data['failed'], 'Expected 0 failed products, got ' . count( $data['failed'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertEquals( 'No matching product exists to update.', $data['skipped'][0]->get_error_message() );

		$variation = wc_get_product( $data['imported_variations'][0] );
		$this->assertEquals( $product->get_id(), $variation->get_parent_id(), 'Expected the new variation to belong to the existing parent' );
		$this->assertEquals( 'IMPORT-26256-L', $variation->get_sku() );
		$this->assertEquals( array( 'size' => 'L' ), $variation->get_attributes() );

		WC_Helper_Product::delete_product( $variation->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that new variations are still skipped when updating existing products if the filter disables their creation.
	 */
	public function test_import_skips_new_variations_when_creation_is_disabled_via_filter_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_false' );

		$csv_file = __DIR__ . '/import-adding-variations-26256-data.csv';
		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'                 => 'type',
				'SKU'                  => 'sku',
				'Name'                 => 'name',
				'Parent'               => 'parent_id',
				'Attribute 1 name'     => 'attributes:name1',
				'Attribute 1 value(s)' => 'attributes:value1',
				'Attribute 1 global'   => 'attributes:taxonomy1',
				'Regular price'        => 'regular_price',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_false' );

		$this->assertEquals( array( $product->get_id() ), $data['updated'], 'Expected the existing parent product to be updated' );
		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 2, $data['skipped'], 'Expected 2 skipped products, got ' . count( $data['skipped'] ) );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that new variations are not created for a trashed parent product when updating existing products, even if the filter tries to force them.
	 */
	public function test_import_skips_new_variations_of_trashed_parents_26256() {
		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->save();
		wp_trash_post( $product->get_id() );

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = trailingslashit( get_temp_dir() ) . 'import-26256-trashed-parent.csv';
		file_put_contents( $csv_file, "Type,SKU,Name,Parent\nvariation,IMPORT-26256-L,Import 26256 Tee - L,IMPORT-26256-PARENT\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Name'   => 'name',
				'Parent' => 'parent_id',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertFalse( wc_get_product_id_by_sku( 'IMPORT-26256-L' ) > 0, 'Expected no variation to be created for a trashed parent' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that variation rows are skipped when the parent is not a variable product, even if the filter tries to force them.
	 */
	public function test_import_skips_new_variations_of_non_variable_parents_26256() {
		$product = new WC_Product_Simple();
		$product->set_name( 'Import 26256 Simple' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->save();

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = trailingslashit( get_temp_dir() ) . 'import-26256-simple-parent.csv';
		file_put_contents( $csv_file, "Type,SKU,Name,Parent\nvariation,IMPORT-26256-L,Import 26256 Simple - L,IMPORT-26256-PARENT\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Name'   => 'name',
				'Parent' => 'parent_id',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertFalse( wc_get_product_id_by_sku( 'IMPORT-26256-L' ) > 0, 'Expected no variation to be created for a non-variable parent' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that variation rows without a SKU are skipped when updating existing products, since re-importing them would duplicate the variation.
	 */
	public function test_import_skips_new_variations_without_a_sku_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = trailingslashit( get_temp_dir() ) . 'import-26256-no-sku.csv';
		file_put_contents( $csv_file, "ID,Type,SKU,Name,Parent\n,variation,,Import 26256 Tee - L,IMPORT-26256-PARENT\n999999999,variation,,Import 26256 Tee - M,IMPORT-26256-PARENT\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'ID'     => 'id',
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Name'   => 'name',
				'Parent' => 'parent_id',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 2, $data['skipped'], 'Expected 2 skipped products, got ' . count( $data['skipped'] ) );
		$variations = wc_get_products(
			array(
				'type'   => ProductType::VARIATION,
				'parent' => $product->get_id(),
			)
		);
		$this->assertCount( 0, $variations, 'Expected no variations to be created for rows without a SKU' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a variation row whose ID belongs to an existing post of another type is skipped when updating existing products, even if the filter tries to force it.
	 */
	public function test_import_skips_new_variations_with_a_foreign_post_id_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$page_id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_title'  => 'Import 26256 Page',
				'post_status' => 'publish',
			)
		);

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = trailingslashit( get_temp_dir() ) . 'import-26256-foreign-id.csv';
		file_put_contents( $csv_file, "ID,Type,SKU,Name,Parent\n{$page_id},variation,IMPORT-26256-L,Import 26256 Tee - L,IMPORT-26256-PARENT\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'ID'     => 'id',
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Name'   => 'name',
				'Parent' => 'parent_id',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertEquals( 'page', get_post( $page_id )->post_type, 'Expected the existing page to keep its post type' );

		wp_delete_post( $page_id, true );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a variation row whose ID does not belong to any post is skipped when updating existing products, even if the filter tries to force it.
	 */
	public function test_import_skips_new_variations_with_a_nonexistent_post_id_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$nonexistent_id = $product->get_id() + 1000;

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = trailingslashit( get_temp_dir() ) . 'import-26256-nonexistent-id.csv';
		file_put_contents( $csv_file, "ID,Type,SKU,Name,Parent\n{$nonexistent_id},variation,IMPORT-26256-L,Import 26256 Tee - L,IMPORT-26256-PARENT\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'ID'     => 'id',
				'Type'   => 'type',
				'SKU'    => 'sku',
				'Name'   => 'name',
				'Parent' => 'parent_id',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertFalse( wc_get_product_id_by_sku( 'IMPORT-26256-L' ) > 0, 'Expected no variation to be created for a row with a nonexistent ID' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that the filter enabling variation creation cannot cause non-variation rows to be imported when updating existing products.
	 */
	public function test_import_filter_does_not_create_non_variation_rows_26256() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import 26256 Tee' );
		$product->set_sku( 'IMPORT-26256-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		add_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$csv_file = __DIR__ . '/import-adding-variations-26256-data.csv';
		$args     = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'                 => 'type',
				'SKU'                  => 'sku',
				'Name'                 => 'name',
				'Parent'               => 'parent_id',
				'Attribute 1 name'     => 'attributes:name1',
				'Attribute 1 value(s)' => 'attributes:value1',
				'Attribute 1 global'   => 'attributes:taxonomy1',
				'Regular price'        => 'regular_price',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();

		remove_filter( 'woocommerce_product_import_create_variation_of_existing_product', '__return_true' );

		$this->assertCount( 1, $data['imported_variations'], 'Expected 1 imported variation, got ' . count( $data['imported_variations'] ) );
		$this->assertEmpty( $data['imported'], 'Expected 0 imported products, got ' . count( $data['imported'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected the non-variation row to be skipped despite the filter' );

		WC_Helper_Product::delete_product( $data['imported_variations'][0] );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Run an import with "update existing products" against an already existing parent.
	 *
	 * @param string $csv_body  CSV contents, header row included. Usually a lone variation row, but a
	 *                          parent row can be included to cover ordering between the two.
	 * @param string $file_name Temp file name to write the CSV to.
	 * @return array Import results.
	 */
	private function import_with_update_existing( $csv_body, $file_name ) {
		$csv_file = trailingslashit( get_temp_dir() ) . $file_name;
		file_put_contents( $csv_file, $csv_body ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture written to the temp dir.

		$args = array(
			'parse'           => true,
			'update_existing' => true,
			'mapping'         => array(
				'Type'                 => 'type',
				'SKU'                  => 'sku',
				'Name'                 => 'name',
				'Parent'               => 'parent_id',
				'Attribute 1 name'     => 'attributes:name1',
				'Attribute 1 value(s)' => 'attributes:value1',
				'Attribute 1 global'   => 'attributes:taxonomy1',
				'Regular price'        => 'regular_price',
			),
		);

		$importer = new WC_Product_CSV_Importer( $csv_file, $args );
		$data     = $importer->import();
		wp_delete_file( $csv_file );

		return $data;
	}

	/**
	 * @testdox Test that a variation row carrying an attribute value the parent does not offer is skipped, since the storefront could never select it.
	 */
	public function test_import_skips_new_variations_with_a_value_the_parent_does_not_offer() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-VALUE-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-VALUE-L,Import Tee - L,IMPORT-VALUE-PARENT,Size,L,0,12\n",
			'import-unknown-value.csv'
		);

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertSame(
			'A new variation cannot be created because "L" is not an option of the parent product\'s "Size" attribute.',
			html_entity_decode( $data['skipped'][0]->get_error_message(), ENT_QUOTES ),
			'Expected the skip message to name the unavailable value and its attribute'
		);
		$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-VALUE-L' ), 'Expected no variation to be created for a value the parent does not offer' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a variation row carrying a global attribute term the parent does not offer is skipped.
	 */
	public function test_import_skips_new_variations_with_a_taxonomy_value_the_parent_does_not_offer() {
		$attribute_data = WC_Helper_Product::create_attribute( 'size-airr19', array( 'S', 'M', 'L' ) );
		$taxonomy       = $attribute_data['attribute_taxonomy'];
		$small          = get_term_by( 'name', 'S', $taxonomy );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $small->term_id ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Global Tee' );
		$product->set_sku( 'IMPORT-TAX-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-TAX-L,Import Global Tee - L,IMPORT-TAX-PARENT,size-airr19,L,1,12\n",
			'import-unknown-taxonomy-value.csv'
		);

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		// Asserted in full so this cannot pass because the global attribute failed to resolve at all,
		// which would report the "parent has no such attribute" refusal instead.
		$this->assertSame(
			sprintf(
				'A new variation cannot be created because "L" is not an option of the parent product\'s "%s" attribute.',
				wc_attribute_label( $taxonomy )
			),
			html_entity_decode( $data['skipped'][0]->get_error_message(), ENT_QUOTES ),
			'Expected the refusal to name the term and the global attribute, not an unresolved attribute'
		);
		$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-TAX-L' ), 'Expected no variation to be created for a term the parent does not offer' );

		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Product::delete_attribute( $attribute_data['attribute_id'] );
	}

	/**
	 * @testdox Test that a variation row using a global attribute term the parent already offers is still created.
	 */
	public function test_import_creates_new_variations_with_a_taxonomy_value_the_parent_offers() {
		$attribute_data = WC_Helper_Product::create_attribute( 'size-airr19-ok', array( 'S', 'L' ) );
		$taxonomy       = $attribute_data['attribute_taxonomy'];

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $attribute_data['term_ids'] );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Global Tee' );
		$product->set_sku( 'IMPORT-TAX-OK-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-TAX-OK-L,Import Global Tee - L,IMPORT-TAX-OK-PARENT,size-airr19-ok,L,1,12\n",
			'import-known-taxonomy-value.csv'
		);

		$this->assertCount( 1, $data['imported_variations'], 'Expected 1 imported variation, got ' . count( $data['imported_variations'] ) );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );

		$variation = wc_get_product( $data['imported_variations'][0] );
		$this->assertEquals( array( $taxonomy => 'l' ), $variation->get_attributes() );

		WC_Helper_Product::delete_product( $variation->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Product::delete_attribute( $attribute_data['attribute_id'] );
	}

	/**
	 * @testdox Test that a variation row is created when an earlier parent row in the same CSV adds the global attribute term it uses.
	 */
	public function test_import_creates_new_variations_when_an_earlier_parent_row_adds_the_taxonomy_value() {
		$attribute_data = WC_Helper_Product::create_attribute( 'size-airr19-widen', array( 'S', 'L' ) );
		$taxonomy       = $attribute_data['attribute_taxonomy'];
		$small          = get_term_by( 'name', 'S', $taxonomy );

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_data['attribute_id'] );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $small->term_id ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Widen Tee' );
		$product->set_sku( 'IMPORT-WIDEN-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		// Parent row first, as WooCommerce exports it: the variation must see the widened term list.
		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\n"
			. "variable,IMPORT-WIDEN-PARENT,Import Widen Tee,,size-airr19-widen,\"S, L\",1,\n"
			. "variation,IMPORT-WIDEN-L,Import Widen Tee - L,IMPORT-WIDEN-PARENT,size-airr19-widen,L,1,12\n",
			'import-parent-widens-taxonomy.csv'
		);

		$this->assertCount( 1, $data['imported_variations'], 'Expected the variation to be created after the parent row added the term' );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );

		WC_Helper_Product::delete_product( $data['imported_variations'][0] );
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Product::delete_attribute( $attribute_data['attribute_id'] );
	}

	/**
	 * @testdox Test that a variation row naming an attribute the parent does not have is skipped, instead of being saved as an "any" variation.
	 */
	public function test_import_skips_new_variations_with_an_attribute_the_parent_does_not_have() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-ATTR-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-ATTR-RED,Import Tee - Red,IMPORT-ATTR-PARENT,Colour,Red,0,12\n",
			'import-unknown-attribute.csv'
		);

		$this->assertEmpty( $data['imported_variations'], 'Expected 0 imported variations, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertSame(
			'A new variation cannot be created because the parent product has no "Colour" attribute.',
			html_entity_decode( $data['skipped'][0]->get_error_message(), ENT_QUOTES ),
			'Expected the skip message to name the missing attribute'
		);
		$this->assertSame( 0, wc_get_product_id_by_sku( 'IMPORT-ATTR-RED' ), 'Expected no variation to be created for an attribute the parent does not have' );

		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a variation row is still created when the parent has the attribute but does not yet use it for variations.
	 */
	public function test_import_creates_new_variations_when_the_parent_attribute_is_not_yet_used_for_variations() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M', 'L' ) );
		$attribute->set_variation( false );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-PROMOTE-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-PROMOTE-L,Import Tee - L,IMPORT-PROMOTE-PARENT,Size,L,0,12\n",
			'import-promoted-attribute.csv'
		);

		$this->assertCount( 1, $data['imported_variations'], 'Expected the row to be created once the parent attribute is promoted for variations' );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );

		WC_Helper_Product::delete_product( $data['imported_variations'][0] );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a global-flagged variation row whose global attribute does not exist resolves to the parent's same-named custom attribute, instead of being saved as an "any" variation alongside a stray global attribute.
	 */
	public function test_import_creates_new_variations_when_a_global_flagged_row_matches_a_custom_parent_attribute() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-GLBFLAG-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-GLBFLAG-S,Import Tee - S,IMPORT-GLBFLAG-PARENT,Size,S,1,12\n",
			'import-global-flagged-custom-attribute.csv'
		);

		$this->assertCount( 1, $data['imported_variations'], 'Expected the row to be created against the parent\'s custom attribute' );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );

		$variation = wc_get_product( $data['imported_variations'][0] );
		$this->assertSame( array( 'size' => 'S' ), $variation->get_attributes(), 'Expected the variation to store the custom attribute value instead of matching any value' );
		$this->assertSame( 0, (int) wc_attribute_taxonomy_id_by_name( 'size' ), 'Expected no global "size" attribute to be created by the import' );

		WC_Helper_Product::delete_product( $variation->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a global-flagged variation row whose global attribute does not exist still promotes the parent's same-named custom attribute for variations.
	 */
	public function test_import_promotes_a_custom_parent_attribute_matched_by_a_global_flagged_row() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( false );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-GLBPROMOTE-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-GLBPROMOTE-S,Import Tee - S,IMPORT-GLBPROMOTE-PARENT,Size,S,1,12\n",
			'import-global-flagged-promoted-attribute.csv'
		);

		$this->assertCount( 1, $data['imported_variations'], 'Expected the row to be created once the parent attribute is promoted for variations' );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );

		$variation         = wc_get_product( $data['imported_variations'][0] );
		$parent_attributes = wc_get_product( $product->get_id() )->get_attributes();
		$this->assertSame( array( 'size' => 'S' ), $variation->get_attributes(), 'Expected the variation to store the custom attribute value instead of matching any value' );
		$this->assertTrue( $parent_attributes['size']->get_variation(), 'Expected the parent\'s custom attribute to be promoted for variations' );
		$this->assertSame( 0, (int) wc_attribute_taxonomy_id_by_name( 'size' ), 'Expected no global "size" attribute to be created by the import' );

		WC_Helper_Product::delete_product( $variation->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that re-importing an existing variation with a global-flagged row whose global attribute does not exist keeps the stored custom attribute, instead of wiping it into an "any" variation.
	 */
	public function test_import_updates_an_existing_variation_from_a_global_flagged_row_without_wiping_its_attribute() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-GLBUPDATE-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		$variation = new WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_sku( 'IMPORT-GLBUPDATE-S' );
		$variation->set_attributes( array( 'size' => 'S' ) );
		$variation->save();

		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\nvariation,IMPORT-GLBUPDATE-S,Import Tee - M,IMPORT-GLBUPDATE-PARENT,Size,M,1,15\n",
			'import-global-flagged-update-existing.csv'
		);

		$this->assertContains( $variation->get_id(), $data['updated'], 'Expected the existing variation to be updated' );
		$this->assertEmpty( $data['skipped'], 'Expected 0 skipped products, got ' . count( $data['skipped'] ) );
		$this->assertSame( array( 'size' => 'M' ), wc_get_product( $variation->get_id() )->get_attributes(), 'Expected the row\'s value to be stored instead of the attribute being wiped into matching any value' );
		$this->assertSame( 0, (int) wc_attribute_taxonomy_id_by_name( 'size' ), 'Expected no global "size" attribute to be created by the import' );

		WC_Helper_Product::delete_product( $variation->get_id() );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that a variation row listed before the parent row that would widen the attribute is skipped, since rows are validated against the parent as it stands when the row is reached.
	 */
	public function test_import_skips_new_variations_listed_before_the_parent_row_that_adds_their_value() {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( 'Size' );
		$attribute->set_options( array( 'S', 'M' ) );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( 'Import Tee' );
		$product->set_sku( 'IMPORT-ORDER-PARENT' );
		$product->set_attributes( array( $attribute ) );
		$product->save();

		// The variation row comes first, so the parent still offers only S and M when it is validated.
		$data = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\n"
			. "variation,IMPORT-ORDER-L,Import Tee - L,IMPORT-ORDER-PARENT,Size,L,0,12\n"
			. "variable,IMPORT-ORDER-PARENT,Import Tee,,Size,\"S, M, L\",0,\n",
			'import-variation-before-parent.csv'
		);

		$this->assertEmpty( $data['imported_variations'], 'Expected the variation row to be skipped, got ' . count( $data['imported_variations'] ) );
		$this->assertCount( 1, $data['skipped'], 'Expected 1 skipped product, got ' . count( $data['skipped'] ) );
		$this->assertCount( 1, $data['updated'], 'Expected the parent row to still be updated' );

		// The parent row widened the options, so the same import creates the variation on a second run.
		$product = wc_get_product( $product->get_id() );
		$this->assertEquals( array( 'S', 'M', 'L' ), $product->get_attributes()['size']->get_options() );

		$rerun = $this->import_with_update_existing(
			"Type,SKU,Name,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 1 global,Regular price\n"
			. "variation,IMPORT-ORDER-L,Import Tee - L,IMPORT-ORDER-PARENT,Size,L,0,12\n"
			. "variable,IMPORT-ORDER-PARENT,Import Tee,,Size,\"S, M, L\",0,\n",
			'import-variation-before-parent-rerun.csv'
		);

		$this->assertCount( 1, $rerun['imported_variations'], 'Expected the variation to be created on the second run' );
		$this->assertEmpty( $rerun['skipped'], 'Expected 0 skipped products on the second run, got ' . count( $rerun['skipped'] ) );

		WC_Helper_Product::delete_product( $rerun['imported_variations'][0] );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * @testdox Test that attributes with non-ASCII characters are correctly set to "Used for Variations" during import.
	 */
	public function test_variable_product_attributes_with_non_ascii_characters_set_to_used_for_variations() {
		// Set admin user to allow term creation.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// Create a CSV importer instance to access protected methods.
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		// Create a variable product with non-ASCII attributes (Chinese characters).
		$product = new WC_Product_Variable();
		$product->set_name( 'Test Product with Chinese Attributes' );
		$product->set_sku( 'test-non-ascii-attr' );
		$product->save();

		// Create global attributes with Chinese names.
		$color_attr_id = wc_create_attribute(
			array(
				'name'         => '颜色',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		$size_attr_id  = wc_create_attribute(
			array(
				'name'         => '尺寸',
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);

		// Register taxonomies.
		$color_taxonomy = wc_attribute_taxonomy_name_by_id( $color_attr_id );
		$size_taxonomy  = wc_attribute_taxonomy_name_by_id( $size_attr_id );
		register_taxonomy( $color_taxonomy, 'product' );
		register_taxonomy( $size_taxonomy, 'product' );

		// Create terms for the attributes.
		wp_insert_term( '红色', $color_taxonomy );
		wp_insert_term( '绿色', $color_taxonomy );
		wp_insert_term( '大码', $size_taxonomy );
		wp_insert_term( '小码', $size_taxonomy );

		// Set attributes on the product (initially NOT set to "Used for Variations").
		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_id( $color_attr_id );
		$color_attribute->set_name( $color_taxonomy );
		$color_attribute->set_options( array( '红色', '绿色' ) );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( false ); // Initially false.

		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_id( $size_attr_id );
		$size_attribute->set_name( $size_taxonomy );
		$size_attribute->set_options( array( '大码', '小码' ) );
		$size_attribute->set_visible( true );
		$size_attribute->set_variation( false ); // Initially false.

		$product->set_attributes( array( $color_attribute, $size_attribute ) );
		$product->save();

		// Verify attributes are initially NOT set to "Used for Variations".
		$attributes_before = $product->get_attributes();
		$this->assertFalse( $attributes_before[ sanitize_title( $color_taxonomy ) ]->get_variation(), 'Color attribute should initially NOT be set to "Used for Variations"' );
		$this->assertFalse( $attributes_before[ sanitize_title( $size_taxonomy ) ]->get_variation(), 'Size attribute should initially NOT be set to "Used for Variations"' );

		// Simulate variation import data (as would come from CSV).
		$variation_attributes = array(
			array(
				'name'     => '颜色',
				'taxonomy' => true,
			),
			array(
				'name'     => '尺寸',
				'taxonomy' => true,
			),
		);

		// Use reflection to call the protected method.
		$reflection = new ReflectionClass( $importer );
		$method     = $reflection->getMethod( 'get_variation_parent_attributes' );
		$method->setAccessible( true );

		// Call the method (this should set "Used for Variations" to true).
		$method->invoke( $importer, $variation_attributes, $product );

		// Reload product to get updated attributes.
		$product          = wc_get_product( $product->get_id() );
		$attributes_after = $product->get_attributes();

		// Verify attributes are now set to "Used for Variations".
		$this->assertTrue( $attributes_after[ sanitize_title( $color_taxonomy ) ]->get_variation(), 'Color attribute should be set to "Used for Variations" after processing variations' );
		$this->assertTrue( $attributes_after[ sanitize_title( $size_taxonomy ) ]->get_variation(), 'Size attribute should be set to "Used for Variations" after processing variations' );

		// Clean up.
		WC_Helper_Product::delete_product( $product->get_id() );
		wc_delete_attribute( $color_attr_id );
		wc_delete_attribute( $size_attr_id );
	}

	/**
	 * @testdox Variations imported from a CSV that includes IDs do not inherit the default product category (issue #31815).
	 */
	public function test_imported_variations_do_not_inherit_default_product_category_31815() {
		// Term creation during import requires the manage_product_terms capability.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// A default product category must be configured so that the simple-product
		// placeholders created for the ID-bearing rows would otherwise be assigned it.
		$inserted    = wp_insert_term( 'Uncategorized', 'product_cat' );
		$default_cat = is_wp_error( $inserted )
			? (int) $inserted->get_error_data( 'term_exists' )
			: $inserted['term_id'];

		// Preserve the previous option value so other tests reading it are unaffected.
		$previous_default_cat = get_option( 'default_product_cat' );
		update_option( 'default_product_cat', $default_cat );

		$imported_ids = array();

		try {
			// Build the header-to-field mapping the way the admin import UI does.
			$csv_file   = __DIR__ . '/variation-category-31815.csv';
			$headers    = ( new WC_Product_CSV_Importer( $csv_file, array( 'parse' => false ) ) )->get_raw_keys();
			$controller = new WC_Product_CSV_Importer_Controller();
			$auto_map   = new ReflectionMethod( $controller, 'auto_map_columns' );
			$auto_map->setAccessible( true );
			$mapping = array_combine( $headers, $auto_map->invoke( $controller, $headers ) );

			$importer     = new WC_Product_CSV_Importer(
				$csv_file,
				array(
					'parse'   => true,
					'mapping' => $mapping,
				)
			);
			$data         = $importer->import();
			$imported_ids = array_merge( $data['imported'], $data['imported_variations'] );

			$this->assertCount( 2, $data['imported_variations'], 'Expected 2 variations to be imported.' );

			foreach ( $data['imported_variations'] as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				$this->assertInstanceOf( WC_Product_Variation::class, $variation );
				$this->assertEmpty(
					wp_get_object_terms( $variation_id, 'product_cat', array( 'fields' => 'ids' ) ),
					'Imported variations must not be assigned any product category.'
				);
				$this->assertEmpty(
					wp_get_object_terms( $variation_id, 'product_tag', array( 'fields' => 'ids' ) ),
					'Imported variations must not be assigned any product tag.'
				);
			}
		} finally {
			foreach ( $imported_ids as $id ) {
				WC_Helper_Product::delete_product( $id );
			}
			update_option( 'default_product_cat', $previous_default_cat );
		}
	}

	/**
	 * @testdox Re-importing an existing variation with update_existing enabled preserves taxonomy terms attached to it.
	 */
	public function test_reimporting_existing_variation_preserves_attached_terms() {
		// Term creation during import requires the manage_product_terms capability.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$imported_ids = array();
		$term_id      = 0;

		try {
			// Build the header-to-field mapping the way the admin import UI does.
			$csv_file   = __DIR__ . '/variation-category-31815.csv';
			$headers    = ( new WC_Product_CSV_Importer( $csv_file, array( 'parse' => false ) ) )->get_raw_keys();
			$controller = new WC_Product_CSV_Importer_Controller();
			$auto_map   = new ReflectionMethod( $controller, 'auto_map_columns' );
			$auto_map->setAccessible( true );
			$mapping = array_combine( $headers, $auto_map->invoke( $controller, $headers ) );

			$data = ( new WC_Product_CSV_Importer(
				$csv_file,
				array(
					'parse'   => true,
					'mapping' => $mapping,
				)
			) )->import();

			$imported_ids = array_merge( $data['imported'], $data['imported_variations'] );
			$this->assertCount( 2, $data['imported_variations'], 'Expected 2 variations to be imported.' );

			// Simulate an extension attaching a category to an existing variation.
			$inserted     = wp_insert_term( 'Variation Extension Category', 'product_cat' );
			$term_id      = is_wp_error( $inserted ) ? (int) $inserted->get_error_data( 'term_exists' ) : $inserted['term_id'];
			$variation_id = $data['imported_variations'][0];
			wp_set_object_terms( $variation_id, array( $term_id ), 'product_cat' );

			$update = ( new WC_Product_CSV_Importer(
				$csv_file,
				array(
					'parse'           => true,
					'mapping'         => $mapping,
					'update_existing' => true,
				)
			) )->import();

			$this->assertContains( $variation_id, $update['updated'], 'Expected the existing variation to be updated by the re-import.' );
			$this->assertSame(
				array( $term_id ),
				wp_get_object_terms( $variation_id, 'product_cat', array( 'fields' => 'ids' ) ),
				'Terms attached to an existing variation must survive a re-import that updates it.'
			);
		} finally {
			foreach ( $imported_ids as $id ) {
				WC_Helper_Product::delete_product( $id );
			}
			if ( $term_id ) {
				wp_delete_term( $term_id, 'product_cat' );
			}
		}
	}

	/**
	 * @testdox parse_float_field should respect the store's decimal separator setting (issue #38116).
	 * @dataProvider provider_parse_float_field_decimal_separator
	 *
	 * @param string $decimal_sep The store's decimal separator setting.
	 * @param string $value       The raw CSV value to parse.
	 * @param float  $expected    The expected parsed float.
	 */
	public function test_parse_float_field_respects_decimal_separator( string $decimal_sep, string $value, float $expected ) {
		add_filter( 'wc_get_price_decimal_separator', fn() => $decimal_sep );

		$importer = new WC_Product_CSV_Importer( __DIR__ . '/sample.csv' );
		$result   = $importer->parse_float_field( $value );

		$this->assertSame( $expected, $result, "Expected '{$value}' to parse to {$expected} with '{$decimal_sep}' as the decimal separator." );
	}

	/**
	 * Data provider for test_parse_float_field_respects_decimal_separator.
	 *
	 * @return array
	 */
	public function provider_parse_float_field_decimal_separator(): array {
		return array(
			'comma separator, comma value'   => array( ',', '1,5', 1.5 ),
			'comma separator, sub-one value' => array( ',', '0,5', 0.5 ),
			'period separator, period value' => array( '.', '1.5', 1.5 ),
			'comma separator, integer value' => array( ',', '10', 10.0 ),

			// With a period separator the comma is treated as a grouping separator and
			// stripped, mirroring how price fields (which also use wc_format_decimal) behave.
			// A comma-decimal CSV therefore requires the store's separator to be set to comma.
			'period separator, comma value'  => array( '.', '0,5', 5.0 ),
			'period separator, comma+int'    => array( '.', '1,5', 15.0 ),
		);
	}

	/**
	 * @testdox Attribute names with special characters should match existing global attributes on import instead of creating duplicates (issue #28172).
	 */
	public function test_import_matches_existing_attribute_with_special_characters_in_name_28172() {
		// Set admin user to allow term creation.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$attribute_id = wc_create_attribute(
			array(
				'name' => 'ARC Flash > Gloves > CE Category',
				'slug' => 'arc-glove-ce',
			)
		);
		$taxonomy     = wc_attribute_taxonomy_name_by_id( $attribute_id );
		register_taxonomy( $taxonomy, 'product' );
		wp_insert_term( 'Meeny', $taxonomy );

		$csv_file = __DIR__ . '/import-attribute-special-chars-28172-data.csv';
		$args     = array(
			'parse'   => true,
			'mapping' => array(
				'Name'                 => 'name',
				'Type'                 => 'type',
				'Attribute 1 name'     => 'attributes:name1',
				'Attribute 1 value(s)' => 'attributes:value1',
				'Attribute 1 visible'  => 'attributes:visible1',
				'Attribute 1 global'   => 'attributes:taxonomy1',
			),
		);
		$importer = new WC_Product_CSV_Importer( $csv_file, $args );

		$parsed = $importer->get_parsed_data();
		$this->assertSame( 'ARC Flash > Gloves > CE Category', $parsed[0]['raw_attributes'][0]['name'], 'The attribute name should not be HTML-encoded during parsing' );

		$data = $importer->import();
		$this->assertCount( 1, $data['imported'], 'Expected 1 imported product' );
		$this->assertEmpty( $data['failed'], 'Expected 0 failed products' );

		$this->assertSame( 0, wc_attribute_taxonomy_id_by_name( 'arc-flash-gloves-ce-category' ), 'A duplicate attribute should not be created' );

		$product = wc_get_product( $data['imported'][0] );
		$this->assertArrayHasKey( 'pa_arc-glove-ce', $product->get_attributes(), 'The product should use the existing attribute' );

		WC_Helper_Product::delete_product( $product->get_id() );
		wc_delete_attribute( $attribute_id );
	}

	/**
	 * @testdox parse_float_field should return an empty string unchanged.
	 */
	public function test_parse_float_field_returns_empty_string_unchanged() {
		$importer = new WC_Product_CSV_Importer( __DIR__ . '/sample.csv' );

		$this->assertSame( '', $importer->parse_float_field( '' ), 'Empty values should be returned unchanged.' );
	}

	/**
	 * @testdox Constructing the importer with a CSV file that cannot be opened should fail with a clear error.
	 */
	public function test_unopenable_csv_file_fails_with_clear_error() {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Unable to open the CSV file, please try again with a new file.' );

		new WC_Product_CSV_Importer( __DIR__ . '/does-not-exist.csv' );
	}

	/**
	 * @testdox Importing an empty CSV file should yield empty keys and data instead of fataling.
	 */
	public function test_empty_csv_file_yields_empty_keys_and_data() {
		$empty_csv = sys_get_temp_dir() . '/empty-import.csv';
		file_put_contents( $empty_csv, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture.

		try {
			$importer = new WC_Product_CSV_Importer( $empty_csv );

			$this->assertSame( array(), $importer->get_raw_keys(), 'An empty CSV file should produce no raw keys' );
			$this->assertSame( array(), $importer->get_raw_data(), 'An empty CSV file should produce no raw data' );
		} finally {
			unlink( $empty_csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- test fixture.
		}
	}

	/**
	 * @testdox adjust_character_encoding should convert values from the configured encoding to UTF-8 (issue #38541).
	 * @dataProvider provider_adjust_character_encoding
	 *
	 * @param string $encoding The configured character encoding.
	 * @param string $value    The raw value expressed in that encoding.
	 * @param string $expected The expected UTF-8 value.
	 */
	public function test_adjust_character_encoding_converts_to_utf8( string $encoding, string $value, string $expected ) {
		if ( ! function_exists( 'mb_convert_encoding' ) ) {
			$this->markTestSkipped( 'The mbstring extension is required for this test.' );
		}

		$importer = new WC_Product_CSV_Importer( __DIR__ . '/sample.csv', array( 'character_encoding' => $encoding ) );

		$method = new ReflectionMethod( WC_Product_CSV_Importer::class, 'adjust_character_encoding' );
		$method->setAccessible( true );

		$this->assertSame(
			$expected,
			$method->invoke( $importer, $value ),
			"Expected a '{$encoding}' value to be converted to UTF-8."
		);
	}

	/**
	 * Data provider for test_adjust_character_encoding_converts_to_utf8.
	 *
	 * The é character is the single byte 0xE9 in both ISO-8859-1 and Windows-1252, and the
	 * two-byte sequence 0xC3 0xA9 in UTF-8.
	 *
	 * @return array
	 */
	public function provider_adjust_character_encoding(): array {
		return array(
			'UTF-8 is returned unchanged' => array( 'UTF-8', 'Café', 'Café' ),
			'ISO-8859-1 is converted'     => array( 'ISO-8859-1', "Caf\xE9", 'Café' ),
			'Windows-1252 is converted'   => array( 'Windows-1252', "Caf\xE9", 'Café' ),
		);
	}
}
