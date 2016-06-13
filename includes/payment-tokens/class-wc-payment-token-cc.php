<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Credit Card Payment Token.
 *
 * Representation of a payment token for credit cards.
 *
 * @class 		WC_Payment_Token_CC
 * @since		2.6.0
 * @category 	PaymentTokens
 * @package 	WooCommerce/PaymentTokens
 * @author		WooThemes
 */
class WC_Payment_Token_CC extends WC_Payment_Token {

	/** @protected string Token Type String. */
	protected $type = 'CC';

	/**
	 * Validate credit card payment tokens.
	 *
	 * These fields are required by all credit card payment tokens:
	 * expiry_month  - string Expiration date (MM) for the card
	 * expiry_year   - string Expiration date (YYYY) for the card
	 * last4         - string Last 4 digits of the card
	 * card_type     - string Card type (visa, mastercard, etc)
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( false === parent::validate() ) {
			return false;
		}

		if ( ! $this->get_last4() ) {
			return false;
		}

		if ( ! $this->get_expiry_year() ) {
			return false;
		}

		if ( ! $this->get_expiry_month() ) {
			return false;
		}

		if ( ! $this->get_card_type() ) {
			return false;
		}

		if ( 4 !== strlen( $this->get_expiry_year() ) ) {
			return false;
		}

		if ( 2 !== strlen( $this->get_expiry_month() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get type to display to user.
	 * @return string
	 */
	public function get_display_name() {
		$display = wc_get_credit_card_type_label( $this->get_card_type() );
		$display .= '&nbsp;' . sprintf( __( 'ending in %s', 'woocommerce' ), $this->get_last4() );
		$display .= ' ' . sprintf( __( '(expires %s)', 'woocommerce' ), $this->get_expiry_month() . '/' . substr( $this->get_expiry_year(), 2 ) );
		return $display;
	}

	/**
	 * Returns the card type (mastercard, visa, ...).
	 * @since 2.6.0
	 * @return string Card type
	 */
	public function get_card_type() {
		return $this->get_meta( 'card_type' );
	}

	/**
	 * Set the card type (mastercard, visa, ...).
	 * @since 2.6.0
	 * @param string $type
	 */
	public function set_card_type( $type ) {
		$this->add_meta_data( 'card_type', $type, true );
	}

	/**
	 * Returns the card expiration year (YYYY).
	 * @since 2.6.0
	 * @return string Expiration year
	 */
	public function get_expiry_year() {
		return $this->get_meta( 'expiry_year' );
	}

	/**
	 * Set the expiration year for the card (YYYY format).
	 * @since 2.6.0
	 * @param string $year
	 */
	public function set_expiry_year( $year ) {
		$this->add_meta_data( 'expiry_year', $year, true );
	}

	/**
	 * Returns the card expiration month (MM).
	 * @since 2.6.0
	 * @return string Expiration month
	 */
	public function get_expiry_month() {
		return $this->get_meta( 'expiry_month' );
	}

	/**
	 * Set the expiration month for the card (formats into MM format).
	 * @since 2.6.0
	 * @param string $month
	 */
	public function set_expiry_month( $month ) {
		$this->add_meta_data( 'expiry_month', str_pad( $month, 2, '0', STR_PAD_LEFT ), true );
	}

	/**
	 * Returns the last four digits.
	 * @since 2.6.0
	 * @return string Last 4 digits
	 */
	public function get_last4() {
		return $this->get_meta( 'last4' );
	}

	/**
	 * Set the last four digits.
	 * @since 2.6.0
	 * @param string $last4
	 */
	public function set_last4( $last4 ) {
		$this->add_meta_data( 'last4', $last4, true );
	}

}
