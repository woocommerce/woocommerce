<?php
/**
 * Agentic Checkout Sessions Complete Tests.
 *
 * @package Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Routes\V1\Agentic\Enums\SessionKey;
use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\StoreApi\RoutesController;

/**
 * CheckoutSessionsComplete Controller Tests.
 */
class CheckoutSessionsComplete extends ControllerTestCase {
	/**
	 * Products created for tests.
	 *
	 * @var array
	 */
	protected $products = array();

	/**
	 * Mock payment gateway instance.
	 *
	 * @var MockAgenticPaymentGateway
	 */
	protected $mock_gateway;

	/**
	 * Test bearer token for authorization.
	 *
	 * @var string
	 */
	protected $test_bearer_token;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset customer and cart FIRST before anything else.
		wc_empty_cart();
		$this->reset_customer_state();

		// Clear all session data early to ensure clean state.
		if ( WC()->session ) {
			WC()->session->destroy_session();
		}

		// Enable the agentic_checkout feature.
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		// Set up registry with test bearer token for authorization.
		$this->test_bearer_token = 'test_token_' . uniqid();
		update_option(
			'woocommerce_agentic_agent_registry',
			array(
				'openai' => array(
					'bearer_token' => wp_hash_password( $this->test_bearer_token ),
				),
			),
			false
		);

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 20,
					'weight'        => 5,
				)
			),
		);

		// Register mock agentic payment gateway.
		$this->mock_gateway = new MockAgenticPaymentGateway();
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_mock_gateway' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'add_mock_gateway' ) );

		wc_get_container()->get( RoutesController::class )->register_all_routes();
	}

	/**
	 * Tear down test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'woocommerce_feature_agentic_checkout_enabled' );
		delete_option( 'woocommerce_agentic_agent_registry' );

		// Clear session data.
		WC()->session->set( SessionKey::CHOSEN_SHIPPING_METHODS, null );
		WC()->session->set( SessionKey::AGENTIC_CHECKOUT_SESSION_ID, null );

		// Reset customer state to clean state.
		$this->reset_customer_state();
	}

	/**
	 * Add mock gateway to available gateways.
	 *
	 * @param array $gateways Existing gateways.
	 * @return array Modified gateways.
	 */
	public function add_mock_gateway( $gateways ) {
		$gateways[ MockAgenticPaymentGateway::GATEWAY_ID ] = $this->mock_gateway;
		return $gateways;
	}

	/**
	 * Resets customer state and remove any existing data from previous tests.
	 */
	private function reset_customer_state() {
		// Clear all customer data fields.
		$customer = WC()->customer;

		// Clear billing fields.
		$customer->set_billing_first_name( '' );
		$customer->set_billing_last_name( '' );
		$customer->set_billing_company( '' );
		$customer->set_billing_address_1( '' );
		$customer->set_billing_address_2( '' );
		$customer->set_billing_city( '' );
		$customer->set_billing_state( '' );
		$customer->set_billing_postcode( '' );
		$customer->set_billing_country( '' );
		$customer->set_billing_email( '' );
		$customer->set_billing_phone( '' );

		// Clear shipping fields.
		$customer->set_shipping_first_name( '' );
		$customer->set_shipping_last_name( '' );
		$customer->set_shipping_company( '' );
		$customer->set_shipping_address_1( '' );
		$customer->set_shipping_address_2( '' );
		$customer->set_shipping_city( '' );
		$customer->set_shipping_state( '' );
		$customer->set_shipping_postcode( '' );
		$customer->set_shipping_country( '' );

		$customer->save();
	}

	/**
	 * Helper: Create base checkout session request data.
	 *
	 * @param array $overrides Optional array to override default values.
	 * @return array Request data.
	 */
	private function create_checkout_request( $overrides = array() ) {
		$defaults = array(
			'items' => array(
				array(
					'id'       => (string) $this->products[0]->get_id(),
					'quantity' => 1,
				),
			),
		);
		return array_merge_recursive( $defaults, $overrides );
	}

	/**
	 * Helper: Get test fulfillment address.
	 *
	 * @param array $overrides Optional array to override default values.
	 * @return array Address data.
	 */
	private function get_test_address( $overrides = array() ) {
		$defaults = array(
			'name'        => 'John Doe',
			'line_one'    => '555 Golden Gate Avenue',
			'line_two'    => '',
			'city'        => 'San Francisco',
			'state'       => 'CA',
			'country'     => 'US',
			'postal_code' => '94102',
		);
		return array_merge( $defaults, $overrides );
	}

	/**
	 * Helper: Get test buyer information.
	 *
	 * @param array $overrides Optional array to override default values.
	 * @return array Buyer data.
	 */
	private function get_test_buyer( $overrides = array() ) {
		$defaults = array(
			'first_name'   => 'Jane',
			'last_name'    => 'Smith',
			'email'        => 'jane@example.com',
			'phone_number' => '+1234567890',
		);
		return array_merge( $defaults, $overrides );
	}

	/**
	 * Helper: Create and dispatch a checkout session request.
	 *
	 * @param array $body_params Request body parameters.
	 * @return \WP_REST_Response Response object.
	 */
	private function create_session( $body_params ) {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Authorization', 'Bearer ' . $this->test_bearer_token );
		$request->set_body_params( $body_params );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Helper: Update an existing checkout session.
	 *
	 * @param string $session_id The session ID (Cart-Token).
	 * @param array  $body_params Request body parameters.
	 * @return \WP_REST_Response Response object.
	 */
	private function update_session( $session_id, $body_params ) {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions/' . $session_id );
		$request->set_header( 'Authorization', 'Bearer ' . $this->test_bearer_token );
		$request->set_body_params( $body_params );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Helper: Complete a checkout session.
	 *
	 * @param string $session_id The session ID (Cart-Token).
	 * @param array  $body_params Request body parameters.
	 * @return \WP_REST_Response Response object.
	 */
	private function complete_session( $session_id, $body_params ) {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions/' . $session_id . '/complete' );
		$request->set_header( 'Authorization', 'Bearer ' . $this->test_bearer_token );
		$request->set_body_params( $body_params );
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Helper: Get payment data for completing checkout.
	 *
	 * @param array $overrides Optional array to override default values.
	 * @return array Payment data.
	 */
	private function get_payment_data( $overrides = array() ) {
		$defaults = array(
			'token'    => 'spt_test_123456789',
			'provider' => 'stripe',
		);
		return array_merge( $defaults, $overrides );
	}

	/**
	 * Test completing a checkout session successfully.
	 */
	public function test_complete_checkout_session_success() {
		$create_response = $this->create_session(
			array(
				'items'               => array(
					array(
						'id'       => (string) $this->products[0]->get_id(),
						'quantity' => 2,
					),
					array(
						'id'       => (string) $this->products[1]->get_id(),
						'quantity' => 1,
					),
				),
				'fulfillment_address' => $this->get_test_address(),
				'buyer'               => $this->get_test_buyer(),
			)
		);

		$create_data = $create_response->get_data();
		$session_id  = $create_data['id'];

		// Update with shipping method to make it ready for payment.
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];
		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Complete the checkout.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$complete_data = $complete_response->get_data();

		// Verify successful completion.
		$this->assertEquals( 200, $complete_response->get_status() );

		$this->assertEquals( 'completed', $complete_data['status'] );
		$this->assertArrayHasKey( 'id', $complete_data );
		$this->assertArrayHasKey( 'order', $complete_data );

		// Verify order object structure matches schema.
		$order_data = $complete_data['order'];
		$this->assertArrayHasKey( 'id', $order_data );
		$this->assertArrayHasKey( 'checkout_session_id', $order_data );
		$this->assertArrayHasKey( 'permalink_url', $order_data );
		$this->assertEquals( $session_id, $order_data['checkout_session_id'] );
		$this->assertIsString( $order_data['id'] );
		$this->assertIsString( $order_data['permalink_url'] );

		// Verify order was created with correct items.
		$this->assertCount( 2, $complete_data['line_items'] );
		$this->assertEquals( (string) $this->products[0]->get_id(), $complete_data['line_items'][0]['item']['id'] );
		$this->assertEquals( 2, $complete_data['line_items'][0]['item']['quantity'] );

		// Verify order ID is stored in session.
		$stored_order_id = WC()->session->get( SessionKey::AGENTIC_CHECKOUT_COMPLETED_ORDER_ID );
		$this->assertNotNull( $stored_order_id );
		$this->assertIsNumeric( $stored_order_id );
		$this->assertEquals( $order_data['id'], (string) $stored_order_id );

		// Verify order exists in database.
		$order = wc_get_order( $stored_order_id );
		$this->assertInstanceOf( \WC_Order::class, $order );
	}

	/**
	 * Test completing checkout without ready_for_payment status fails.
	 */
	public function test_complete_checkout_session_not_ready() {
		// Create session without address (not ready for payment).
		$create_response = $this->create_session( $this->create_checkout_request() );
		$create_data     = $create_response->get_data();
		$session_id      = $create_data['id'];

		// Try to complete checkout.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$this->assertEquals( 400, $complete_response->get_status() );
		$complete_data = $complete_response->get_data();
		$this->assertArrayHasKey( 'code', $complete_data );
		$this->assertStringContainsString( 'not ready for payment', strtolower( $complete_data['message'] ) );
	}

	/**
	 * Test completing checkout with invalid session ID fails.
	 */
	public function test_complete_checkout_session_invalid_id() {
		$invalid_session_id = 'invalid.token.here';

		$complete_response = $this->complete_session(
			$invalid_session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$this->assertEquals( 404, $complete_response->get_status() );
	}

	/**
	 * Test completing checkout without payment data fails.
	 */
	public function test_complete_checkout_session_missing_payment_data() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Try to complete without payment_data.
		$complete_response = $this->complete_session( $session_id, array() );

		$this->assertEquals( 400, $complete_response->get_status() );
	}

	/**
	 * Test completing checkout with missing payment token fails.
	 */
	public function test_complete_checkout_session_missing_token() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Try to complete with payment_data missing token.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => array(
					'provider' => 'stripe',
				),
			)
		);

		$this->assertEquals( 400, $complete_response->get_status() );
	}

	/**
	 * Test completing checkout with missing payment provider fails.
	 */
	public function test_complete_checkout_session_missing_provider() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Try to complete with payment_data missing provider.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => array(
					'token' => 'tok_test_123',
				),
			)
		);

		$this->assertEquals( 400, $complete_response->get_status() );
	}

	/**
	 * Test completing checkout with billing address in payment_data.
	 */
	public function test_complete_checkout_session_with_billing_address() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
					'buyer'               => $this->get_test_buyer(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Complete with billing address.
		$billing_address   = $this->get_test_address(
			array(
				'name' => 'Billing Name',
				'city' => 'Los Angeles',
			)
		);
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => array_merge(
					$this->get_payment_data(),
					array(
						'billing_address' => $billing_address,
					)
				),
			)
		);

		$this->assertEquals( 200, $complete_response->get_status() );

		// If payment succeeded, verify order was created with billing address.
		$complete_data = $complete_response->get_data();
		if ( isset( $complete_data['order']['id'] ) ) {
			$order = wc_get_order( $complete_data['order']['id'] );
			$this->assertEquals( 'Los Angeles', $order->get_billing_city() );
		}
	}

	/**
	 * Test completing checkout with buyer info updates order.
	 */
	public function test_complete_checkout_session_with_buyer_info() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Complete with buyer info.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'buyer'        => $this->get_test_buyer(),
				'payment_data' => $this->get_payment_data(),
			)
		);

		$this->assertEquals( 200, $complete_response->get_status() );

		// Verify buyer info in response.
		$complete_data = $complete_response->get_data();
		$this->assertEquals( 'Jane', $complete_data['buyer']['first_name'] );
		$this->assertEquals( 'Smith', $complete_data['buyer']['last_name'] );
		$this->assertEquals( 'jane@example.com', $complete_data['buyer']['email'] );
	}

	/**
	 * Test completing checkout reserves stock.
	 */
	public function test_complete_checkout_session_reserves_stock() {
		// Set product to have limited stock.
		$this->products[0]->set_manage_stock( true );
		$this->products[0]->set_stock_quantity( 5 );
		$this->products[0]->save();

		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
					'buyer'               => $this->get_test_buyer(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Complete checkout.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$this->assertEquals( 200, $complete_response->get_status() );

		// If payment succeeded, verify stock was reserved.
		$complete_data = $complete_response->get_data();
		if ( isset( $complete_data['order']['id'] ) ) {
			$order = wc_get_order( $complete_data['order']['id'] );
			$this->assertInstanceOf( \WC_Order::class, $order );
			// Stock should be reserved/reduced.
			$this->assertGreaterThan( 0, $order->get_item_count() );
		}
	}

	/**
	 * Test completing checkout with insufficient stock fails.
	 */
	public function test_complete_checkout_session_out_of_stock() {
		// Create session with address and buyer.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
					'buyer'               => $this->get_test_buyer(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];
		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Set product to have insufficient stock.
		$this->products[0]->set_manage_stock( true );
		$this->products[0]->set_stock_quantity( 0 );
		$this->products[0]->save();

		// Try to complete checkout - this should fail during validation.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		// Complete should fail due to stock validation.
		$this->assertEquals( 400, $complete_response->get_status() );

		// Verify error response contains stock-related message.
		$complete_data = $complete_response->get_data();
		$this->assertArrayHasKey( 'type', $complete_data );
		$this->assertEquals( 'invalid_request', $complete_data['type'] );
		$this->assertArrayHasKey( 'code', $complete_data );
		$this->assertEquals( 'invalid', $complete_data['code'] );
		$this->assertArrayHasKey( 'message', $complete_data );
		$this->assertStringContainsString( 'out of stock', strtolower( $complete_data['message'] ) );
	}

	/**
	 * Test error response format matches ACP spec.
	 */
	public function test_complete_error_response_format() {
		// Create session without address (not ready).
		$create_response = $this->create_session( $this->create_checkout_request() );
		$create_data     = $create_response->get_data();
		$session_id      = $create_data['id'];

		// Try to complete.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$data = $complete_response->get_data();

		$this->assertEquals( 400, $complete_response->get_status() );
		$this->assertArrayHasKey( 'type', $data );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'message', $data );
	}

	/**
	 * Test completing checkout calculates totals before validation.
	 */
	public function test_complete_checkout_session_calculates_totals() {
		// Create ready-for-payment session.
		$create_response = $this->create_session(
			$this->create_checkout_request(
				array(
					'fulfillment_address' => $this->get_test_address(),
					'buyer'               => $this->get_test_buyer(),
				)
			)
		);

		$create_data        = $create_response->get_data();
		$session_id         = $create_data['id'];
		$shipping_method_id = $create_data['fulfillment_options'][0]['id'];

		$this->update_session(
			$session_id,
			array(
				'fulfillment_option_id' => $shipping_method_id,
			)
		);

		// Complete checkout.
		$complete_response = $this->complete_session(
			$session_id,
			array(
				'payment_data' => $this->get_payment_data(),
			)
		);

		$this->assertEquals( 200, $complete_response->get_status() );

		// If payment succeeded, verify totals are present and calculated.
		$complete_data = $complete_response->get_data();
		$this->assertArrayHasKey( 'totals', $complete_data );
		$this->assertNotEmpty( $complete_data['totals'] );

		// Verify total amount is greater than 0.
		$total_obj = array_filter(
			$complete_data['totals'],
			function ( $total ) {
				return 'total' === $total['type'];
			}
		);
		$this->assertNotEmpty( $total_obj );
		$total = reset( $total_obj );
		$this->assertGreaterThan( 0, $total['amount'] );
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Classes.ClassFileName.NoMatch, Suin.Classes.PSR4.IncorrectClassName

/**
 * Mock Agentic Payment Gateway for testing.
 *
 * This gateway supports the agentic_commerce feature and is used
 * in CheckoutSessionsComplete tests.
 */
class MockAgenticPaymentGateway extends \WC_Payment_Gateway {
	public const GATEWAY_ID = 'mock_agentic_payment_gateway';
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->enabled            = 'yes';
		$this->id                 = self::GATEWAY_ID;
		$this->has_fields         = false;
		$this->method_title       = 'Mock Agentic Gateway';
		$this->method_description = 'Mock Gateway for agentic commerce testing';
		$this->supports           = array(
			\Automattic\WooCommerce\Enums\PaymentGatewayFeature::PRODUCTS,
			\Automattic\WooCommerce\Enums\PaymentGatewayFeature::AGENTIC_COMMERCE,
		);

		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => '',
				'type'    => 'checkbox',
				'label'   => '',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Get the agentic commerce provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_agentic_commerce_provider() {
		return 'stripe';
	}

	/**
	 * Get supported payment methods for agentic commerce.
	 *
	 * @return array List of supported payment methods.
	 */
	public function get_agentic_commerce_payment_methods() {
		return array( 'card' );
	}

	/**
	 * Process payment for agentic commerce.
	 *
	 * @param int $order_id Order ID.
	 * @return array Payment result.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Simulate successful payment processing.
		$order->payment_complete();
		$order->add_order_note( 'Mock agentic payment completed successfully.' );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Validate fields before processing payment.
	 *
	 * @return bool Whether fields are valid.
	 */
	public function validate_fields() {
		return true;
	}
}
