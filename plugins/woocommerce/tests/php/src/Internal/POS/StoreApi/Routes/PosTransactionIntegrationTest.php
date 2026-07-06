<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\Controller;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\SessionHandlerSwap;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * End-to-end POS transaction tests.
 *
 * Drives the whole surface the way the app will: add-items (with a partial
 * failure) → add-fee → checkout with an empty body → assert a pending guest
 * order, correctly totalled, never owned by the operator. Routes land on each
 * test's fresh REST server through the production rest_api_init wiring.
 *
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Controller
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddItems
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\CartAddFee
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Checkout
 */
class PosTransactionIntegrationTest extends ControllerTestCase {

	/**
	 * Operator user (shop manager, has manage_woocommerce).
	 *
	 * @var int
	 */
	private $operator_id;

	/**
	 * In-stock product.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Out-of-stock product (sellable through the POS stock policy).
	 *
	 * @var WC_Product_Simple
	 */
	private $out_of_stock_product;

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		// rest_get_server()->dispatch() bypasses the URL routing layer, so URI
		// detection can't apply; force POS context the way a real POS URI would.
		Context::set_test_override( true );

		// Re-initialize the session under POS context so the whole class runs
		// on POSSessionHandler (pos_-keyed transaction session), as production
		// requests do.
		WC()->session = null;
		WC()->initialize_session();

		// The bootstrap registered the policy hooks via Controller::register();
		// the swap is inert outside POS context and engages now via the override.
		$this->original_user_id = get_current_user_id();
		$this->operator_id      = $this->factory()->user->create( array( 'role' => 'shop_manager' ) );

		$fixtures                   = new FixtureData();
		$this->product              = $fixtures->get_simple_product(
			array(
				'name'          => 'POS Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			)
		);
		$this->out_of_stock_product = $fixtures->get_simple_product(
			array(
				'name'          => 'POS Out of Stock Product',
				'stock_status'  => ProductStockStatus::OUT_OF_STOCK,
				'regular_price' => 5,
			)
		);
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		Context::set_test_override( null );
		wp_set_current_user( $this->original_user_id );

		remove_all_filters( 'woocommerce_session_handler' );
		WC()->session = null;
		WC()->initialize_session();

		parent::tearDown();
	}

	/**
	 * @testdox All three POS routes are registered through the production wiring.
	 */
	public function test_routes_are_registered(): void {
		$routes = rest_get_server()->get_routes();

		foreach ( array( '/cart/add-items', '/cart/add-fee', '/checkout' ) as $path ) {
			$this->assertArrayHasKey( '/' . Controller::REST_NAMESPACE . $path, $routes );
		}
	}

	/**
	 * @testdox Anonymous and non-manager users cannot use POS routes.
	 */
	public function test_capability_gate(): void {
		wp_set_current_user( 0 );
		$this->assertSame(
			401,
			$this->add_items(
				array(
					array(
						'id'       => $this->product->get_id(),
						'quantity' => 1,
					),
				)
			)->get_status()
		);

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertSame(
			403,
			$this->add_items(
				array(
					array(
						'id'       => $this->product->get_id(),
						'quantity' => 1,
					),
				)
			)->get_status()
		);
	}

	/**
	 * @testdox add-items reports per-item outcomes: sellable items land, invalid items fail without aborting the rest.
	 */
	public function test_add_items_partial_success(): void {
		wp_set_current_user( $this->operator_id );

		$response = $this->add_items(
			array(
				array(
					'id'       => $this->product->get_id(),
					'quantity' => 2,
				),
				array(
					'id'       => $this->out_of_stock_product->get_id(),
					'quantity' => 1,
				),
				array(
					'id'       => 999999,
					'quantity' => 1,
				),
			)
		);

		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['items'][0]['added'] );
		$this->assertTrue( $data['items'][1]['added'], 'Out-of-stock product should be sellable through the POS stock policy.' );
		$this->assertFalse( $data['items'][2]['added'] );
		$this->assertNotEmpty( $data['items'][2]['error']['code'] );

		$this->assertCount( 2, $data['cart']['items'] );
	}

	/**
	 * @testdox add-items returns 400 with per-item detail when nothing could be added.
	 */
	public function test_add_items_all_failed(): void {
		wp_set_current_user( $this->operator_id );

		$response = $this->add_items(
			array(
				array(
					'id'       => 999999,
					'quantity' => 1,
				),
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_no_items_added', $response->get_data()['code'] );
	}

	/**
	 * Extensions on woocommerce_store_api_add_to_cart_data read the request's
	 * top-level params; each batch item must present itself like a web
	 * add-item request.
	 *
	 * @testdox The add-to-cart data filter sees per-item request params.
	 */
	public function test_add_to_cart_filter_sees_per_item_params(): void {
		wp_set_current_user( $this->operator_id );

		$seen_ids  = array();
		$extension = function ( $data, $request ) use ( &$seen_ids ) {
			$seen_ids[] = $request['id'];
			return $data;
		};
		add_filter( 'woocommerce_store_api_add_to_cart_data', $extension, 10, 2 );

		try {
			$this->add_items(
				array(
					array(
						'id'       => $this->product->get_id(),
						'quantity' => 1,
					),
					array(
						'id'       => $this->out_of_stock_product->get_id(),
						'quantity' => 1,
					),
				)
			);
		} finally {
			remove_filter( 'woocommerce_store_api_add_to_cart_data', $extension, 10 );
		}

		$this->assertSame( array( $this->product->get_id(), $this->out_of_stock_product->get_id() ), $seen_ids );
	}

	/**
	 * Plugins on the add-to-cart hooks can throw anything; one item's failure
	 * must stay that item's failure.
	 *
	 * @testdox A plugin exception on one item does not abort the batch.
	 */
	public function test_plugin_exception_is_contained_per_item(): void {
		wp_set_current_user( $this->operator_id );

		$second_product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'Cursed Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 3,
			)
		);

		$throw_for_cursed = function ( $cart_item_key, $product_id ) use ( $second_product ) {
			unset( $cart_item_key );
			if ( $product_id === $second_product->get_id() ) {
				throw new \Exception( 'Plugin exploded.' );
			}
		};
		add_action( 'woocommerce_add_to_cart', $throw_for_cursed, 10, 2 );

		try {
			$response = $this->add_items(
				array(
					array(
						'id'       => $this->product->get_id(),
						'quantity' => 1,
					),
					array(
						'id'       => $second_product->get_id(),
						'quantity' => 1,
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_add_to_cart', $throw_for_cursed, 10 );
		}

		$this->assertSame( 201, $response->get_status(), 'Body: ' . wp_json_encode( $response->get_data() ) );
		$data = $response->get_data();
		$this->assertTrue( $data['items'][0]['added'] );
		$this->assertFalse( $data['items'][1]['added'] );
		$this->assertSame( 'woocommerce_pos_rest_add_item_failed', $data['items'][1]['error']['code'] );
		$this->assertCount( 1, $data['cart']['items'] );
	}

	/**
	 * @testdox A valid Cart-Token threads the transaction across requests.
	 */
	public function test_cart_token_threads_transaction(): void {
		wp_set_current_user( $this->operator_id );

		$first = $this->add_items( array( array( 'id' => $this->product->get_id(), 'quantity' => 1 ) ) );
		$this->assertSame( 201, $first->get_status() );
		$token = \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::get_cart_token( WC()->session->get_customer_id() );

		wp_set_current_user( $this->operator_id );
		$second = $this->add_items( array( array( 'id' => $this->product->get_id(), 'quantity' => 1 ) ), $token );

		$this->assertSame( 201, $second->get_status() );
		$this->assertSame( 2, $second->get_data()['cart']['items'][0]['quantity'] );
	}

	/**
	 * A presented-but-unusable token must fail loud — silently starting a
	 * fresh cart would drop every scanned item and undercharge at checkout.
	 *
	 * @testdox Invalid and expired Cart-Tokens are rejected with 401, not silently discarded.
	 */
	public function test_unusable_cart_token_fails_loud(): void {
		wp_set_current_user( $this->operator_id );

		$garbage = $this->add_items( array( array( 'id' => $this->product->get_id(), 'quantity' => 1 ) ), 'not-a-token' );
		$this->assertSame( 401, $garbage->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_invalid_cart_token', $garbage->get_data()['code'] );

		// Mint an already-expired token by forcing a negative lifetime.
		$expire_now = function () {
			return -100;
		};
		add_filter( 'wc_session_expiration', $expire_now, 100 );
		try {
			$expired_token = \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::get_cart_token( 'pos_' . wc_rand_hash( '', 28 ) );
		} finally {
			remove_filter( 'wc_session_expiration', $expire_now, 100 );
		}

		wp_set_current_user( $this->operator_id );
		$expired = $this->add_items( array( array( 'id' => $this->product->get_id(), 'quantity' => 1 ) ), $expired_token );
		$this->assertSame( 401, $expired->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_invalid_cart_token', $expired->get_data()['code'] );
	}

	/**
	 * @testdox add-fee rejects non-positive amounts.
	 */
	public function test_add_fee_rejects_non_positive_amount(): void {
		wp_set_current_user( $this->operator_id );

		$response = $this->dispatch_post(
			'/cart/add-fee',
			array(
				'name'   => 'Discount?',
				'amount' => -5,
			)
		);

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_invalid_fee_amount', $response->get_data()['code'] );
	}

	/**
	 * The full register flow, exactly as the app drives it.
	 *
	 * @testdox A full transaction — add-items, add-fee, empty-body checkout — creates a pending guest order with correct totals.
	 */
	public function test_full_transaction_creates_pending_guest_order(): void {
		wp_set_current_user( $this->operator_id );

		// Scan two units at 10.00.
		$add = $this->add_items(
			array(
				array(
					'id'       => $this->product->get_id(),
					'quantity' => 2,
				),
			)
		);
		$this->assertSame( 201, $add->get_status() );

		// Add a 5.00 custom amount. Each dispatch drops the operator identity
		// via CurrentUserSwap; a real client re-authenticates per HTTP request,
		// so simulate that here too.
		wp_set_current_user( $this->operator_id );
		$fee = $this->dispatch_post(
			'/cart/add-fee',
			array(
				'name'   => 'Gift wrap',
				'amount' => 5,
			)
		);
		$this->assertSame( 201, $fee->get_status() );
		$this->assertSame( '500', $fee->get_data()['totals']->total_fees, 'The response cart should include the fee.' );

		// Checkout with an empty body: no payment method, no addresses, no email.
		wp_set_current_user( $this->operator_id );
		$checkout = $this->dispatch_post( '/checkout', array() );
		$this->assertSame( 200, $checkout->get_status(), 'Checkout failed: ' . wp_json_encode( $checkout->get_data() ) );

		$data = $checkout->get_data();
		$this->assertSame( 'pending', $data['status'] );

		// The order is real, pending, correctly totalled — and belongs to a
		// guest (or an identified customer), never to the operator.
		$order = wc_get_order( $data['order_id'] );
		$this->assertNotFalse( $order );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 25.0, (float) $order->get_total(), 'Total should be 2×10.00 + 5.00 fee.' );
		$this->assertSame( 0, $order->get_customer_id(), 'The order must not be attributed to the operator.' );
		$this->assertNotEquals( $this->operator_id, $order->get_customer_id() );
	}

	/**
	 * POS sales are account-independent: web account settings must not block
	 * the register or create accounts for walk-in customers.
	 *
	 * @testdox Checkout succeeds as guest even when web guest checkout is disabled, and never creates an account.
	 */
	public function test_checkout_ignores_web_account_settings(): void {
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
		$user_count_before = count_users()['total_users'];

		try {
			wp_set_current_user( $this->operator_id );
			$this->add_items( array( array( 'id' => $this->product->get_id(), 'quantity' => 1 ) ) );

			wp_set_current_user( $this->operator_id );
			$checkout = $this->dispatch_post( '/checkout', array() );

			$this->assertSame( 200, $checkout->get_status(), 'Guest-checkout-disabled must not block POS: ' . wp_json_encode( $checkout->get_data() ) );
			$this->assertSame( 0, wc_get_order( $checkout->get_data()['order_id'] )->get_customer_id() );
			$this->assertSame( $user_count_before, count_users()['total_users'], 'No account may be created for a walk-in customer.' );
		} finally {
			delete_option( 'woocommerce_enable_guest_checkout' );
			delete_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		}
	}

	/**
	 * @testdox Checkout attaches an identified customer account via customer_id — still never the operator.
	 */
	public function test_checkout_attaches_identified_customer(): void {
		wp_set_current_user( $this->operator_id );
		$customer_id = $this->factory()->user->create( array( 'role' => 'customer' ) );

		// Give the account saved address data; a POS sale must never touch it.
		$customer = new \WC_Customer( $customer_id );
		$customer->set_billing_city( 'Brno' );
		$customer->set_billing_email( 'saved@example.com' );
		$customer->save();

		$this->add_items(
			array(
				array(
					'id'       => $this->product->get_id(),
					'quantity' => 1,
				),
			)
		);

		wp_set_current_user( $this->operator_id );
		// CurrentUserSwap dropped the identity during the previous dispatch.
		$checkout = $this->dispatch_post( '/checkout', array( 'customer_id' => $customer_id ) );

		$this->assertSame( 200, $checkout->get_status(), 'Checkout failed: ' . wp_json_encode( $checkout->get_data() ) );

		$order = wc_get_order( $checkout->get_data()['order_id'] );
		$this->assertSame( $customer_id, $order->get_customer_id() );

		$customer = new \WC_Customer( $customer_id );
		$this->assertSame( 'Brno', $customer->get_billing_city(), 'A POS sale must not overwrite the account saved addresses.' );
		$this->assertSame( 'saved@example.com', $customer->get_billing_email() );
	}

	/**
	 * @testdox Checkout with an unknown customer_id fails with 400.
	 */
	public function test_checkout_rejects_unknown_customer(): void {
		wp_set_current_user( $this->operator_id );

		$this->add_items(
			array(
				array(
					'id'       => $this->product->get_id(),
					'quantity' => 1,
				),
			)
		);

		wp_set_current_user( $this->operator_id );
		$checkout = $this->dispatch_post( '/checkout', array( 'customer_id' => 987654321 ) );

		$this->assertSame( 400, $checkout->get_status() );
		$this->assertSame( 'woocommerce_pos_rest_customer_not_found', $checkout->get_data()['code'] );
	}

	/**
	 * Dispatch POST /cart/add-items.
	 *
	 * @param array       $items      Items payload.
	 * @param string|null $cart_token Optional Cart-Token header value.
	 * @return \WP_REST_Response
	 */
	private function add_items( array $items, ?string $cart_token = null ): \WP_REST_Response {
		return $this->dispatch_post( '/cart/add-items', array( 'items' => $items ), $cart_token );
	}

	/**
	 * Dispatch a POST request to a POS route.
	 *
	 * @param string      $path       Route path below the POS namespace.
	 * @param array       $body       Body params.
	 * @param string|null $cart_token Optional Cart-Token header value.
	 * @return \WP_REST_Response
	 */
	private function dispatch_post( string $path, array $body, ?string $cart_token = null ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . $path );
		$request->set_body_params( $body );
		if ( null !== $cart_token ) {
			$request->set_header( 'Cart-Token', $cart_token );
		}

		return rest_get_server()->dispatch( $request );
	}
}
