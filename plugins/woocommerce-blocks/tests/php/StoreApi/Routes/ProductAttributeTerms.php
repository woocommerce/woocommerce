<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Product Attributes Controller Tests.
 */
class ProductAttributeTerms extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->attributes    = [];
		$this->attributes[0] = ProductHelper::create_attribute( 'color', [ 'red', 'green', 'blue' ] );
		$this->attributes[1] = ProductHelper::create_attribute( 'size', [ 'small', 'medium', 'large' ] );

		wp_insert_term(
			'test',
			'pa_size',
			[
				'description' => 'This is a test description',
				'slug'        => 'test-slug',
			]
		);
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/products/attributes/(?P<attribute_id>[\d]+)/terms', $routes );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$request = new WP_REST_Request( 'GET', '/wc/store/products/attributes/' . $this->attributes[0]['attribute_id'] . '/terms' );
		$request->set_param( 'hide_empty', false );
		$response = $this->server->dispatch( $request );
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
	public function test_prepare_item_for_response() {
		$schema     = new \Automattic\WooCommerce\Blocks\StoreApi\Schemas\TermSchema();
		$controller = new \Automattic\WooCommerce\Blocks\StoreApi\Routes\ProductAttributeTerms( $schema );
		$response   = $controller->prepare_item_for_response( get_term_by( 'name', 'test', 'pa_size' ), new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( 'test', $data['name'] );
		$this->assertEquals( 'test-slug', $data['slug'] );
		$this->assertEquals( 'This is a test description', $data['description'] );
		$this->assertEquals( 0, $data['count'] );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-attribute-terms' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'hide_empty', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-attribute-terms' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( get_term_by( 'name', 'test', 'pa_size' ), new \WP_REST_Request() );
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
