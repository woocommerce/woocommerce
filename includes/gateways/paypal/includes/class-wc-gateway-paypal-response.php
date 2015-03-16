<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles refunds
 */
abstract class WC_Gateway_Paypal_Response {

	/** @var bool Sandbox mode */
	protected $sandbox = false;

	/**
	 * Stores logging class
	 * @var WC_Logger
	 */
	protected $log;

	/**
	 * Logging method
	 * @param  string $message
	 */
	protected function log( $message ) {
		if ( $this->sandbox ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}
			$this->log->add( 'paypal', $message );
		}
	}

	/**
	 * Get the order from the PayPal 'Custom' variable
	 *
	 * @param  string $custom
	 * @return bool|WC_Order object
	 */
	protected function get_paypal_order( $custom ) {
		$custom = maybe_unserialize( $custom );

		if ( is_array( $custom ) ) {

			list( $order_id, $order_key ) = $custom;

			if ( ! $order = wc_get_order( $order_id ) ) {
				// We have an invalid $order_id, probably because invoice_prefix has changed
				$order_id 	= wc_get_order_id_by_order_key( $order_key );
				$order 		= wc_get_order( $order_id );
			}

			if ( ! $order || $order->order_key !== $order_key ) {
				$this->log( 'Error: Order Keys do not match.' );
				return false;
			}

		} elseif ( ! $order = apply_filters( 'woocommerce_get_paypal_order', false, $custom ) ) {
			$this->log( 'Error: Order ID and key were not found in "custom".' );
			return false;
		}

		return $order;
	}

	/**
	 * Complete order, add transaction ID and note
	 * @param  WC_Order $order
	 * @param  string $txn_id
	 * @param  string $note
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->add_order_note( $note );
		$order->payment_complete( $txn_id );
	}

	/**
	 * Hold order and add note
	 * @param  WC_Order $order
	 * @param  string $reason
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
	}
}
