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
	 * @return bool
	 */
	protected function validate_transaction( $transaction ) {
		$pdt = array(
			'body' 			=> array(
				'cmd' => '_notify-synch',
				'tx'  => $transaction,
				'at'  => $this->identity_token
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
		
		return $response;
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

		if ( 'completed' === $status && ( $response = $this->validate_transaction( $transaction ) ) !== false ) {
			if ( $order->get_total() != $amount ) {
				WC_Gateway_Paypal::log( 'Payment error: Amounts do not match (amt ' . $amount . ')' );
				$this->payment_on_hold( $order, sprintf( __( 'Validation error: PayPal amounts do not match (amt %s).', 'woocommerce' ), $amount ) );
			} else {
				$this->payment_complete( $order, $transaction,  __( 'PDT payment completed', 'woocommerce' ) );

				$this->save_paypal_meta_data( $order, $this->extract_paypal_data( $response ) );
			}
		}
	}


	/**
	 * Extract data from the PDT response body.
	 * @param array $response
	 * @return array
	 */
	protected function extract_paypal_data( $response ) {
		$posted = array();

		if ( preg_match_all( '~^(\w+)=(.*)$~m', $response['body'], $matches, PREG_PATTERN_ORDER ) ) {
			for ( $i=1; $i<count($matches[0]); $i++ ) {
				$posted[$matches[1][$i]] = urldecode( $matches[2][$i] );
			}
		}
		
		if ( ! empty( $posted['charset'] ) && strtolower( $posted['charset'] ) !== 'utf-8' ) {
			$posted['first_name'] = utf8_encode( $posted['first_name'] );
			$posted['last_name'] = utf8_encode( $posted['last_name'] );
		}

		return $posted;
	}


	/**
	 * Save important data from the PDT request to the order.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function save_paypal_meta_data( $order, $posted ) {
		if ( ! empty( $posted['mc_fee'] ) ) {
			update_post_meta( $order->id, 'PayPal Transaction Fee', wc_clean( $posted['mc_fee'] ) );
		}
		if ( ! empty( $posted['payer_email'] ) ) {
			update_post_meta( $order->id, 'Payer PayPal address', wc_clean( $posted['payer_email'] ) );
		}
		if ( ! empty( $posted['first_name'] ) ) {
			update_post_meta( $order->id, 'Payer first name', wc_clean( $posted['first_name'] ) );
		}
		if ( ! empty( $posted['last_name'] ) ) {
			update_post_meta( $order->id, 'Payer last name', wc_clean( $posted['last_name'] ) );
		}
		if ( ! empty( $posted['payment_type'] ) ) {
			update_post_meta( $order->id, 'Payment type', wc_clean( $posted['payment_type'] ) );
		}
	}
}

