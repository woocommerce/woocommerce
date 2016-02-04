<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Credit Card Payment Token
 *
 * Representation of a payment token for credit cards
 * Extends from WC_Payment_Token_eCheck since both types contain last4 digits.
 *
 * @class 		WC_Payment_Token_CC
 * @since		2.6.0
 * @category 	PaymentTokens
 * @package 	WooCommerce/PaymentTokens
 * @author		WooThemes
 */
class WC_Payment_Token_CC extends WC_Payment_Token_eCheck {

 	/**
	 * Validate credit card payment tokens
	 *
	 * These fields are required by all credit card payment tokens:
	 * expiry_month  - string Expiration date (MM) for the card
	 * expiry_year   - string Expiration date (YYYY) for the card
	 * last4         - string Last 4 digits of the card
	 * card_type     - string Card type (visa, mastercard, etc)
	 *
	 * @since 2.6.0
	 * @param array $args Data to validate
	 * @return boolean    True if the passed data is valid
	 */
	public static function validate( $args ) {
		$validate = parent::validate( $args );
		if ( ! $validate ) {
			return false;
		}

		if ( empty( $args['expiry_year'] ) ) {
			return false;
		}

		if ( empty( $args['expiry_month'] ) ) {
			return false;
		}

		if ( empty ( $args['card_type'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the card type (mastercard, visa, ...)
	 *
	 * @since 2.6.0
	 * @return string Card type
	 */
	public function get_card_type() {
		return isset( $this->meta['card_type'] ) ? $this->meta['card_type'] : null;
	}

	/**
	 * Set the card type (mastercard, visa, ...)
	 *
	 * @since 2.6.0
	 * @param string $type
	 */
	public function set_card_type( $type ) {
		$this->meta['card_type'] = $type;
	}

	/**
	 * Returns the card expiration year (YYYY)
	 *
	 * @since 2.6.0
	 * @return string Expiration year
	 */
	public function get_expiry_year() {
		return isset( $this->meta['expiry_year'] ) ? $this->meta['expiry_year'] : null;
	}

	/**
	 * Set the expiration year for the card (YYYY format)
	 *
	 * @since 2.6.0
	 * @param string $year
	 */
	public function set_expiry_year( $year ) {
		$this->meta['expiry_year'] = $year;
	}

	/**
	 * Returns the card expiration month (MM)
	 *
	 * @since 2.6.0
	 * @return string Expiration month
	 */
	public function get_expiry_month() {
		return isset( $this->meta['expiry_month'] ) ? $this->meta['expiry_month'] : null;
	}

	/**
	 * Set the expiration month for the card (MM format)
	 *
	 * @since 2.6.0
	 * @param string $month
	 */
	public function set_expiry_month( $month ) {
		$this->meta['expiry_month'] = $month;
	}

}
