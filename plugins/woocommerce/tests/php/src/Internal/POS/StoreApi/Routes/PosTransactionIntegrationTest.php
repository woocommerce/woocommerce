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
	 * @testdox Checkout attaches an identified customer account via customer_id — still never the operator.
	 */
	public function test_checkout_attaches_identified_customer(): void {
		wp_set_current_user( $this->operator_id );
		$customer_id = $this->factory()->user->create( array( 'role' => 'customer' ) );

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
	 * @param array $items Items payload.
	 * @return \WP_REST_Response
	 */
	private function add_items( array $items ): \WP_REST_Response {
		return $this->dispatch_post( '/cart/add-items', array( 'items' => $items ) );
	}

	/**
	 * Dispatch a POST request to a POS route.
	 *
	 * @param string $path Route path below the POS namespace.
	 * @param array  $body Body params.
	 * @return \WP_REST_Response
	 */
	private function dispatch_post( string $path, array $body ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . $path );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}
}
