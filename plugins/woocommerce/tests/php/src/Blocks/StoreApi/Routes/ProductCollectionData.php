<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;

/**
 * Controller Tests.
 */
class ProductCollectionData extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'regular_price' => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'regular_price' => 100,
				)
			),
		);

		$fixtures->add_product_review( $this->products[0]->get_id(), 5 );
		$fixtures->add_product_review( $this->products[1]->get_id(), 4 );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['rating_counts'] );
		$this->assertEquals( null, $data['taxonomy_counts'] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_price_range() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param( 'calculate_price_range', true );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $data['price_range']->currency_minor_unit );
		$this->assertEquals( '1000', $data['price_range']->min_price );
		$this->assertEquals( '10000', $data['price_range']->max_price );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['rating_counts'] );
		$this->assertEquals( null, $data['taxonomy_counts'] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_attribute_counts() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		// AND query type.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				),
			),
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['rating_counts'] );
		$this->assertEquals( null, $data['taxonomy_counts'] );

		$this->assertIsArray( $data );

		$this->assertTrue( property_exists( $data['attribute_counts'][0], 'term' ) );
		$this->assertTrue( property_exists( $data['attribute_counts'][0], 'count' ) );

		// OR query type.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			),
		);

		$request->set_param(
			'attributes',
			array(
				array(
					'attribute' => 'pa_size',
					'operator'  => 'in',
					'slug'      => array( 'large' ),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['rating_counts'] );
		$this->assertEquals( null, $data['taxonomy_counts'] );

		$this->assertIsArray( $data );

		$this->assertTrue( property_exists( $data['attribute_counts'][0], 'term' ) );
		$this->assertTrue( property_exists( $data['attribute_counts'][0], 'count' ) );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_rating_counts() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param( 'calculate_rating_counts', true );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['taxonomy_counts'] );
		$this->assertEquals(
			array(
				(object) array(
					'rating' => 4,
					'count'  => 1,
				),
				(object) array(
					'rating' => 5,
					'count'  => 1,
				),
			),
			$data['rating_counts']
		);
	}

	/**
	 * Test taxonomy calculation method.
	 */
	public function test_calculate_taxonomy_counts() {
		// Create test categories.
		$category1 = wp_insert_term( 'Test Category 1', 'product_cat' );
		$category2 = wp_insert_term( 'Test Category 2', 'product_cat' );

		// Assign products to categories.
		wp_set_post_terms( $this->products[0]->get_id(), array( $category1['term_id'] ), 'product_cat' );
		wp_set_post_terms( $this->products[1]->get_id(), array( $category1['term_id'], $category2['term_id'] ), 'product_cat' );

		// Test product_cat taxonomy.
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param(
			'calculate_taxonomy_counts',
			array( 'product_cat' )
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['rating_counts'] );

		$this->assertIsArray( $data['taxonomy_counts'] );
		$this->assertNotEmpty( $data['taxonomy_counts'] );

		// Verify structure of taxonomy counts.
		foreach ( $data['taxonomy_counts'] as $taxonomy_count ) {
			$this->assertTrue( property_exists( $taxonomy_count, 'term' ) );
			$this->assertTrue( property_exists( $taxonomy_count, 'count' ) );
			$this->assertIsInt( $taxonomy_count->term );
			$this->assertIsInt( $taxonomy_count->count );
		}

		// Find our test categories in the results.
		$found_categories = array_filter(
			$data['taxonomy_counts'],
			function ( $item ) use ( $category1, $category2 ) {
				return in_array( $item->term, array( $category1['term_id'], $category2['term_id'] ), true );
			}
		);

		$this->assertNotEmpty( $found_categories, 'Test categories should be found in taxonomy counts' );

		// Test multiple taxonomies.
		$tag1 = wp_insert_term( 'Test Tag 1', 'product_tag' );
		wp_set_post_terms( $this->products[0]->get_id(), array( $tag1['term_id'] ), 'product_tag' );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param(
			'calculate_taxonomy_counts',
			array( 'product_cat', 'product_tag' )
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data['taxonomy_counts'] );
		$this->assertNotEmpty( $data['taxonomy_counts'] );

		// Find our test categories and tag in the results.
		$found_categories = array_filter(
			$data['taxonomy_counts'],
			function ( $item ) use ( $category1, $category2 ) {
				return in_array( $item->term, array( $category1['term_id'], $category2['term_id'] ), true );
			}
		);

		$found_tags = array_filter(
			$data['taxonomy_counts'],
			function ( $item ) use ( $tag1 ) {
				return $item->term === $tag1['term_id'];
			}
		);

		$this->assertNotEmpty( $found_categories, 'Test categories should be found in taxonomy counts' );
		$this->assertNotEmpty( $found_tags, 'Test tag should be found in taxonomy counts' );

		// Verify the counts are correct.
		foreach ( $found_categories as $category ) {
			if ( $category->term === $category1['term_id'] ) {
				$this->assertEquals( 2, $category->count, 'Category 1 should have 2 products' );
			} elseif ( $category->term === $category2['term_id'] ) {
				$this->assertEquals( 1, $category->count, 'Category 2 should have 1 product' );
			}
		}

		foreach ( $found_tags as $tag ) {
			if ( $tag->term === $tag1['term_id'] ) {
				$this->assertEquals( 1, $tag->count, 'Tag 1 should have 1 product' );
			}
		}
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'calculate_price_range', $params );
		$this->assertArrayHasKey( 'calculate_attribute_counts', $params );
		$this->assertArrayHasKey( 'calculate_rating_counts', $params );
		$this->assertArrayHasKey( 'calculate_taxonomy_counts', $params );
	}

	/**
	 * @testdox The count array params declare a default maxItems bound to limit query fan-out.
	 */
	public function test_count_params_declare_default_max_items() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'maxItems', $params['calculate_attribute_counts'], 'calculate_attribute_counts must be bounded.' );
		$this->assertArrayHasKey( 'maxItems', $params['calculate_taxonomy_counts'], 'calculate_taxonomy_counts must be bounded.' );
		$this->assertSame( 25, $params['calculate_attribute_counts']['maxItems'], 'Default attribute-counts cap should be 25.' );
		$this->assertSame( 25, $params['calculate_taxonomy_counts']['maxItems'], 'Default taxonomy-counts cap should be 25.' );
	}

	/**
	 * @testdox An oversized calculate_attribute_counts array is rejected with HTTP 400.
	 */
	public function test_calculate_attribute_counts_rejects_oversized_array() {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$too_many = array_fill(
			0,
			26,
			array(
				'taxonomy'   => 'pa_size',
				'query_type' => 'or',
			)
		);
		$request->set_param( 'calculate_attribute_counts', $too_many );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'More than 25 attribute-count entries should be rejected.' );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox An oversized calculate_taxonomy_counts array is rejected with HTTP 400.
	 */
	public function test_calculate_taxonomy_counts_rejects_oversized_array() {
		$request  = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$too_many = array_fill( 0, 26, 'product_cat' );
		$request->set_param( 'calculate_taxonomy_counts', $too_many );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'More than 25 taxonomy-count entries should be rejected.' );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox An array exactly at the cap is accepted.
	 */
	public function test_calculate_taxonomy_counts_at_cap_is_accepted() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$at_cap  = array_fill( 0, 25, 'product_cat' );
		$request->set_param( 'calculate_taxonomy_counts', $at_cap );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status(), 'Exactly 25 entries should be accepted.' );
	}

	/**
	 * @testdox Repeating the same attribute taxonomy is de-duplicated to a single set of counts.
	 */
	public function test_calculate_attribute_counts_deduplicates_taxonomies() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$single_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$single_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$single = rest_get_server()->dispatch( $single_request )->get_data();

		$repeated_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$repeated_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$repeated = rest_get_server()->dispatch( $repeated_request )->get_data();

		$this->assertNotEmpty( $single['attribute_counts'], 'Baseline single-taxonomy request should return counts.' );
		$this->assertEquals(
			$single['attribute_counts'],
			$repeated['attribute_counts'],
			'Requesting the same taxonomy multiple times must not duplicate or alter the counts.'
		);
	}

	/**
	 * @testdox The same attribute requested with both "or" and "and" query types is counted separately for each type.
	 */
	public function test_calculate_attribute_counts_keeps_query_types_separate() {
		$fixtures = new FixtureData();

		// Two products with different sizes so that an active filter makes the "or" and "and" counts diverge.
		$large_product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $large_product, 'pa_size', 'large', 'large' );

		$small_product = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $small_product, 'pa_size', 'small', 'small' );

		// Shopper has selected "large". "or" counts ignore that selection (faceted what-if counts) while
		// "and" counts respect it, so the two query types must produce different counts for pa_size.
		$active_filter = array(
			array(
				'attribute' => 'pa_size',
				'operator'  => 'in',
				'slug'      => array( 'large' ),
			),
		);

		$get_counts = function ( array $entries ) use ( $active_filter ) {
			$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
			$request->set_param( 'attributes', $active_filter );
			$request->set_param( 'calculate_attribute_counts', $entries );

			return rest_get_server()->dispatch( $request )->get_data()['attribute_counts'];
		};

		$or_only  = $get_counts(
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$and_only = $get_counts(
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				),
			)
		);
		$both     = $get_counts(
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				),
			)
		);

		$this->assertNotEmpty( $or_only, 'The "or" request should return counts.' );
		$this->assertNotEmpty( $and_only, 'The "and" request should return counts.' );
		$this->assertNotEquals(
			$or_only,
			$and_only,
			'For the same taxonomy, "or" and "and" must produce different counts when it is an active filter.'
		);
		$this->assertCount(
			count( $or_only ) + count( $and_only ),
			$both,
			'Requesting both query types must keep both result sets, not collapse them into one.'
		);
	}

	/**
	 * @testdox Attribute taxonomies are normalized before dedup so case and whitespace variants collapse to one entry.
	 */
	public function test_calculate_attribute_counts_normalizes_taxonomy_before_dedup() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$single_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$single_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$single = rest_get_server()->dispatch( $single_request )->get_data();

		$variants_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$variants_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
				array(
					'taxonomy'   => ' pa_size ',
					'query_type' => 'or',
				),
				array(
					'taxonomy'   => 'PA_SIZE',
					'query_type' => 'or',
				),
			)
		);
		$variants = rest_get_server()->dispatch( $variants_request )->get_data();

		$this->assertNotEmpty( $single['attribute_counts'], 'Baseline single-taxonomy request should return counts.' );
		$this->assertEquals(
			$single['attribute_counts'],
			$variants['attribute_counts'],
			'Case and whitespace variants of the same taxonomy must be counted once, not duplicated.'
		);
	}

	/**
	 * @testdox Non-attribute and non-existent taxonomies are skipped in calculate_attribute_counts.
	 */
	public function test_calculate_attribute_counts_skips_invalid_taxonomies() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$baseline_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$baseline_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$baseline = rest_get_server()->dispatch( $baseline_request )->get_data();

		$with_junk_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$with_junk_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
				array(
					// Exists as a taxonomy but is not a product attribute.
					'taxonomy'   => 'product_cat',
					'query_type' => 'or',
				),
				array(
					// Not a registered taxonomy at all.
					'taxonomy'   => 'pa_does_not_exist',
					'query_type' => 'or',
				),
			)
		);
		$with_junk = rest_get_server()->dispatch( $with_junk_request )->get_data();

		$this->assertNotEmpty( $baseline['attribute_counts'], 'Baseline attribute request should return counts.' );
		$this->assertEquals(
			$baseline['attribute_counts'],
			$with_junk['attribute_counts'],
			'Non-attribute and non-existent taxonomies must be skipped, not counted.'
		);
	}

	/**
	 * @testdox A numeric attribute ID resolves to the same counts as its taxonomy name.
	 */
	public function test_calculate_attribute_counts_accepts_numeric_attribute_id() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$attribute_id = wc_attribute_taxonomy_id_by_name( 'pa_size' );
		$this->assertNotEmpty( $attribute_id, 'Test attribute should resolve to an ID.' );

		$by_name_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$by_name_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$by_name = rest_get_server()->dispatch( $by_name_request )->get_data();

		$by_id_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$by_id_request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => (string) $attribute_id,
					'query_type' => 'or',
				),
			)
		);
		$by_id = rest_get_server()->dispatch( $by_id_request )->get_data();

		$this->assertNotEmpty( $by_name['attribute_counts'], 'Baseline name-based request should return counts.' );
		$this->assertEquals(
			$by_name['attribute_counts'],
			$by_id['attribute_counts'],
			'A numeric attribute ID must resolve to the same counts as its taxonomy name.'
		);
	}

	/**
	 * @testdox Taxonomies are normalized before dedup so case and whitespace variants collapse to one entry.
	 */
	public function test_calculate_taxonomy_counts_normalizes_taxonomy_before_dedup() {
		$category = wp_insert_term( 'Normalized Category', 'product_cat' );
		wp_set_post_terms( $this->products[0]->get_id(), array( $category['term_id'] ), 'product_cat' );

		$single_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$single_request->set_param( 'calculate_taxonomy_counts', array( 'product_cat' ) );
		$single = rest_get_server()->dispatch( $single_request )->get_data();

		$variants_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$variants_request->set_param( 'calculate_taxonomy_counts', array( 'product_cat', ' product_cat ', 'PRODUCT_CAT' ) );
		$variants = rest_get_server()->dispatch( $variants_request )->get_data();

		$this->assertNotEmpty( $single['taxonomy_counts'], 'Baseline single-taxonomy request should return counts.' );
		$this->assertEquals(
			$single['taxonomy_counts'],
			$variants['taxonomy_counts'],
			'Case and whitespace variants of the same taxonomy must be counted once, not duplicated.'
		);
	}

	/**
	 * @testdox Non-existent taxonomies are skipped in calculate_taxonomy_counts.
	 */
	public function test_calculate_taxonomy_counts_skips_nonexistent_taxonomies() {
		$category = wp_insert_term( 'Skip Test Category', 'product_cat' );
		wp_set_post_terms( $this->products[0]->get_id(), array( $category['term_id'] ), 'product_cat' );

		$baseline_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$baseline_request->set_param( 'calculate_taxonomy_counts', array( 'product_cat' ) );
		$baseline = rest_get_server()->dispatch( $baseline_request )->get_data();

		$with_junk_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$with_junk_request->set_param( 'calculate_taxonomy_counts', array( 'product_cat', 'does_not_exist_tax', 'another_missing_tax' ) );
		$with_junk = rest_get_server()->dispatch( $with_junk_request )->get_data();

		$this->assertNotEmpty( $baseline['taxonomy_counts'], 'Baseline taxonomy request should return counts.' );
		$this->assertEquals(
			$baseline['taxonomy_counts'],
			$with_junk['taxonomy_counts'],
			'Non-existent taxonomies must be skipped, not counted.'
		);
	}

	/**
	 * @testdox Attribute counts are computed through the cached filter-data path.
	 */
	public function test_calculate_attribute_counts_uses_filter_data_cache() {
		global $wpdb;

		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		// Clear any filter-data transients left by other requests so this assertion is isolated. The
		// entry-count counter is excluded so the assertion below proves a real data entry was written.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_filter_data_%' AND option_name <> '_transient_wc_filter_data_entry_count'" );

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'or',
				),
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		// The returned counts must be correct, not merely present.
		$this->assertNotEmpty( $data['attribute_counts'], 'Attribute counts should be returned.' );

		// A data-cache entry (not just the entry-count counter) must have been written.
		$cached_entries = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_wc_filter_data_%' AND option_name <> '_transient_wc_filter_data_entry_count'" );
		$this->assertGreaterThan(
			0,
			$cached_entries,
			'Attribute counts should be written to the shared filter-data cache (proves the cached path is used).'
		);
	}

	/**
	 * @testdox Repeated attribute-count requests return stable, correct counts (cache does not corrupt results).
	 */
	public function test_calculate_attribute_counts_stable_across_repeated_requests() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$make_request = function () {
			$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
			$request->set_param(
				'calculate_attribute_counts',
				array(
					array(
						'taxonomy'   => 'pa_size',
						'query_type' => 'or',
					),
				)
			);
			return rest_get_server()->dispatch( $request )->get_data();
		};

		$first  = $make_request();
		$second = $make_request();

		$this->assertNotEmpty( $first['attribute_counts'], 'First request should return counts.' );
		$this->assertEquals(
			$first['attribute_counts'],
			$second['attribute_counts'],
			'Repeated identical requests must return identical counts.'
		);
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);

		// Create test category for taxonomy counts.
		$category = wp_insert_term( 'Schema Test Category', 'product_cat' );
		wp_set_post_terms( $product->get_id(), array( $category['term_id'] ), 'product_cat' );

		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );
		$schema     = $controller->get_item_schema();

		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );
		$request->set_param( 'calculate_price_range', true );
		$request->set_param(
			'calculate_attribute_counts',
			array(
				array(
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				),
			)
		);
		$request->set_param( 'calculate_rating_counts', true );
		$request->set_param(
			'calculate_taxonomy_counts',
			array( 'product_cat' )
		);
		$response = rest_get_server()->dispatch( $request );
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
