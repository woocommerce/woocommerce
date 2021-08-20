<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\Tests\Helpers\FixtureData;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Product Attributes Controller Tests.
 */
class ProductAttributeTerms extends ControllerTestCase {

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->attributes = [
			$fixtures->get_product_attribute( 'color', [ 'red', 'green', 'blue' ] ),
			$fixtures->get_product_attribute( 'size', [ 'small', 'medium', 'large' ] )
		];
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/products/attributes/(?P<attribute_id>[\d]+)/terms', $routes );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$request = new \WP_REST_Request( 'GET', '/wc/store/products/attributes/' . $this->attributes[0]['attribute_id'] . '/terms' );
		$request->set_param( 'hide_empty', false );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $data ) );
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'slug', $data[0] );
		$this->assertArrayHasKey( 'description', $data[0] );
		$this->assertArrayHasKey( 'count', $data[0] );
	}

	/**
	 * Test conversion of product to rest response.
	 */
	public function test_prepare_item() {
		$schema     = new \Automattic\WooCommerce\Blocks\StoreApi\Schemas\TermSchema( $this->mock_extend );
		$controller = new \Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductAttributeTerms( $schema );
		$response   = $controller->prepare_item_for_response( get_term_by( 'name', 'small', 'pa_size' ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'small', $data['name'] );
		$this->assertEquals( 'small-slug', $data['slug'] );
		$this->assertEquals( 'Description of small', $data['description'] );
		$this->assertEquals( 0, $data['count'] );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'hide_empty', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'product-attribute-terms' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( get_term_by( 'name', 'small', 'pa_size' ), new \WP_REST_Request() );
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
