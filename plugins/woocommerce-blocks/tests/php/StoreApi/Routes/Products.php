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
 * Products Controller Tests.
 */
class Products extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		global $wpdb;

		parent::setUp();

		wp_set_current_user( 0 );

		$this->products    = [];
		$this->products[0] = ProductHelper::create_simple_product( true );
		$this->products[1] = ProductHelper::create_simple_product( true );

		$image_url = media_sideload_image( 'http://cldup.com/Dr1Bczxq4q.png', $this->products[0]->get_id(), '', 'src' );
		$image_id  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $image_url ) );
		$this->products[0]->set_image_id( $image_id[0] );
		$this->products[0]->save();

		$image_url = media_sideload_image( 'http://cldup.com/Dr1Bczxq4q.png', $this->products[1]->get_id(), '', 'src' );
		$image_id  = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s", $image_url ) );
		$this->products[1]->set_image_id( $image_id[0] );
		$this->products[1]->save();
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
		$this->assertEquals( $this->products[0]->get_price(), $data['prices']->price / ( 10 ** $data['prices']->currency_minor_unit ) );
		$this->assertEquals( $this->products[0]->get_average_rating(), $data['average_rating'] );
		$this->assertEquals( $this->products[0]->get_review_count(), $data['review_count'] );
		$this->assertEquals( $this->products[0]->has_options(), $data['has_options'] );
		$this->assertEquals( $this->products[0]->is_purchasable(), $data['is_purchasable'] );
		$this->assertEquals( $this->products[0]->is_in_stock(), $data['is_in_stock'] );
		$this->assertEquals( $this->products[0]->add_to_cart_text(), $data['add_to_cart']->text );
		$this->assertEquals( $this->products[0]->add_to_cart_description(), $data['add_to_cart']->description );
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
	 * Test conversion of prdouct to rest response.
	 */
	public function test_prepare_item_for_response() {
		$schemas    = new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController();
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( $schemas );
		$schema     = $schemas->get( 'product' );
		$controller = $routes->get( 'products' );
		$response   = $controller->prepare_item_for_response( $this->products[0], new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'variation', $data );
		$this->assertArrayHasKey( 'permalink', $data );
		$this->assertArrayHasKey( 'description', $data );
		$this->assertArrayHasKey( 'on_sale', $data );
		$this->assertArrayHasKey( 'sku', $data );
		$this->assertArrayHasKey( 'prices', $data );
		$this->assertArrayHasKey( 'average_rating', $data );
		$this->assertArrayHasKey( 'review_count', $data );
		$this->assertArrayHasKey( 'images', $data );
		$this->assertArrayHasKey( 'has_options', $data );
		$this->assertArrayHasKey( 'is_purchasable', $data );
		$this->assertArrayHasKey( 'is_in_stock', $data );
		$this->assertArrayHasKey( 'add_to_cart', $data );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'products' );
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

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'products' );
		$schema     = $controller->get_item_schema();
		$response   = $controller->prepare_item_for_response( $this->products[0], new \WP_REST_Request() );
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
