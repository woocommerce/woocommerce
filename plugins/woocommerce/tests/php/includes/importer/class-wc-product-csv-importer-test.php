<?php
/**
 * Unit tests for the WC_Product_CSV_Importer_Test class.
 *
 * @package WooCommerce\Tests\Importer.
 */

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
	 * @testdox variations need to set the status back to published if parent product is a draft
	 */
	public function test_expand_data_with_draft_variable() {
		$csv_file = __DIR__ . '/sample.csv';
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
	 * @testdox parse_images_field preserves slashes in relative paths so deep paths can be matched against the media library.
	 */
	public function test_parse_images_field_preserves_relative_paths() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$result = $importer->parse_images_field( 'media/image/ab/cd/ef/qa-test-image-001.jpg' );

		$this->assertSame( array( 'media/image/ab/cd/ef/qa-test-image-001.jpg' ), $result );
	}

	/**
	 * @testdox parse_images_field preserves numeric attachment IDs so pre-registered media can be attached.
	 */
	public function test_parse_images_field_preserves_numeric_attachment_ids() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$result = $importer->parse_images_field( '38191' );

		$this->assertSame( array( '38191' ), $result );
	}

	/**
	 * @testdox parse_images_field still sanitizes plain filenames.
	 */
	public function test_parse_images_field_sanitizes_plain_filenames() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$result = $importer->parse_images_field( 'image with spaces.jpg' );

		$this->assertSame( array( 'image-with-spaces.jpg' ), $result );
	}

	/**
	 * @testdox parse_images_field accepts a mixed list of URLs, paths, IDs and filenames.
	 */
	public function test_parse_images_field_mixed_inputs() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$result = $importer->parse_images_field( 'https://example.com/foo.jpg, media/image/ab/cd/ef/bar.jpg, 38191, plain.jpg' );

		$this->assertSame(
			array(
				'https://example.com/foo.jpg',
				'media/image/ab/cd/ef/bar.jpg',
				'38191',
				'plain.jpg',
			),
			$result
		);
	}

	/**
	 * @testdox get_attachment_id_from_url resolves a numeric value to an existing attachment post ID.
	 */
	public function test_get_attachment_id_from_url_resolves_numeric_attachment_id() {
		$attachment_id = wp_insert_post(
			array(
				'post_title'     => 'qa-test-image-001',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);

		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$resolved = $importer->get_attachment_id_from_url( (string) $attachment_id, 0 );

		$this->assertSame( (int) $attachment_id, $resolved );

		wp_delete_post( $attachment_id, true );
	}

	/**
	 * @testdox get_attachment_id_from_url throws when a numeric value does not correspond to an attachment.
	 */
	public function test_get_attachment_id_from_url_throws_for_unknown_numeric_id() {
		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to use image "999999999"' );

		$importer->get_attachment_id_from_url( '999999999', 0 );
	}

	/**
	 * @testdox get_attachment_id_from_url matches an attachment registered at a deep nested upload path.
	 */
	public function test_get_attachment_id_from_url_matches_deep_relative_path() {
		$relative_path = 'media/image/ab/cd/ef/qa-test-image-001.jpg';

		$attachment_id = wp_insert_post(
			array(
				'post_title'     => 'qa-test-image-001',
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $attachment_id, '_wp_attached_file', $relative_path );

		$csv_file = __DIR__ . '/sample.csv';
		$importer = new WC_Product_CSV_Importer( $csv_file );

		$resolved = $importer->get_attachment_id_from_url( $relative_path, 0 );

		$this->assertSame( (int) $attachment_id, $resolved );

		wp_delete_post( $attachment_id, true );
	}
}
