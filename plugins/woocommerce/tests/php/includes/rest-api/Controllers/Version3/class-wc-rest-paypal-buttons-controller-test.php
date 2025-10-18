<?php

declare(strict_types=1);

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * class WC_REST_Paypal_Buttons_Controller_Test.
 * PayPal Buttons Controller tests for V3 REST API.
 */
class WC_REST_Paypal_Buttons_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		// Mock Jetpack options to return a valid site ID.
		add_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );

		// Return a Jetpack blog token.
		add_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );

		$this->endpoint = new WC_REST_Paypal_Buttons_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		remove_filter( 'pre_option_jetpack_options', array( $this, 'return_valid_site_id' ) );
		remove_filter( 'pre_option_jetpack_private_options', array( $this, 'return_blog_token' ) );
		wp_set_current_user( 0 );
	}

	/**
	 * Helper method to return valid site ID for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return int
	 */
	public function return_valid_site_id( $value ) {
		return array( 'id' => 12345 );
	}

	/**
	 * Helper method to return valid blog token for Jetpack options.
	 *
	 * @param mixed $value The option value.
	 *
	 * @return array
	 */
	public function return_blog_token( $value ) {
		return array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' );
	}

	/**
	 * Tests for the `create_order` method.
	 *
	 * @param bool       $include_nonce Whether to include a nonce in the request.
	 * @param int        $order_id Order ID.
	 * @param string     $order_key Order key.
	 * @param string     $payment_source Payment source (e.g., 'paypal').
	 * @param array      $order_data Order meta data to set up before the test.
	 * @param array|null $wpcom_response Mocked response from WPCOM API.
	 * @param int        $expected_status Expected HTTP status code.
	 * @param array|null $expected_response Expected response data.
	 * @return void
	 *
	 * @dataProvider provide_test_create_order
	 */
	public function test_create_order(
		$include_nonce = false,
		$order_id = null,
		$order_key = null,
		$payment_source = null,
		$order_data = array(),
		$wpcom_response = null,
		$expected_status = 200,
		$expected_response = null
	) {
		if ( count( $order_data ) > 0 ) {
			$order = new WC_Order();
			if ( isset( $order_data['id'] ) ) {
				$order->set_id( $order_data['id'] );
			}
			if ( isset( $order_data['status'] ) ) {
				$order->set_status( $order_data['status'] );
			} else {
				$order->set_status( OrderStatus::PENDING );
			}
			if ( isset( $order_data['order_key'] ) ) {
				$order->set_order_key( $order_data['order_key'] );
			}
			$order->save();
		}

		$response_mock_ref = function () use ( $wpcom_response ) {
			return $wpcom_response;
		};

		add_filter( 'pre_http_request', $response_mock_ref, 10, 3 );

		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-buttons/create-order' );

		if ( $include_nonce ) {
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_gateway_paypal_standard_create_order' ) );
		}

		$request->set_header( 'content-type', 'application/json' );

		$request->set_body(
			wp_json_encode(
				array(
					'order_id'       => $order_id,
					'order_key'      => $order_key,
					'payment_source' => $payment_source,
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Cleanup the filter to avoid affecting other tests.
		remove_filter( 'pre_http_request', $response_mock_ref );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$order->delete( true );
			}
		}

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Data provider for `test_create_order`.
	 *
	 * @return array[]
	 */
	public function provide_test_create_order(): array {
		return array(
			'missing nonce'                => array(
				'include nonce'     => false,
				'order ID'          => 123,
				'order key'         => 'some_key',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'     => 123,
					'status' => OrderStatus::PENDING,
				),
				'WPCOM response'    => null,
				'expected status'   => 403,
				'expected response' => array(
					'code'    => 'rest_forbidden',
					'message' => 'Sorry, you are not allowed to do that.',
					'data'    => array(
						'status' => 403,
					),
				),
			),
			'missing order ID'             => array(
				'include nonce'     => true,
				'order ID'          => '',
				'order key'         => 'some_key',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'     => 123,
					'status' => OrderStatus::PENDING,
				),
				'WPCOM response'    => null,
				'expected status'   => 400,
				'expected response' => array( 'error' => 'Invalid request' ),
			),
			'missing payment source'       => array(
				'include nonce'     => true,
				'order ID'          => 123,
				'order key'         => 'some_key',
				'payment source'    => '',
				'order data'        => array(
					'id'     => 123,
					'status' => OrderStatus::PENDING,
				),
				'WPCOM response'    => null,
				'expected status'   => 400,
				'expected response' => array( 'error' => 'Missing/Invalid payment source: ' ),
			),
			'order not found'              => array(
				'include nonce'     => true,
				'order ID'          => 123,
				'order key'         => 'some_key',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'     => 123,
					'status' => OrderStatus::PENDING,
				),
				'WPCOM response'    => null,
				'expected status'   => 404,
				'expected response' => array( 'error' => 'Order not found' ),
			),
			'invalid order key'            => array(
				'include nonce'     => true,
				'order ID'          => 1235,
				'order key'         => 'invalid_key',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'     => 1235,
					'status' => OrderStatus::PENDING,
				),
				'WPCOM response'    => null,
				'expected status'   => 404,
				'expected response' => array( 'error' => 'Order not found' ),
			),
			'invalid order status'         => array(
				'include nonce'     => true,
				'order ID'          => 1235,
				'order key'         => 'wc_order_test_key_123',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'        => 1235,
					'status'    => OrderStatus::COMPLETED,
					'order_key' => 'wc_order_test_key_123',
				),
				'WPCOM response'    => null,
				'expected status'   => 409,
				'expected response' => array( 'error' => 'Invalid order status' ),
			),
			'PayPal order creation failed' => array(
				'include nonce'     => true,
				'order ID'          => 1234,
				'order key'         => 'wc_order_test_key_124',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'        => 1234,
					'status'    => OrderStatus::PENDING,
					'order_key' => 'wc_order_test_key_124',
				),
				'WPCOM response'    => '',
				'expected status'   => 400,
				'expected response' => array( 'error' => 'Failed to create PayPal order' ),
			),
			'successful order creation'    => array(
				'include nonce'     => true,
				'order ID'          => 1234,
				'order key'         => 'wc_order_test_key_125',
				'payment source'    => 'paypal',
				'order data'        => array(
					'id'        => 1234,
					'status'    => OrderStatus::PENDING,
					'order_key' => 'wc_order_test_key_125',
				),
				'WPCOM response'    => array(
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
				'expected status'   => 200,
				'expected response' => array(
					'paypal_order_id' => '123',
					'order_id'        => 1234,
					'return_url'      => site_url() . '?order-received=1234&key=wc_order_test_key_125&utm_nooverride=1',
				),
			),
		);
	}

	/**
	 * Tests for the `cancel_payment` method.
	 *
	 * @param bool   $include_nonce Whether to include a nonce in the request.
	 * @param int    $order_id Order ID.
	 * @param string $paypal_order_id PayPal order ID.
	 * @param array  $order_data Order meta data to set up before the test.
	 * @param int    $expected_status Expected HTTP status code.
	 * @param array  $expected_response Expected response data.
	 * @return void
	 *
	 * @dataProvider provide_test_cancel_payment
	 */
	public function test_cancel_payment(
		bool $include_nonce,
		int $order_id,
		string $paypal_order_id,
		array $order_data,
		int $expected_status,
		array $expected_response
	) {
		$order = null;
		if ( ! empty( $order_data ) ) {
			$order = new WC_Order();
			if ( isset( $order_data['id'] ) ) {
				$order->set_id( $order_data['id'] );
			}
			if ( isset( $order_data['status'] ) ) {
				$order->set_status( $order_data['status'] );
			} else {
				$order->set_status( OrderStatus::PENDING );
			}
			$order->save();
			if ( isset( $order_data['_paypal_order_id'] ) ) {
				$order->update_meta_data( '_paypal_order_id', $order_data['_paypal_order_id'] );
				$order->save_meta_data();
			}
		}

		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-buttons/cancel-payment' );

		if ( $include_nonce ) {
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_gateway_paypal_standard_cancel_payment' ) );
		}

		$request->set_header( 'content-type', 'application/json' );

		$request->set_body(
			wp_json_encode(
				array(
					'order_id'        => $order_id,
					'paypal_order_id' => $paypal_order_id,
				)
			)
		);
		$response = $this->server->dispatch( $request );

		// Clean up.
		if ( $order ) {
			$order->delete( true );
		}

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Data provider for `test_cancel_payment`.
	 *
	 * @return array
	 */
	public function provide_test_cancel_payment(): array {
		return array(
			'invalid nonce'                 => array(
				'include nonce'     => false,
				'order ID'          => 0,
				'PayPal order ID'   => '94N960803Z669244Y',
				'order data'        => array(),
				'expected status'   => 403,
				'expected response' => array(
					'code'    => 'rest_forbidden',
					'message' => 'Sorry, you are not allowed to do that.',
					'data'    => array(
						'status' => 403,
					),
				),
			),
			'invalid order ID'              => array(
				'include nonce'     => true,
				'order ID'          => 0,
				'PayPal order ID'   => '94N960803Z669244Y',
				'order data'        => array(),
				'expected status'   => 400,
				'expected response' => array( 'error' => 'Invalid request' ),
			),
			'order not found'               => array(
				'include nonce'     => true,
				'order ID'          => 99999,
				'PayPal order ID'   => '94N960803Z669244Y',
				'order data'        => array(),
				'expected status'   => 404,
				'expected response' => array( 'error' => 'Order not found' ),
			),
			'invalid PayPal order ID'       => array(
				'include nonce'     => true,
				'order ID'          => 1235,
				'PayPal order ID'   => '94N960803Z669244Y',
				'order data'        => array(
					'id'               => 1235,
					'_paypal_order_id' => '',
				),
				'expected status'   => 404,
				'expected response' => array( 'error' => 'Invalid PayPal order' ),
			),
			'order already in draft status' => array(
				'include nonce'     => true,
				'order ID'          => 1236,
				'PayPal order ID'   => '84M859702Y558133X',
				'order data'        => array(
					'id'               => 1236,
					'status'           => OrderStatus::CHECKOUT_DRAFT,
					'_paypal_order_id' => '84M859702Y558133X',
				),
				'expected status'   => 200,
				'expected response' => array( 'success' => true ),
			),
			'invalid order status'          => array(
				'include nonce'     => true,
				'order ID'          => 1235,
				'PayPal order ID'   => '74L758601X447022W',
				'order data'        => array(
					'id'               => 1235,
					'status'           => OrderStatus::COMPLETED,
					'_paypal_order_id' => '74L758601X447022W',
				),
				'expected status'   => 409,
				'expected response' => array( 'error' => 'Order is not pending' ),
			),
			'successful cancellation'       => array(
				'include nonce'     => true,
				'order ID'          => 1234,
				'PayPal order ID'   => '94N960803Z669244Y',
				'order data'        => array(
					'id'               => 1234,
					'_paypal_order_id' => '94N960803Z669244Y',
				),
				'expected status'   => 200,
				'expected response' => array( 'success' => true ),
			),
		);
	}
}
