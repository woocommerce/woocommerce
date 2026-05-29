<?php
/**
 * POS CartAddItem integration tests.
 *
 * Exercises the route end-to-end against the real REST server, the real Store
 * API cart pipeline, and the real session handler swap. These tests would
 * have caught:
 *
 *   - The route not being registered (404 / missing namespace).
 *   - The `get_args` mismatch that corrupted the parent's `schema` /
 *     `allow_batch` metadata and crashed the REST index.
 *   - Missing capability gate (anonymous access succeeding).
 *   - Cart-Token round-trip not actually loading the same server-side
 *     session.
 *   - The stock-policy hook not actually relaxing oversell.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CartPersistencePolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItem;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItem
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
 */
class CartAddItemIntegrationTest extends ControllerTestCase {

	/**
	 * Admin user with manage_woocommerce capability.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * In-stock product used across most tests.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Out-of-stock product used to exercise the POS oversell policy.
	 *
	 * @var WC_Product_Simple
	 */
	private $out_of_stock_product;

	/**
	 * Original current_user_id captured in setUp so it can be restored —
	 * individual tests call wp_set_current_user() and must not leak into
	 * neighbours.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Setup.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Most tests assert behaviour conditional on this request being a POS request.
		// We can't rely on URI detection here because rest_get_server()->dispatch()
		// bypasses the URL routing layer that populates $_SERVER['REQUEST_URI'];
		// individual tests that exercise detection itself can clear this override.
		Context::set_test_override( true );

		$this->original_user_id = get_current_user_id();
		$this->admin_id         = $this->factory()->user->create( array( 'role' => 'administrator' ) );

		// Engage the POS persistent-cart opt-out so the per-user-meta cart row
		// doesn't leak into POS requests. In production this is registered in
		// class-woocommerce.php; in tests we register it explicitly so the test
		// is self-contained and exercises the production wiring path.
		( new CartPersistencePolicy() )->register();

		// Make sure the POS routes register themselves into our fresh REST server.
		wc_get_container()->get( Controller::class )->register_routes();
		wc_get_container()->get( RoutesController::class )->register_all_routes();

		$fixtures                   = new FixtureData();
		$this->product              = $fixtures->get_simple_product(
			array(
				'name'          => 'POS Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
		$this->out_of_stock_product = $fixtures->get_simple_product(
			array(
				'name'          => 'POS Out-of-stock Product',
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				'regular_price' => 5,
			)
		);
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		remove_all_filters( 'woocommerce_persistent_cart_enabled' );
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox The POS namespace and route are present in the REST server's index.
	 */
	public function test_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey( '/' . Controller::REST_NAMESPACE, $routes );
		$this->assertArrayHasKey( '/' . Controller::REST_NAMESPACE . '/cart/add-item', $routes );
	}

	/**
	 * @testdox The REST index for the namespace is buildable (regression for the schema corruption bug).
	 */
	public function test_rest_index_for_namespace_is_buildable(): void {
		$request  = new \WP_REST_Request( 'GET', '/' . Controller::REST_NAMESPACE );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( Controller::REST_NAMESPACE, $data['namespace'] );
		$this->assertArrayHasKey( '/' . Controller::REST_NAMESPACE . '/cart/add-item', $data['routes'] );
	}

	/**
	 * @testdox Anonymous request to cart/add-item is forbidden.
	 */
	public function test_anonymous_request_is_forbidden(): void {
		wp_set_current_user( 0 );

		$response = $this->add_item( $this->product->get_id() );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox Admin can add an in-stock item and receives a Cart-Token response header.
	 */
	public function test_admin_can_add_item_and_receives_cart_token(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->add_item( $this->product->get_id() );

		$this->assertSame( 201, $response->get_status() );

		$cart_token = $this->cart_token_header( $response );
		$this->assertNotEmpty( $cart_token, 'Cart-Token response header should be present.' );
		$this->assertTrue(
			CartTokenUtils::validate_cart_token( $cart_token ),
			'Cart-Token in response header should be a valid signed JWT.'
		);

		$data = $response->get_data();
		$this->assertCount( 1, $data['items'] );
		$this->assertSame( $this->product->get_id(), $data['items'][0]['id'] );
	}

	/**
	 * @testdox Replaying the Cart-Token via the cart_token URL parameter keeps adding to the same cart.
	 */
	public function test_cart_token_replay_persists_session_across_requests(): void {
		wp_set_current_user( $this->admin_id );

		$first = $this->add_item( $this->product->get_id() );
		$this->assertSame( 201, $first->get_status() );
		$cart_token = $this->cart_token_header( $first );
		$this->assertNotEmpty( $cart_token );

		// CurrentUserSwap (registered globally via class-woocommerce.php for the test
		// environment) set current_user to 0 during the prior dispatch; re-auth the
		// cashier so the next request's permission check sees them.
		wp_set_current_user( $this->admin_id );

		$second = $this->add_item( $this->product->get_id(), 1, array( 'cart_token' => $cart_token ) );
		$this->assertSame( 201, $second->get_status() );

		$data = $second->get_data();
		// Same product added twice should collapse to one line with quantity 2 — proving the cart
		// state from the first request was actually loaded for the second.
		$this->assertCount( 1, $data['items'] );
		$this->assertSame( 2, $data['items'][0]['quantity'] );
	}

	/**
	 * @testdox Out-of-stock products can be added on POS (stock policy override).
	 */
	public function test_stock_policy_allows_oversell_for_pos(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->add_item( $this->out_of_stock_product->get_id() );

		$this->assertSame(
			201,
			$response->get_status(),
			'StockPolicy should let POS oversell; response was: ' . wp_json_encode( $response->get_data() )
		);
	}

	/**
	 * @testdox Nonce is not required for POS requests.
	 */
	public function test_nonce_is_not_required(): void {
		wp_set_current_user( $this->admin_id );

		$response = $this->add_item( $this->product->get_id() );

		// If the parent route's nonce check were active we'd get a 401 with
		// `woocommerce_rest_missing_nonce`; absence of that is the assertion.
		$this->assertSame( 201, $response->get_status() );
		$this->assertNotSame( 'woocommerce_rest_missing_nonce', $response->get_data()['code'] ?? null );
	}

	/**
	 * Dispatch POST /wc/pos/v1/cart/add-item with the given product id + quantity.
	 *
	 * @param int   $product_id Product ID to add.
	 * @param int   $quantity   Quantity to add.
	 * @param array $extra      Extra request params (e.g. cart_token).
	 * @return \WP_REST_Response
	 */
	private function add_item( int $product_id, int $quantity = 1, array $extra = array() ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$request->set_body_params(
			array_merge(
				array(
					'id'       => $product_id,
					'quantity' => $quantity,
				),
				$extra
			)
		);

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Pull the Cart-Token value out of a response's headers, case-insensitive.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @return string
	 */
	private function cart_token_header( \WP_REST_Response $response ): string {
		foreach ( $response->get_headers() as $key => $value ) {
			if ( strtolower( $key ) === 'cart-token' ) {
				return (string) $value;
			}
		}
		return '';
	}

	/**
	 * Regression for "items from a previous transaction reappear in the cart
	 * even after clearing the app, with a fresh session and a different
	 * cart-token." Root cause: `WC_Cart_Session` saves the cart to
	 * `_woocommerce_persistent_cart_{blog_id}` in `wp_usermeta` keyed by the
	 * logged-in WP user_id. Since every POS request authenticates as the
	 * same admin, that row leaks across transactions, devices and app
	 * restarts. `CartPersistencePolicy` disables this via the
	 * `woocommerce_persistent_cart_enabled` filter for POS context.
	 *
	 * @testdox A pre-existing persistent-cart user_meta row does not leak items into a POS add-item.
	 */
	public function test_persistent_cart_user_meta_does_not_leak_into_pos(): void {
		wp_set_current_user( $this->admin_id );

		// Prime the admin's persistent-cart user_meta with a different
		// product. Without the policy hook, WC_Cart_Session::get_saved_cart
		// would read this on the next cart load and merge it into the cart.
		$leaked_product   = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'Leaked Persistent Cart Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 999,
			)
		);
		$persistent_cart  = array(
			'cart' => array(
				wp_generate_password( 32, false ) => array(
					'key'          => wp_generate_password( 32, false ),
					'product_id'   => $leaked_product->get_id(),
					'variation_id' => 0,
					'variation'    => array(),
					'quantity'     => 1000000,
					'data_hash'    => wc_get_cart_item_data_hash( $leaked_product ),
				),
			),
		);
		update_user_meta(
			$this->admin_id,
			'_woocommerce_persistent_cart_' . get_current_blog_id(),
			$persistent_cart
		);

		// Now do a fresh POS add-item with quantity 1 of the test product.
		$response = $this->add_item( $this->product->get_id(), 1 );

		$this->assertSame( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertCount(
			1,
			$data['items'],
			'POS cart should contain only the one item just added, not the leaked persistent-cart line.'
		);
		$this->assertSame(
			$this->product->get_id(),
			$data['items'][0]['id'],
			'POS cart line should be the freshly added product, not the leaked one.'
		);
		$this->assertSame(
			1,
			$data['items'][0]['quantity'],
			'POS cart line quantity should be exactly 1.'
		);
	}

	/**
	 * Silence the unused-import warning while making the class-level @covers explicit.
	 *
	 * @return void
	 */
	public function test_covered_classes_are_present(): void {
		$this->assertTrue( class_exists( CartAddItem::class ) );
	}
}
