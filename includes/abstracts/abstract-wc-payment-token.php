<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Token Meta API - set table name
 */
function wc_payment_token_metadata_wpdbfix() {
	global $wpdb;
	$wpdb->payment_tokenmeta = $wpdb->prefix . 'woocommerce_payment_tokenmeta';
	$wpdb->tables[] = 'woocommerce_payment_tokenmeta';
}
add_action( 'init', 'wc_payment_token_metadata_wpdbfix', 0 );

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
 abstract class WC_Payment_Token implements WC_Data {

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
	 * token        - string  Required - The actual token to store
	 * gateway_id   - string  Required - Identifier for the gateway this token is associated with
	 * user_id      - int     Optional - ID for the user this token is associated with. 0 if this token is not associated with a user
	 *
	 * @since 2.6.0
	 * @param string $id Token ID
	 * @param array $data Core token data
	 * @param array $meta Meta token data
	 */
	public function __construct( $id = 0, $data = array(), $meta = array() ) {
		$this->id = $id;
		$this->data = $data;
		$this->data['type'] = $this->type;
		$this->meta = $meta;
	}

	/**
	 * Returns the payment token ID
	 *
	 * @since 2.6.0
	 * @return ID Token ID
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
	 * Returns the type of this payment token (CC, eCheck, or something else)
	 *
	 * @since 2.6.0
	 * @return string Payment Token Type (CC, eCheck)
	 */
	public function get_type() {
		return isset( $this->data['type'] ) ? $this->data['type'] : '';
	}

	/**
	 * Returns the user ID associated with the token or false if this token is not associated
	 *
	 * @since 2.6.0
	 * @return int User ID if this token is associated with a user or 0 if no user is associated
	 */
	public function get_user_id() {
		return ( isset( $this->data['user_id'] ) && $this->data['user_id'] > 0 ) ? $this->data['user_id'] : 0;
	}

	/**
	 * Set the user ID for the user associated with this order
	 *
	 * @since 2.6.0
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->data['user_id'] = $user_id;
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
		return ! empty( $this->data['is_default'] );
	}

	/**
	 * Marks the payment as default or non-default
	 *
	 * @since 2.6.0
	 * @param boolean $is_default True or false
	 */
	public function set_default( $is_default ) {
		$this->data['is_default'] = (bool) $is_default;
	}

	/**
	 * Returns a dump of the token data (combined data and meta)
	 *
	 * @since 2.6.0
	 * @return mixed array representation
	 */
	public function get_data() {
		return array_merge( $this->data, array( 'meta' => $this->meta ) );
	}

	/**
	 * Validate basic token info (token and type are required)
	 *
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		if ( empty( $this->data['token'] ) ) {
			return false;
		}

		if ( empty( $this->data['type'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get a token from the database
	 *
	 * @since 2.6.0
	 * @param  int $token_id Token ID
	 */
	public function read( $token_id ) {
		global $wpdb;
		if ( $token = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d LIMIT 1;", $token_id ) ) ) {
			$this->id = $token->token_id;
			$token = (array) $token;
			unset( $token['token_id'] );
			$this->data = $token;
			$meta =  get_metadata( 'payment_token', $token_id );
			$passed_meta = array();
			if ( ! empty( $meta ) ) {
				foreach( $meta as $meta_key => $meta_value ) {
					$passed_meta[ $meta_key ] = $meta_value[0];
				}
			}
			$this->meta = $passed_meta;
		}
	}

	/**
	 * Update a payment token
	 *
	 * @since 2.6.0
	 * @return True on success, false if validation failed and a payment token could not be updated
	 */
	public function update() {
		if ( false === $this->validate() ) {
			return false;
		}

		global $wpdb;

		$wpdb->update( $wpdb->prefix . 'woocommerce_payment_tokens', $this->data, array( 'token_id' => $this->get_id() ) );
		foreach ( $this->meta as $meta_key => $meta_value ) {
			update_metadata( 'payment_token', $this->get_id(), $meta_key, $meta_value );
		}

		do_action( 'woocommerce_payment_token_updated', $this->get_id() );
		return true;
	}

	/**
	 * Create a new payment token in the database
	 *
	 * @since 2.6.0
	 * @return True on success, false if validation failed and a payment token could not be created
	 */
	public function create() {
		if ( false === $this->validate() ) {
			return false;
		}

		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $this->data );
		$this->id = $token_id = $wpdb->insert_id;
		foreach ( $this->meta as $meta_key => $meta_value ) {
			add_metadata( 'payment_token', $token_id, $meta_key, $meta_value, true );
		}

		do_action( 'woocommerce_payment_token_created', $token_id );
		return true;
	}

	/**
	 * Saves a payment token to the database - does not require you to know if this is a new token or an update token
	 *
	 * @since 2.6.0
	 * @return True on success, false if validation failed and a payment token could not be saved
	 */
	public function save() {
		if ( $this->get_id() > 0 ) {
			return $this->update();
		} else {
			return $this->create();
		}
	}

	/**
	 * Remove a payment token from the database
	 */
	public function delete() {
		global $wpdb;
		$this->read( $this->get_id() ); // Make sure we have a token to return after deletion
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $this->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokenmeta', array( 'payment_token_id' => $this->get_id() ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $this->get_id(), $this );
	}

}
