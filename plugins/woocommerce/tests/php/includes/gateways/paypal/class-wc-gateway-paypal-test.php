<?php
/**
 * Unit tests for WC_Gateway_Paypal class.
 *
 * @package WooCommerce\Tests\Paypal.
 */

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
		$order->set_payment_method( 'paypal' );
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
		$order->set_payment_method( 'paypal' );
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
		$order->set_payment_method( 'paypal' );
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
			'payment_type'   => 'paypal',
			'txn_id'         => $this->transaction_id_26960,
			'payment_status' => 'Completed',
		);

		$call_posted_meta = function ( $order, $posted_meta ) {
			$this->save_paypal_meta_data( $order, $posted_meta );
		};

		$call_posted_meta->call( ( new WC_Gateway_Paypal_IPN_Handler( true ) ), $order, $posted_meta );

		$this->assertEquals( $order->get_meta( 'Payment type' ), 'paypal' );
		$this->assertEquals( $order->get_transaction_id(), $this->transaction_id_26960 );
		$this->assertEquals( $order->get_meta( '_paypal_status' ), 'Completed' );
	}

}
