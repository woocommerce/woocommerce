<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'class-wc-gateway-paypal-response.php' );

/**
 * Handle PDT Responses from PayPal.
 */
class WC_Gateway_Paypal_PDT_Handler extends WC_Gateway_Paypal_Response {

	/** @var string identity_token for PDT support */
	protected $identity_token;

	/**
	 * Constructor.
	 *
	 * @param bool $sandbox
	 * @param string $identity_token
	 */
	public function __construct( $sandbox = false, $identity_token = '' ) {
		add_action( 'woocommerce_thankyou_paypal', array( $this, 'check_response' ) );

		$this->identity_token = $identity_token;
		$this->sandbox        = $sandbox;
	}

	/**
	 * Validate a PDT transaction to ensure its authentic.
	 * @param  string $transaction
	 * @return bool|array False or result array
	 */
	protected function validate_transaction( $transaction ) {
		$pdt = array(
			'body' 			=> array(
				'cmd' => '_notify-synch',
				'tx'  => $transaction,
				'at'  => $this->identity_token,
			),
			'timeout' 		=> 60,
			'httpversion'   => '1.1',
			'user-agent'	=> 'WooCommerce/' . WC_VERSION
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $pdt );

		if ( is_wp_error( $response ) || ! strpos( $response['body'], "SUCCESS" ) === 0 ) {
			return false;
		}

		// Parse transaction result data
		$transaction_result  = array_map( 'wc_clean', array_map( 'urldecode', explode( "\n", $response['body'] ) ) );
		$transaction_results = array();

		foreach ( $transaction_result as $line ) {
			$line                            = explode( "=", $line );
			$transaction_results[ $line[0] ] = isset( $line[1] ) ? $line[1] : '';
		}

		if ( ! empty( $transaction_results['charset'] ) && function_exists( 'iconv' ) ) {
			foreach ( $transaction_results as $key => $value ) {
				$transaction_results[ $key ] = iconv( $transaction_results['charset'], 'utf-8', $value );
			}
		}

		return $transaction_results;
	}

	/**
	 * Check Response for PDT.
	 */
	public function check_response() {
		if ( empty( $_REQUEST['cm'] ) || empty( $_REQUEST['tx'] ) || empty( $_REQUEST['st'] ) ) {
			return;
		}

		$order_id    = wc_clean( stripslashes( $_REQUEST['cm'] ) );
		$status      = wc_clean( strtolower( stripslashes( $_REQUEST['st'] ) ) );
		$amount      = wc_clean( stripslashes( $_REQUEST['amt'] ) );
		$transaction = wc_clean( stripslashes( $_REQUEST['tx'] ) );

		if ( ! ( $order = $this->get_paypal_order( $order_id ) ) || ! $order->has_status( 'pending' ) ) {
			return false;
		}

		$transaction_result = $this->validate_transaction( $transaction );

		if ( $transaction_result && 'completed' === $status ) {
			if ( $order->get_total() != $amount ) {
				WC_Gateway_Paypal::log( 'Payment error: Amounts do not match (amt ' . $amount . ')' );
				$this->payment_on_hold( $order, sprintf( __( 'Validation error: PayPal amounts do not match (amt %s).', 'woocommerce' ), $amount ) );
			} else {
				$this->payment_complete( $order, $transaction,  __( 'PDT payment completed', 'woocommerce' ) );

				// Log paypal transaction fee and other meta data.
				if ( ! empty( $transaction_result['mc_fee'] ) ) {
					update_post_meta( $order->id, 'PayPal Transaction Fee', $transaction_result['mc_fee'] );
				}
				if ( ! empty( $transaction_result['payer_email'] ) ) {
					update_post_meta( $order->id, 'Payer PayPal address', $transaction_result['payer_email'] );
				}
				if ( ! empty( $transaction_result['first_name'] ) ) {
					update_post_meta( $order->id, 'Payer first name', $transaction_result['first_name'] );
				}
				if ( ! empty( $transaction_result['last_name'] ) ) {
					update_post_meta( $order->id, 'Payer last name', $transaction_result['last_name'] );
				}
				if ( ! empty( $transaction_result['payment_type'] ) ) {
					update_post_meta( $order->id, 'Payment type', $transaction_result['payment_type'] );
				}
			}
		}
	}
}
