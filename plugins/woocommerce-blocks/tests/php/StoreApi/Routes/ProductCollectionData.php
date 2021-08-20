<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\Tests\Helpers\FixtureData;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Controller Tests.
 */
class ProductCollectionData extends ControllerTestCase {

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->products = [
			$fixtures->get_simple_product( [
				'name' => 'Test Product 1',
				'regular_price' => 10,
			] ),
			$fixtures->get_simple_product( [
				'name' => 'Test Product 2',
				'regular_price' => 100,
			] ),
		];

		$fixtures->add_product_review( $this->products[0]->get_id(), 5 );
		$fixtures->add_product_review( $this->products[1]->get_id(), 4 );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/products/collection-data', $routes );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/products/collection-data' ) );
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
		$request = new \WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
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
			[],
			[
				$fixtures->get_product_attribute( 'size', [ 'small', 'medium', 'large' ] )
			]
		);

		$request = new \WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			[
				[
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				],
			]
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['rating_counts'] );

		$this->assertObjectHasAttribute( 'term', $data['attribute_counts'][0] );
		$this->assertObjectHasAttribute( 'count', $data['attribute_counts'][0] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_rating_counts() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param( 'calculate_rating_counts', true );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['price_range'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals(
			[
				(object) [
					'rating' => 4,
					'count'  => 1,
				],
				(object) [
					'rating' => 5,
					'count'  => 1,
				],
			],
			$data['rating_counts']
		);
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
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
			[],
			[
				$fixtures->get_product_attribute( 'size', [ 'small', 'medium', 'large' ] )
			]
		);

		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-collection-data' );
		$schema     = $controller->get_item_schema();

		$request    = new \WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param( 'calculate_price_range', true );
		$request->set_param(
			'calculate_attribute_counts',
			[
				[
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				],
			]
		);
		$request->set_param( 'calculate_rating_counts', true );
		$response = rest_get_server()->dispatch( $request );
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
