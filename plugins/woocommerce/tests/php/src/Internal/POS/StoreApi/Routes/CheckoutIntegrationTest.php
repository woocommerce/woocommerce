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
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutEmailPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\DefaultPaymentMethodPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy;
use Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller;
use WC_Customer;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes\ControllerTestCase;
use WC_Product_Simple;

/**
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\Routes\Controller
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutPaymentMethodPolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutAddressPolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CheckoutEmailPolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CurrentUserSwap
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\CustomerSwap
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\DefaultPaymentMethodPolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\ShippingPolicy
 * @covers \Automattic\WooCommerce\Internal\POS\StoreApi\PolicyHooks\TaxLocationPolicy
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
	 * Original WC()->customer captured in setUp so it can be restored.
	 *
	 * @var WC_Customer|null
	 */
	private $original_customer;

	/**
	 * Original current_user_id captured in setUp so it can be restored.
	 *
	 * CurrentUserSwap mutates the global user mid-test; without restoring
	 * it in tearDown, a stray user id would leak into subsequent tests.
	 *
	 * @var int
	 */
	private $original_user_id;

	/**
	 * Setup.
	 */
	protected function setUp(): void {
		parent::setUp();

		Context::set_test_override( true );

		$this->original_customer = WC()->customer;
		$this->original_user_id  = get_current_user_id();

		// Engage the POS opt-outs so the Store API guards are relaxed for these
		// requests. In production these are registered in class-woocommerce.php;
		// in tests we register them explicitly so the test is self-contained
		// and exercises the production wiring path end-to-end.
		( new CheckoutPaymentMethodPolicy() )->register();
		( new CheckoutAddressPolicy() )->register();
		( new CheckoutEmailPolicy() )->register();
		( new DefaultPaymentMethodPolicy() )->register();
		( new ShippingPolicy() )->register();
		( new TaxLocationPolicy() )->register();
		( new CurrentUserSwap() )->register();
		( new CustomerSwap() )->register();

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
		remove_all_filters( 'woocommerce_store_api_validate_addresses' );
		remove_all_filters( 'woocommerce_store_api_require_billing_email' );
		remove_all_filters( 'woocommerce_store_api_order_default_payment_method' );
		remove_all_filters( 'woocommerce_cart_needs_shipping' );
		remove_all_filters( 'woocommerce_customer_taxable_address' );
		remove_all_filters( 'woocommerce_pos_tax_location' );
		remove_all_filters( 'rest_dispatch_request' );
		Context::set_test_override( null );
		WC()->customer = $this->original_customer;
		wp_set_current_user( $this->original_user_id );
		wp_delete_user( $this->admin_id );
		parent::tearDown();
	}

	/**
	 * @testdox The /checkout route is registered under wc/internal/pos/v1.
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
	 * @testdox billing_address and shipping_address are not schema-required on the POS checkout route.
	 */
	public function test_address_fields_are_optional_at_schema_level(): void {
		$routes  = rest_get_server()->get_routes();
		$handlers = $routes[ '/' . Controller::REST_NAMESPACE . '/checkout' ] ?? array();

		$post_endpoint = null;
		foreach ( $handlers as $endpoint ) {
			if ( in_array( 'POST', (array) ( $endpoint['methods'] ?? array() ), true )
				|| ( is_array( $endpoint['methods'] ?? null ) && isset( $endpoint['methods']['POST'] ) ) ) {
				$post_endpoint = $endpoint;
				break;
			}
		}

		$this->assertNotNull( $post_endpoint, 'POS checkout route should define a POST endpoint.' );
		$this->assertFalse(
			$post_endpoint['args']['billing_address']['required'] ?? null,
			'billing_address should not be schema-required for POS checkout.'
		);
		$this->assertFalse(
			$post_endpoint['args']['shipping_address']['required'] ?? null,
			'shipping_address should not be schema-required for POS checkout.'
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
	 * @testdox Add-item then checkout with a truly empty body creates a pending order with the same line item.
	 */
	public function test_checkout_with_empty_body_creates_pending_order_from_cart(): void {
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

		// CurrentUserSwap in the prior dispatch set current_user to 0 (the entire
		// point of Nadir's model). In production the next HTTP request would
		// re-authenticate the cashier from scratch; here we mirror that by
		// re-asserting the authenticated user before dispatching again.
		wp_set_current_user( $this->admin_id );

		// Run checkout against the same cart with a truly empty body — no
		// payment_method, no billing_address, no shipping_address. POS legitimately
		// defers all of these past order creation:
		//   - payment_method: the existing post-checkout flow records it
		//     (WooPayments terminal capture for cards, cash mark-paid endpoint).
		//   - addresses: in-store retail (cash sale of physical goods) often has
		//     no customer address to capture; the cashier can edit the order
		//     later via admin if needed for products that genuinely need one.
		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
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

		/*
		 * Source-level guarantees (asserted here so the wiring is exercised
		 * end to end through the real Store API checkout pipeline, not just
		 * the per-callback unit tests):
		 *
		 *   - CustomerIdPolicy gates `woocommerce_store_api_order_customer_id`
		 *     in OrderController::update_order_from_cart to 0 for POS, so
		 *     the cashier's WP user is never attributed.
		 *   - DefaultPaymentMethodPolicy gates
		 *     `woocommerce_store_api_order_default_payment_method` to '' so
		 *     no gateway is stamped before the cashier picks tender.
		 *   - CustomerSwap replaces WC()->customer with a guest WC_Customer
		 *     at rest_pre_dispatch, so update_addresses_from_cart reads
		 *     empties from session instead of the cashier's saved profile.
		 */
		$this->assertSame( 0, $order->get_customer_id(), 'POS orders must not be attributed to the cashier.' );
		$this->assertSame( '', $order->get_billing_first_name() );
		$this->assertSame( '', $order->get_billing_email() );
		$this->assertSame( '', $order->get_shipping_first_name() );
	}

	/**
	 * End-to-end regression for the four web-checkout assumptions that the
	 * Store API's update_order_from_cart bakes in:
	 *
	 *   - customer_id = get_current_user_id() (cashier leaks as customer)
	 *   - billing/shipping copied from wc()->customer (cashier profile leaks)
	 *   - payment_method = default gateway (woocommerce_payments stamped)
	 *   - wc()->cart->needs_shipping() triggers a shipping line
	 *
	 * Each is addressed at its source by a dedicated policy class. This test
	 * seeds the cashier's WP user profile with a recognisable billing address,
	 * uses a shipping-needing physical product, and asserts the resulting
	 * order shows none of the leaks — proving the fix works end-to-end through
	 * the real checkout pipeline rather than only at the per-callback level.
	 *
	 * @testdox A POS checkout with a shipping-needing product strips admin attribution, addresses, default payment_method and shipping at the source.
	 */
	public function test_checkout_does_not_leak_admin_or_shipping_at_source(): void {
		wp_set_current_user( $this->admin_id );

		update_user_meta( $this->admin_id, 'billing_first_name', 'CashierFirstNameLeak' );
		update_user_meta( $this->admin_id, 'billing_address_1', '999 Admin Lane' );
		update_user_meta( $this->admin_id, 'billing_city', 'AdminCity' );
		update_user_meta( $this->admin_id, 'billing_email', 'admin-leak@example.com' );
		update_user_meta( $this->admin_id, 'shipping_first_name', 'CashierShipLeak' );
		update_user_meta( $this->admin_id, 'shipping_address_1', '999 Admin Lane' );

		$shipping_needing_product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS Physical Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 50,
				'virtual'       => false,
				'weight'        => 1,
			)
		);

		$add_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$add_request->set_body_params(
			array(
				'id'       => $shipping_needing_product->get_id(),
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
		$this->assertNotEmpty( $cart_token );

		// Prior dispatch's CurrentUserSwap set current_user to 0; re-auth
		// the cashier so the next request's permission check sees them.
		wp_set_current_user( $this->admin_id );

		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_response = rest_get_server()->dispatch( $checkout_request );

		$this->assertSame(
			200,
			$checkout_response->get_status(),
			'Checkout failed: ' . wp_json_encode( $checkout_response->get_data() )
		);

		$order = wc_get_order( $checkout_response->get_data()['order_id'] );

		$this->assertSame( 0, $order->get_customer_id() );
		$this->assertSame( '', $order->get_payment_method() );
		$this->assertSame( '', $order->get_billing_first_name(), 'Cashier profile must not leak onto billing.' );
		$this->assertSame( '', $order->get_billing_address_1() );
		$this->assertSame( '', $order->get_billing_email() );
		$this->assertSame( '', $order->get_shipping_first_name(), 'Cashier profile must not leak onto shipping.' );
		$this->assertSame( '', $order->get_shipping_address_1() );
		$this->assertCount( 0, $order->get_items( 'shipping' ), 'In-person POS sales must not carry a shipping line.' );
		$this->assertSame( 0.0, (float) $order->get_shipping_total() );
	}

	/**
	 * Tax must compute to the store's location for POS even though the order
	 * itself carries no billing/shipping address. Regression for:
	 * "totals are broken - it doesn't calculate taxes at all" caused by the
	 * Store API's tax pipeline reading from wc()->customer (which we
	 * replace with a guest) and getting an empty taxable address back, so
	 * no rate matched.
	 *
	 * TaxLocationPolicy hooks woocommerce_customer_taxable_address for POS
	 * to return store base address regardless of customer state, restoring
	 * the in-person-retail rule that tax follows the register's location.
	 *
	 * @testdox POS checkout applies the store's tax rate even when the order has no address.
	 */
	public function test_checkout_applies_store_location_tax_when_order_address_is_blank(): void {
		wp_set_current_user( $this->admin_id );

		$store_country  = 'US';
		$store_state    = 'CA';
		$store_postcode = '94103';
		$store_city     = 'San Francisco';

		$original_options = array(
			'woocommerce_default_country'    => get_option( 'woocommerce_default_country' ),
			'woocommerce_store_postcode'     => get_option( 'woocommerce_store_postcode' ),
			'woocommerce_store_city'         => get_option( 'woocommerce_store_city' ),
			'woocommerce_calc_taxes'         => get_option( 'woocommerce_calc_taxes' ),
			'woocommerce_tax_based_on'       => get_option( 'woocommerce_tax_based_on' ),
			'woocommerce_prices_include_tax' => get_option( 'woocommerce_prices_include_tax' ),
		);

		update_option( 'woocommerce_default_country', "{$store_country}:{$store_state}" );
		update_option( 'woocommerce_store_postcode', $store_postcode );
		update_option( 'woocommerce_store_city', $store_city );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		// "billing" specifically — the failure mode being guarded against is
		// the Store API reading the (empty for POS) customer billing address.
		update_option( 'woocommerce_tax_based_on', 'billing' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$tax_rate_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => $store_country,
				'tax_rate_state'    => $store_state,
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'POS Test Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 0,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);

		try {
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
			$this->assertNotEmpty( $cart_token );

			// Prior dispatch swapped current_user to 0; re-auth so the
			// next request's permission check sees the cashier.
			wp_set_current_user( $this->admin_id );

			$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
			$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
			$checkout_response = rest_get_server()->dispatch( $checkout_request );

			$this->assertSame(
				200,
				$checkout_response->get_status(),
				'Checkout failed: ' . wp_json_encode( $checkout_response->get_data() )
			);

			$order = wc_get_order( $checkout_response->get_data()['order_id'] );

			$this->assertSame( '', $order->get_billing_country(), 'POS order should still carry no billing address.' );
			$this->assertGreaterThan(
				0.0,
				(float) $order->get_total_tax(),
				'POS order should carry tax computed from the store location.'
			);
			// Product is $25, tax rate is 10% — expect exactly $2.50 of tax.
			$this->assertEqualsWithDelta( 2.50, (float) $order->get_total_tax(), 0.01 );
		} finally {
			\WC_Tax::_delete_tax_rate( $tax_rate_id );
			foreach ( $original_options as $opt => $value ) {
				if ( false === $value ) {
					delete_option( $opt );
				} else {
					update_option( $opt, $value );
				}
			}
		}
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

		// Prior dispatch swapped current_user to 0; re-auth so the
		// next request's permission check sees the cashier.
		wp_set_current_user( $this->admin_id );

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

	/**
	 * End-to-end regression for downloadable products on POS. PosCheckoutRequirements
	 * marks downloadables as email-requiring; this verifies the supplied email
	 * actually flows through CustomerSwap onto the order, and that the standard WC
	 * download-permission grant fires once the order transitions to processing.
	 *
	 * @testdox A downloadable in the cart with an email supplied creates a pending order carrying the email and grants download permissions on payment_complete.
	 */
	public function test_checkout_downloadable_product_with_email_seeds_download_permissions(): void {
		wp_set_current_user( $this->admin_id );

		$download = new \WC_Product_Download();
		$download->set_id( wp_generate_uuid4() );
		$download->set_name( 'POS Test Download' );
		$download->set_file( 'https://example.com/pos-test-download.zip' );

		$downloadable_product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS Downloadable Test Product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 15,
				'virtual'       => true,
				'downloadable'  => true,
				'downloads'     => array( $download ),
			)
		);

		$add_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$add_request->set_body_params(
			array(
				'id'       => $downloadable_product->get_id(),
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
		$this->assertNotEmpty( $cart_token );

		// Prior dispatch's CurrentUserSwap set current_user to 0; re-auth.
		wp_set_current_user( $this->admin_id );

		$customer_email = 'pos-downloadable-customer@example.com';

		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_request->set_body_params(
			array(
				'billing_address' => array( 'email' => $customer_email ),
			)
		);
		$checkout_response = rest_get_server()->dispatch( $checkout_request );

		$this->assertSame(
			200,
			$checkout_response->get_status(),
			'Checkout failed: ' . wp_json_encode( $checkout_response->get_data() )
		);

		$order = wc_get_order( $checkout_response->get_data()['order_id'] );
		$this->assertNotFalse( $order );
		$this->assertSame(
			$customer_email,
			$order->get_billing_email(),
			'The customer email supplied on the request must land on the order so the download-link email can be delivered.'
		);
		$this->assertFalse(
			(bool) $order->get_data_store()->get_download_permissions_granted( $order ),
			'Download permissions should not be granted yet — the order is still pending.'
		);

		// Simulate the payment-complete that the existing post-checkout flow
		// triggers (cash mark-paid or WooPayments terminal capture). For pure
		// downloadables WC auto-completes; here we just transition explicitly
		// so the test exercises the WC core hook chain
		// (wc_downloadable_product_permissions on order_status_processing).
		$order->update_status( 'processing' );

		$this->assertTrue(
			(bool) $order->get_data_store()->get_download_permissions_granted( $order ),
			'Standard WC download-permission grant must fire once the POS order transitions to processing.'
		);

		$downloads_for_order = $order->get_downloadable_items();
		$this->assertCount(
			1,
			$downloads_for_order,
			'The order should expose exactly one downloadable item linkable from the customer email.'
		);
	}

	/**
	 * The cart-aware email rule short-circuits to "required" when a
	 * downloadable is in the cart. Without an email, /checkout must surface
	 * the standard Store API error so the mobile client can prompt for one
	 * and retry — mirroring the web flow.
	 *
	 * @testdox A downloadable in the cart with no email returns the standard missing-email 400.
	 */
	public function test_checkout_downloadable_without_email_returns_missing_email_error(): void {
		wp_set_current_user( $this->admin_id );

		$download = new \WC_Product_Download();
		$download->set_id( wp_generate_uuid4() );
		$download->set_name( 'POS Test Download' );
		$download->set_file( 'https://example.com/pos-test-download.zip' );

		$downloadable_product = ( new FixtureData() )->get_simple_product(
			array(
				'name'          => 'POS Downloadable Test Product (no-email regression)',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 15,
				'virtual'       => true,
				'downloadable'  => true,
				'downloads'     => array( $download ),
			)
		);

		$add_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/cart/add-item' );
		$add_request->set_body_params(
			array(
				'id'       => $downloadable_product->get_id(),
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
		$this->assertNotEmpty( $cart_token );

		wp_set_current_user( $this->admin_id );

		$checkout_request = new \WP_REST_Request( 'POST', '/' . Controller::REST_NAMESPACE . '/checkout' );
		$checkout_request->set_query_params( array( 'cart_token' => $cart_token ) );
		$checkout_response = rest_get_server()->dispatch( $checkout_request );

		$this->assertSame( 400, $checkout_response->get_status() );
		$this->assertSame(
			'woocommerce_rest_missing_email_address',
			$checkout_response->get_data()['code'] ?? null,
			'A downloadable in the cart must surface the standard Store API missing-email error when no email was supplied.'
		);
	}
}
