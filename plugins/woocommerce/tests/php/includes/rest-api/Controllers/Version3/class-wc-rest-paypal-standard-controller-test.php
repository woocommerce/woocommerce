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

		/**
		 * Mock WC_REST_Paypal_Standard_Controller to override rebuild_cart_from_order method
		 */
		$controller = new class() extends WC_REST_Paypal_Standard_Controller {
			private function rebuild_cart_from_order( $order ) {} // Override to avoid calling cart methods.
		};

		$this->endpoint = $controller;
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
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Tests for `process_shipping_callback` method.
	 *
	 * @param string $paypal_order_id_arg PayPal order ID.
	 * @param bool   $valid_custom_id_arg Whether the custom ID is valid.
	 * @param array  $shipping_option_arg Shipping option data from PayPal.
	 * @param bool   $create_shipping_data Whether to create a shipping data for the order.
	 * @param string $paypal_order_id_meta PayPal order ID stored in order meta.
	 * @param array  $expected_response Expected response from the endpoint.
	 * @param int    $expected_status Expected HTTP status code.
	 * @return void
	 *
	 * @dataProvider provide_test_process_shipping_callback
	 */
	public function test_process_shipping_callback(
		string $paypal_order_id_arg,
		bool $valid_custom_id_arg,
		array $shipping_option_arg,
		bool $create_shipping_data,
		string $paypal_order_id_meta,
		array $expected_response,
		int $expected_status
	): void {
		$order = $create_shipping_data ? WC_Helper_Order::create_order() : wc_create_order();
		$order->save();
		$order->update_meta_data( '_paypal_order_id', $paypal_order_id_meta );
		$order->save_meta_data();

		if ( $valid_custom_id_arg ) {
			$purchase_units = array(
				'custom_id' => wp_json_encode(
					array(
						'order_id' => $order->get_id(),
						'order_key' => $order->get_order_key(),
					),
				),
			);
		} else {
			$purchase_units = array(
				'custom_id' => 'non_existent_order',
			);
		}

		$request = new WP_REST_Request( 'POST', '/wc/v3/paypal-standard/update-shipping' );
		$request->set_body_params(
			array(
				'id'               => $paypal_order_id_arg,
				'shipping_address' => array(
					'postal_code'  => '90001',
					'country_code' => 'US',
					'admin_area_1' => 'CA',
					'admin_area_2' => 'Test City',
				),
				'shipping_option'  => $shipping_option_arg,
				'purchase_units'   => array( $purchase_units ),
			)
		);
		$response = $this->server->dispatch( $request );

		// Clean up.
		if ( ! empty( $purchase_units['custom_id'] ) ) {
			$custom_id = json_decode( $purchase_units['custom_id'], true );
			if ( isset( $custom_id['order_id'] ) ) {
				$order = wc_get_order( $custom_id['order_id'] );
				if ( $order ) {
					$order->delete( true );
				}
			}
		}

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Provider for `test_process_shipping_callback`.
	 *
	 * @return array
	 */
	public function provide_test_process_shipping_callback(): array {
		return array(
			'missing PayPal order ID'   => array(
				'PayPal order ID arg'  => '',
				'valid custom ID arg'  => true,
				'shipping option arg'  => array(),
				'create shipping data' => false,
				'PayPal order ID meta' => '',
				'expected response'    => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'unable to find order'      => array(
				'PayPal order ID arg'  => '74L756601X447022W',
				'valid custom ID arg'  => false,
				'shipping option arg'  => array(),
				'create shipping data' => false,
				'PayPal order ID meta' => '',
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'PayPal order ID mismatch' => array(
				'PayPal order ID arg'  => '94N960803Z669244Y',
				'valid custom ID arg'  => true,
				'shipping option arg'  => array(),
				'create shipping data' => false,
				'PayPal order ID meta' => '84M859702Y558133X',
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array(
							'issue' => 'ADDRESS_ERROR',
						),
					),
				),
				'expected status'   => 422,
			),
			'no shipping options found' => array(
				'PayPal order ID arg'  => '94N960803Z669244Y',
				'valid custom ID arg'  => true,
				'shipping option arg'  => array(),
				'create shipping data' => false,
				'PayPal order ID meta' => '94N960803Z669244Y',
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array(
							'issue' => 'ADDRESS_ERROR',
						),
					),
				),
				'expected status'   => 422,
			),
			'successful update'         => array(
				'PayPal order ID arg'  => '94N960803Z669244Y',
				'valid custom ID arg'  => true,
				'shipping option arg'  => array(
					'id' => 'legacy_flat_rate',
				),
				'create shipping data' => true,
				'PayPal order ID meta' => '94N960803Z669244Y',
				'expected response' => array(
					'id'             => '94N960803Z669244Y',
					'purchase_units' => array(
						array(
							'shipping_options' => array(
								array(
									'id'       => 'legacy_flat_rate',
									'label'    => 'Flat rate',
									'type'     => 'SHIPPING',
									'amount'   => array(
										'currency_code' => 'USD',
										'value'         => '10.00',
									),
									'selected' => true,
								),
							),
							'reference_id'     => '',
							'amount'           => array(
								'currency_code' => 'USD',
								'value'         => '50.00',
								'breakdown'     => array(
									'item_total' => array(
										'currency_code' => 'USD',
										'value'         => '40.00',
									),
									'shipping'   => array(
										'currency_code' => 'USD',
										'value'         => '10.00',
									),
									'tax_total'  => array(
										'currency_code' => 'USD',
										'value'         => '0.00',
									),
									'discount'   => array(
										'currency_code' => 'USD',
										'value'         => '0.00',
									),
								),
							),
						),
					),
				),
				'expected status'   => 200,
			),
		);
	}
}
