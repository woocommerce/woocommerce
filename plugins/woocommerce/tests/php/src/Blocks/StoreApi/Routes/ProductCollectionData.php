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
