<?php

declare(strict_types=1);

/**
 * class WC_REST_Paypal_Standard_Controller_Test.
 * PayPal Standard Controller tests for V3 REST API.
 */
class WC_REST_Paypal_Standard_Controller_Test extends WC_REST_Unit_Test_Case {
	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->endpoint = new WC_REST_Paypal_Standard_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'customer',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Tests for `process_shipping_callback` method.
	 *
	 * @param string $paypal_order_id PayPal order ID.
	 * @param array  $purchase_units Purchase units from PayPal order.
	 * @param array  $shipping_option Shipping option selected by customer.
	 * @param array  $expected_response Expected response from the endpoint.
	 * @param int    $expected_status Expected HTTP status code.
	 * @return void
	 *
	 * @dataProvider provide_test_process_shipping_callback
	 */
	public function test_process_shipping_callback(
		string $paypal_order_id,
		array $purchase_units,
		array $shipping_option,
		array $expected_response,
		int $expected_status
	): void {
		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-standard/update-shipping' );
		$request->set_body_params(
			array(
				'id'               => $paypal_order_id,
				'shipping_address' => array(
					'postal_code'  => '90001',
					'country_code' => 'US',
					'admin_area_1' => 'CA',
					'admin_area_2' => 'Test City',
				),
				'shipping_option'  => $shipping_option,
				'purchase_units'   => array( $purchase_units ),
			)
		);
		$response = $this->server->dispatch( $request );

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Provider for `test_process_shipping_callback`.
	 *
	 * @return array
	 */
	public function provide_test_process_shipping_callback(): array {
		$order = WC_Helper_Order::create_order();
		$order->save();
		$order->update_meta_data( '_paypal_order_id', '94N960803Z669244Y' );
		$order->save_meta_data();

		$order_mismatch = WC_Helper_Order::create_order();
		$order_mismatch->save();
		$order_mismatch->update_meta_data( '_paypal_order_id', '84M859702Y558133X' );
		$order_mismatch->save_meta_data();

		return array(
			'missing PayPal order ID'   => array(
				'PayPal order ID'   => '',
				'purchase units'    => array(),
				'shipping option'   => array(),
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'unable to find order'    => array(
				'PayPal order ID'   => '74L756601X447022W',
				'purchase units'    => array(
					'custom_id' => 'non_existent_order',
				),
				'shipping option'   => array(),
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'PayPal order ID mismatch'  => array(
				'PayPal order ID'   => '94N960803Z669244Y',
				'purchase units'    => array(
					'custom_id' => wp_json_encode( array( 'order_id' => $order_mismatch->get_id(), 'order_key' => $order_mismatch->get_order_key() ) ),
				),
				'shipping option'   => array(),
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'no shipping options found' => array(
				'PayPal order ID'   => '94N960803Z669244Y',
				'purchase units'    => array(
					'custom_id' => wp_json_encode( array( 'order_id' => $order->get_id(), 'order_key' => $order->get_order_key() ) ),
				),
				'shipping option'   => array(),
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'successful update' => array(
				'PayPal order ID'   => '94N960803Z669244Y',
				'purchase units'    => array(
					'custom_id' => wp_json_encode( array( 'order_id' => $order->get_id(), 'order_key' => $order->get_order_key() ) ),
				),
				'shipping option'   => array(
					'id' => 'flat_rate:1',
				),
				'expected response' => array(
					'shipping_options' => array(
						array(
							'id'          => 'flat_rate:1',
							'label'       => 'Flat rate',
							'type'        => 'SHIPPING',
							'amount'      => array(
								'currency_code' => 'USD',
								'value'         => '10.00',
							),
							'default'     => true,
							'description' => '',
						),
						array(
							'id'          => 'free_shipping:2',
							'label'       => 'Free shipping',
							'type'        => 'SHIPPING',
							'amount'      => array(
								'currency_code' => 'USD',
								'value'         => '0.00',
							),
							'default'     => false,
							'description' => '',
						),
					),
				),
				'expected status'   => 200,
			),
		);
	}
}
