<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce eCheck Payment Token
 *
 * Representation of a payment token for eChecks
 *
 * @class 		WC_Payment_Token_eCheck
 * @since		2.6.0
 * @category 	PaymentTokens
 * @package 	WooCommerce/PaymentTokens
 * @author		WooThemes
 */
class WC_Payment_Token_eCheck extends WC_Payment_Token {

 	/**
	 * Validate eCheck payment tokens.
	 *
	 * These fields are required by all credit card payment tokens:
	 * last4  - string Last 4 digits of the check
	 *
	 * @since 2.6.0
	 * @param array $args Data to validate
	 * @return boolean    True if the passed data is valid
	 */
	public static function validate( $args ) {
		if ( empty( $args['last4'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Returns the last four digits
	 *
	 * @since 2.6.0
	 * @return string Last 4 digits
	 */
	public function get_last4() {
		return isset( $this->meta['last4'] ) ? $this->meta['last4'] : null;
	}

	/**
	 * Set the last four digits
	 *
	 * @since 2.6.0
	 * @param string $last4
	 */
	public function set_last4( $last4 ) {
		$this->meta['last4'] = $last4;
	}

}
