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
	 * Product attributes created during a test.
	 *
	 * @var array<string,int>
	 */
	private $created_product_attributes = array();

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->created_product_attributes = array();

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
	 * Cleanup test product data. Called after every test.
	 */
	protected function tearDown(): void {
		global $wc_product_attributes;

		foreach ( $this->created_product_attributes as $taxonomy => $attribute_id ) {
			wc_delete_attribute( $attribute_id );

			if ( taxonomy_exists( $taxonomy ) ) {
				unregister_taxonomy( $taxonomy );
			}

			unset( $wc_product_attributes[ $taxonomy ] );
		}

		delete_transient( 'wc_attribute_taxonomies' );
		\WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );

		parent::tearDown();
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
				$this->create_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
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
	public function test_count_params_declare_default_max_items(): void {
		$params = $this->get_collection_params();

		$this->assertArrayHasKey( 'maxItems', $params['calculate_attribute_counts'], 'calculate_attribute_counts must be bounded.' );
		$this->assertArrayHasKey( 'maxItems', $params['calculate_taxonomy_counts'], 'calculate_taxonomy_counts must be bounded.' );
		$this->assertSame( 25, $params['calculate_attribute_counts']['maxItems'], 'Default attribute-counts cap should be 25.' );
		$this->assertSame( 25, $params['calculate_taxonomy_counts']['maxItems'], 'Default taxonomy-counts cap should be 25.' );
	}

	/**
	 * @testdox An oversized calculate_attribute_counts array is rejected with HTTP 400.
	 */
	public function test_calculate_attribute_counts_rejects_oversized_array(): void {
		$too_many = array_fill(
			0,
			26,
			array(
				'taxonomy'   => 'pa_size',
				'query_type' => 'or',
			)
		);

		$response = $this->dispatch_collection_data_request(
			array(
				'calculate_attribute_counts' => $too_many,
			)
		);

		$this->assertEquals( 400, $response->get_status(), 'More than 25 attribute-count entries should be rejected.' );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox An oversized calculate_taxonomy_counts array is rejected with HTTP 400.
	 */
	public function test_calculate_taxonomy_counts_rejects_oversized_array(): void {
		$response = $this->dispatch_collection_data_request(
			array(
				'calculate_taxonomy_counts' => array_fill( 0, 26, 'product_cat' ),
			)
		);

		$this->assertEquals( 400, $response->get_status(), 'More than 25 taxonomy-count entries should be rejected.' );
		$this->assertEquals( 'rest_invalid_param', $response->get_data()['code'] );
	}

	/**
	 * @testdox Count arrays exactly at the cap are accepted.
	 */
	public function test_count_arrays_at_cap_are_accepted(): void {
		$attribute_response = $this->dispatch_collection_data_request(
			array(
				'calculate_attribute_counts' => array_fill(
					0,
					25,
					array(
						'taxonomy'   => 'pa_size',
						'query_type' => 'or',
					)
				),
			)
		);
		$taxonomy_response  = $this->dispatch_collection_data_request(
			array(
				'calculate_taxonomy_counts' => array_fill( 0, 25, 'product_cat' ),
			)
		);

		$this->assertEquals( 200, $attribute_response->get_status(), 'Exactly 25 attribute-count entries should be accepted.' );
		$this->assertEquals( 200, $taxonomy_response->get_status(), 'Exactly 25 taxonomy-count entries should be accepted.' );
	}

	/**
	 * @testdox Attribute count requests are normalized and deduplicated before filter data is queried.
	 */
	public function test_calculate_attribute_counts_normalizes_and_deduplicates_taxonomies(): void {
		$this->create_size_attribute();
		$calls  = array();
		$filter = $this->register_filter_data_spy( $calls, array( array( 101 => 2 ) ) );

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'calculate_attribute_counts' => array(
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
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
			),
			$calls,
			'Text variants of the same attribute taxonomy should fan out to one filter-data call.'
		);
		$this->assertEquals(
			array(
				(object) array(
					'term'  => 101,
					'count' => 2,
				),
			),
			$response->get_data()['attribute_counts']
		);
	}

	/**
	 * @testdox The same attribute requested with both query types is counted once per query type.
	 */
	public function test_calculate_attribute_counts_keeps_query_types_separate(): void {
		$this->create_size_attribute();
		$calls  = array();
		$filter = $this->register_filter_data_spy(
			$calls,
			array(
				array( 201 => 3 ),
				array( 202 => 1 ),
			)
		);

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'attributes'                 => array(
						array(
							'attribute' => 'pa_size',
							'operator'  => 'in',
							'slug'      => array( 'large' ),
						),
					),
					'calculate_attribute_counts' => array(
						array(
							'taxonomy'   => 'pa_size',
							'query_type' => 'or',
						),
						array(
							'taxonomy'   => 'pa_size',
							'query_type' => 'and',
						),
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
			),
			$calls,
			'The route should keep OR and AND fan-out separate for the same taxonomy.'
		);
		$this->assertEquals(
			array(
				(object) array(
					'term'  => 201,
					'count' => 3,
				),
				(object) array(
					'term'  => 202,
					'count' => 1,
				),
			),
			$response->get_data()['attribute_counts'],
			'Both query-type result sets should be returned, not merely the right number of rows.'
		);
	}

	/**
	 * @testdox Invalid attribute taxonomies are skipped before filter data is queried.
	 */
	public function test_calculate_attribute_counts_skips_invalid_taxonomies(): void {
		$this->create_size_attribute();
		$calls  = array();
		$filter = $this->register_filter_data_spy( $calls, array( array( 301 => 4 ) ) );

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'calculate_attribute_counts' => array(
						array(
							'taxonomy'   => 'pa_size',
							'query_type' => 'or',
						),
						array(
							'taxonomy'   => 'product_cat',
							'query_type' => 'or',
						),
						array(
							'taxonomy'   => 'pa_does_not_exist',
							'query_type' => 'or',
						),
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
			),
			$calls,
			'Non-attribute and missing taxonomies should not reach filter data.'
		);
	}

	/**
	 * @testdox Numeric attribute IDs are resolved before filter data is queried.
	 */
	public function test_calculate_attribute_counts_accepts_numeric_attribute_id(): void {
		$attribute_id = $this->create_size_attribute();
		$calls        = array();
		$filter       = $this->register_filter_data_spy( $calls, array( array( 401 => 5 ) ) );

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'calculate_attribute_counts' => array(
						array(
							'taxonomy'   => (string) $attribute_id,
							'query_type' => 'or',
						),
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
			),
			$calls,
			'Numeric attribute IDs should resolve to the canonical taxonomy name.'
		);
	}

	/**
	 * @testdox Taxonomy count requests are normalized and filtered before filter data is queried.
	 */
	public function test_calculate_taxonomy_counts_normalizes_deduplicates_and_skips_invalid_taxonomies(): void {
		$calls  = array();
		$filter = $this->register_filter_data_spy( $calls, array( array( 501 => 6 ) ) );

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'calculate_taxonomy_counts' => array(
						'product_cat',
						' product_cat ',
						'PRODUCT_CAT',
						'does_not_exist_tax',
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'taxonomy',
					'taxonomy' => 'product_cat',
				),
			),
			$calls,
			'Text variants and missing taxonomies should fan out to one taxonomy filter-data call.'
		);
		$this->assertEquals(
			array(
				(object) array(
					'term'  => 501,
					'count' => 6,
				),
			),
			$response->get_data()['taxonomy_counts']
		);
	}

	/**
	 * @testdox Attribute counts are computed through the filter-data provider path.
	 */
	public function test_calculate_attribute_counts_uses_filter_data_provider(): void {
		$this->create_size_attribute();
		$calls  = array();
		$filter = $this->register_filter_data_spy( $calls, array( array( 601 => 7 ) ) );

		try {
			$response = $this->dispatch_collection_data_request(
				array(
					'calculate_attribute_counts' => array(
						array(
							'taxonomy'   => 'pa_size',
							'query_type' => 'or',
						),
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_pre_product_filter_data', $filter, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			array(
				array(
					'type'     => 'attribute',
					'taxonomy' => 'pa_size',
				),
			),
			$calls,
			'Attribute counts should use the shared filter-data provider path.'
		);
		$this->assertEquals(
			array(
				(object) array(
					'term'  => 601,
					'count' => 7,
				),
			),
			$response->get_data()['attribute_counts']
		);
	}

	/**
	 * @testdox Repeated attribute-count requests return stable counts.
	 */
	public function test_calculate_attribute_counts_stable_across_repeated_requests(): void {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$this->create_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);
		$fixtures->get_taxonomy_and_term( $product, 'pa_size', 'large', 'large' );

		$first  = $this->dispatch_collection_data_request(
			array(
				'calculate_attribute_counts' => array(
					array(
						'taxonomy'   => 'pa_size',
						'query_type' => 'or',
					),
				),
			)
		)->get_data();
		$second = $this->dispatch_collection_data_request(
			array(
				'calculate_attribute_counts' => array(
					array(
						'taxonomy'   => 'pa_size',
						'query_type' => 'or',
					),
				),
			)
		)->get_data();

		$this->assertNotEmpty( $first['attribute_counts'], 'First request should return counts.' );
		$this->assertEquals(
			$first['attribute_counts'],
			$second['attribute_counts'],
			'Repeated identical requests must return identical counts.'
		);
	}

	/**
	 * Get collection params for the product collection data route.
	 *
	 * @return array
	 */
	private function get_collection_params(): array {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );

		return $controller->get_collection_params();
	}

	/**
	 * Dispatch a product collection data request.
	 *
	 * @param array $params Request params.
	 * @return \WP_REST_Response
	 */
	private function dispatch_collection_data_request( array $params ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products/collection-data' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Create a product attribute taxonomy and track it for cleanup.
	 *
	 * @param string $raw_name Attribute name.
	 * @param array  $terms Attribute terms.
	 * @return array Attribute data and created terms.
	 */
	private function create_product_attribute( string $raw_name, array $terms ): array {
		$attribute_name = wc_sanitize_taxonomy_name( $raw_name );
		$existing_id    = wc_attribute_taxonomy_id_by_name( $attribute_name );
		$attribute      = FixtureData::get_product_attribute( $raw_name, $terms );
		$attribute_id   = (int) $attribute['attribute_id'];
		$taxonomy       = $attribute['attribute_taxonomy'];

		if ( ! $existing_id && $attribute_id ) {
			$this->created_product_attributes[ $taxonomy ] = $attribute_id;
		}

		return $attribute;
	}

	/**
	 * Create the size product attribute taxonomy.
	 *
	 * @return int
	 */
	private function create_size_attribute(): int {
		$attribute = $this->create_product_attribute( 'size', array( 'small', 'medium', 'large' ) );

		return (int) $attribute['attribute_id'];
	}

	/**
	 * Register a filter-data spy that returns canned counts.
	 *
	 * @param array $calls Captured filter-data calls.
	 * @param array $results_by_call Count results keyed by call index.
	 * @return callable
	 */
	private function register_filter_data_spy( array &$calls, array $results_by_call = array() ): callable {
		$filter = function ( $pre_filter_counts, $type, $_query_vars, $context ) use ( &$calls, $results_by_call ) {
			if ( ! in_array( $type, array( 'attribute', 'taxonomy' ), true ) || empty( $context['taxonomy'] ) ) {
				return $pre_filter_counts;
			}

			$calls[]    = array(
				'type'     => $type,
				'taxonomy' => $context['taxonomy'],
			);
			$call_index = count( $calls ) - 1;

			return $results_by_call[ $call_index ] ?? array( 1000 + $call_index => $call_index + 1 );
		};

		add_filter( 'woocommerce_pre_product_filter_data', $filter, 10, 4 );

		return $filter;
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$fixtures = new FixtureData();
		$product  = $fixtures->get_variable_product(
			array(),
			array(
				$this->create_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
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
