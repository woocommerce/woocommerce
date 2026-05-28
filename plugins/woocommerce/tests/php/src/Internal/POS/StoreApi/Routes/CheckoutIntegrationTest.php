<?php
/**
 * POS Checkout integration tests.
 *
 * Verifies that the POS /checkout route is registered and produces a
 * pending order from the cart established by prior /cart/add-item calls,
 * without requiring a payment_method on the request (POS defers payment
 * method selection past order creation).
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy
 */
class CheckoutIntegrationTest extends ControllerTestCase {

	/**
	 * Admin user with manage_woocommerce capability.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Product used in the checkout flow.
	 *
	 * @var WC_Product_Simple
	 */
	private $product;

	/**
	 * Setup.
	 */
	protected function setUp(): void {
		parent::setUp();

		Context::set_test_override( true );

		// Engage the POS opt-out so the Store API's require-payment_method guard
		// is relaxed for these requests. In production this is registered in
		// class-woocommerce.php; in tests we register it explicitly so the test
		// is self-contained.
		( new CheckoutPaymentMethodPolicy() )->register();

		$this->admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );

		wc_get_container()->get( Controller::class )->register_routes();
		wc_get_container()->get( RoutesController::class )->register_all_routes();

		$fixtures      = new FixtureData();
		$this->product = $fixtures->get_simple_product(
			array(
				'name'          => 'POS Checkout Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 25,
			)
		);
	}

	/**
	 * Tear down.
	 */
	protected function tearDown(): void {
		remove_all_filters( 'woocommerce_store_api_checkout_require_payment_method' );
		Context::set_test_override( null );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox The /checkout route is registered under wc/pos/v1.
	 */
	public function test_checkout_route_is_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey(
			'/' . Controller::REST_NAMESPACE . '/checkout',
			$routes,
			'POS /checkout route should be registered.'
		);
	}

	/**
	 * @testdox Anonymous request to /checkout is forbidden.
	 */
	public function test_anonymous_request_is_forbidden(): void {
		wp_set_current_user( 0 );

		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		// Send a minimal body so WP REST args validation passes; the
		// permission_callback fires next and is the thing we are asserting on.
		$request->set_body_params(
			array(
				'billing_address'  => $this->minimal_address(),
				'shipping_address' => $this->minimal_address(),
			)
		);
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * @testdox Add-item then checkout with no payment_method creates a pending order with the same line item.
	 */
	public function test_checkout_without_payment_method_creates_pending_order_from_cart(): void {
		wp_set_current_user( $this->admin_id );

		// Build the cart with one item.
		$add_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$add_request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);
		$add_response = rest_get_server()->dispatch( $add_request );
		$this->assertSame( 201, $add_response->get_status() );

		$cart_token = '';
		foreach ( $add_response->get_headers() as $key => $value ) {
			if ( strtolower( $key ) === 'cart-token' ) {
				$cart_token = (string) $value;
				break;
			}
		}
		$this->assertNotEmpty( $cart_token, 'add-item should emit a Cart-Token header for checkout to use.' );

		// Run checkout against the same cart. Deliberately NO payment_method —
		// POS defers payment selection past order creation. The POS opt-out
		// (`woocommerce_store_api_checkout_require_payment_method`) allows this.
		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_request->set_body_params(
			array(
				'billing_address'  => $this->minimal_address(),
				'shipping_address' => $this->minimal_address(),
			)
		);
		$checkout_response = rest_get_server()->dispatch( $checkout_request );

		$this->assertSame(
			200,
			$checkout_response->get_status(),
			'Checkout failed: ' . wp_json_encode( $checkout_response->get_data() )
		);

		$data = $checkout_response->get_data();
		$this->assertArrayHasKey( 'order_id', $data );
		$this->assertGreaterThan( 0, $data['order_id'] );
		$this->assertSame( 'pending', $data['status'] );

		// There should be a real order in the database with our line item.
		$order = wc_get_order( $data['order_id'] );
		$this->assertNotFalse( $order, 'Order should exist in the database.' );
		$this->assertSame( 'pending', $order->get_status() );
		// payment_method is intentionally empty — the order is created in
		// `pending` and a subsequent payment flow (WooPayments capture, cash
		// mark-paid) will populate it when the cashier completes payment.
		$this->assertSame( '', $order->get_payment_method() );
		$items = $order->get_items();
		$this->assertCount( 1, $items );
		$first_item = reset( $items );
		$this->assertSame( $this->product->get_id(), $first_item->get_product_id() );
	}

	/**
	 * @testdox Without the POS opt-out, /checkout still rejects missing payment_method (regression).
	 */
	public function test_default_behavior_still_requires_payment_method(): void {
		wp_set_current_user( $this->admin_id );

		// Turn the POS opt-out off for this one test, mirroring how a non-POS
		// caller would experience the guard.
		Context::set_test_override( false );

		$add_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$add_request->set_body_params(
			array(
				'id'       => $this->product->get_id(),
				'quantity' => 1,
			)
		);
		$add_response = rest_get_server()->dispatch( $add_request );
		// add-item ran outside POS context so the URI-based detection would
		// normally apply auth/oversell defaults — but our wrapper still gates
		// on capability and add-item itself doesn't need payment_method, so
		// it still succeeds. The point of this test is only the checkout guard.
		$this->assertSame( 201, $add_response->get_status() );

		$cart_token = '';
		foreach ( $add_response->get_headers() as $key => $value ) {
			if ( strtolower( $key ) === 'cart-token' ) {
				$cart_token = (string) $value;
				break;
			}
		}

		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_request->set_body_params(
			array(
				'billing_address'  => $this->minimal_address(),
				'shipping_address' => $this->minimal_address(),
			)
		);
		$checkout_response = rest_get_server()->dispatch( $checkout_request );

		$this->assertSame( 400, $checkout_response->get_status() );
		$this->assertSame(
			'woocommerce_rest_checkout_missing_payment_method',
			$checkout_response->get_data()['code'] ?? null,
			'Outside POS context the original Store API guard must still fire.'
		);
	}

	/**
	 * Minimal billing/shipping address payload accepted by the Store API checkout schema.
	 *
	 * @return array
	 */
	private function minimal_address(): array {
		return array(
			'first_name' => 'POS',
			'last_name'  => 'Tester',
			'company'    => '',
			'address_1'  => '123 Main St',
			'address_2'  => '',
			'city'       => 'Townsville',
			'state'      => 'CA',
			'postcode'   => '12345',
			'country'    => 'US',
			'email'      => 'pos-tester@example.com',
			'phone'      => '5551234567',
		);
	}
}
