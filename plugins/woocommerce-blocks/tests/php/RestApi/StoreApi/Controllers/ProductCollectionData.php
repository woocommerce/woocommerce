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
 * Controller Tests.
 */
class ProductCollectionData extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->products = [];
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_regular_price( 100 );
		$this->products[1]->save();

		wp_insert_comment( [
			'comment_post_ID'      => $this->products[0]->get_id(),
			'comment_author'       => 'admin',
			'comment_author_email' => 'woo@woo.local',
			'comment_author_url'   => '',
			'comment_content'      => 'Good product.',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
			'comment_meta' => [
				'rating' => 5,
			]
		] );

		wp_insert_comment( [
			'comment_post_ID'      => $this->products[1]->get_id(),
			'comment_author'       => 'admin',
			'comment_author_email' => 'woo@woo.local',
			'comment_author_url'   => '',
			'comment_content'      => 'Another very good product.',
			'comment_approved'     => 1,
			'comment_type'         => 'review',
			'comment_meta' => [
				'rating' => 4,
			]
		] );

		\WC_Comments::clear_transients( $this->products[0]->get_id() );
		\WC_Comments::clear_transients( $this->products[1]->get_id() );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/products/collection-data', $routes );
	}

	/**
	 * Test getting items.
	 */
	public function test_get_items() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/store/products/collection-data' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['min_price'] );
		$this->assertEquals( null, $data['max_price'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['rating_counts'] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_price_range() {
		$request = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param( 'calculate_price_range', true );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( '10.00', $data['min_price'] );
		$this->assertEquals( '100.00', $data['max_price'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( null, $data['rating_counts'] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_attribute_counts() {
		ProductHelper::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			[
				[
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				]
			]
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['min_price'] );
		$this->assertEquals( null, $data['max_price'] );
		$this->assertEquals( null, $data['rating_counts'] );

		$this->assertArrayHasKey( 'term', $data['attribute_counts'][0] );
		$this->assertArrayHasKey( 'count', $data['attribute_counts'][0] );
	}

	/**
	 * Test calculation method.
	 */
	public function test_calculate_rating_counts() {
		$request = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param( 'calculate_rating_counts', true );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, $data['min_price'] );
		$this->assertEquals( null, $data['max_price'] );
		$this->assertEquals( null, $data['attribute_counts'] );
		$this->assertEquals( [
			[
				'rating' => 4,
				'count'  => 1,
			],
			[
				'rating' => 5,
				'count'  => 1,
			]
		], $data['rating_counts'] );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\ProductCollectionData();
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'min_price', $schema['properties'] );
		$this->assertArrayHasKey( 'max_price', $schema['properties'] );
		$this->assertArrayHasKey( 'attribute_counts', $schema['properties'] );
		$this->assertArrayHasKey( 'rating_counts', $schema['properties'] );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\ProductCollectionData();
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'calculate_price_range', $params );
		$this->assertArrayHasKey( 'calculate_attribute_counts', $params );
		$this->assertArrayHasKey( 'calculate_rating_counts', $params );
	}
}
