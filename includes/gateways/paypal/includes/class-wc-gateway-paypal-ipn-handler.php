<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( dirname( __FILE__ ) . '/class-wc-gateway-paypal-response.php' );

/**
 * Handles responses from PayPal IPN.
 */
class WC_Gateway_Paypal_IPN_Handler extends WC_Gateway_Paypal_Response {

	/** @var string Receiver email address to validate */
	protected $receiver_email;

	/**
	 * Constructor.
	 *
	 * @param bool $sandbox
	 * @param string $receiver_email
	 */
	public function __construct( $sandbox = false, $receiver_email = '' ) {
		add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_response' ) );
		add_action( 'valid-paypal-standard-ipn-request', array( $this, 'valid_response' ) );

		$this->receiver_email = $receiver_email;
		$this->sandbox        = $sandbox;
	}

	/**
	 * Check for PayPal IPN Response.
	 */
	public function check_response() {
		if ( ! empty( $_POST ) && $this->validate_ipn() ) {
			$posted = wp_unslash( $_POST );

			// @codingStandardsIgnoreStart
			do_action( 'valid-paypal-standard-ipn-request', $posted );
			// @codingStandardsIgnoreEnd
			exit;
		}

		wp_die( 'PayPal IPN Request Failure', 'PayPal IPN', array( 'response' => 500 ) );
	}

	/**
	 * There was a valid response.
	 * @param  array $posted Post data after wp_unslash
	 */
	public function valid_response( $posted ) {
		if ( ! empty( $posted['custom'] ) && ( $order = $this->get_paypal_order( $posted['custom'] ) ) ) {

			// Lowercase returned variables.
			$posted['payment_status'] = strtolower( $posted['payment_status'] );

			WC_Gateway_Paypal::log( 'Found order #' . $order->get_id() );
			WC_Gateway_Paypal::log( 'Payment status: ' . $posted['payment_status'] );

			if ( method_exists( $this, 'payment_status_' . $posted['payment_status'] ) ) {
				call_user_func( array( $this, 'payment_status_' . $posted['payment_status'] ), $order, $posted );
			}
		}
	}

	/**
	 * Check PayPal IPN validity.
	 */
	public function validate_ipn() {
		WC_Gateway_Paypal::log( 'Checking IPN response is valid' );

		// Get received values from post data
		$validate_ipn        = wp_unslash( $_POST );
		$validate_ipn['cmd'] = '_notify-validate';

		// Send back post vars to paypal
		$params = array(
			'body'        => $validate_ipn,
			'timeout'     => 60,
			'httpversion' => '1.1',
			'compress'    => false,
			'decompress'  => false,
			'user-agent'  => 'WooCommerce/' . WC()->version,
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $params );

		WC_Gateway_Paypal::log( 'IPN Request: ' . wc_print_r( $params, true ) );
		WC_Gateway_Paypal::log( 'IPN Response: ' . wc_print_r( $response, true ) );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && strstr( $response['body'], 'VERIFIED' ) ) {
			WC_Gateway_Paypal::log( 'Received valid response from PayPal' );
			return true;
		}

		WC_Gateway_Paypal::log( 'Received invalid response from PayPal' );

		if ( is_wp_error( $response ) ) {
			WC_Gateway_Paypal::log( 'Error response: ' . $response->get_error_message() );
		}

		return false;
	}

	/**
	 * Check for a valid transaction type.
	 * @param string $txn_type
	 */
	protected function validate_transaction_type( $txn_type ) {
		$accepted_types = array( 'cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money', 'paypal_here' );

		if ( ! in_array( strtolower( $txn_type ), $accepted_types ) ) {
			WC_Gateway_Paypal::log( 'Aborting, Invalid type:' . $txn_type );
			exit;
		}
	}

	/**
	 * Check currency from IPN matches the order.
	 * @param WC_Order $order
	 * @param string $currency
	 */
	protected function validate_currency( $order, $currency ) {
		if ( $order->get_currency() != $currency ) {
			WC_Gateway_Paypal::log( 'Payment error: Currencies do not match (sent "' . $order->get_currency() . '" | returned "' . $currency . '")' );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'woocommerce' ), $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from IPN matches the order.
	 * @param WC_Order $order
	 * @param int $amount
	 */
	protected function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) != number_format( $amount, 2, '.', '' ) ) {
			WC_Gateway_Paypal::log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'woocommerce' ), $amount ) );
			exit;
		}
	}

	/**
	 * Check receiver email from PayPal. If the receiver email in the IPN is different than what is stored in.
	 * WooCommerce -> Settings -> Checkout -> PayPal, it will log an error about it.
	 * @param WC_Order $order
	 * @param string $receiver_email
	 */
	protected function validate_receiver_email( $order, $receiver_email ) {
		if ( strcasecmp( trim( $receiver_email ), trim( $this->receiver_email ) ) != 0 ) {
			WC_Gateway_Paypal::log( "IPN Response is for another account: {$receiver_email}. Your email is {$this->receiver_email}" );

			// Put this order on-hold for manual checking.
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal IPN response from a different email address (%s).', 'woocommerce' ), $receiver_email ) );
			exit;
		}
	}

	/**
	 * Handle a completed payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_completed( $order, $posted ) {
		if ( $order->has_status( wc_get_is_paid_statuses() ) ) {
			WC_Gateway_Paypal::log( 'Aborting, Order #' . $order->get_id() . ' is already complete.' );
			exit;
		}

		$this->validate_transaction_type( $posted['txn_type'] );
		$this->validate_currency( $order, $posted['mc_currency'] );
		$this->validate_amount( $order, $posted['mc_gross'] );
		$this->validate_receiver_email( $order, $posted['receiver_email'] );
		$this->save_paypal_meta_data( $order, $posted );

		if ( 'completed' === $posted['payment_status'] ) {
			if ( $order->has_status( 'cancelled' ) ) {
				$this->payment_status_paid_cancelled_order( $order, $posted );
			}

			$this->payment_complete( $order, ( ! empty( $posted['txn_id'] ) ? wc_clean( $posted['txn_id'] ) : '' ), __( 'IPN payment completed', 'woocommerce' ) );

			if ( ! empty( $posted['mc_fee'] ) ) {
				// Log paypal transaction fee.
				update_post_meta( $order->get_id(), 'PayPal Transaction Fee', wc_clean( $posted['mc_fee'] ) );
			}
		} else {
			if ( 'authorization' === $posted['pending_reason'] ) {
				$this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'woocommerce' ) );
			} else {
				$this->payment_on_hold( $order, sprintf( __( 'Payment pending (%s).', 'woocommerce' ), $posted['pending_reason'] ) );
			}
		}
	}

	/**
	 * Handle a pending payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_pending( $order, $posted ) {
		$this->payment_status_completed( $order, $posted );
	}

	/**
	 * Handle a failed payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_failed( $order, $posted ) {
		$order->update_status( 'failed', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), wc_clean( $posted['payment_status'] ) ) );
	}

	/**
	 * Handle a denied payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_denied( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle an expired payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_expired( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * Handle a voided payment.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_voided( $order, $posted ) {
		$this->payment_status_failed( $order, $posted );
	}

	/**
	 * When a user cancelled order is marked paid.
	 *
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_paid_cancelled_order( $order, $posted ) {
		$this->send_ipn_email_notification(
			sprintf( __( 'Payment for cancelled order %s received', 'woocommerce' ), '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
			sprintf( __( 'Order #%1$s has been marked paid by PayPal IPN, but was previously cancelled. Admin handling required.', 'woocommerce' ), $order->get_order_number() )
		);
	}

	/**
	 * Handle a refunded order.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_refunded( $order, $posted ) {
		// Only handle full refunds, not partial.
		if ( $order->get_total() == ( $posted['mc_gross'] * -1 ) ) {

			// Mark order as refunded.
			$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), strtolower( $posted['payment_status'] ) ) );

			$this->send_ipn_email_notification(
				sprintf( __( 'Payment for order %s refunded', 'woocommerce' ), '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
				sprintf( __( 'Order #%1$s has been marked as refunded - PayPal reason code: %2$s', 'woocommerce' ), $order->get_order_number(), $posted['reason_code'] )
			);
		}
	}

	/**
	 * Handle a reversal.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_reversed( $order, $posted ) {
		$order->update_status( 'on-hold', sprintf( __( 'Payment %s via IPN.', 'woocommerce' ), wc_clean( $posted['payment_status'] ) ) );

		$this->send_ipn_email_notification(
			sprintf( __( 'Payment for order %s reversed', 'woocommerce' ), '<a class="link" href="' . esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) . '">' . $order->get_order_number() . '</a>' ),
			sprintf( __( 'Order #%1$s has been marked on-hold due to a reversal - PayPal reason code: %2$s', 'woocommerce' ), $order->get_order_number(), wc_clean( $posted['reason_code'] ) )
		);
	}

	/**
	 * Handle a cancelled reversal.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function payment_status_canceled_reversal( $order, $posted ) {
		$this->send_ipn_email_notification(
			sprintf( __( 'Reversal cancelled for order #%s', 'woocommerce' ), $order->get_order_number() ),
			sprintf( __( 'Order #%1$s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %2$s', 'woocommerce' ), $order->get_order_number(), esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ) )
		);
	}

	/**
	 * Save important data from the IPN to the order.
	 * @param WC_Order $order
	 * @param array $posted
	 */
	protected function save_paypal_meta_data( $order, $posted ) {
		if ( ! empty( $posted['payer_email'] ) ) {
			update_post_meta( $order->get_id(), 'Payer PayPal address', wc_clean( $posted['payer_email'] ) );
		}
		if ( ! empty( $posted['first_name'] ) ) {
			update_post_meta( $order->get_id(), 'Payer first name', wc_clean( $posted['first_name'] ) );
		}
		if ( ! empty( $posted['last_name'] ) ) {
			update_post_meta( $order->get_id(), 'Payer last name', wc_clean( $posted['last_name'] ) );
		}
		if ( ! empty( $posted['payment_type'] ) ) {
			update_post_meta( $order->get_id(), 'Payment type', wc_clean( $posted['payment_type'] ) );
		}
		if ( ! empty( $posted['txn_id'] ) ) {
			update_post_meta( $order->get_id(), '_transaction_id', wc_clean( $posted['txn_id'] ) );
		}
		if ( ! empty( $posted['payment_status'] ) ) {
			update_post_meta( $order->get_id(), '_paypal_status', wc_clean( $posted['payment_status'] ) );
		}
	}

	/**
	 * Send a notification to the user handling orders.
	 * @param string $subject
	 * @param string $message
	 */
	protected function send_ipn_email_notification( $subject, $message ) {
		$new_order_settings = get_option( 'woocommerce_new_order_settings', array() );
		$mailer             = WC()->mailer();
		$message            = $mailer->wrap_message( $subject, $message );

		$mailer->send( ! empty( $new_order_settings['recipient'] ) ? $new_order_settings['recipient'] : get_option( 'admin_email' ), strip_tags( $subject ), $message );
	}
}
