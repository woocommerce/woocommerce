<?php
/**
 * Unit tests for WC_Gateway_Paypal class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

declare(strict_types=1);

use Automattic\WooCommerce\Gateways\PayPal\Constants as PayPalConstants;

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

		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, 'pending' );
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

		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, 'pending' );
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
		$order->update_meta_data( PayPalConstants::PAYPAL_ORDER_META_STATUS, 'pending' );
		$order->set_transaction_id( $this->transaction_id_26960 );
		$order->set_payment_method( WC_Gateway_Paypal::ID );
		$order->save();

		// Force HTTP error.
		add_filter( 'pre_http_request', array( $this, '__return_paypal_success' ), 10, 2 );

		( new WC_Gateway_Paypal() )->capture_payment( $order->get_id() );

		remove_filter( 'pre_http_request', array( $this, '__return_paypal_success' ) );

		$order = wc_get_order( $order->get_id() );
		$this->assertEquals( 'Completed', $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS ) );
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
		$this->assertEquals( $order->get_meta( PayPalConstants::PAYPAL_ORDER_META_STATUS ), 'Completed' );
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
	 * Test that gateway is available when Orders v2 is disabled (legacy mode).
	 */
	public function test_is_available_with_legacy_mode() {
		// Enable the gateway.
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'yes' ) );

		// Mock Orders v2 to be disabled.
		$mock_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )
			->onlyMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mock_gateway->method( 'should_use_orders_v2' )->willReturn( false );

		$this->assertTrue( $mock_gateway->is_available() );
	}

	/**
	 * Test that gateway availability depends on email field value when Orders v2 is enabled.
	 *
	 * @dataProvider gateway_availability_data_provider_for_orders_v2
	 *
	 * @param string|null $email The email to set for the gateway.
	 * @param bool        $expected_available Whether the gateway should be available.
	 */
	public function test_is_available_with_orders_v2( ?string $email, bool $expected_available ) {
		$new_settings = array(
			'enabled' => 'yes',
		);

		if ( null !== $email ) {
			$new_settings['email'] = $email;
		} else {
			// Remove the email field from the settings to test the case where the email field is not set.
			$current_settings = get_option( 'woocommerce_paypal_settings', array() );
			unset( $current_settings['email'] );
			$new_settings = array_merge( $new_settings, $current_settings );
		}

		update_option( 'woocommerce_paypal_settings', $new_settings );

		// Mock Orders v2 to be enabled.
		$mock_gateway = $this->getMockBuilder( WC_Gateway_Paypal::class )
			->onlyMethods( array( 'should_use_orders_v2' ) )
			->getMock();
		$mock_gateway->method( 'should_use_orders_v2' )->willReturn( true );

		$this->assertSame( $expected_available, $mock_gateway->is_available() );
	}

	/**
	 * Data provider for payment gateway availability tests when Orders v2 is enabled.
	 *
	 * @return array Test cases with email values and expected paypal gateway availability.
	 */
	public function gateway_availability_data_provider_for_orders_v2() {
		return array(
			'email field is not set' => array(
				'email'              => null,
				'expected_available' => false,
			),
			'email is empty string'  => array(
				'email'              => '',
				'expected_available' => false,
			),
			'email is invalid'       => array(
				'email'              => 'example@',
				'expected_available' => false,
			),
			'email is valid'         => array(
				'email'              => 'merchant@example.com',
				'expected_available' => true,
			),
		);
	}
}
