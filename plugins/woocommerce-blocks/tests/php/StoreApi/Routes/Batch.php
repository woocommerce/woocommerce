<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes;

use Automattic\WooCommerce\Blocks\Tests\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Blocks\Tests\Helpers\FixtureData;

/**
 * Batch Controller Tests.
 */
class Batch extends ControllerTestCase {

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
				'regular_price' => 10,
			] ),
		];
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/wc/store/batch', $routes );
	}

	/**
	 * Test that a batch of requests are successful.
	 */
	public function test_success_cart_route_batch() {
		$request  = new \WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[0]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
				),
			)
		);
		$response      = rest_get_server()->dispatch( $request );
		$response_data = $response->get_data();

		// Assert that there were 2 successful results from the batch.
		$this->assertEquals( 2, count( $response_data['responses'] ) );
		$this->assertEquals( 201, $response_data['responses'][0]['status'] );
		$this->assertEquals( 201, $response_data['responses'][1]['status'] );
	}

	/**
	 * Test for a mixture of successful and non-successful requests in a batch.
	 */
	public function test_mix_cart_route_batch() {
		$request  = new \WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => 99,
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
					array(
						'method' => 'POST',
						'path' => '/wc/store/cart/add-item',
						'body' => array(
							'id' => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'X-WC-Store-API-Nonce' => wp_create_nonce( 'wc_store_api' ),
						)
					),
				),
			)
		);
		$response      = rest_get_server()->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 2, count( $response_data['responses'] ) );
		$this->assertEquals( 400, $response_data['responses'][0]['status'], $response_data['responses'][0]['status'] );
		$this->assertEquals( 201, $response_data['responses'][1]['status'], $response_data['responses'][1]['status'] );
	}

	/**
	 * Get Requests not supported by batch.
	 */
	public function test_get_cart_route_batch() {
		$request  = new \WP_REST_Request( 'POST', '/wc/store/batch' );
		$request->set_header( 'X-WC-Store-API-Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path' => '/wc/store/cart',
						'body' => array(
							'id' => 99,
							'quantity' => 1,
						),
					),
				),
			)
		);
		$response      = rest_get_server()->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 'rest_invalid_param', $response_data['code'], print_r( $response_data, true ) );
	}
}
