<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Regression tests for the AbstractCartRoute nonce-failure flow.
 *
 * Covers the case reported in https://github.com/woocommerce/woocommerce/issues/32070
 * where a stale nonce after a session change (logging into My Account and navigating
 * back to checkout) caused the cart update flow to keep syncing the draft order in
 * spite of the 403 response, producing an endless checkout spinner.
 */
class AbstractCartRouteNonceTest extends ControllerTestCase {

	/**
	 * Test product used by the cart update fixtures.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Cart item key for the seeded product.
	 *
	 * @var string
	 */
	private $cart_item_key;

	/**
	 * Setup cart fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();

		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'Test Product 1',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
				'weight'        => 10,
			)
		);

		wc_empty_cart();
		$this->cart_item_key = wc()->cart->add_to_cart( $this->product->get_id(), 2 );
	}

	/**
	 * @testdox Update requests with a missing nonce header are rejected with a 401.
	 */
	public function test_update_request_without_nonce_is_rejected(): void {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->cart_item_key );
		$request->set_body_params( array( 'quantity' => '5' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 401, $response->get_status(), 'A missing Nonce header should produce a 401 response.' );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_missing_nonce', $data['code'] );
	}

	/**
	 * @testdox Update requests with an invalid nonce header are rejected with a 403.
	 */
	public function test_update_request_with_invalid_nonce_is_rejected(): void {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->cart_item_key );
		$request->set_header( 'Nonce', 'this-is-not-a-valid-nonce' );
		$request->set_body_params( array( 'quantity' => '5' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status(), 'An invalid Nonce header should produce a 403 response.' );
		$data = $response->get_data();
		$this->assertSame( 'woocommerce_rest_invalid_nonce', $data['code'] );
	}

	/**
	 * @testdox An invalid-nonce update response advertises a fresh nonce so the client can recover.
	 */
	public function test_invalid_nonce_response_carries_fresh_nonce_header(): void {
		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->cart_item_key );
		$request->set_header( 'Nonce', 'this-is-not-a-valid-nonce' );
		$request->set_body_params( array( 'quantity' => '5' ) );

		$response = rest_get_server()->dispatch( $request );
		$headers  = $response->get_headers();

		$this->assertArrayHasKey( 'Nonce', $headers, 'Error responses must still expose a fresh Nonce header for client recovery.' );
		$this->assertNotEmpty( $headers['Nonce'], 'The fresh Nonce header must contain a value.' );
		$this->assertTrue( (bool) wp_verify_nonce( $headers['Nonce'], 'wc_store_api' ), 'The Nonce header value must validate against the wc_store_api action.' );
	}

	/**
	 * @testdox A rejected update request does not trigger the cart-updated draft order sync.
	 */
	public function test_invalid_nonce_does_not_trigger_cart_updated_hook(): void {
		$hook_calls = 0;
		$listener   = function () use ( &$hook_calls ) {
			++$hook_calls;
		};

		add_action( 'woocommerce_store_api_cart_update_order_from_request', $listener );

		// Seed a draft order so cart_updated() would otherwise have something to sync.
		$draft_order = wc_create_order(
			array(
				'status'        => 'checkout-draft',
				'created_via'   => 'store-api',
				'cart_hash'     => wc()->cart->get_cart_hash(),
				'customer_note' => 'fixture',
			)
		);
		wc()->session->set( 'store_api_draft_order', $draft_order->get_id() );

		$request = new \WP_REST_Request( 'PUT', '/wc/store/v1/cart/items/' . $this->cart_item_key );
		$request->set_header( 'Nonce', 'this-is-not-a-valid-nonce' );
		$request->set_body_params( array( 'quantity' => '5' ) );

		$response = rest_get_server()->dispatch( $request );

		remove_action( 'woocommerce_store_api_cart_update_order_from_request', $listener );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 0, $hook_calls, 'cart_updated() must not run when the nonce check has failed.' );
	}
}
