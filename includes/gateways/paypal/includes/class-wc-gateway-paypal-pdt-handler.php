<?php
/**
 * Class WC_Gateway_Paypal_PDT_Handler file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/class-wc-gateway-paypal-response.php';

/**
 * Handle PDT Responses from PayPal.
 */
class WC_Gateway_Paypal_PDT_Handler extends WC_Gateway_Paypal_Response {

	/**
	 * Identity token for PDT support
	 *
	 * @var string
	 */
	protected $identity_token;

	/**
	 * Constructor.
	 *
	 * @param bool   $sandbox Whether to use sandbox mode or not.
	 * @param string $identity_token Identity token for PDT support.
	 */
	public function __construct( $sandbox = false, $identity_token = '' ) {
		add_action( 'woocommerce_thankyou_paypal', array( $this, 'check_response' ) );

		$this->identity_token = $identity_token;
		$this->sandbox        = $sandbox;
	}

	/**
	 * Validate a PDT transaction to ensure its authentic.
	 *
	 * @param  string $transaction TX ID.
	 * @return bool|array False or result array if successful and valid.
	 */
	protected function validate_transaction( $transaction ) {
		$pdt = array(
			'body'        => array(
				'cmd' => '_notify-synch',
				'tx'  => $transaction,
				'at'  => $this->identity_token,
			),
			'timeout'     => 60,
			'httpversion' => '1.1',
			'user-agent'  => 'WooCommerce/' . WC_VERSION,
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $pdt );

		if ( is_wp_error( $response ) || strpos( $response['body'], 'SUCCESS' ) !== 0 ) {
			return false;
		}

		// Parse transaction result data.
		$transaction_result  = array_map( 'wc_clean', array_map( 'urldecode', explode( "\n", $response['body'] ) ) );
		$transaction_results = array();

		foreach ( $transaction_result as $line ) {
			$line                            = explode( '=', $line );
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
		if ( empty( $_REQUEST['cm'] ) || empty( $_REQUEST['tx'] ) || empty( $_REQUEST['st'] ) ) { // WPCS: Input var ok, CSRF ok, sanitization ok.
			return;
		}

		$order_id    = wc_clean( wp_unslash( $_REQUEST['cm'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.
		$status      = wc_clean( strtolower( wp_unslash( $_REQUEST['st'] ) ) ); // WPCS: input var ok, CSRF ok, sanitization ok.
		$amount      = wc_clean( wp_unslash( $_REQUEST['amt'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.
		$transaction = wc_clean( wp_unslash( $_REQUEST['tx'] ) ); // WPCS: input var ok, CSRF ok, sanitization ok.
		$order       = $this->get_paypal_order( $order_id );

		if ( ! $order || ! $order->needs_payment() ) {
			return false;
		}

		$transaction_result = $this->validate_transaction( $transaction );

		if ( $transaction_result ) {
			WC_Gateway_Paypal::log( 'PDT Transaction Status: ' . wc_print_r( $status, true ) );

			update_post_meta( $order->get_id(), '_paypal_status', $status );
			update_post_meta( $order->get_id(), '_transaction_id', $transaction );

			if ( 'completed' === $status ) {
				if ( $order->get_total() !== $amount ) {
					WC_Gateway_Paypal::log( 'Payment error: Amounts do not match (amt ' . $amount . ')', 'error' );
					/* translators: 1: Payment amount */
					$this->payment_on_hold( $order, sprintf( __( 'Validation error: PayPal amounts do not match (amt %s).', 'woocommerce' ), $amount ) );
				} else {
					$this->payment_complete( $order, $transaction, __( 'PDT payment completed', 'woocommerce' ) );

					// Log paypal transaction fee and payment type.
					if ( ! empty( $transaction_result['mc_fee'] ) ) {
						update_post_meta( $order->get_id(), 'PayPal Transaction Fee', $transaction_result['mc_fee'] );
					}
					if ( ! empty( $transaction_result['payment_type'] ) ) {
						update_post_meta( $order->get_id(), 'Payment type', $transaction_result['payment_type'] );
					}
				}
			} else {
				if ( 'authorization' === $transaction_result['pending_reason'] ) {
					$this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'woocommerce' ) );
				} else {
					/* translators: 1: Pending reason */
					$this->payment_on_hold( $order, sprintf( __( 'Payment pending (%s).', 'woocommerce' ), $transaction_result['pending_reason'] ) );
				}
			}
		} else {
			WC_Gateway_Paypal::log( 'Received invalid response from PayPal PDT' );
		}
	}
}
