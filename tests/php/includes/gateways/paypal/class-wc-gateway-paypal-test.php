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

		update_post_meta( $order->get_id(), '_paypal_status', 'pending' );
		update_post_meta( $order->get_id(), '_transaction_id', $this->transaction_id_26960 );
		$order->set_payment_method( 'paypal' );
		$order->save();

		// Force HTTP error.
		add_filter( 'pre_http_request', array( $this, '__return_paypal_error' ), 10, 2 );

		( new WC_Gateway_Paypal() )->capture_payment( $order->get_id() );

		// reset error.
		remove_filter( 'pre_http_request', array( $this, '__return_paypal_error' ) );

		$order_notes = wc_get_order_notes( array( 'order_id' => $order->get_id() ) );
		$latest_note = current( $order_notes );
		$this->assertContains( $this->error_message_26960, $latest_note->content );
	}

	/**
	 * Test do_capture when API returns error.
	 *
	 * see @link https://github.com/woocommerce/woocommerce/issues/26960
	 */
	public function test_refund_transaction_when_api_return_error() {
		$order = WC_Helper_Order::create_order();
		$order->save();

		update_post_meta( $order->get_id(), '_paypal_status', 'pending' );
		update_post_meta( $order->get_id(), '_transaction_id', $this->transaction_id_26960 );
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
		$this->assertContains( $this->error_message_26960, $response->get_error_message() );
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

}
