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
	 * Tear down the test environment.
	 */
	public function tearDown(): void {
		parent::tearDown();

		wp_set_current_user( 0 );
	}

	/**
	 * Tests for `process_shipping_callback` method.
	 *
	 * @param string $paypal_order_id PayPal order ID.
	 * @param array  $purchase_units Purchase units from PayPal order.
	 * @param array  $shipping_option Shipping option selected by customer.
	 * @param array  $shipping_methods Available shipping methods.
	 * @param array  $shipping_rates Available shipping rates.
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
		array $shipping_methods,
		array $shipping_rates,
		array $expected_response,
		int $expected_status
	): void {
		$shipping_methods_hook = function () use ( $shipping_methods ) {
			return $shipping_methods;
		};
		add_action( 'woocommerce_shipping_methods', $shipping_methods_hook );

		$package_rates_hook = function ( $rates, $package ) use ( $shipping_rates ) {
			return $shipping_rates;
		};
		add_filter( 'woocommerce_package_rates', $package_rates_hook, 10, 2 );

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
		remove_filter( 'woocommerce_package_rates', $package_rates_hook, 10 );
		remove_action( 'woocommerce_shipping_methods', $shipping_methods_hook );

		$this->assertEquals( $expected_status, $response->get_status() );
		$this->assertEquals( $expected_response, $response->get_data() );
	}

	/**
	 * Provider for `test_process_shipping_callback`.
	 *
	 * @return array
	 */
	public function provide_test_process_shipping_callback(): array {
		$shipping_method = new class() extends WC_Shipping_Method {
			/**
			 * Custom pickup shipping method.
			 * @var string
			 */
			public $id = 'flat_rate';

			/**
			 * Array of features this rate supports.
			 * @var array
			 */
			public $supports = array( 'local-pickup' );

			/**
			 * Get rates for package.
			 * @param array $package package.
			 *
			 * @return WC_Shipping_Rate[]
			 */
			public function get_rates_for_package( $package ) {
				return array( 'flat_rate:1' => new WC_Shipping_Rate( 'flat_rate:1', 'Flat Rate 1', '10.00', array(), 'flat_rate' ) );
			}
		};

		$flat_rate = new WC_Shipping_Rate( 'flat_rate:1', 'Flat Rate 1', '10.00', array(), 'flat_rate' );

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
				'shipping methods'  => array( $shipping_method ),
				'shipping rates'    => array( 'flat_rate:1' => $flat_rate ),
				'expected response' => array(
					'name'    => 'UNPROCESSABLE_ENTITY',
					'details' => array(
						array( 'issue' => 'ADDRESS_ERROR' ),
					),
				),
				'expected status'   => 422,
			),
			'unable to find order'      => array(
				'PayPal order ID'   => '74L756601X447022W',
				'purchase units'    => array(
					'custom_id' => 'non_existent_order',
				),
				'shipping option'   => array(),
				'shipping methods'  => array( $shipping_method ),
				'shipping rates'    => array( 'flat_rate:1' => $flat_rate ),
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
					'custom_id' => wp_json_encode(
						array(
							'order_id'  => $order_mismatch->get_id(),
							'order_key' => $order_mismatch->get_order_key(),
						),
					),
				),
				'shipping option'   => array(),
				'shipping methods'  => array( $shipping_method ),
				'shipping rates'    => array( 'flat_rate:1' => $flat_rate ),
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
				'PayPal order ID'   => '94N960803Z669244Y',
				'purchase units'    => array(
					'custom_id' => wp_json_encode(
						array(
							'order_id'  => $order->get_id(),
							'order_key' => $order->get_order_key(),
						),
					),
				),
				'shipping option'   => array(),
				'shipping methods'  => array(),
				'shipping rates'    => array(),
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
				'PayPal order ID'   => '94N960803Z669244Y',
				'purchase units'    => array(
					'custom_id' => wp_json_encode(
						array(
							'order_id'  => $order->get_id(),
							'order_key' => $order->get_order_key(),
						),
					),
				),
				'shipping option'   => array(
					'id' => 'flat_rate:1',
				),
				'shipping methods'  => array( $shipping_method ),
				'shipping rates'    => array( 'flat_rate:1' => $flat_rate ),
				'expected response' => array(
					'id'                 => '94N960803Z669244Y',
					'purchase_units'     => array(
						array(
							'shipping_options' => array(
								array(
									'id'          => 'flat_rate:1',
									'label'       => 'Flat Rate 1',
									'type'        => 'SHIPPING',
									'amount'      => array(
										'currency_code' => 'USD',
										'value'         => '10.00',
									),
									'selected'    => true,
								),
							),
							'reference_id'    => '',
							'amount'          => array(
								'currency_code' => 'USD',
								'value'         => '50.00',
								'breakdown'    => array(
									'item_total'     => array(
										'currency_code' => 'USD',
										'value'         => '40.00',
									),
									'shipping'       => array(
										'currency_code' => 'USD',
										'value'         => '10.00',
									),
									'tax_total'     => array(
										'currency_code' => 'USD',
										'value'         => '0.00',
									),
									'discount'      => array(
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
