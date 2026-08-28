<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Batch Controller Tests.
 */
class Batch extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		add_filter(
			'__experimental_woocommerce_store_api_batch_request_methods',
			function ( $methods ) {
				$methods[] = 'GET';
				return $methods;
			}
		);
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
					'regular_price' => 10,
				)
			),
		);
	}

	/**
	 * Test that a batch of requests are successful.
	 */
	public function test_success_cart_route_batch() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'body'    => array(
							'id'       => $this->products[0]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'Nonce' => wp_create_nonce( 'wc_store_api' ),
						),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'body'    => array(
							'id'       => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'Nonce' => wp_create_nonce( 'wc_store_api' ),
						),
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
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'body'    => array(
							'id'       => 99,
							'quantity' => 1,
						),
						'headers' => array(
							'Nonce' => wp_create_nonce( 'wc_store_api' ),
						),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/add-item',
						'body'    => array(
							'id'       => $this->products[1]->get_id(),
							'quantity' => 1,
						),
						'headers' => array(
							'Nonce' => wp_create_nonce( 'wc_store_api' ),
						),
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
	 * @testdox Should preserve the session cart when loading it fails in a batch sub-request.
	 */
	public function test_cart_session_failure_does_not_clear_session_cart(): void {
		WC()->cart->add_to_cart( $this->products[0]->get_id() );

		$stored_cart          = WC()->cart->get_cart_for_session();
		$cart_contents_backup = WC()->cart->get_cart_contents();
		$cart_backup          = WC()->cart;
		$load_action_count    = $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] ?? null;
		$callback             = static function () {
			throw new \RuntimeException( 'Synthetic Store API cart-session failure.' );
		};

		WC()->session->set( 'cart', $stored_cart );
		WC()->cart->set_cart_contents( array() );
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );
		add_filter( 'woocommerce_get_cart_item_from_session', $callback );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/update-customer',
						'body'    => array(),
						'headers' => array( 'Nonce' => wp_create_nonce( 'wc_store_api' ) ),
					),
					array(
						'method'  => 'POST',
						'path'    => '/wc/store/v1/cart/update-customer',
						'body'    => array(),
						'headers' => array( 'Nonce' => wp_create_nonce( 'wc_store_api' ) ),
					),
				),
			)
		);

		try {
			$response      = rest_get_server()->dispatch( $request );
			$response_data = $response->get_data();

			// A failed cart remains referenced by its registered callbacks after WC()->cart is cleared.
			do_action( 'woocommerce_removed_coupon', 'synthetic-coupon' );

			$this->assertSame( 500, $response_data['responses'][0]['status'], 'The request that fails to load the cart should return an error.' );
			$this->assertSame( $stored_cart, WC()->session->get( 'cart' ), 'The stored session cart should remain unchanged after callbacks from the failed cart run.' );
			$this->assertSame( 500, $response_data['responses'][1]['status'], 'Later cart requests should not use a partially loaded cart.' );
		} finally {
			remove_filter( 'woocommerce_get_cart_item_from_session', $callback );
			WC()->cart = $cart_backup;
			WC()->cart->set_cart_contents( $cart_contents_backup );
			if ( null === $load_action_count ) {
				unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action count changed by the test.
				$GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] = $load_action_count;
			}
		}
	}

	/**
	 * Do a batch request with a get request.
	 */
	public function test_batch_get_requests() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/products',
					),
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/products/collection-data',
					),
				),
			)
		);

		$response      = rest_get_server()->dispatch( $request );
		$response_data = $response->get_data();

		$this->assertEquals( 2, count( $response_data['responses'] ) );
		$this->assertEquals( 200, $response_data['responses'][0]['status'] );
	}

	/**
	 * @testdox Should reject batch sub-request with path outside Store API namespace.
	 * @dataProvider invalid_batch_paths_data
	 * @param string $path The path to test.
	 */
	public function test_batch_rejects_invalid_path( string $path ): void {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'POST',
						'path'   => $path,
						'body'   => array(),
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), "Path '$path' should be rejected" );
		$this->assertEquals( 'woocommerce_rest_invalid_path', $response->get_data()['code'], "Path '$path' should return woocommerce_rest_invalid_path error code" );
	}

	/**
	 * Data provider for paths that should be rejected by batch path validation.
	 *
	 * @return array
	 */
	public function invalid_batch_paths_data(): array {
		return array(
			'non-store-api path'                         => array( '/wp/v2/users' ),
			'query string containing wc/store'           => array( '/wp/v2/users?query=wc/store' ),
			'fragment containing wc/store'               => array( '/wp/v2/users#wc/store' ),
			'wc/store appears in middle of non-api path' => array( '/other/wc/store/endpoint' ),
			'empty path'                                 => array( '' ),
		);
	}

	/**
	 * @testdox Should accept batch sub-request with valid Store API path.
	 * @dataProvider valid_batch_paths_data
	 * @param string $path The path to test.
	 */
	public function test_batch_accepts_valid_store_api_path( string $path ): void {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path'   => $path,
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertNotEquals( 'woocommerce_rest_invalid_path', $response->get_data()['code'] ?? '', "Path '$path' should not be rejected by path validation" );
	}

	/**
	 * Data provider for paths that should pass batch path validation.
	 *
	 * @return array
	 */
	public function valid_batch_paths_data(): array {
		return array(
			'store api cart'             => array( '/wc/store/v1/cart' ),
			'store api products'         => array( '/wc/store/v1/products' ),
			'store api with query param' => array( '/wc/store/v1/products?per_page=5' ),
		);
	}

	/**
	 * @testdox Should reject batch when one sub-request has a valid path and another has an invalid path.
	 */
	public function test_batch_rejects_if_any_path_is_invalid(): void {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'requests' => array(
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/cart',
					),
					array(
						'method' => 'POST',
						'path'   => '/wp/v2/users?query=wc/store',
						'body'   => array(
							'username' => 'newuser',
							'email'    => 'newuser@example.com',
							'password' => 'password123',
						),
					),
				),
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status(), 'Batch should be rejected when any sub-request path is invalid' );
		$this->assertEquals( 'woocommerce_rest_invalid_path', $response->get_data()['code'] );
	}
}
