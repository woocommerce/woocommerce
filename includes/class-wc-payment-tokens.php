<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Tokens
 *
 * An API for storing and managing tokens for gateways and customers.
 *
 * @class 		WC_Payment_Tokens
 * @since		2.6.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author		WooThemes
 */
class WC_Payment_Tokens {

	/**
	 * Makes sure that the passed data is suitable for storing a token
	 * @param  array $args  An array of arguments (can be meta or core token field) to validate
	 * @return boolean      True if the passed data is valid
	 */
	public static function validate( $args ) {
		if ( empty( $args['token'] ) ) {
			return false;
		}

		if ( empty( $args['type'] ) ) {
			return false;
		}

		// If 'token' and 'type' are present, validate that any specific token types (credit card for example)
		// contain their neccesary fields.
		$is_valid = true;
		$token_class = 'WC_Payment_Token_' . $args['type'];
		if ( is_callable( $token_class, 'validate' ) ) {
			$is_valid = $token_class::validate( $args );
		}

		return apply_filters( 'woocommerce_payment_token_validate', $is_valid, $args );
	}

	/**
	 * Creates, stores in the database, and returns a new payment token object
	 * @param  array $args  An array of arguments (meta or core token fields)
	 * @return WC_Payment_Token
	 */
	public static function create( $args ) {
		if ( ! self::validate( $args ) ) {
			return; // @todo throw an error
		}

		global $wpdb;

		$meta = $args['meta'];
		unset( $args['token_id'], $args['meta'] );

		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $args );
		$token_id = $wpdb->insert_id;
		foreach ( $meta as $meta_key => $meta_value ) {
			add_metadata( 'payment_token', $token_id, $meta_key, $meta_value, true );
		}

		do_action( 'woocommerce_payment_token_created', $token_id );
		return self::generate_token( $token_id, $args );
	}

	/**
	 * Updates (stores in the database), and returns a payment token object
	 * @param int               $token_id Token ID
	 * @param  WC_Payment_Token $token    An existing payment token object (updated fields can be changed with set_)
	 * @return WC_Payment_Token Updated token
	 */
	public static function update( $token_id, $token ) {
		if ( ! ( $token instanceof WC_Payment_Token ) ) {
			return; // @todo throw an error
		}

		$args = $token->__data_format();
		$meta = $args['meta'];

		if ( ! self::validate( $args ) ) {
			return; // @todo throw an error
		}

		unset( $args['token_id'], $args['meta'] );
		global $wpdb;

		$wpdb->update( $wpdb->prefix . 'woocommerce_payment_tokens', $args, array( 'token_id' => $token_id ) );
		foreach ( $meta as $meta_key => $meta_value ) {
			update_metadata( 'payment_token', $token_id, $meta_key, $meta_value );
		}

		do_action( 'woocommerce_payment_token_updated', $token_id );
		return self::generate_token( $token_id, $args );
	}

	/**
	 * Remove a payment token from the database
	 * @param int $token_id Token ID
	 */
	public static function delete( $token_id ) {
		global $wpdb;

		// Generate a token object for this token so we can return it in our action hook
		$token_result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
			$token_id
		) );
		$_token = self::generate_token( $token_id, (array) $token_result );

		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $token_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokenmeta', array( 'payment_token_id' => $token_id ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $token_id, $_token );
	}

	/**
	 * Returns an array of payment token objects associated with the passed customer ID
	 * @param int $customer_id Customer ID
	 * @return array Array of token objects
	 */
	public static function get_customer_tokens( $customer_id ) {
		if ( $customer_id < 1 ) {
			return array();
		}

		global $wpdb;

		$token_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE user_id = %d",
			$customer_id
		) );

		if ( empty( $token_results ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $token_results as $token_result ) {
			$_token = self::generate_token( $token_result->token_id, (array) $token_result );
			if ( ! empty( $_token ) ) {
				$tokens[ $token_result->token_id ] = $_token;
			}
		}

		return apply_filters( 'woocommerce_get_customer_payment_tokens', $tokens, $customer_id );
	}

	/**
	 * Returns an array of payment token objects associated with the passed order ID
	 * @param int $order_id Order ID
	 * @return array Array of token objects
	 */
	public static function get_order_tokens( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		$token_ids = get_post_meta( $order_id, '_payment_tokens', true );
		if ( empty ( $token_ids ) ) {
			return array();
		}

		global $wpdb;

		$token_ids_as_string = implode( ',', array_map( 'intval', $token_ids ) );
		$token_results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id IN ( {$token_ids_as_string} )"
		);

		if ( empty( $token_results ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $token_results as $token_result ) {
			$_token = self::generate_token( $token_result->token_id, (array) $token_result );
			if ( ! empty( $_token ) ) {
				$tokens[ $token_result->token_id ] = $_token;
			}
		}

		return apply_filters( 'woocommerce_get_order_payment_tokens', $tokens, $order_id );
	}

	/**
	 * Generates a token object
	 * @param  int $token_id        ID of the token being returned
	 * @param  array $token_result  Internal data values for the token
	 * @return WC_Payment_Token
	 */
	private static function generate_token( $token_id, $token_result ) {
		global $wpdb;
		$token_class = 'WC_Payment_Token_' . $token_result['type'];
		if ( class_exists( $token_class ) ) {
			$meta =  get_metadata( 'payment_token', $token_id );
			$passed_meta = array();
			if ( ! empty( $meta ) ) {
				foreach( $meta as $meta_key => $meta_value ) {
					$passed_meta[ $meta_key ] = $meta_value[0];
				}
			}
			return new $token_class( $token_id, $token_result, $passed_meta );
		}
	}

}
