<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Token
 *
 * Representation of a general payment token to be extended by individuals types of tokens
 * examples: Credit Card, eCheck.
 *
 * @class 		WC_Payment_Token
 * @since		2.6.0
 * @package		WooCommerce/Abstracts
 * @category	Abstract Class
 * @author		WooThemes
 */
 abstract class WC_Payment_Token {

 	/** @protected int Token ID */
 	protected $id;
 	/** @protected array Core Token Data (stored in the payment_tokens table) */
 	protected $data;
 	/** @protected array Meta Token Data (extra data associated with a payment token, stored in the payment_token_meta table) */
 	protected $meta;

 	/**
	 * Initialize a payment token
	 *
	 * These fields are accepted by all payment tokens:
	 * default      - boolean Optional - Indicates this is the default payment token for a user
	 * type         - string  Required - WC core ships with 'cc' or 'echeck' but other values can be used for custom payment token types
	 * token        - string  Required - The actual token to store
	 * gateway_id   - string  Required - Identifier for the gateway this token is associated with
	 * customer_id  - int     Optional - ID for the customer this token is associated with. 0 if this token is not associated with a user
	 *
	 * @since 2.6.0
	 * @param string $id Token ID
	 * @param array $data Core token data
	 * @param array $meta Meta token data
	 */
	public function __construct( $id, $data, $meta ) {
		$this->id = $id;
		$this->data = $data;
		$this->meta = $meta;
	}

	/**
	 * Returns the payment token ID
	 *
	 * @since 2.6.0
	 * @return string Token ID
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Returns the raw payment token
	 *
	 * @since 2.6.0
	 * @return string Raw token
	 */
	public function get_token() {
		return $this->data['token'];
	}

	/**
	 * Set the raw payment token
	 *
	 * @since 2.6.0
	 * @param string $token
	 */
	public function set_token( $token ) {
		$this->data['token'] = $token;
	}

	/**
	 * Returns the type of this payment token (cc, echeck, or something else)
	 *
	 * @since 2.6.0
	 * @return string Payment token type
	 */
	public function get_type() {
		return isset( $this->data['type'] ) ? $this->data['type'] : null;
	}

	/**
	 * Set the payment token type
	 *
	 * @since 2.6.0
	 * @param string $type
	 */
	public function set_type( $type ) {
		$this->data['type'] = $type;
	}

	/**
	 * Returns the customer ID associated with the token or false if this token is not associated
	 *
	 * @since 2.6.0
	 * @return mixed Customer ID if this token is associated with a user or false.
	 */
	public function get_customer_id() {
		return ( $this->data['customer_id'] > 0 ) ? $this->data['customer_id'] : false;
	}

	/**
	 * Set the customer ID
	 *
	 * @since 2.6.0
	 * @param int $customer_id
	 */
	public function set_customer_id( $customer_id ) {
		$this->data['customer_id'] = $customer_id;
	}

	/**
	 * Returns the ID of the gateway associated with this payment token
	 *
	 * @since 2.6.0
	 * @return string Gateway ID
	 */
	public function get_gateway_id() {
		return $this->data['gateway_id'];
	}

	/**
	 * Set the gateway ID
	 *
	 * @since 2.6.0
	 * @param string $gateway_id
	 */
	public function set_gateway_id( $gateway_id ) {
		$this->data['gateway_id'] = $gateway_id;
	}

	/**
	 * Returns if the token is marked as default
	 *
	 * @since 2.6.0
	 * @return boolean True if the token is default
	 */
	public function is_default() {
		return isset( $this->data['is_default'] ) && $this->data['is_default'];
	}

	/**
	 * Marks the payment as default or non-default
	 *
	 * @since 2.6.0
	 * @param boolean $is_default True or false
	 */
	public function set_default( $is_default ) {
		$this->data['is_default'] = $is_default;
	}

	/**
	 * Returns a dump of the token data (combined data and meta)
	 *
	 * @since 2.6.0
	 * @return mixed array representation
	 */
	public function __data_format() {
		return array_merge( $this->data, $this->meta );
	}

}
