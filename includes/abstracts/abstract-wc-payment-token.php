<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Token.
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
 abstract class WC_Payment_Token extends WC_Data {

	/**
	 * Token Data (stored in the payment_tokens table).
	 * @var array
	 */
	protected $_data = array(
		 'id'         => 0,
		 'gateway_id' => '',
		 'token'      => '',
		 'is_default' => 0,
		 'user_id'    => 0,
	);

	/**
	 * Meta type. Payment tokens are a new object type.
	 * @var string
	 */
	protected $_meta_type = 'payment_token';

	 /**
	 * Initialize a payment token.
	 *
	 * These fields are accepted by all payment tokens:
	 * is_default   - boolean Optional - Indicates this is the default payment token for a user
	 * token        - string  Required - The actual token to store
	 * gateway_id   - string  Required - Identifier for the gateway this token is associated with
	 * user_id      - int     Optional - ID for the user this token is associated with. 0 if this token is not associated with a user
	 *
	 * @since 2.6.0
	 * @param mixed $token
	 */
	public function __construct( $token = '' ) {
		if ( is_numeric( $token ) ) {
			$this->read( $token );
		} else if ( is_object( $token ) ) {
			$token_id = $token->get_id();
			if ( ! empty( $token_id ) ) {
				$this->read( $token->get_id() );
			}
		}
		// Set token type (cc, echeck)
		if ( ! empty( $this->type ) ) {
			$this->_data['type'] = $this->type;
		}
	}

	/**
	 * Returns the payment token ID.
	 * @since 2.6.0
	 * @return integer Token ID
	 */
	public function get_id() {
		return absint( $this->_data['id'] );
	}

	/**
	 * Returns the raw payment token.
	 * @since 2.6.0
	 * @return string Raw token
	 */
	public function get_token() {
		return $this->_data['token'];
	}

	/**
	 * Set the raw payment token.
	 * @since 2.6.0
	 * @param string $token
	 */
	public function set_token( $token ) {
		$this->_data['token'] = $token;
	}

	/**
	 * Returns the type of this payment token (CC, eCheck, or something else).
	 * @since 2.6.0
	 * @return string Payment Token Type (CC, eCheck)
	 */
	public function get_type() {
		return isset( $this->_data['type'] ) ? $this->_data['type'] : '';
	}

	/**
	 * Get type to display to user.
	 * @return string
	 */
	public function get_display_name() {
		return $this->get_type();
	}

	/**
	 * Returns the user ID associated with the token or false if this token is not associated.
	 * @since 2.6.0
	 * @return int User ID if this token is associated with a user or 0 if no user is associated
	 */
	public function get_user_id() {
		return ( isset( $this->_data['user_id'] ) && $this->_data['user_id'] > 0 ) ? absint( $this->_data['user_id'] ) : 0;
	}

	/**
	 * Set the user ID for the user associated with this order.
	 * @since 2.6.0
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->_data['user_id'] = absint( $user_id );
	}

	/**
	 * Returns the ID of the gateway associated with this payment token.
	 * @since 2.6.0
	 * @return string Gateway ID
	 */
	public function get_gateway_id() {
		return $this->_data['gateway_id'];
	}

	/**
	 * Set the gateway ID.
	 * @since 2.6.0
	 * @param string $gateway_id
	 */
	public function set_gateway_id( $gateway_id ) {
		$this->_data['gateway_id'] = $gateway_id;
	}

	/**
	 * Returns if the token is marked as default.
	 * @since 2.6.0
	 * @return boolean True if the token is default
	 */
	public function is_default() {
		return ! empty( $this->_data['is_default'] );
	}

	/**
	 * Marks the payment as default or non-default.
	 * @since 2.6.0
	 * @param boolean $is_default True or false
	 */
	public function set_default( $is_default ) {
		$this->_data['is_default'] = (bool) $is_default;
	}

	/**
	 * Validate basic token info (token and type are required).
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( empty( $this->_data['token'] ) ) {
			return false;
		}

		if ( empty( $this->_data['type'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get a token from the database.
	 * @since 2.6.0
	 * @param  int $token_id Token ID
	 */
	public function read( $token_id ) {
		global $wpdb;
		if ( $token = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d LIMIT 1;", $token_id ) ) ) {
			$token_id = $token->token_id;
			$token = (array) $token;
			unset( $token['token_id'] );
			$this->_data = $token;
			$this->_data['id'] = $token_id;
			$this->read_meta_data();
		}
	}

	/**
	 * Update a payment token.
	 * @since 2.6.0
	 * @return boolean on success, false if validation failed and a payment token could not be updated
	 */
	public function update() {
		if ( false === $this->validate() ) {
			return false;
		}

		global $wpdb;

		$payment_token_data = array(
			'gateway_id' => $this->get_gateway_id(),
			'token'      => $this->get_token(),
			'user_id'    => $this->get_user_id(),
			'type'       => $this->get_type(),
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_payment_tokens',
			$payment_token_data,
			array( 'token_id' => $this->get_id() )
		);

		$this->save_meta_data();

		// Make sure all other tokens are not set to default
		if ( $this->is_default() && $this->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $this->get_user_id(), $this->get_id() );
		}

		do_action( 'woocommerce_payment_token_updated', $this->get_id() );
		return true;
	}

	/**
	 * Create a new payment token in the database.
	 * @since 2.6.0
	 * @return boolean on success, false if validation failed and a payment token could not be created
	 */
	public function create() {
		if ( false === $this->validate() ) {
			return false;
		}

		global $wpdb;
		// Are there any other tokens? If not, set this token as default
		if ( ! $this->is_default() && $this->get_user_id() > 0 ) {
			$default_token = WC_Payment_Tokens::get_customer_default_token( $this->get_user_id() );
			if ( is_null( $default_token ) ) {
				$this->set_default( true );
			}
		}

		$payment_token_data = array(
			'gateway_id' => $this->get_gateway_id(),
			'token'      => $this->get_token(),
			'user_id'    => $this->get_user_id(),
			'type'       => $this->get_type(),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $payment_token_data );
		$this->_data['id'] = $token_id = $wpdb->insert_id;
		$this->save_meta_data();

		// Make sure all other tokens are not set to default
		if ( $this->is_default() && $this->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $this->get_user_id(), $token_id );
		}

		do_action( 'woocommerce_payment_token_created', $token_id );
		return true;
	}

	/**
	 * Saves a payment token to the database - does not require you to know if this is a new token or an update token.
	 * @since 2.6.0
	 * @return boolean on success, false if validation failed and a payment token could not be saved
	 */
	public function save() {
		if ( $this->get_id() > 0 ) {
			return $this->update();
		} else {
			return $this->create();
		}
	}

	/**
	 * Remove a payment token from the database.
	 * @since 2.6.0
	 */
	public function delete() {
		global $wpdb;
		$this->read( $this->get_id() ); // Make sure we have a token to return after deletion
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $this->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokenmeta', array( 'payment_token_id' => $this->get_id() ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $this->get_id(), $this );
	}

}
