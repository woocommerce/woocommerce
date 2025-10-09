<?php

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * class WC_REST_Paypal_Buttons_Controller_Test.
 * PayPal Buttons Controller tests for V3 REST API.
 */
class WC_REST_Paypal_Buttons_Controller_Test  extends WC_REST_Unit_Test_Case {
	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->endpoint = new WC_REST_Paypal_Buttons_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Tests for the `create_order` method.
	 *
	 * @param string $nonce Nonce for request validation.
	 * @param int    $order_id Order ID.
	 * @param string $order_key Order key.
	 * @param string $payment_source Payment source (e.g., 'paypal').
	 * @param array|null $wpcom_response Mocked response from WPCOM API.
	 * @param int    $expected_status Expected HTTP status code.
	 * @param array|null $expected_response Expected response data.
	 * @return void
	 *
	 * @dataProvider provide_test_create_order
	 */
	public function test_create_order(
		$nonce,
		$order_id = null,
		$order_key = null,
		$payment_source = null,
		$wpcom_response = null,
		$expected_status = 200,
		$expected_response = null
	) {
		$response_mock_ref = function() use ( $wpcom_response ) {
			return $wpcom_response;
		};

		add_filter( 'pre_http_request', $response_mock_ref, 10, 3 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-buttons/create_order' );
		$request->set_body_params(
			array(
				'nonce'      => $nonce,
				'order_id'   => $order_id,
				'order_key'  => $order_key,
				'payment_source' => $payment_source,
			)
		);
		$response = $this->server->dispatch( $request );

		// Cleanup the filter to avoid affecting other tests.
		remove_filter( 'pre_http_request', $response_mock_ref );

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Data provider for `test_create_order`.
	 *
	 * @return array[]
	 */
	public function provide_test_create_order(): array {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$order_invalid_status = WC_Helper_Order::create_order();
		$order_invalid_status->set_status( OrderStatus::COMPLETED );
		$order_invalid_status->save();

		return array(
			'missing nonce' => array(
				'nonce' => '',
				'order ID' => 123,
				'order key' => 'some_key',
				'payment source' => 'paypal',
				'WPCOM response' => null,
				'expected status' => 403,
				'expected response' => '',
			),
			'missing order ID' => array(
				'nonce'    => wp_create_nonce( '' ),
				'order ID' => '',
				'order key' => 'some_key',
				'payment source' => 'paypal',
				'WPCOM response' => null,
				'expected status' => 400,
				'expected response' => '',
			),
			'missing payment source' => array(
				'nonce'          => wp_create_nonce( '' ),
				'order ID'       => 123,
				'order key'      => 'some_key',
				'payment source' => '',
				'WPCOM response' => null,
				'expected status' => 400,
				'expected response' => '',
			),
			'order not found' => array(
				'nonce'    => wp_create_nonce( '' ),
				'order ID' => 123,
				'order key' => 'some_key',
				'payment source' => 'paypal',
				'WPCOM response' => null,
				'expected status' => 404,
				'expected response' => '',
			),
			'invalid order key' => array(
				'nonce'     => wp_create_nonce( '' ),
				'order ID'  => $order_invalid_status->get_id(),
				'order key' => 'invalid_key',
				'payment source' => 'paypal',
				'WPCOM response' => null,
				'expected status' => 404,
				'expected response' => '',
			),
			'invalid order status' => array(
				'nonce'     => wp_create_nonce( '' ),
				'order ID'  => $order_invalid_status->get_id(),
				'order key' => $order_invalid_status->get_order_key(),
				'payment source' => 'paypal',
				'WPCOM response' => null,
				'expected status' => 409,
				'expected response' => '',
			),
			'PayPal order creation failed' => array(
				'nonce'          => wp_create_nonce( '' ),
				'order ID'       => $order->get_id(),
				'order key'      => $order->get_order_key(),
				'payment source' => 'paypal',
				'WPCOM response' => '',
				'expected status' => 400,
				'expected response' => '',
			),
			'successful order creation' => array(
				'nonce'          => wp_create_nonce( '' ),
				'order ID'       => $order->get_id(),
				'order key'      => $order->get_order_key(),
				'payment source' => 'paypal',
				'WPCOM response' => array(
					'response' => array(
						'code' => 200,
					),
					'body'     => wp_json_encode(
						array(
							'id'    => '123',
							'links' => array(
								array(
									'rel'    => 'approve',
									'href'   => 'https://www.paypal.com/checkoutnow?token=123',
									'method' => 'GET',
								),
							),
						)
					),
				),
			),
		);
	}
}
