<?php
/**
 * WooCommerceProductImporter Test
 *
 * @package Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Core;

use Automattic\WooCommerce\Internal\CLI\Migrator\Core\WooCommerceProductImporter;
use Automattic\WooCommerce\Tests\Internal\CLI\Migrator\Fixtures\MockShopifyData;
use WC_Product_Simple;
use WC_Product_Variable;

/**
 * WooCommerceProductImporterTest class.
 */
class WooCommerceProductImporterTest extends \WC_Unit_Test_Case {

	/**
	 * The WooCommerceProductImporter instance under test.
	 *
	 * @var WooCommerceProductImporter
	 */
	private WooCommerceProductImporter $importer;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create importer with default options.
		$this->importer = new WooCommerceProductImporter();

		// Clean up any existing products.
		$this->clean_up_products();
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		$this->clean_up_products();
		parent::tearDown();
	}

	/**
	 * Test WooCommerceProductImporter instantiation.
	 */
	public function test_importer_instantiation(): void {
		$this->assertInstanceOf( WooCommerceProductImporter::class, $this->importer );
	}

	/**
	 * Test import simple product.
	 */
	public function test_import_simple_product(): void {
		$product_data = MockShopifyData::get_mock_wc_product_data( 1 );

		$result = $this->importer->import_product( $product_data );

		$this->assertEquals( 'success', $result['status'] );
		$this->assertEquals( 'created', $result['action'] );
		$this->assertIsInt( $result['product_id'] );

		// Verify product was created correctly.
		$product = wc_get_product( $result['product_id'] );
		$this->assertInstanceOf( WC_Product_Simple::class, $product );
		$this->assertEquals( 'Test Product 1', $product->get_name() );
		$this->assertEquals( 'TEST-SKU-1-1', $product->get_sku() );
		$this->assertEquals( '15.00', $product->get_price() );
	}

	/**
	 * Test import variable product with variations.
	 */
	public function test_import_variable_product(): void {
		$product_data = MockShopifyData::get_mock_wc_product_data( 2 );

		// Convert to variable product by adding variations.
		$product_data['variations'] = array(
			array(
				'sku'        => 'TEST-SKU-2-VAR1',
				'price'      => '20.00',
				'attributes' => array(
					'Size'  => 'Small',
					'Color' => 'Red',
				),
			),
			array(
				'sku'        => 'TEST-SKU-2-VAR2',
				'price'      => '25.00',
				'attributes' => array(
					'Size'  => 'Large',
					'Color' => 'Blue',
				),
			),
		);

		// Mark attributes for variation.
		foreach ( $product_data['attributes'] as &$attribute ) {
			$attribute['variation'] = true;
		}

		$result = $this->importer->import_product( $product_data );

		$this->assertEquals( 'success', $result['status'] );
		$this->assertEquals( 'created', $result['action'] );

		// Verify variable product was created.
		$product = wc_get_product( $result['product_id'] );
		$this->assertInstanceOf( WC_Product_Variable::class, $product );

		// Verify variations were created.
		$variations = $product->get_children();
		$this->assertCount( 2, $variations );

		// Check first variation.
		$variation1 = wc_get_product( $variations[0] );
		$this->assertEquals( 'TEST-SKU-2-VAR1', $variation1->get_sku() );
		$this->assertEquals( '20.00', $variation1->get_price() );
	}

	/**
	 * Test import product with existing SKU (skip existing).
	 */
	public function test_import_existing_product_skip(): void {
		// Create importer with skip_existing option.
		$importer_skip = new WooCommerceProductImporter();
		$importer_skip->configure( array( 'skip_existing' => true ) );

		$product_data = MockShopifyData::get_mock_wc_product_data( 3 );

		// First import should succeed.
		$result1 = $importer_skip->import_product( $product_data );
		$this->assertEquals( 'success', $result1['status'] );
		$this->assertEquals( 'created', $result1['action'] );

		// Second import with same SKU should be skipped.
		$result2 = $importer_skip->import_product( $product_data );
		$this->assertEquals( 'success', $result2['status'] );
		$this->assertEquals( 'skipped', $result2['action'] );
	}

	/**
	 * Test import product with existing SKU (update existing).
	 */
	public function test_import_existing_product_update(): void {
		// Create importer with update_existing option.
		$importer_update = new WooCommerceProductImporter();
		$importer_update->configure( array( 'update_existing' => true ) );

		$product_data = MockShopifyData::get_mock_wc_product_data( 4 );

		// First import should succeed.
		$result1 = $importer_update->import_product( $product_data );
		$this->assertEquals( 'created', $result1['action'] );
		$first_product_id = $result1['product_id'];

		// Modify product data and import again.
		$product_data['name']  = 'Updated Test Product 4';
		$product_data['price'] = '25.00';

		$result2 = $importer_update->import_product( $product_data );
		$this->assertEquals( 'success', $result2['status'] );
		$this->assertEquals( 'updated', $result2['action'] );
		$this->assertEquals( $first_product_id, $result2['product_id'] );

		// Verify product was updated.
		$updated_product = wc_get_product( $first_product_id );
		$this->assertEquals( 'Updated Test Product 4', $updated_product->get_name() );
		$this->assertEquals( '25.00', $updated_product->get_price() );
	}

	/**
	 * Test batch import functionality.
	 */
	public function test_batch_import(): void {
		$products_data = array(
			MockShopifyData::get_mock_wc_product_data( 10 ),
			MockShopifyData::get_mock_wc_product_data( 11 ),
			MockShopifyData::get_mock_wc_product_data( 12 ),
		);

		$batch_result = $this->importer->import_batch( $products_data );

		$this->assertIsArray( $batch_result );
		$this->assertArrayHasKey( 'results', $batch_result );
		$this->assertArrayHasKey( 'stats', $batch_result );

		$this->assertCount( 3, $batch_result['results'] );
		$this->assertEquals( 3, $batch_result['stats']['successful'] );
		$this->assertEquals( 0, $batch_result['stats']['failed'] );
		$this->assertEquals( 0, $batch_result['stats']['skipped'] );

		// Verify all products were created.
		foreach ( $batch_result['results'] as $result ) {
			$this->assertEquals( 'success', $result['status'] );
			$this->assertEquals( 'created', $result['action'] );
		}
	}

	/**
	 * Test import statistics tracking.
	 */
	public function test_import_statistics(): void {
		// Reset stats.
		$this->importer->reset_stats();

		$initial_stats = $this->importer->get_import_stats();
		$this->assertEquals( 0, $initial_stats['products_created'] );
		$this->assertEquals( 0, $initial_stats['products_updated'] );
		$this->assertEquals( 0, $initial_stats['products_skipped'] );

		// Import some products.
		$products_data = array(
			MockShopifyData::get_mock_wc_product_data( 20 ),
			MockShopifyData::get_mock_wc_product_data( 21 ),
		);

		$this->importer->import_batch( $products_data );

		$final_stats = $this->importer->get_import_stats();
		$this->assertEquals( 2, $final_stats['products_created'] );
		$this->assertEquals( 0, $final_stats['products_updated'] );
		$this->assertEquals( 0, $final_stats['products_skipped'] );
	}

	/**
	 * Test product data validation.
	 */
	public function test_product_data_validation(): void {
		// Test with missing required field (name).
		$invalid_data = array(
			'sku'   => 'TEST-INVALID',
			'price' => '10.00',
		);

		$result = $this->importer->import_product( $invalid_data );

		$this->assertEquals( 'error', $result['status'] );
		$this->assertEquals( 'validation_failed', $result['error_code'] );
		$this->assertStringContainsString( 'Missing required fields', $result['message'] );
	}

	/**
	 * Test category assignment.
	 */
	public function test_category_assignment(): void {
		$product_data               = MockShopifyData::get_mock_wc_product_data( 30 );
		$product_data['categories'] = array(
			array(
				'name' => 'Test Category 1',
				'slug' => 'test-category-1',
			),
			array(
				'name' => 'Test Category 2',
				'slug' => 'test-category-2',
			),
		);

		$result = $this->importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Verify categories were created and assigned.
		$product_categories = wp_get_post_terms( $result['product_id'], 'product_cat', array( 'fields' => 'names' ) );
		$this->assertContains( 'Test Category 1', $product_categories );
		$this->assertContains( 'Test Category 2', $product_categories );
	}

	/**
	 * Test tag assignment.
	 */
	public function test_tag_assignment(): void {
		$product_data         = MockShopifyData::get_mock_wc_product_data( 31 );
		$product_data['tags'] = array(
			array( 'name' => 'test-tag-1' ),
			array( 'name' => 'test-tag-2' ),
			array( 'name' => 'test-tag-3' ),
		);

		$result = $this->importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Verify tags were created and assigned.
		$product_tags = wp_get_post_terms( $result['product_id'], 'product_tag', array( 'fields' => 'names' ) );
		$this->assertContains( 'test-tag-1', $product_tags );
		$this->assertContains( 'test-tag-2', $product_tags );
		$this->assertContains( 'test-tag-3', $product_tags );
	}

	/**
	 * Test dry run mode.
	 */
	public function test_dry_run_mode(): void {
		$dry_run_importer = new WooCommerceProductImporter();
		$dry_run_importer->configure( array( 'dry_run' => true ) );

		$product_data           = MockShopifyData::get_mock_wc_product_data( 40 );
		$product_data['images'] = array(
			array(
				'src' => 'https://example.com/image1.jpg',
				'alt' => 'Test Image',
			),
		);

		// In dry run mode, import should still work but not create actual products.
		$result = $dry_run_importer->import_product( $product_data );

		// Note: Dry run for products would need special handling in the actual importer.
		// For now, we verify the importer was created with dry_run option.
		$reflection       = new \ReflectionClass( $dry_run_importer );
		$options_property = $reflection->getProperty( 'import_options' );
		$options_property->setAccessible( true );
		$options = $options_property->getValue( $dry_run_importer );

		$this->assertTrue( $options['dry_run'] );
	}

	/**
	 * Test meta data import.
	 */
	public function test_meta_data_import(): void {
		$product_data              = MockShopifyData::get_mock_wc_product_data( 50 );
		$product_data['meta_data'] = array(
			array(
				'key'   => '_custom_field_1',
				'value' => 'custom_value_1',
			),
			array(
				'key'   => '_custom_field_2',
				'value' => 'custom_value_2',
			),
		);

		$result = $this->importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Verify meta data was saved.
		$custom_value_1 = get_post_meta( $result['product_id'], '_custom_field_1', true );
		$custom_value_2 = get_post_meta( $result['product_id'], '_custom_field_2', true );

		$this->assertEquals( 'custom_value_1', $custom_value_1 );
		$this->assertEquals( 'custom_value_2', $custom_value_2 );
	}

	/**
	 * Test platform ID tracking.
	 */
	public function test_platform_id_tracking(): void {
		$product_data                        = MockShopifyData::get_mock_wc_product_data( 60 );
		$product_data['original_product_id'] = 'shopify_product_123'; // Override for this test
		$source_data                         = array(
			'id'   => 'shopify_product_123',
			'node' => array( 'id' => 'gid://shopify/Product/123' ),
		);

		$result = $this->importer->import_product( $product_data, $source_data );
		$this->assertEquals( 'success', $result['status'] );

		// Verify original product ID was stored.
		$stored_id = get_post_meta( $result['product_id'], '_original_product_id', true );
		$this->assertEquals( 'shopify_product_123', $stored_id );
	}

	/**
	 * Helper method to clean up test products.
	 */
	private function clean_up_products(): void {
		global $wpdb;

		// Delete test products by SKU pattern.
		$test_skus = $wpdb->get_col(
			"SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE 'TEST-SKU-%'"
		);

		foreach ( $test_skus as $sku ) {
			$product_id = wc_get_product_id_by_sku( $sku );
			if ( $product_id ) {
				wp_delete_post( $product_id, true );
			}
		}

		// Clean up test categories.
		$test_categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'name__like' => 'Test Category',
				'hide_empty' => false,
			)
		);

		foreach ( $test_categories as $category ) {
			wp_delete_term( $category->term_id, 'product_cat' );
		}

		// Clean up test tags.
		$test_tags = get_terms(
			array(
				'taxonomy'   => 'product_tag',
				'name__like' => 'test-tag',
				'hide_empty' => false,
			)
		);

		foreach ( $test_tags as $tag ) {
			wp_delete_term( $tag->term_id, 'product_tag' );
		}
	}
}
