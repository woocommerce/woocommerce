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
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'calculate_price_range', $params );
		$this->assertArrayHasKey( 'calculate_attribute_counts', $params );
		$this->assertArrayHasKey( 'calculate_rating_counts', $params );
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
		$response = rest_get_server()->dispatch( $request );
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
