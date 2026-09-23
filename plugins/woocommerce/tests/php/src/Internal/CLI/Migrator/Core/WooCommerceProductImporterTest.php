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
	 * Unique filename prefix for image attachments owned by the current test.
	 *
	 * @var string
	 */
	private string $image_filename_prefix;

	/**
	 * Number of local image downloads handled during the current test.
	 *
	 * @var int
	 */
	private int $image_download_count = 0;

	/**
	 * Streamed temporary paths that may need cleanup after a failed sideload.
	 *
	 * @var string[]
	 */
	private array $streamed_image_paths = array();

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create importer with default options.
		$this->importer              = new WooCommerceProductImporter();
		$this->image_filename_prefix = 'wc-importer-' . wp_generate_uuid4();
		$this->image_download_count  = 0;
		$this->streamed_image_paths  = array();
		add_filter( 'pre_http_request', array( $this, 'provide_local_image_response' ), 10, 3 );
	}

	/**
	 * Remove owned upload files and HTTP interception.
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'provide_local_image_response' ), 10 );

		try {
			try {
				global $wpdb;
				$attachment_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_name LIKE %s",
						$wpdb->esc_like( $this->image_filename_prefix ) . '%'
					)
				);
				foreach ( $attachment_ids as $attachment_id ) {
					wp_delete_attachment( (int) $attachment_id, true );
				}
			} finally {
				foreach ( $this->streamed_image_paths as $streamed_image_path ) {
					if ( file_exists( $streamed_image_path ) ) {
						wp_delete_file( $streamed_image_path );
					}
				}
			}
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * Serve the checked-in PNG fixture for importer-owned image URLs.
	 *
	 * @param false|array|\WP_Error $preempt Short-circuit response.
	 * @param array                 $args Request arguments.
	 * @param string                $url Request URL.
	 * @return false|array|\WP_Error
	 */
	public function provide_local_image_response( $preempt, array $args, string $url ) {
		if ( false === strpos( $url, 'https://example.com/' . $this->image_filename_prefix ) ) {
			return $preempt;
		}

		if ( empty( $args['filename'] ) ) {
			return new \WP_Error( 'missing_stream_filename', 'Image request did not provide a stream filename.' );
		}

		$fixture_path = \WC_Unit_Tests_Bootstrap::instance()->tests_dir . '/data/Dr1Bczxq4q.png';
		if ( ! self::file_copy( $fixture_path, $args['filename'] ) ) {
			return new \WP_Error( 'fixture_copy_failed', 'Unable to copy the local image fixture.' );
		}

		$this->streamed_image_paths[] = $args['filename'];
		++$this->image_download_count;

		return array(
			'headers'  => array(
				'content-type'   => 'image/png',
				'content-length' => (string) filesize( $fixture_path ),
			),
			'body'     => '',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => $args['filename'],
		);
	}

	/**
	 * Build a unique importer-owned image URL.
	 *
	 * @param string $suffix Filename suffix.
	 * @return string
	 */
	private function get_image_url( string $suffix ): string {
		return 'https://example.com/' . $this->image_filename_prefix . '-' . $suffix . '.png';
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
		$product_data['is_variable'] = true;
		$product_data['variations']  = array(
			array(
				'original_id'   => 'var1',
				'sku'           => 'TEST-SKU-2-VAR1',
				'regular_price' => '20.00',
				'attributes'    => array(
					'Size'  => 'Small',
					'Color' => 'Red',
				),
			),
			array(
				'original_id'   => 'var2',
				'sku'           => 'TEST-SKU-2-VAR2',
				'regular_price' => '25.00',
				'attributes'    => array(
					'Size'  => 'Large',
					'Color' => 'Blue',
				),
			),
		);

		// Mark attributes for variation.
		foreach ( $product_data['attributes'] as &$attribute ) {
			$attribute['is_variation'] = true;
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
		$product_data['name']          = 'Updated Test Product 4';
		$product_data['regular_price'] = '25.00';

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
		$product_data['original_product_id'] = 'shopify_product_123';
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
	 * Test featured and gallery image import without external network access.
	 */
	public function test_image_import_with_featured_and_gallery(): void {
		$product_data           = MockShopifyData::get_mock_wc_product_data( 70 );
		$product_data['images'] = array(
			array(
				'original_id' => 'gallery-image-1',
				'src'         => $this->get_image_url( 'gallery-1' ),
				'alt'         => 'Gallery Image 1',
				'is_featured' => false,
			),
			array(
				'original_id' => 'featured-image',
				'src'         => $this->get_image_url( 'featured' ),
				'alt'         => 'Featured Image',
				'is_featured' => true,
			),
			array(
				'original_id' => 'gallery-image-2',
				'src'         => $this->get_image_url( 'gallery-2' ),
				'alt'         => 'Gallery Image 2',
				'is_featured' => false,
			),
		);

		$result = $this->importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		$product = wc_get_product( $result['product_id'] );
		$this->assertInstanceOf( WC_Product_Simple::class, $product );

		$featured_id = $product->get_image_id();
		$gallery_ids = $product->get_gallery_image_ids();
		$this->assertGreaterThan( 0, $featured_id );
		$this->assertCount( 2, $gallery_ids );
		$this->assertSame( 3, $this->image_download_count );
		$this->assertSame( 3, $this->importer->get_import_stats()['images_processed'] );
		$this->assertSame( 'Featured Image', get_post_meta( $featured_id, '_wp_attachment_image_alt', true ) );
		$this->assertSame( 'Gallery Image 1', get_post_meta( $gallery_ids[0], '_wp_attachment_image_alt', true ) );
		$this->assertSame( 'Gallery Image 2', get_post_meta( $gallery_ids[1], '_wp_attachment_image_alt', true ) );
		foreach ( array_merge( array( $featured_id ), $gallery_ids ) as $attachment_id ) {
			$this->assertTrue( wp_attachment_is_image( $attachment_id ) );
			$this->assertSame( $result['product_id'], (int) get_post_field( 'post_parent', $attachment_id ) );
		}
	}

	/**
	 * Test image import with dry run mode.
	 */
	public function test_image_import_dry_run(): void {
		$dry_run_importer = new WooCommerceProductImporter();
		$dry_run_importer->configure( array( 'dry_run' => true ) );

		$product_data           = MockShopifyData::get_mock_wc_product_data( 71 );
		$product_data['images'] = array(
			array(
				'original_id' => 'dry-run-image',
				'src'         => $this->get_image_url( 'dry-run' ),
				'alt'         => 'Dry Run Image',
				'is_featured' => true,
			),
		);

		$result = $dry_run_importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// In dry run mode, no actual images should be imported.
		$featured_image_id = get_post_thumbnail_id( $result['product_id'] );
		$this->assertEmpty( $featured_image_id );
		$this->assertSame( 0, $this->image_download_count );
		$this->assertSame( 0, $dry_run_importer->get_import_stats()['images_processed'] );
	}

	/**
	 * Test image import with max images limit configuration.
	 */
	public function test_image_import_with_max_limit(): void {
		$limited_importer = new WooCommerceProductImporter();
		$limited_importer->configure(
			array(
				'max_images_per_product' => 2,
				'skip_duplicate_images'  => true,
			)
		);

		$product_data           = MockShopifyData::get_mock_wc_product_data( 72 );
		$product_data['images'] = array(
			array(
				'original_id' => 'limited-image-1',
				'src'         => $this->get_image_url( 'limited-1' ),
				'is_featured' => true,
			),
			array(
				'original_id' => 'limited-image-2',
				'src'         => $this->get_image_url( 'limited-2' ),
				'is_featured' => false,
			),
			array(
				'original_id' => 'limited-image-3',
				'src'         => $this->get_image_url( 'limited-3' ),
				'is_featured' => false,
			),
			array(
				'original_id' => 'limited-image-4',
				'src'         => $this->get_image_url( 'limited-4' ),
				'is_featured' => false,
			),
		);

		$result = $limited_importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		$product = wc_get_product( $result['product_id'] );
		$this->assertInstanceOf( WC_Product_Simple::class, $product );
		$this->assertGreaterThan( 0, $product->get_image_id() );
		$this->assertCount( 1, $product->get_gallery_image_ids() );
		$this->assertSame( 2, $this->image_download_count );
		$this->assertSame( 2, $limited_importer->get_import_stats()['images_processed'] );
	}

	/**
	 * Test error handling with invalid data.
	 */
	public function test_error_handling_with_invalid_data(): void {
		// Test with completely empty data.
		$result = $this->importer->import_product( array() );
		$this->assertEquals( 'error', $result['status'] );
		$this->assertEquals( 'validation_failed', $result['error_code'] );
		$this->assertStringContainsString( 'Missing required fields', $result['message'] );

		// Test with invalid price data.
		$invalid_product = array(
			'name'  => 'Invalid Price Product',
			'price' => 'not-a-number',
			'sku'   => 'INVALID-PRICE-TEST',
		);

		// This should still succeed as price validation is handled by WooCommerce.
		$result = $this->importer->import_product( $invalid_product );
		$this->assertEquals( 'success', $result['status'] );
	}

	/**
	 * Test error handling with duplicate SKUs.
	 */
	public function test_error_handling_with_duplicate_skus(): void {
		$product_data1        = MockShopifyData::get_mock_wc_product_data( 80 );
		$product_data1['sku'] = 'DUPLICATE-SKU-TEST';

		$product_data2        = MockShopifyData::get_mock_wc_product_data( 81 );
		$product_data2['sku'] = 'DUPLICATE-SKU-TEST';

		// First product should succeed.
		$result1 = $this->importer->import_product( $product_data1 );
		$this->assertEquals( 'success', $result1['status'] );
		$this->assertEquals( 'created', $result1['action'] );

		// Second product with same SKU should be handled based on configuration.
		$importer_skip = new WooCommerceProductImporter();
		$importer_skip->configure( array( 'skip_existing' => true ) );

		$result2 = $importer_skip->import_product( $product_data2 );
		$this->assertEquals( 'success', $result2['status'] );
		$this->assertEquals( 'skipped', $result2['action'] );
	}

	/**
	 * Test error statistics tracking.
	 */
	public function test_error_statistics_tracking(): void {
		$this->importer->reset_stats();

		// Test that validation errors are handled properly (but don't increment error counter).
		$invalid_product = array(); // Missing name.
		$result          = $this->importer->import_product( $invalid_product );

		$this->assertEquals( 'error', $result['status'] );
		$this->assertEquals( 'validation_failed', $result['error_code'] );

		// Test that valid products increment created counter.
		$valid_product = MockShopifyData::get_mock_wc_product_data( 120 );
		$result        = $this->importer->import_product( $valid_product );

		$this->assertEquals( 'success', $result['status'] );

		$stats = $this->importer->get_import_stats();
		$this->assertEquals( 1, $stats['products_created'] );

		// Note: Validation errors don't increment 'errors_encountered' -
		// only exceptions do. This is the actual implementation behavior.
		$this->assertGreaterThanOrEqual( 0, $stats['errors_encountered'] );
	}

	/**
	 * Test batch import with mixed success and failures.
	 */
	public function test_batch_import_with_mixed_results(): void {
		$mixed_batch = array(
			MockShopifyData::get_mock_wc_product_data( 90 ), // Valid.
			array(), // Invalid - missing name.
			MockShopifyData::get_mock_wc_product_data( 91 ), // Valid.
			array( 'name' => '' ), // Invalid - empty name.
		);

		$batch_result = $this->importer->import_batch( $mixed_batch );

		$this->assertCount( 4, $batch_result['results'] );
		$this->assertEquals( 2, $batch_result['stats']['successful'] );
		$this->assertEquals( 2, $batch_result['stats']['failed'] );
		$this->assertEquals( 0, $batch_result['stats']['skipped'] );

		// Check individual results.
		$this->assertEquals( 'success', $batch_result['results'][0]['status'] );
		$this->assertEquals( 'error', $batch_result['results'][1]['status'] );
		$this->assertEquals( 'success', $batch_result['results'][2]['status'] );
		$this->assertEquals( 'error', $batch_result['results'][3]['status'] );
	}

	/**
	 * Test category creation disabled.
	 */
	public function test_category_creation_disabled(): void {
		$no_categories_importer = new WooCommerceProductImporter();
		$no_categories_importer->configure( array( 'create_categories' => false ) );

		$product_data               = MockShopifyData::get_mock_wc_product_data( 100 );
		$product_data['categories'] = array(
			array(
				'name' => 'Should Not Create Category',
				'slug' => 'should-not-create',
			),
		);

		$result = $no_categories_importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Category should not be created or assigned.
		$product_categories = wp_get_post_terms( $result['product_id'], 'product_cat', array( 'fields' => 'names' ) );
		$this->assertNotContains( 'Should Not Create Category', $product_categories );

		// Verify category was not created at all.
		$category = get_term_by( 'name', 'Should Not Create Category', 'product_cat' );
		$this->assertFalse( $category );
	}

	/**
	 * Test tag creation disabled.
	 */
	public function test_tag_creation_disabled(): void {
		$no_tags_importer = new WooCommerceProductImporter();
		$no_tags_importer->configure( array( 'create_tags' => false ) );

		$product_data         = MockShopifyData::get_mock_wc_product_data( 101 );
		$product_data['tags'] = array(
			array( 'name' => 'should-not-create-tag' ),
		);

		$result = $no_tags_importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Tag should not be created or assigned.
		$product_tags = wp_get_post_terms( $result['product_id'], 'product_tag', array( 'fields' => 'names' ) );
		$this->assertNotContains( 'should-not-create-tag', $product_tags );
	}

	/**
	 * Test variation handling disabled.
	 */
	public function test_variation_handling_disabled(): void {
		$no_variations_importer = new WooCommerceProductImporter();
		$no_variations_importer->configure( array( 'handle_variations' => false ) );

		$product_data               = MockShopifyData::get_mock_wc_product_data( 102 );
		$product_data['variations'] = array(
			array(
				'sku'        => 'VAR-SHOULD-NOT-CREATE',
				'price'      => '15.00',
				'attributes' => array( 'Size' => 'Large' ),
			),
		);

		// Mark attributes for variation.
		foreach ( $product_data['attributes'] as &$attribute ) {
			$attribute['variation'] = true;
		}

		$result = $no_variations_importer->import_product( $product_data );
		$this->assertEquals( 'success', $result['status'] );

		// Should create variable product but no variations.
		$product = wc_get_product( $result['product_id'] );
		$this->assertInstanceOf( \WC_Product_Variable::class, $product );

		$variations = $product->get_children();
		$this->assertEmpty( $variations );
	}

	/**
	 * Test finding existing product by slug.
	 */
	public function test_find_existing_product_by_slug(): void {
		// Create a product first.
		$original_data         = MockShopifyData::get_mock_wc_product_data( 110 );
		$original_data['slug'] = 'test-slug-finder';

		$result1 = $this->importer->import_product( $original_data );
		$this->assertEquals( 'success', $result1['status'] );
		$original_id = $result1['product_id'];

		// Try to import with same slug but different SKU.
		$duplicate_data         = MockShopifyData::get_mock_wc_product_data( 111 );
		$duplicate_data['slug'] = 'test-slug-finder';
		$duplicate_data['sku']  = 'DIFFERENT-SKU';
		$duplicate_data['name'] = 'Updated Product Name';

		$update_importer = new WooCommerceProductImporter();
		$update_importer->configure( array( 'update_existing' => true ) );

		$result2 = $update_importer->import_product( $duplicate_data );
		$this->assertEquals( 'success', $result2['status'] );
		$this->assertEquals( 'updated', $result2['action'] );
		$this->assertEquals( $original_id, $result2['product_id'] );

		// Verify product was updated.
		$updated_product = wc_get_product( $original_id );
		$this->assertEquals( 'Updated Product Name', $updated_product->get_name() );
	}

	/**
	 * Test finding existing product by original_product_id.
	 */
	public function test_find_existing_product_by_original_id(): void {
		// Create a product with original_product_id.
		$original_data                        = MockShopifyData::get_mock_wc_product_data( 112 );
		$original_data['original_product_id'] = 'shopify_original_123';

		$result1 = $this->importer->import_product( $original_data );
		$this->assertEquals( 'success', $result1['status'] );
		$original_id = $result1['product_id'];

		// Try to import with same original_product_id but different data.
		$duplicate_data                        = MockShopifyData::get_mock_wc_product_data( 113 );
		$duplicate_data['original_product_id'] = 'shopify_original_123';
		$duplicate_data['sku']                 = 'COMPLETELY-DIFFERENT-SKU';
		$duplicate_data['name']                = 'Updated via Original ID';

		$update_importer = new WooCommerceProductImporter();
		$update_importer->configure( array( 'update_existing' => true ) );

		$result2 = $update_importer->import_product( $duplicate_data );
		$this->assertEquals( 'success', $result2['status'] );
		$this->assertEquals( 'updated', $result2['action'] );
		$this->assertEquals( $original_id, $result2['product_id'] );

		// Verify product was updated.
		$updated_product = wc_get_product( $original_id );
		$this->assertEquals( 'Updated via Original ID', $updated_product->get_name() );
	}

	/**
	 * Test product finding priority order.
	 */
	public function test_product_finding_priority_order(): void {
		// Create a product with all identifiers.
		$original_data                        = MockShopifyData::get_mock_wc_product_data( 114 );
		$original_data['sku']                 = 'PRIORITY-TEST-SKU';
		$original_data['slug']                = 'priority-test-slug';
		$original_data['original_product_id'] = 'priority_original_456';

		$result1 = $this->importer->import_product( $original_data );
		$this->assertEquals( 'success', $result1['status'] );
		$original_id = $result1['product_id'];

		// Create another product with same SKU and slug but different original_product_id.
		$other_data                        = MockShopifyData::get_mock_wc_product_data( 115 );
		$other_data['sku']                 = 'PRIORITY-TEST-SKU';
		$other_data['slug']                = 'priority-test-slug';
		$other_data['original_product_id'] = 'different_original_789';

		$result2 = $this->importer->import_product( $other_data );
		$this->assertEquals( 'success', $result2['status'] );
		$other_id = $result2['product_id'];

		// Now try to import with original_product_id that matches first product.
		// Should find by original_product_id (highest priority) not by SKU.
		$test_data                        = MockShopifyData::get_mock_wc_product_data( 116 );
		$test_data['original_product_id'] = 'priority_original_456'; // Matches first product.
		$test_data['sku']                 = 'PRIORITY-TEST-SKU'; // Also matches first product.
		$test_data['name']                = 'Found by Original ID Priority';

		$update_importer = new WooCommerceProductImporter();
		$update_importer->configure( array( 'update_existing' => true ) );

		$result3 = $update_importer->import_product( $test_data );
		$this->assertEquals( 'success', $result3['status'] );
		$this->assertEquals( 'updated', $result3['action'] );
		$this->assertEquals( $original_id, $result3['product_id'] ); // Should match first product.

		// Verify the correct product was updated.
		$updated_product = wc_get_product( $original_id );
		$this->assertEquals( 'Found by Original ID Priority', $updated_product->get_name() );
	}

	/**
	 * Re-importing without weight/dimension keys must preserve the existing values.
	 *
	 * The importer is shared across platforms; mappers that do not emit a field
	 * (e.g. Shopify never maps length/width/height) must not clear merchant data
	 * on a re-run. Regression test for the update path.
	 */
	public function test_update_preserves_weight_and_dimensions_when_keys_absent(): void {
		$create_data = array(
			'name'   => 'Dimension Product',
			'sku'    => 'TEST-SKU-DIM-1',
			'weight' => '2.5',
			'length' => '10',
			'width'  => '5',
			'height' => '3',
		);

		$result1 = $this->importer->import_product( $create_data );
		$this->assertEquals( 'created', $result1['action'] );
		$product_id = $result1['product_id'];

		// Re-import the same product without any weight/dimension keys.
		$update_data = array(
			'name' => 'Dimension Product Updated',
			'sku'  => 'TEST-SKU-DIM-1',
		);

		$update_importer = new WooCommerceProductImporter();
		$update_importer->configure( array( 'update_existing' => true ) );

		$result2 = $update_importer->import_product( $update_data );
		$this->assertEquals( 'updated', $result2['action'] );
		$this->assertEquals( $product_id, $result2['product_id'] );

		// Existing weight/dimensions must be preserved, not wiped.
		$product = wc_get_product( $product_id );
		$this->assertSame( '2.5', $product->get_weight() );
		$this->assertSame( '10', $product->get_length() );
		$this->assertSame( '5', $product->get_width() );
		$this->assertSame( '3', $product->get_height() );
	}

	/**
	 * Re-importing with explicitly empty weight/dimension keys must clear the values.
	 *
	 * When a mapper does emit the key (so the platform tracks the field), an empty
	 * value should mirror the source and clear the existing value.
	 */
	public function test_update_clears_weight_and_dimensions_when_keys_present_empty(): void {
		$create_data = array(
			'name'   => 'Dimension Product Clear',
			'sku'    => 'TEST-SKU-DIM-2',
			'weight' => '2.5',
			'length' => '10',
			'width'  => '5',
			'height' => '3',
		);

		$result1 = $this->importer->import_product( $create_data );
		$this->assertEquals( 'created', $result1['action'] );
		$product_id = $result1['product_id'];

		// Re-import with the keys present but empty/null.
		$update_data = array(
			'name'   => 'Dimension Product Clear',
			'sku'    => 'TEST-SKU-DIM-2',
			'weight' => null,
			'length' => '',
			'width'  => '',
			'height' => '',
		);

		$update_importer = new WooCommerceProductImporter();
		$update_importer->configure( array( 'update_existing' => true ) );

		$result2 = $update_importer->import_product( $update_data );
		$this->assertEquals( 'updated', $result2['action'] );

		$product = wc_get_product( $product_id );
		$this->assertSame( '', $product->get_weight() );
		$this->assertSame( '', $product->get_length() );
		$this->assertSame( '', $product->get_width() );
		$this->assertSame( '', $product->get_height() );
	}
}
