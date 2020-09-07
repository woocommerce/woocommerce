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
 * Controller Tests.
 */
class ProductCollectionData extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->products    = [];
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_regular_price( 100 );
		$this->products[1]->save();

		wp_insert_comment(
			[
				'comment_post_ID'      => $this->products[0]->get_id(),
				'comment_author'       => 'admin',
				'comment_author_email' => 'woo@woo.local',
				'comment_author_url'   => '',
				'comment_content'      => 'Good product.',
				'comment_approved'     => 1,
				'comment_type'         => 'review',
				'comment_meta'         => [
					'rating' => 5,
				],
			]
		);

		wp_insert_comment(
			[
				'comment_post_ID'      => $this->products[1]->get_id(),
				'comment_author'       => 'admin',
				'comment_author_email' => 'woo@woo.local',
				'comment_author_url'   => '',
				'comment_content'      => 'Another very good product.',
				'comment_approved'     => 1,
				'comment_type'         => 'review',
				'comment_meta'         => [
					'rating' => 4,
				],
			]
		);

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
		$this->assertEquals( null, $data['price_range'] );
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
		ProductHelper::create_variation_product();

		$request = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param(
			'calculate_attribute_counts',
			[
				[
					'taxonomy'   => 'pa_size',
					'query_type' => 'and',
				],
			]
		);
		$response = $this->server->dispatch( $request );
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
		$request = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
		$request->set_param( 'calculate_rating_counts', true );
		$response = $this->server->dispatch( $request );
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
		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-collection-data' );
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'calculate_price_range', $params );
		$this->assertArrayHasKey( 'calculate_attribute_counts', $params );
		$this->assertArrayHasKey( 'calculate_rating_counts', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		ProductHelper::create_variation_product();

		$routes     = new \Automattic\WooCommerce\Blocks\StoreApi\RoutesController( new \Automattic\WooCommerce\Blocks\StoreApi\SchemaController() );
		$controller = $routes->get( 'product-collection-data' );
		$schema     = $controller->get_item_schema();

		$request    = new WP_REST_Request( 'GET', '/wc/store/products/collection-data' );
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
		$response = $this->server->dispatch( $request );
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
