<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles refunds
 */
class WC_Gateway_Paypal_Refund {

	/** @var string API Username for refunds */
	public static $api_username;

	/** @var string API Password for refunds */
	public static $api_password;

	/** @var string API Signature for refunds */
	public static $api_signature;

	/**
	 * Get refund request args
	 * @param  WC_Order $order
	 * @param  float $amount
	 * @param  string $reason
	 * @return array
	 */
	public static function get_request( $order, $amount = null, $reason = '' ) {
		$request = array(
			'VERSION'       => '84.0',
			'SIGNATURE'     => self::$api_signature,
			'USER'          => self::$api_username,
			'PWD'           => self::$api_password,
			'METHOD'        => 'RefundTransaction',
			'TRANSACTIONID' => $order->get_transaction_id(),
			'NOTE'          => html_entity_decode( wc_trim_string( $reason, 255 ), ENT_NOQUOTES, 'UTF-8' ),
			'REFUNDTYPE'    => 'Full'
		);
		if ( ! is_null( $amount ) ) {
			$request['AMT']          = number_format( $amount, 2, '.', '' );
			$request['CURRENCYCODE'] = $order->get_order_currency();
			$request['REFUNDTYPE']   = 'Partial';
		}
		return $request;
	}

	/**
	 * Refund an order via PayPal
	 * @param  WC_Order $order
	 * @param  float $amount
	 * @param  string $reason
	 * @param  boolean $sandbox
	 * @return array|wp_error The parsed response from paypal, or a WP_Error object
	 */
	public static function refund_order( $order, $amount = null, $reason = '', $sandbox = false ) {
		$response = wp_safe_remote_post(
			$sandbox ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp',
			array(
				'method'      => 'POST',
				'body'        => self::get_request( $order, $amount, $reason ),
				'timeout'     => 70,
				'user-agent'  => 'WooCommerce',
				'httpversion' => '1.1'
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['body'] ) ) {
			return new WP_Error( 'paypal-refunds', 'Empty Response' );
		}

		parse_str( $response['body'], $response_array );

		return $response_array;
	}
}
