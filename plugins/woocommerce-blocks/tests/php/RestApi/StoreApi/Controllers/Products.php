<?php
/**
 * Controller Tests.
 *
 * @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\RestApi\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;

/**
 * Products Controller Tests.
 */
class Products extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->products = [];
		$this->products[0] = ProductHelper::create_simple_product( true );
		$this->products[1] = ProductHelper::create_simple_product( true );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/products', $routes );
		$this->assertArrayHasKey( '/wc/store/products/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting item.
	 */
	public function test_get_item() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/products/' . $this->products[0]->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $this->products[0]->get_id(), $data['id'] );
		$this->assertEquals( $this->products[0]->get_title(), $data['name'] );
		$this->assertEquals( $this->products[0]->get_permalink(), $data['permalink'] );
		$this->assertEquals( $this->products[0]->get_sku(), $data['sku'] );
		$this->assertEquals( $this->products[0]->get_price(), $data['prices']['price'] );
		$this->assertEquals( $this->products[0]->get_average_rating(), $data['average_rating'] );
		$this->assertEquals( $this->products[0]->get_review_count(), $data['review_count'] );
		$this->assertEquals( $this->products[0]->has_options(), $data['has_options'] );
		$this->assertEquals( $this->products[0]->is_purchasable(), $data['is_purchasable'] );
		$this->assertEquals( $this->products[0]->is_in_stock(), $data['is_in_stock'] );
		$this->assertEquals( $this->products[0]->add_to_cart_text(), $data['add_to_cart']['text'] );
		$this->assertEquals( $this->products[0]->add_to_cart_description(), $data['add_to_cart']['description'] );
		$this->assertEquals( $this->products[0]->is_on_sale(), $data['on_sale'] );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/products' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $data ) );
		$this->assertArrayHasKey( 'id', $data[0] );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'variation', $data[0] );
		$this->assertArrayHasKey( 'permalink', $data[0] );
		$this->assertArrayHasKey( 'description', $data[0] );
		$this->assertArrayHasKey( 'on_sale', $data[0] );
		$this->assertArrayHasKey( 'sku', $data[0] );
		$this->assertArrayHasKey( 'prices', $data[0] );
		$this->assertArrayHasKey( 'average_rating', $data[0] );
		$this->assertArrayHasKey( 'review_count', $data[0] );
		$this->assertArrayHasKey( 'images', $data[0] );
		$this->assertArrayHasKey( 'has_options', $data[0] );
		$this->assertArrayHasKey( 'is_purchasable', $data[0] );
		$this->assertArrayHasKey( 'is_in_stock', $data[0] );
		$this->assertArrayHasKey( 'add_to_cart', $data[0] );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\Products();
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'name', $schema['properties'] );
		$this->assertArrayHasKey( 'variation', $schema['properties'] );
		$this->assertArrayHasKey( 'permalink', $schema['properties'] );
		$this->assertArrayHasKey( 'description', $schema['properties'] );
		$this->assertArrayHasKey( 'on_sale', $schema['properties'] );
		$this->assertArrayHasKey( 'sku', $schema['properties'] );
		$this->assertArrayHasKey( 'prices', $schema['properties'] );
		$this->assertArrayHasKey( 'average_rating', $schema['properties'] );
		$this->assertArrayHasKey( 'review_count', $schema['properties'] );
		$this->assertArrayHasKey( 'images', $schema['properties'] );
		$this->assertArrayHasKey( 'has_options', $schema['properties'] );
		$this->assertArrayHasKey( 'is_purchasable', $schema['properties'] );
		$this->assertArrayHasKey( 'is_in_stock', $schema['properties'] );
		$this->assertArrayHasKey( 'add_to_cart', $schema['properties'] );
	}

	/**
	 * Test conversion of prdouct to rest response.
	 */
	public function test_prepare_item_for_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\Products();
		$response   = $controller->prepare_item_for_response( $this->products[0], [] );

		$this->assertArrayHasKey( 'id', $response->get_data() );
		$this->assertArrayHasKey( 'name', $response->get_data() );
		$this->assertArrayHasKey( 'variation', $response->get_data() );
		$this->assertArrayHasKey( 'permalink', $response->get_data() );
		$this->assertArrayHasKey( 'description', $response->get_data() );
		$this->assertArrayHasKey( 'on_sale', $response->get_data() );
		$this->assertArrayHasKey( 'sku', $response->get_data() );
		$this->assertArrayHasKey( 'prices', $response->get_data() );
		$this->assertArrayHasKey( 'average_rating', $response->get_data() );
		$this->assertArrayHasKey( 'review_count', $response->get_data() );
		$this->assertArrayHasKey( 'images', $response->get_data() );
		$this->assertArrayHasKey( 'has_options', $response->get_data() );
		$this->assertArrayHasKey( 'is_purchasable', $response->get_data() );
		$this->assertArrayHasKey( 'is_in_stock', $response->get_data() );
		$this->assertArrayHasKey( 'add_to_cart', $response->get_data() );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\Products();
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'page', $params );
		$this->assertArrayHasKey( 'per_page', $params );
		$this->assertArrayHasKey( 'search', $params );
		$this->assertArrayHasKey( 'after', $params );
		$this->assertArrayHasKey( 'before', $params );
		$this->assertArrayHasKey( 'date_column', $params );
		$this->assertArrayHasKey( 'exclude', $params );
		$this->assertArrayHasKey( 'include', $params );
		$this->assertArrayHasKey( 'offset', $params );
		$this->assertArrayHasKey( 'order', $params );
		$this->assertArrayHasKey( 'orderby', $params );
		$this->assertArrayHasKey( 'parent', $params );
		$this->assertArrayHasKey( 'parent_exclude', $params );
		$this->assertArrayHasKey( 'type', $params );
		$this->assertArrayHasKey( 'sku', $params );
		$this->assertArrayHasKey( 'featured', $params );
		$this->assertArrayHasKey( 'category', $params );
		$this->assertArrayHasKey( 'tag', $params );
		$this->assertArrayHasKey( 'on_sale', $params );
		$this->assertArrayHasKey( 'min_price', $params );
		$this->assertArrayHasKey( 'max_price', $params );
		$this->assertArrayHasKey( 'stock_status', $params );
		$this->assertArrayHasKey( 'category_operator', $params );
		$this->assertArrayHasKey( 'tag_operator', $params );
		$this->assertArrayHasKey( 'attribute_relation', $params );
		$this->assertArrayHasKey( 'attributes', $params );
		$this->assertArrayHasKey( 'catalog_visibility', $params );
		$this->assertArrayHasKey( 'rating', $params );
	}
}
