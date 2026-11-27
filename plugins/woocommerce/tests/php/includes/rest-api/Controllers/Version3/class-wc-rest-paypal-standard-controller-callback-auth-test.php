<?php
declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

// phpcs:disable Squiz.Classes.ClassFileName.NoMatch, SlevomatCodingStandard.Files.TypeNameMatchesFileName.NoMatchBetweenTypeNameAndFileName, Squiz.Classes.ValidClassName.NotCamelCaps -- Match legacy controller test naming.

/**
 * Tests for PayPal Standard shipping callback authorization.
 */
class WC_REST_Paypal_Standard_Controller_Callback_Auth_Test extends WC_REST_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var WC_REST_Paypal_Standard_Controller
	 */
	private $sut;

	/**
	 * Customer replaced by the shipping callback.
	 *
	 * @var WC_Customer|null
	 */
	private $original_customer;

	/**
	 * Session replaced by wc_load_cart() under the inherited mock handler filter.
	 *
	 * @var WC_Session|null
	 */
	private $original_session;

	/**
	 * Set up the controller and an anonymous checkout session.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut               = new WC_REST_Paypal_Standard_Controller();
		$this->original_customer = WC()->customer;
		$this->original_session  = WC()->session;
		wp_set_current_user( 0 );
		wc_load_cart();
		add_filter(
			'pre_http_request',
			static function () {
				return new WP_Error( 'network_disabled' );
			}
		);
	}

	/**
	 * Restore the customer and session singletons after the inherited cleanup.
	 */
	public function tearDown(): void {
		try {
			parent::tearDown();
		} finally {
			WC()->customer = $this->original_customer;
			WC()->session  = $this->original_session;
		}
	}

	/**
	 * @testdox Invalid callback credentials are rejected without changing order or session data.
	 * @testWith [null, null]
	 *           [null, "callback-token"]
	 *           ["", "callback-token"]
	 *           ["callback-token", null]
	 *           ["callback-token", ""]
	 *           ["callback-token", "wrong-token"]
	 *           ["callback-token", ["callback-token"]]
	 *           ["callback-token", 123]
	 *           ["callback-token", true]
	 *           [["callback-token"], "callback-token"]
	 *
	 * @param mixed $stored_token Stored credential, or null for missing metadata.
	 * @param mixed $provided_token Request credential, or null for a missing parameter.
	 */
	public function test_invalid_callback_credentials( $stored_token, $provided_token ): void {
		$order = $this->create_callback_order();
		if ( null !== $stored_token ) {
			$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, $stored_token );
			$order->save();
		}
		$request = $this->create_callback_request( $order );
		if ( null !== $provided_token ) {
			$request->set_param( 'token', $provided_token );
		}
		$order_data   = wc_get_order( $order->get_id() )->get_data();
		$session_data = clone WC()->session;

		$response = $this->server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
		$this->assertEquals( $order_data, wc_get_order( $order->get_id() )->get_data() );
		$this->assertEquals( $session_data, WC()->session );
	}

	/**
	 * @testdox A valid token cannot bypass the order key, provider ID, status or transaction checks.
	 * @testWith ["order_key", 401]
	 *           ["provider_id", 422]
	 *           ["status", 422]
	 *           ["transaction_id", 422]
	 *
	 * @param string $invalid_field Field to invalidate.
	 * @param int    $expected_status Expected response status.
	 */
	public function test_existing_callback_checks( string $invalid_field, int $expected_status ): void {
		$order = $this->create_callback_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'callback-token' );
		if ( 'status' === $invalid_field ) {
			$order->set_status( OrderStatus::COMPLETED );
		} elseif ( 'transaction_id' === $invalid_field ) {
			$order->set_transaction_id( 'test-transaction' );
		}
		$order->save();
		$request = $this->create_callback_request( $order );
		$request->set_param( 'token', 'callback-token' );
		if ( 'order_key' === $invalid_field ) {
			$request->set_param(
				'purchase_units',
				array(
					array(
						'custom_id' => wp_json_encode(
							array(
								'order_id'  => $order->get_id(),
								'order_key' => 'wrong-key',
							)
						),
					),
				)
			);
		} elseif ( 'provider_id' === $invalid_field ) {
			$request->set_param( 'id', 'another-provider-order' );
		}
		$order_data   = wc_get_order( $order->get_id() )->get_data();
		$session_data = clone WC()->session;

		$response = $this->server->dispatch( $request );

		$this->assertSame( $expected_status, $response->get_status() );
		$this->assertEquals( $order_data, wc_get_order( $order->get_id() )->get_data() );
		$this->assertEquals( $session_data, WC()->session );
	}

	/**
	 * @testdox Valid pending callbacks can update shipping repeatedly before token revocation.
	 */
	public function test_repeated_callbacks_and_revocation(): void {
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( 'Callback test zone' );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$zone->add_shipping_method( 'free_shipping' );

		$order = $this->create_callback_order();
		$order->add_product( WC_Helper_Product::create_simple_product(), 1 );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'callback-token' );
		$order->calculate_totals();
		$request = $this->create_callback_request( $order );
		$request->set_param( 'token', 'callback-token' );

		foreach ( array( '94105', '94107' ) as $postcode ) {
			$address                = $request->get_param( 'shipping_address' );
			$address['postal_code'] = $postcode;
			$request->set_param( 'shipping_address', $address );
			$response = $this->server->dispatch( $request );

			$this->assertSame( 200, $response->get_status() );
			$this->assertSame( $postcode, wc_get_order( $order->get_id() )->get_shipping_postcode() );
			$this->assertTrue( $this->sut->validate_shipping_callback_request( $request ) );
		}

		$order = wc_get_order( $order->get_id() );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, '' );
		$order->save();
		$this->assertSame( 401, $this->server->dispatch( $request )->get_status() );
	}

	/**
	 * @testdox Cancellation revokes callbacks for pending and already-draft orders and remains repeatable.
	 * @testWith ["pending"]
	 *           ["checkout-draft"]
	 *
	 * @param string $order_status Order status before cancellation.
	 */
	public function test_cancellation_revokes_callback_credentials( string $order_status ): void {
		$order = $this->create_callback_order();
		$order->set_status( $order_status );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'callback-token' );
		$order->save();
		$callback = $this->create_callback_request( $order );
		$callback->set_param( 'token', 'callback-token' );
		$this->assertTrue( $this->sut->validate_shipping_callback_request( $callback ), 'The active attempt must initially permit its credential.' );

		$cancel = $this->create_cancel_request( $order );
		foreach ( array( 'initial cancellation', 'cancellation retry' ) as $attempt ) {
			$this->assertSame( 200, $this->server->dispatch( $cancel )->get_status(), $attempt );
			$saved_order = wc_get_order( $order->get_id() );
			$this->assertSame( OrderStatus::CHECKOUT_DRAFT, $saved_order->get_status(), $attempt );
			$this->assertSame( '', $saved_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN ), $attempt );
			$this->assertSame( 'provider-order', $saved_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID ), 'Keep the provider reference for retries and accounting.' );
			$this->assertSame( 401, $this->server->dispatch( $callback )->get_status(), 'Canceled attempts must not change shipping.' );
			$this->assertSame( '10001', wc_get_order( $order->get_id() )->get_shipping_postcode(), 'Cancellation must preserve the existing destination.' );
		}
	}

	/**
	 * @testdox Rejected cancellation preserves the callback credential and order data.
	 * @testWith ["nonce", 401]
	 *           ["provider_id", 404]
	 *           ["status", 409]
	 *
	 * @param string $invalid_field Field to invalidate.
	 * @param int    $expected_status Expected response status.
	 */
	public function test_rejected_cancellation_preserves_callback_credentials( string $invalid_field, int $expected_status ): void {
		$order = $this->create_callback_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'callback-token' );
		if ( 'status' === $invalid_field ) {
			$order->set_status( OrderStatus::COMPLETED );
		}
		$order->save();
		$cancel = $this->create_cancel_request( $order );
		if ( 'nonce' === $invalid_field ) {
			$cancel->set_header( 'Nonce', 'invalid-nonce' );
		} elseif ( 'provider_id' === $invalid_field ) {
			$cancel->set_body(
				wp_json_encode(
					array(
						'order_id'        => $order->get_id(),
						'paypal_order_id' => 'another-provider-order',
					)
				)
			);
		}
		$order_data = wc_get_order( $order->get_id() )->get_data();

		$this->assertSame( $expected_status, $this->server->dispatch( $cancel )->get_status() );
		$this->assertEquals( $order_data, wc_get_order( $order->get_id() )->get_data(), 'A rejected cancellation must not modify the order or its credential.' );
	}

	/**
	 * @testdox A callback credential from a different order cannot authorize shipping updates.
	 */
	public function test_callback_credential_is_bound_to_its_order(): void {
		$order = $this->create_callback_order();
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'first-order-token' );
		$order->save();
		$other_order = $this->create_callback_order();
		$other_order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'other-provider-order' );
		$other_order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN, 'other-order-token' );
		$other_order->save();
		$callback = $this->create_callback_request( $order );
		$callback->set_param( 'token', $other_order->get_meta( PayPalConstants::PAYPAL_ORDER_META_SHIPPING_CALLBACK_TOKEN ) );
		$order_data = wc_get_order( $order->get_id() )->get_data();

		$this->assertSame( 401, $this->server->dispatch( $callback )->get_status() );
		$this->assertEquals( $order_data, wc_get_order( $order->get_id() )->get_data(), 'A different order credential must not modify this order.' );
	}

	/**
	 * Build an authorized cancellation request for the current provider order.
	 *
	 * @param WC_Order $order Order whose attempt is being canceled.
	 * @return WP_REST_Request
	 */
	private function create_cancel_request( WC_Order $order ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-buttons/cancel-payment' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_gateway_paypal_standard_cancel_payment' ) );
		$request->set_body(
			wp_json_encode(
				array(
					'order_id'        => $order->get_id(),
					'paypal_order_id' => $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID ),
				)
			)
		);
		return $request;
	}

	/**
	 * Create an unpaid order with a provider order ID.
	 *
	 * @return WC_Order
	 */
	private function create_callback_order(): WC_Order {
		$order = new WC_Order();
		$order->set_status( OrderStatus::PENDING );
		$order->set_shipping_postcode( '10001' );
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_ORDER_ID, 'provider-order' );
		// Match an editable attempt when the companion address-integrity example is applied.
		$order->update_meta_data(
			'_paypal_address_context',
			array(
				'address_source'      => 'shipping',
				'shipping_preference' => PayPalConstants::SHIPPING_GET_FROM_FILE,
			)
		);
		$order->save();
		return $order;
	}

	/**
	 * Build an otherwise valid shipping callback without a token.
	 *
	 * @param WC_Order $order Order receiving the update.
	 * @return WP_REST_Request
	 */
	private function create_callback_request( WC_Order $order ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-standard/update-shipping' );
		$request->set_body_params(
			array(
				'id'               => 'provider-order',
				'purchase_units'   => array(
					array(
						'custom_id' => wp_json_encode(
							array(
								'order_id'  => $order->get_id(),
								'order_key' => $order->get_order_key(),
							)
						),
					),
				),
				'shipping_address' => array(
					'country_code' => 'US',
					'admin_area_1' => 'CA',
					'admin_area_2' => 'San Francisco',
					'postal_code'  => '94105',
				),
			)
		);
		return $request;
	}
}
