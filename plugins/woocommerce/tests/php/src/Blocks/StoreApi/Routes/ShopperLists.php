<?php
/**
 * Shopper Lists Route Tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Tests for the /wc/store/v1/shopper-lists/* endpoints.
 */
class ShopperLists extends ControllerTestCase {

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

		$fixtures      = new FixtureData();
		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product',
				'regular_price' => 10,
			)
		);

		$this->customer_id       = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'shopper-lists-1@test.com',
			)
		);
		$this->other_customer_id = $this->factory->user->create(
			array(
				'role'       => 'customer',
				'user_email' => 'shopper-lists-2@test.com',
			)
		);
	}

	/**
	 * Tear down test data.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		if ( $this->customer_id ) {
			wp_delete_user( $this->customer_id );
		}
		if ( $this->other_customer_id ) {
			wp_delete_user( $this->other_customer_id );
		}
	}

	/**
	 * Helper: dispatch a request and return the response.
	 *
	 * @param string $method HTTP method.
	 * @param string $route Route path.
	 * @param array  $params Body params.
	 * @return \WP_REST_Response
	 */
	private function dispatch( string $method, string $route, array $params = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
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
		$this->assertTrue( $data['product_exists'] );
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
}
