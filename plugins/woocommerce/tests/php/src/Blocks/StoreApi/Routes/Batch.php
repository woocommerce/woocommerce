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
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_cart_session_failure_does_not_clear_session_cart(): void {
		WC()->cart->add_to_cart( $this->products[0]->get_id() );
		WC()->cart->add_to_cart( $this->products[1]->get_id() );

		global $wp_query;

		$stored_cart              = WC()->cart->get_cart_for_session();
		$cart_contents_backup     = WC()->cart->get_cart_contents();
		$cart_backup              = WC()->cart;
		$load_action_count        = $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] ?? null;
		$current_user_id          = get_current_user_id();
		$is_singular_backup       = $wp_query->is_singular;
		$is_archive_backup        = $wp_query->is_archive;
		$is_search_backup         = $wp_query->is_search;
		$restored_item_count      = 0;
		$session_failure_callback = static function ( $session_data ) use ( &$restored_item_count ) {
			++$restored_item_count;
			if ( 2 === $restored_item_count ) {
				throw new \RuntimeException( 'Synthetic Store API cart-session failure.' );
			}
			return $session_data;
		};
		$cookie_update_count      = 0;
		$cookie_update_callback   = static function () use ( &$cookie_update_count ) {
			++$cookie_update_count;
			return false;
		};

		WC()->session->set( 'cart', $stored_cart );
		WC()->cart->set_cart_contents( array() );
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );
		add_filter( 'woocommerce_get_cart_item_from_session', $session_failure_callback );
		add_filter( 'woocommerce_set_cookie_enabled', $cookie_update_callback, 10, 0 );

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
					array(
						'method' => 'GET',
						'path'   => '/wc/store/v1/products',
					),
				),
			)
		);

		try {
			$response      = rest_get_server()->dispatch( $request );
			$response_data = $response->get_data();

			$this->assertSame( 500, $response_data['responses'][0]['status'], 'The request that fails to load the cart should return an error.' );
			$this->assertSame( 500, $response_data['responses'][1]['status'], 'Later cart requests should not use a partially loaded cart.' );
			$this->assertSame( 200, $response_data['responses'][2]['status'], 'Later non-cart requests should remain available.' );
			$this->assertCount( 1, $cart_backup->get_cart_contents(), 'The synthetic failure should occur after partially restoring the cart.' );
			$this->assertArrayNotHasKey( 'Cart-Token', $response_data['responses'][0]['headers'], 'A failed cart response should not include a cart token.' );
			$this->assertArrayNotHasKey( 'Cart-Hash', $response_data['responses'][0]['headers'], 'A failed cart response should not include a cart hash.' );

			// A failed cart remains referenced by its registered callbacks after WC()->cart is cleared.
			$failed_cart_session = new \WC_Cart_Session( $cart_backup );
			$failed_cart_session->get_cart_from_session();
			$this->assertCount( 1, $cart_backup->get_cart_contents(), 'The failed cart should not resume loading from the session.' );

			do_action( 'woocommerce_removed_coupon', 'synthetic-coupon' );
			$this->assertSame( $stored_cart, WC()->session->get( 'cart' ), 'The failed cart should not update the session.' );

			do_action( 'woocommerce_cart_emptied' );
			$this->assertSame( $stored_cart, WC()->session->get( 'cart' ), 'The failed cart should not destroy the session.' );

			$user_id                = self::factory()->user->create();
			$persistent_cart_key    = '_woocommerce_persistent_cart_' . get_current_blog_id();
			$stored_persistent_cart = array( 'cart' => $stored_cart );
			wp_set_current_user( $user_id );
			update_user_meta( $user_id, $persistent_cart_key, $stored_persistent_cart );
			do_action( 'woocommerce_cart_item_set_quantity', 'synthetic-item', 2, $cart_backup );
			$this->assertSame( $stored_persistent_cart, get_user_meta( $user_id, $persistent_cart_key, true ), 'The failed cart should not update the persistent cart.' );
			$failed_cart_session->persistent_cart_destroy();
			$this->assertSame( $stored_persistent_cart, get_user_meta( $user_id, $persistent_cart_key, true ), 'The failed cart should not destroy the persistent cart.' );

			$stored_removed_cart_contents = array( 'synthetic-item' => array( 'quantity' => 1 ) );
			WC()->session->set( 'removed_cart_contents', $stored_removed_cart_contents );
			$wp_query->is_singular = true;
			$wp_query->is_archive  = false;
			$wp_query->is_search   = false;
			$failed_cart_session->clean_up_removed_cart_contents();
			$this->assertSame( $stored_removed_cart_contents, WC()->session->get( 'removed_cart_contents' ), 'The failed cart should not clean up removed cart contents.' );

			$this->assertFalse( headers_sent(), 'Cookie behavior can only be verified before headers are sent.' );
			$failed_cart_session->maybe_set_cart_cookies();
			$this->assertSame( 0, $cookie_update_count, 'The failed cart should not update cart cookies.' );
		} finally {
			remove_filter( 'woocommerce_get_cart_item_from_session', $session_failure_callback );
			remove_filter( 'woocommerce_set_cookie_enabled', $cookie_update_callback );
			\WC_Cart_Session::set_updates_enabled_for_cart( $cart_backup, true );
			WC()->cart = $cart_backup;
			WC()->cart->set_cart_contents( $cart_contents_backup );
			wp_set_current_user( $current_user_id );
			$wp_query->is_singular = $is_singular_backup;
			$wp_query->is_archive  = $is_archive_backup;
			$wp_query->is_search   = $is_search_backup;
			if ( null === $load_action_count ) {
				unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );
			} else {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the action count changed by the test.
				$GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] = $load_action_count;
			}
		}
	}

	/**
	 * @testdox Disabling cart-session updates only affects the exact marked cart.
	 */
	public function test_cart_session_update_registry_is_scoped_to_the_marked_cart(): void {
		$marked_cart         = WC()->cart;
		$unmarked_clone      = clone $marked_cart;
		$marked_session      = new \WC_Cart_Session( $marked_cart );
		$unmarked_session    = new \WC_Cart_Session( $unmarked_clone );
		$stored_session_cart = $marked_cart->get_cart_for_session();

		try {
			\WC_Cart_Session::set_updates_enabled_for_cart( $marked_cart, false );

			WC()->session->set( 'cart', $stored_session_cart );
			$marked_session->destroy_cart_session();
			$this->assertSame( $stored_session_cart, WC()->session->get( 'cart' ), 'The marked cart should not destroy session data.' );

			$unmarked_session->destroy_cart_session();
			$this->assertNull( WC()->session->get( 'cart' ), 'An unmarked clone should continue updating session data.' );

			WC()->session->set( 'cart', $stored_session_cart );
			\WC_Cart_Session::set_updates_enabled_for_cart( $marked_cart, true );
			$marked_session->destroy_cart_session();
			$this->assertNull( WC()->session->get( 'cart' ), 'Re-enabled carts should resume updating session data.' );
		} finally {
			\WC_Cart_Session::set_updates_enabled_for_cart( $marked_cart, true );
			WC()->session->set( 'cart', $stored_session_cart );
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
