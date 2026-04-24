<?php
/**
 * SaveForLater route tests.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Integration tests covering the save-for-later Store API routes end-to-end.
 */
class SaveForLater extends ControllerTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * Simple product used in cart fixtures.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Cart item key for the simple product.
	 *
	 * @var string
	 */
	private $cart_item_key;

	/**
	 * Set up shared fixtures for each test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->user_id = self::factory()->user->create( array( 'role' => 'customer' ) );
		wp_set_current_user( $this->user_id );

		$fixtures      = new FixtureData();
		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);

		wc_empty_cart();
		$this->cart_item_key = wc()->cart->add_to_cart( $this->product->get_id(), 2 );
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		delete_user_meta( $this->user_id, '_wc_saved_list_save_for_later_' . get_current_blog_id() );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	/**
	 * Test that GET is rejected for anonymous users.
	 */
	public function test_get_returns_unauthorized_when_not_logged_in() {
		wp_set_current_user( 0 );
		$this->assertAPIResponse( '/wc/store/v1/saved-lists/save-for-later', 401 );
	}

	/**
	 * Test GET returns an empty list for a logged-in user with nothing saved.
	 */
	public function test_get_returns_empty_list_for_logged_in_user() {
		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/saved-lists/save-for-later' ) );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'save-for-later', $data['list_id'] );
		$this->assertSame( 0, $data['items_count'] );
		$this->assertSame( array(), $data['items'] );
	}

	/**
	 * Test POST /items copies a cart item into the list without touching the cart — the client
	 * owns cart removal so the optimistic cart store stays authoritative. The response shape is
	 * the saved-list item so the client can push it into its Interactivity state directly.
	 */
	public function test_save_for_later_copies_cart_item_to_list() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/saved-lists/save-for-later/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'cart_item_key' => $this->cart_item_key ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, wc()->cart->get_cart_contents(), 'Cart should remain untouched; client handles removal.' );
		$this->assertArrayHasKey( 'key', $data );
		$this->assertSame( $this->product->get_id(), $data['product_id'] );
		$this->assertSame( 2, $data['quantity'] );

		$get      = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/saved-lists/save-for-later' ) );
		$get_data = $get->get_data();
		$this->assertSame( 1, $get_data['items_count'] );
		$this->assertSame( $data['key'], $get_data['items'][0]['key'] );
	}

	/**
	 * Test POST /items returns 409 when the referenced cart item does not exist.
	 */
	public function test_save_for_later_with_invalid_cart_item_key_returns_409() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/saved-lists/save-for-later/items' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( array( 'cart_item_key' => str_repeat( 'f', 32 ) ) );

		$this->assertAPIResponse( $request, 409 );
	}

	/**
	 * Test DELETE removes an item from the list without restoring to cart.
	 */
	public function test_delete_removes_item_from_list() {
		$save_request = new \WP_REST_Request( 'POST', '/wc/store/v1/saved-lists/save-for-later/items' );
		$save_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$save_request->set_body_params( array( 'cart_item_key' => $this->cart_item_key ) );
		rest_get_server()->dispatch( $save_request );

		$saved_key = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/saved-lists/save-for-later' ) )->get_data()['items'][0]['key'];

		$delete_request = new \WP_REST_Request( 'DELETE', '/wc/store/v1/saved-lists/save-for-later/items/' . $saved_key );
		$delete_request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$response = rest_get_server()->dispatch( $delete_request );

		$this->assertSame( 204, $response->get_status() );
		$this->assertCount( 0, wc()->cart->get_cart_contents(), 'Cart should remain empty (item was already saved and now deleted).' );

		$list_after = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/saved-lists/save-for-later' ) )->get_data();
		$this->assertSame( 0, $list_after['items_count'] );
	}
}
