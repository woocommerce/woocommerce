<?php
/**
 * Class WC_Payment_Token_eCheck file.
 *
 * @package WooCommerce\PaymentTokens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce eCheck Payment Token.
 *
 * Representation of a payment token for eChecks.
 *
 * @class       WC_Payment_Token_ECheck
 * @version     3.0.0
 * @since       2.6.0
 * @package     WooCommerce\PaymentTokens
 */
class WC_Payment_Token_ECheck extends WC_Payment_Token {

	/**
	 * Token Type String.
	 *
	 * @var string
	 */
	protected $type = 'eCheck';

	/**
	 * Stores eCheck payment token data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'last4' => '',
	);

	/**
	 * Get type to display to user.
	 *
	 * @since  2.6.0
	 * @param  string $deprecated Deprecated since WooCommerce 3.0.
	 * @return string
	 */
	public function get_display_name( $deprecated = '' ) {
		$display = sprintf(
			/* translators: 1: last 4 digits */
			__( 'eCheck ending in %1$s', 'woocommerce' ),
			$this->get_last4()
		);
		return $display;
	}

	/**
	 * Hook prefix
	 *
	 * @since 3.0.0
	 */
	protected function get_hook_prefix() {
		return 'woocommerce_payment_token_echeck_get_';
	}

	/**
	 * Validate eCheck payment tokens.
	 *
	 * These fields are required by all eCheck payment tokens:
	 * last4  - string Last 4 digits of the check
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		$invalid_fields = $this->get_invalid_token_fields();
		if ( $invalid_fields !== array() ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the last four digits.
	 *
	 * @since  2.6.0
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return string Last 4 digits
	 */
	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	/**
	 * Set the last four digits.
	 *
	 * @since 2.6.0
	 * @param string $last4 eCheck last four digits.
	 */
	public function set_last4( $last4 ) {
		$this->set_prop( 'last4', $last4 );
	}

	/**
	 * Get the invalid token fields.
	 *
	 * @since x.x.x
	 *
	 * @return array<string, mixed> The invalid token fields with the field name as the key and the field value as the value.
	 */
	public function get_invalid_token_fields(): array {
		$invalid_fields = parent::get_invalid_token_fields();

		$last4 = $this->get_last4( 'edit' );
		if ( ! $last4 ) {
			$invalid_fields['last4'] = $last4;
		}

		return $invalid_fields;
	}
}
