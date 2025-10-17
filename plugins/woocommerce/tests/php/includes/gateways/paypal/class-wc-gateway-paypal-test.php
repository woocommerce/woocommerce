<?php
/**
 * Unit tests for WC_Gateway_Paypal class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

/**
 * Class WC_Gateway_Paypal_Test.
 */
class WC_Gateway_Paypal_Test extends \WC_Unit_Test_Case {
	/**
	 * @var string Dummy identifiable transaction ID.
	 */
	private $transaction_id_26960 = 'dummy_id_26960';

	/**
	 * @var string Dummy indentifiable error message.
	 */
	private $error_message_26960 = 'Paypal error for GH issue 26960';

	/**
	 * Test do_capture when API returns error.
	 *
	 * see @link https://github.com/woocommerce/woocommerce/issues/26960
	 */
	public function test_do_capture_when_api_return_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$order->update_meta_data( '_paypal_status', 'pending' );
		$order->set_transaction_id( $this->transaction_id_26960 );
		$order->set_payment_method( WC_Gateway_Paypal::ID );
		$order->save();

		// Force HTTP error.
		add_filter( 'pre_http_request', array( $this, '__return_paypal_error' ), 10, 2 );

		( new WC_Gateway_Paypal() )->capture_payment( $order->get_id() );

		// reset error.
		remove_filter( 'pre_http_request', array( $this, '__return_paypal_error' ) );

		$order_notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$latest_note = current( $order_notes );
		$this->assertStringContainsString( $this->error_message_26960, $latest_note->content );
	}

	/**
	 * Test do_capture when API returns error.
	 *
	 * see @link https://github.com/woocommerce/woocommerce/issues/26960
	 */
	public function test_refund_transaction_when_api_return_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$order->update_meta_data( '_paypal_status', 'pending' );
		$order->set_transaction_id( $this->transaction_id_26960 );
		$order->set_payment_method( WC_Gateway_Paypal::ID );
		$order->save();

		// Force HTTP error.
		add_filter( 'pre_http_request', array( $this, '__return_paypal_error' ), 10, 2 );

		// Force refunds check to true.
		$paypal_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )->setMethods( array( 'can_refund_order' ) )->getMock();
		$paypal_gateway->method( 'can_refund_order' )->willReturn( 'true' );

		$response = $paypal_gateway->process_refund( $order );

		// reset error.
		remove_filter( 'pre_http_request', array( $this, '__return_paypal_error' ) );

		$this->assertWPError( $response );
		$this->assertStringContainsString( $this->error_message_26960, $response->get_error_message() );
	}

	/**
	 * Utility function for raising error when this is a PayPal request using transaction_id_26960.
	 *
	 * @param bool  $value      Original pre-value, likely to be false.
	 * @param array $parsed_url Parsed URL object.
	 *
	 * @return bool|WP_Error Raise error or return original value.
	 */
	public function __return_paypal_error( $value, $parsed_url ) {
		if ( isset( $parsed_url['body'] ) && isset( $parsed_url['body']['AUTHORIZATIONID'] ) && $this->transaction_id_26960 === $parsed_url['body']['AUTHORIZATIONID'] ) {
			return new WP_Error( 'error', $this->error_message_26960 );
		}
		if ( isset( $parsed_url['body'] ) && isset( $parsed_url['body']['TRANSACTIONID'] ) && $this->transaction_id_26960 === $parsed_url['body']['TRANSACTIONID'] ) {
			return new WP_Error( 'error', $this->error_message_26960 );
		}
		return $value;
	}

	/**
	 * Test do_capture when API returns success.
	 */
	public function test_capture_payment() {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data( '_paypal_status', 'pending' );
		$order->set_transaction_id( $this->transaction_id_26960 );
		$order->set_payment_method( WC_Gateway_Paypal::ID );
		$order->save();

		// Force HTTP error.
		add_filter( 'pre_http_request', array( $this, '__return_paypal_success' ), 10, 2 );

		( new WC_Gateway_Paypal() )->capture_payment( $order->get_id() );

		remove_filter( 'pre_http_request', array( $this, '__return_paypal_success' ) );

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'Completed', $order->get_meta( '_paypal_status' ) );
	}

	/**
	 * Helper function for raising success when this is a PayPal request using transaction_id_26960.
	 *
	 * @param bool  $value      Original pre-value, likely to be false.
	 * @param array $parsed_url Parsed URL object.
	 *
	 * @return bool|WP_Error Return success object or return original value.
	 */
	public function __return_paypal_success( $value, $parsed_url ) {
		$response_body = array(
			'TRANSACTIONID'   => $this->transaction_id_26960,
			'PAYMENTSTATUS'   => 'Completed',
			'AMT'             => '100.00',
			'CURRENCYCODE'    => 'USD',
			'AVSCODE'         => 'X',
			'CVV2MATCH'       => 'M',
			'ACK'             => 'Success',
			'AUTHORIZATIONID' => $this->transaction_id_26960,
		);
		$response      = array( 'body' => http_build_query( $response_body ) );
		if ( isset( $parsed_url['body'] ) && isset( $parsed_url['body']['AUTHORIZATIONID'] ) && $this->transaction_id_26960 === $parsed_url['body']['AUTHORIZATIONID'] ) {
			return $response;
		}
		if ( isset( $parsed_url['body'] ) && isset( $parsed_url['body']['TRANSACTIONID'] ) && $this->transaction_id_26960 === $parsed_url['body']['TRANSACTIONID'] ) {
			return $response;
		}
		return $value;
	}

	/**
	 * Test that paypal metadata is saved properly in opn request.
	 */
	public function test_ipn_save_paypal_meta_data() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		$posted_meta = array(
			'payment_type'   => WC_Gateway_Paypal::ID,
			'txn_id'         => $this->transaction_id_26960,
			'payment_status' => 'Completed',
		);

		$call_posted_meta = function ( $order, $posted_meta ) {
			$this->save_paypal_meta_data( $order, $posted_meta );
		};

		$call_posted_meta->call( ( new WC_Gateway_Paypal_IPN_Handler( true ) ), $order, $posted_meta );

		$this->assertEquals( $order->get_meta( 'Payment type' ), WC_Gateway_Paypal::ID );
		$this->assertEquals( $order->get_transaction_id(), $this->transaction_id_26960 );
		$this->assertEquals( $order->get_meta( '_paypal_status' ), 'Completed' );
	}

	/**
	 * Test that correct settings are displayed when Orders v2 is enabled.
	 */
	public function test_correct_settings_is_displayed_when_orders_v2_is_enabled() {
		// Enable the gateway.
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'yes' ) );

		// Mock Orders v2 to be enabled.
		$mock_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )
			->onlyMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mock_gateway->method( 'should_use_orders_v2' )->willReturn( true );

		$form_fields = $mock_gateway->get_form_fields();

		// Verify that the number of fields are correct.
		$this->assertEquals( count( $form_fields ), 12 );

		// When Orders v2 is enabled, paypal_buttons field should be present.
		$this->assertArrayHasKey( 'paypal_buttons', $form_fields );

		// Verify legacy fields are removed (these would have 'is_legacy' => true).
		// We need to check the original form fields to see what should be removed.
		$all_form_fields = include WC_ABSPATH . 'includes/gateways/paypal/includes/settings-paypal.php';

		foreach ( $all_form_fields as $key => $field ) {
			if ( isset( $field['is_legacy'] ) && $field['is_legacy'] ) {
				$this->assertArrayNotHasKey( $key, $form_fields, "Legacy field '{$key}' should be removed when Orders v2 is enabled" );
			}
		}
	}

	/**
	 * Test that correct settings are displayed when Orders v2 is disabled.
	 */
	public function test_correct_settings_is_displayed_when_orders_v2_is_disabled() {
		$all_form_fields = include WC_ABSPATH . 'includes/gateways/paypal/includes/settings-paypal.php';

		// Enable the gateway.
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'yes' ) );

		$gateway     = new WC_Gateway_Paypal();
		$form_fields = $gateway->get_form_fields();

		$this->assertEquals( count( $form_fields ), 22 );
		$this->assertArrayNotHasKey( 'paypal_buttons', $form_fields );

		foreach ( $all_form_fields as $key => $field ) {
			if ( isset( $field['is_legacy'] ) && $field['is_legacy'] ) {
				$this->assertArrayHasKey( $key, $form_fields, "Legacy field '{$key}' should be present when Orders v2 is disabled" );
			}
		}
	}

	/**
	 * Tests for the `update_addresses_in_order` method.
	 *
	 * @param bool $should_create_order Whether the order exists.
	 * @param string $payment_method Payment method to set on the order.
	 * @param string|null $paypal_order_id PayPal order ID to set on the order.
	 * @param bool $should_use_orders_v2 Whether Orders v2 is enabled.
	 * @param bool $mock_jetpack_params Whether to mock valid Jetpack params.
	 * @param bool $expect_to_save Whether we expect the order to be saved.
	 * @return void
	 *
	 * @dataProvider provide_test_update_addresses_in_order
	 */
	public function test_update_addresses_in_order(
		bool $should_create_order,
		string $payment_method,
		?string $paypal_order_id,
		bool $should_use_orders_v2,
		bool $mock_jetpack_params,
		bool $expect_to_save
	) {
		if ( $should_create_order ) {
			$order = WC_Helper_Order::create_order();
			$order->set_payment_method( $payment_method );
			$order->update_meta_data( '_paypal_order_id', $paypal_order_id );
			$order->save();
		}

		$order_id = $order ? $order->get_id() : 0;

		$return_valid_site_id = function () {
			return array( 'id' => 12345 );
		};
		$return_blog_token    = function () {
			return array( 'blog_token' => 'IAM.AJETPACKBLOGTOKEN' );
		};

		if ( $mock_jetpack_params ) {
			add_filter( 'pre_option_jetpack_options', $return_valid_site_id );
			add_filter( 'pre_option_jetpack_private_options', $return_blog_token );
		}

		$response_mock_ref = function () {
			return array(
				'response' => array(
					'code' => 200,
				),
				'body'     => wp_json_encode( array() ),
			);
		};
		add_filter( 'pre_http_request', $response_mock_ref, 10, 2 );

		$triggered = false;
		$callback  = static function () use ( &$triggered ) {
			$triggered = true;
		};
		add_action( 'woocommerce_before_order_object_save', $callback );

		/**
		 * @var WC_Gateway_Paypal $mock_gateway Mocked gateway with Orders v2 enabled.
		 */
		$mock_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )
			->onlyMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mock_gateway->method( 'should_use_orders_v2' )
			->willReturn( $should_use_orders_v2 );
		$mock_gateway->testmode = false;

		$mock_gateway->update_addresses_in_order( $order_id );

		// Clean up after test.
		remove_filter( 'pre_option_jetpack_options', $return_valid_site_id );
		remove_filter( 'pre_option_jetpack_private_options', $return_blog_token );
		remove_action( 'woocommerce_before_order_object_save', $callback );
		remove_filter( 'pre_http_request', $response_mock_ref );

		$this->assertSame( $expect_to_save, $triggered );

		// If we expected the order to be saved, verify that addresses were set.
		if ( $expect_to_save ) {
			$this->assertEquals( 'US', $order->get_billing_country() );
			$this->assertEquals( '12345', $order->get_billing_postcode() );
			$this->assertEquals( 'NY', $order->get_billing_state() );
			$this->assertEquals( 'WooCity', $order->get_billing_city() );
			$this->assertEquals( 'WooAddress', $order->get_billing_address_1() );
			$this->assertEquals( '', $order->get_billing_address_2() );
		}

		// Clean up.
		if ( $order ) {
			$order->delete( true );
		}
	}

	/**
	 * Data provider for `test_update_addresses_in_order`.
	 *
	 * @return array[]
	 * @throws WC_Data_Exception If order creation fails.
	 */
	public function provide_test_update_addresses_in_order(): array {
		return array(
			'order not found'         => array(
				'order exists'         => false,
				'payment method'       => 'paypal',
				'paypal order ID'      => 'TEST_PAYPAL_ORDER_ID',
				'should use orders v2' => true,
				'mock Jetpack params'  => true,
				'expect to save'       => false,
			),
			'invalid payment method'  => array(
				'order exists'         => true,
				'payment method'       => 'bacs',
				'paypal order ID'      => null,
				'should use orders v2' => true,
				'mock Jetpack params'  => true,
				'expect to save'       => false,
			),
			'orders v2 not enabled'   => array(
				'order exists'         => true,
				'payment method'       => 'paypal',
				'paypal order ID'      => 'TEST_PAYPAL_ORDER_ID',
				'should use orders v2' => false,
				'mock Jetpack params'  => true,
				'expect to save'       => false,
			),
			'missing PayPal order ID' => array(
				'order exists'         => true,
				'payment method'       => 'paypal',
				'paypal order ID'      => null,
				'should use orders v2' => true,
				'mock Jetpack params'  => true,
				'expect to save'       => false,
			),
			'exception thrown'        => array(
				'order exists'         => true,
				'payment method'       => 'paypal',
				'paypal order ID'      => 'TEST_PAYPAL_ORDER_ID',
				'should use orders v2' => true,
				'mock Jetpack params'  => false,
				'expect to save'       => false,
			),
			'successful update'       => array(
				'order exists'         => true,
				'payment method'       => 'paypal',
				'paypal order ID'      => 'TEST_PAYPAL_ORDER_ID',
				'should use orders v2' => true,
				'mock Jetpack params'  => true,
				'expect to save'       => true,
			),
		);
	}

	/**
	 * Tests for the `enqueue_scripts` method.
	 *
	 * @param bool   $gateway_enabled Whether the gateway is enabled.
	 * @param string $client_id       The client ID to set in options.
	 * @param bool   $script_expected Whether we expect the script to be enqueued.
	 * @return void
	 *
	 * @dataProvider provide_test_enqueue_scripts
	 */
	public function test_enqueue_scripts( $gateway_enabled, $client_id, $script_expected ) {
		// Enable the gateway.
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => $gateway_enabled ? 'yes' : 'no' ) );

		// Set cached client ID.
		update_option( 'woocommerce_paypal_client_id_live', $client_id );

		add_filter( 'woocommerce_is_cart', '__return_true' );

		/**
		 * @var WC_Gateway_Paypal $mock_gateway Mocked gateway with Orders v2 enabled.
		 */
		$mock_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )
			->onlyMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mock_gateway->method( 'should_use_orders_v2' )->willReturn( true );
		$mock_gateway->testmode = false;

		$mock_gateway->enqueue_scripts();

		// Clean up.
		delete_option( 'woocommerce_paypal_settings' );
		delete_option( 'woocommerce_paypal_client_id_live' );
		remove_filter( 'woocommerce_is_cart', '__return_true' );

		$this->assertEquals( $script_expected, wp_script_is( 'paypal-standard-sdk', 'enqueued' ) );
	}

	/**
	 * Data provider for `test_enqueue_scripts`.
	 *
	 * @return array[]
	 */
	public function provide_test_enqueue_scripts(): array {
		return array(
			'gateway disabled'  => array(
				'gateway enabled' => false,
				'client ID'       => 'test_client_id',
				'script expected' => false,
			),
			'missing client ID' => array(
				'gateway enabled' => true,
				'client ID'       => '',
				'script expected' => false,
			),
			'script enqueued'   => array(
				'gateway enabled' => true,
				'client ID'       => 'test_client_id',
				'script expected' => true,
			),
		);
	}

	/**
	 * Tests for the `add_paypal_sdk_attributes` method.
	 *
	 * @return void
	 */
	public function test_add_paypal_sdk_attributes() {
		$gateway = new WC_Gateway_Paypal();

		// Without the JS SDK.
		$actual = $gateway->add_paypal_sdk_attributes( array( 'id' => '' ) );
		$this->assertSame( array( 'id' => '' ), $actual );

		// With the JS SDK.
		$actual = $gateway->add_paypal_sdk_attributes( array( 'id' => 'paypal-standard-sdk-js' ) );
		$this->assertSame(
			array(
				'id'                          => 'paypal-standard-sdk-js',
				'data-page-type'              => 'checkout',
				'data-partner-attribution-id' => 'Woo_Cart_CoreUpgrade',
			),
			$actual
		);
	}

	/**
	 * Tests for the `render_buttons_container` method.
	 *
	 * @return void
	 */
	public function test_render_buttons_container() {
		$gateway = new WC_Gateway_Paypal();

		ob_start();
		$gateway->render_buttons_container();
		$output = ob_get_clean();

		$this->assertSame( '<div id="paypal-standard-container"></div>', trim( $output ) );
	}
}
