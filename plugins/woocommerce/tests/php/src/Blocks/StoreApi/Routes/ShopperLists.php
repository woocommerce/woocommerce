<?php
/**
 * Shopper Lists Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Tests for the /wc/store/v1/shopper-lists/* endpoints.
 */
class ShopperLists extends ControllerTestCase {
	/**
	 * Product ID shared by the class.
	 *
	 * @var int
	 */
	private static $product_id;

	/**
	 * Customer IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $customer_ids = array();

	/**
	 * Whether the feature option existed before class setup.
	 *
	 * @var bool
	 */
	private static $had_feature_option;

	/**
	 * Feature option value before class setup.
	 *
	 * @var mixed
	 */
	private static $feature_option;

	/**
	 * Create immutable fixtures before route registration starts.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$feature_option     = get_option( 'woocommerce_cart_save_for_later_enabled' );
		self::$had_feature_option = false !== self::$feature_option;
		update_option( 'woocommerce_cart_save_for_later_enabled', 'yes' );

		$product          = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'Test Product',
					'regular_price' => 10,
				),
			)
		)[0];
		self::$product_id = $product->get_id();

		self::$customer_ids = array(
			$factory->user->create(
				array(
					'role'       => 'customer',
					'user_email' => 'shopper-lists-1@test.com',
				)
			),
			$factory->user->create(
				array(
					'role'       => 'customer',
					'user_email' => 'shopper-lists-2@test.com',
				)
			),
		);
	}

	/**
	 * Delete class fixtures and restore the incoming feature option.
	 */
	public static function wpTearDownAfterClass(): void {
		self::delete_class_fixture_products( array( self::$product_id ) );

		if ( self::$had_feature_option ) {
			update_option( 'woocommerce_cart_save_for_later_enabled', self::$feature_option );
		} else {
			delete_option( 'woocommerce_cart_save_for_later_enabled' );
		}
	}

	/**
	 * Test product.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Test customer user ID.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * Second test customer user ID, used to verify cross-user isolation.
	 *
	 * @var int
	 */
	private $other_customer_id;

	/**
	 * Setup test data.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->product           = wc_get_product( self::$product_id );
		$this->customer_id       = self::$customer_ids[0];
		$this->other_customer_id = self::$customer_ids[1];
	}

	/**
	 * Helper: dispatch a request and return the response.
	 *
	 * On writes, `$nonce` defaults to a valid `wc_store_api` nonce. Pass `''`
	 * to omit the header, or any other string to send a bad one.
	 *
	 * @param string      $method HTTP method.
	 * @param string      $route  Route path.
	 * @param array       $params Body params.
	 * @param string|null $nonce  Nonce override.
	 * @return \WP_REST_Response
	 */
	private function dispatch( string $method, string $route, array $params = array(), ?string $nonce = null ): \WP_REST_Response {
		$request = new \WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$is_write = in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true );
		if ( $is_write ) {
			if ( null === $nonce ) {
				$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			} elseif ( '' !== $nonce ) {
				$request->set_header( 'Nonce', $nonce );
			}
		}

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Helper: add the test product to the cart and return its cart_item_key.
	 *
	 * @return string
	 */
	private function add_product_to_cart(): string {
		$key = wc()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->assertNotEmpty( $key, 'add_to_cart should return a non-empty cart item key.' );
		return (string) $key;
	}

	/**
	 * Test that an unauthenticated request to GET /shopper-lists is rejected.
	 */
	public function test_get_lists_requires_login() {
		wp_set_current_user( 0 );

		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists' );

		$this->assertContains( $response->get_status(), array( 401, 403 ), 'Unauthenticated requests must be rejected by the permission callback.' );
	}

	/**
	 * Test that a logged-in user starts with saved-for-later auto-created and empty.
	 */
	public function test_get_lists_returns_save_for_later_for_logged_in_user() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists' );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertCount( 1, $data, 'Only saved-for-later is returned in v1.' );
		$this->assertSame( 'saved-for-later', $data[0]['slug'] );
		$this->assertSame( 0, $data[0]['item_count'] );
	}

	/**
	 * Test that GET /shopper-lists/saved-for-later returns the list metadata.
	 */
	public function test_get_list_by_id_returns_save_for_later_metadata() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later' );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( 'saved-for-later', $data['slug'] );
	}

	/**
	 * Test that GET /shopper-lists/{slug} returns 404 for any list other than saved-for-later.
	 */
	public function test_get_list_by_id_returns_404_for_unsupported_list() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/wishlist' );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test POST /shopper-lists/saved-for-later/items with a real cart_item_key returns the saved item.
	 */
	public function test_post_item_via_cart_item_key() {
		wp_set_current_user( $this->customer_id );

		$cart_item_key = $this->add_product_to_cart();

		$response = $this->dispatch(
			'POST',
			'/wc/store/v1/shopper-lists/saved-for-later/items',
			array( 'cart_item_key' => $cart_item_key )
		);
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertSame( $this->product->get_id(), $data['product_id'] );
		$this->assertSame( 1, $data['quantity'], 'Saved quantity should mirror the cart line quantity.' );
		$this->assertTrue( $data['is_live'] );
		$this->assertSame( $this->product->get_title(), $data['name'] );
		$this->assertNotEmpty( wc()->cart->cart_contents, 'Cart should still contain the line — POST is additive only.' );
	}

	/**
	 * Test POST rejects requests without cart_item_key or product_id.
	 */
	public function test_post_item_requires_cart_item_key_or_product_id() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items' );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test POST /shopper-lists/saved-for-later/items via direct product payload returns the saved item.
	 */
	public function test_post_item_via_manual_product_payload() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch(
			'POST',
			'/wc/store/v1/shopper-lists/saved-for-later/items',
			array(
				'product_id' => $this->product->get_id(),
				'quantity'   => 2,
			)
		);
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertSame( $this->product->get_id(), $data['product_id'] );
		$this->assertSame( 2, $data['quantity'], 'Posted quantity should be honored.' );
	}

	/**
	 * Test POST rejects an unknown cart_item_key.
	 */
	public function test_post_item_unknown_cart_item_key_returns_404() {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch(
			'POST',
			'/wc/store/v1/shopper-lists/saved-for-later/items',
			array( 'cart_item_key' => 'thiskeydoesnotexist' )
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test POST returns 404 for any slug other than saved-for-later.
	 */
	public function test_post_item_unsupported_slug_returns_404() {
		wp_set_current_user( $this->customer_id );
		$cart_item_key = $this->add_product_to_cart();

		$response = $this->dispatch(
			'POST',
			'/wc/store/v1/shopper-lists/wishlist/items',
			array( 'cart_item_key' => $cart_item_key )
		);

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test that adding the same cart line twice merges quantities into a single row.
	 */
	public function test_post_item_is_idempotent_for_same_cart_line() {
		wp_set_current_user( $this->customer_id );
		$cart_item_key = $this->add_product_to_cart();

		$first  = $this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items', array( 'cart_item_key' => $cart_item_key ) );
		$second = $this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items', array( 'cart_item_key' => $cart_item_key ) );

		$this->assertEquals( 201, $first->get_status() );
		$this->assertEquals( 201, $second->get_status() );
		$this->assertSame( $first->get_data()['key'], $second->get_data()['key'], 'Same cart line should resolve to the same item key.' );
		$this->assertSame( 2, $second->get_data()['quantity'], 'Repeating the same cart line must merge quantities.' );

		$items_response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later/items' );
		$this->assertCount( 1, $items_response->get_data(), 'Same cart line should not produce a duplicate row.' );
	}

	/**
	 * Test that GET /items returns the saved items.
	 */
	public function test_get_items_returns_saved_items() {
		wp_set_current_user( $this->customer_id );
		$cart_item_key = $this->add_product_to_cart();

		$this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items', array( 'cart_item_key' => $cart_item_key ) );

		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later/items' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
	}

	/**
	 * Test that DELETE removes the item and returns 204 No Content.
	 */
	public function test_delete_item_removes_item() {
		wp_set_current_user( $this->customer_id );
		$cart_item_key = $this->add_product_to_cart();

		$created = $this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items', array( 'cart_item_key' => $cart_item_key ) );
		$key     = $created->get_data()['key'];

		$response = $this->dispatch( 'DELETE', '/wc/store/v1/shopper-lists/saved-for-later/items/' . $key );

		$this->assertEquals( 204, $response->get_status() );
		$this->assertNull( $response->get_data() );

		$items_response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later/items' );
		$this->assertCount( 0, $items_response->get_data(), 'List should be empty after the only item is deleted.' );
	}

	/**
	 * Test that DELETE returns 404 when the item does not exist.
	 */
	public function test_delete_item_unknown_returns_404() {
		wp_set_current_user( $this->customer_id );

		// Auto-create the list so the route reaches the item-lookup branch.
		$this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later' );

		$nonexistent_key = str_repeat( 'a', 32 );
		$response        = $this->dispatch( 'DELETE', '/wc/store/v1/shopper-lists/saved-for-later/items/' . $nonexistent_key );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test that a logged-out user cannot delete via the route.
	 */
	public function test_delete_item_requires_login() {
		wp_set_current_user( 0 );

		$nonexistent_key = str_repeat( 'a', 32 );
		$response        = $this->dispatch( 'DELETE', '/wc/store/v1/shopper-lists/saved-for-later/items/' . $nonexistent_key );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}

	/**
	 * Test that one user cannot see another user's items.
	 */
	public function test_users_lists_are_isolated() {
		wp_set_current_user( $this->customer_id );
		$cart_item_key = $this->add_product_to_cart();
		$this->dispatch( 'POST', '/wc/store/v1/shopper-lists/saved-for-later/items', array( 'cart_item_key' => $cart_item_key ) );

		wp_set_current_user( $this->other_customer_id );
		$response = $this->dispatch( 'GET', '/wc/store/v1/shopper-lists/saved-for-later/items' );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertCount( 0, $response->get_data(), 'Other user should not see the first user\'s items.' );
	}

	/**
	 * Test writes without (or with invalid) Nonce header are rejected.
	 *
	 * @testWith ["POST", "", 401, "woocommerce_rest_missing_nonce"]
	 *           ["POST", "not-a-valid-nonce", 403, "woocommerce_rest_invalid_nonce"]
	 *           ["DELETE", "", 401, "woocommerce_rest_missing_nonce"]
	 *           ["DELETE", "not-a-valid-nonce", 403, "woocommerce_rest_invalid_nonce"]
	 *
	 * @param string $method              HTTP method.
	 * @param string $nonce               Nonce header value.
	 * @param int    $expected_status     Expected HTTP status code.
	 * @param string $expected_error_code Expected WP_Error code.
	 */
	public function test_write_nonce_enforcement( string $method, string $nonce, int $expected_status, string $expected_error_code ) {
		wp_set_current_user( $this->customer_id );

		$is_post = 'POST' === $method;
		$path    = $is_post
			? '/wc/store/v1/shopper-lists/saved-for-later/items'
			: '/wc/store/v1/shopper-lists/saved-for-later/items/' . str_repeat( 'a', 32 );
		$params  = $is_post ? array( 'product_id' => $this->product->get_id() ) : array();

		$response = $this->dispatch( $method, $path, $params, $nonce );

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertSame( $expected_error_code, $response->get_data()['code'] );
	}

	/**
	 * Test every response (success or auth failure) refreshes the Nonce headers.
	 *
	 * @testWith [null, 201]
	 *           ["", 401]
	 *
	 * @param string|null $nonce           Nonce header value, or null to auto-attach a valid one.
	 * @param int         $expected_status Expected HTTP status code.
	 */
	public function test_response_refreshes_nonce_headers( ?string $nonce, int $expected_status ) {
		wp_set_current_user( $this->customer_id );

		$response = $this->dispatch(
			'POST',
			'/wc/store/v1/shopper-lists/saved-for-later/items',
			array( 'product_id' => $this->product->get_id() ),
			$nonce
		);
		$headers  = $response->get_headers();

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertArrayHasKey( 'Nonce', $headers );
		$this->assertArrayHasKey( 'Nonce-Timestamp', $headers );
		$this->assertTrue( (bool) wp_verify_nonce( $headers['Nonce'], 'wc_store_api' ) );
	}

	/**
	 * Test the `woocommerce_store_api_disable_nonce_check` filter bypass.
	 */
	public function test_disable_nonce_check_filter_bypasses_enforcement() {
		wp_set_current_user( $this->customer_id );

		add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		try {
			$response = $this->dispatch(
				'POST',
				'/wc/store/v1/shopper-lists/saved-for-later/items',
				array( 'product_id' => $this->product->get_id() ),
				''
			);
		} finally {
			remove_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );
		}

		$this->assertEquals( 201, $response->get_status() );
	}
}
