<?php
/**
 * POS Checkout integration tests.
 *
 * Verifies that the POS /checkout route is registered and produces a
 * pending order from the cart established by prior /cart/add-item calls.
 *
 * @package Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\StoreApi\Routes;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\POS\StoreApi\Context;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
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
	 * Instance of the no-op POS test gateway.
	 *
	 * @var PosCheckoutTestGateway
	 */
	private $test_gateway;

	/**
	 * Setup.
	 */
	protected function setUp(): void {
		// The Store API checkout validates payment_method on POST (via
		// CheckoutTrait::update_order_from_request) — a no-op POS gateway lets
		// us prove the end-to-end flow without depending on a real one.
		// Two filters are needed (mirroring the agentic test pattern): the
		// first puts the gateway in the registered list; the second injects
		// it into the per-request "available" gateway map that the Store API
		// schema enum reads from. We also force the WC gateways singleton to
		// re-init so a previously-cached gateway list from a prior test does
		// not mask our additions.
		$this->test_gateway = new PosCheckoutTestGateway();
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_test_gateway' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'inject_available_test_gateway' ) );
		WC()->payment_gateways()->init();

		parent::setUp();

		Context::set_test_override( true );

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
		remove_filter( 'woocommerce_payment_gateways', array( $this, 'register_test_gateway' ) );
		remove_filter( 'woocommerce_available_payment_gateways', array( $this, 'inject_available_test_gateway' ) );
		Context::set_test_override( null );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * Append a no-op POS test gateway to the list of registered payment gateway classes.
	 *
	 * @param string[] $gateways Registered gateway class names.
	 * @return string[]
	 */
	public function register_test_gateway( array $gateways ): array {
		$gateways[] = PosCheckoutTestGateway::class;
		return $gateways;
	}

	/**
	 * Inject the POS test gateway instance into the per-request available
	 * gateway map. This is what the Store API checkout schema enum reads from.
	 *
	 * @param array $gateways Existing available gateways keyed by ID.
	 * @return array
	 */
	public function inject_available_test_gateway( array $gateways ): array {
		$gateways[ PosCheckoutTestGateway::GATEWAY_ID ] = $this->test_gateway;
		return $gateways;
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
	 * @testdox Add-item then checkout creates a pending order with the same line item.
	 */
	public function test_checkout_creates_pending_order_from_cart(): void {
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

		// Run checkout against the same cart, no payment_method (POS leaves the order
		// in pending and hands off to the existing payment flow).
		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_request->set_body_params(
			array(
				'payment_method'   => PosCheckoutTestGateway::GATEWAY_ID,
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

		// And there should be a real order in the database with our line item.
		$order = wc_get_order( $data['order_id'] );
		$this->assertNotFalse( $order, 'Order should exist in the database.' );
		$this->assertSame( 'pending', $order->get_status() );
		$items = $order->get_items();
		$this->assertCount( 1, $items );
		$first_item = reset( $items );
		$this->assertSame( $this->product->get_id(), $first_item->get_product_id() );
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

/**
 * No-op payment gateway used to satisfy the Store API's
 * payment_method validation during checkout integration tests.
 *
 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
 * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
 */
class PosCheckoutTestGateway extends \WC_Payment_Gateway {

	public const GATEWAY_ID = 'pos_test_gateway';

	public function __construct() {
		$this->id           = self::GATEWAY_ID;
		$this->method_title = 'POS Test Gateway';
		$this->enabled      = 'yes';
		$this->title        = 'POS Test Gateway';
		$this->supports     = array( 'products' );
	}

	public function is_available() {
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		// Intentionally leave the order in 'pending' so tests can assert on that state.
		return array(
			'result'   => 'success',
			'redirect' => $order ? $order->get_checkout_order_received_url() : '',
		);
	}
}
// phpcs:enable
