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
class ProductAttributes extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$color_attribute = ProductHelper::create_attribute( 'color', [ 'red', 'green', 'blue' ] );
		$size_attribute  = ProductHelper::create_attribute( 'size', [ 'small', 'medium', 'large' ] );

		$this->attributes   = [];
		$this->attributes[] = wc_get_attribute( $color_attribute['attribute_id'] );
		$this->attributes[] = wc_get_attribute( $size_attribute['attribute_id'] );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/products/attributes', $routes );
		$this->assertArrayHasKey( '/wc/store/products/attributes/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting item.
	 */
	public function test_get_item() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/products/attributes/' . $this->attributes[0]->id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->attributes[0]->id, $data['id'] );
		$this->assertEquals( $this->attributes[0]->name, $data['name'] );
		$this->assertEquals( $this->attributes[0]->slug, $data['taxonomy'] );
		$this->assertEquals( $this->attributes[0]->type, $data['type'] );
		$this->assertEquals( $this->attributes[0]->order_by, $data['order'] );
		$this->assertEquals( $this->attributes[0]->has_archives, $data['has_archives'] );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/products/attributes' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) );
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'taxonomy', $data[0] );
		$this->assertArrayHasKey( 'type', $data[0] );
		$this->assertArrayHasKey( 'order', $data[0] );
		$this->assertArrayHasKey( 'has_archives', $data[0] );
	}

	/**
	 * Test conversion of product to rest response.
	 */
	public function test_prepare_item_for_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-attributes' );
		$response   = $controller->prepare_item_for_response( $this->attributes[0], new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'taxonomy', $data );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'order', $data );
		$this->assertArrayHasKey( 'has_archives', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-attributes' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( $this->attributes[0], new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
