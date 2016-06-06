<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Payment Tokens.
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
	 * Gets valid tokens from the database based on user defined criteria.
	 * @param  array $args
	 * @return array
	 */
	public static function get_tokens( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'token_id'   => '',
			'user_id'    => '',
			'gateway_id' => '',
			'type'       => '',
		) );

		$sql   = "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens";
		$where = array( '1=1' );

		if ( $args['token_id'] ) {
			$token_ids = array_map( 'absint', is_array( $args['token_id'] ) ? $args['token_id'] : array( $args['token_id'] ) );
			$where[]   = "token_id IN ('" . implode( "','", array_map( 'esc_sql', $token_ids ) ) . "')";
		}

		if ( $args['user_id'] ) {
			$where[] = 'user_id = ' . absint( $args['user_id'] );
		}

		if ( $args['gateway_id'] ) {
			$gateway_ids = array( $args['gateway_id'] );
		} else {
			$gateways    = WC_Payment_Gateways::instance();
			$gateway_ids = $gateways->get_payment_gateway_ids();
		}

		$gateway_ids[] = '';
		$where[]       = "gateway_id IN ('" . implode( "','", array_map( 'esc_sql', $gateway_ids ) ) . "')";

		if ( $args['type'] ) {
			$where[] = 'type = ' . esc_sql( $args['type'] );
		}

		$token_results = $wpdb->get_results( $sql . ' WHERE ' . implode( ' AND ', $where ) );
		$tokens        = array();

		if ( ! empty( $token_results ) ) {
			foreach ( $token_results as $token_result ) {
				$_token = self::get( $token_result->token_id, $token_result );
				if ( ! empty( $_token ) ) {
					$tokens[ $token_result->token_id ] = $_token;
				}
			}
		}

		return $tokens;
	}

	/**
	 * Returns an array of payment token objects associated with the passed customer ID.
	 * @since 2.6.0
	 * @param  int    $customer_id  Customer ID
	 * @param  string $gateway_id      Optional Gateway ID for getting tokens for a specific gateway
	 * @return array                Array of token objects
	 */
	public static function get_customer_tokens( $customer_id, $gateway_id = '' ) {
		if ( $customer_id < 1 ) {
			return array();
		}

		$tokens = self::get_tokens( array(
			'user_id'    => $customer_id,
			'gateway_id' => $gateway_id
		) );

		return apply_filters( 'woocommerce_get_customer_payment_tokens', $tokens, $customer_id, $gateway_id );
	}

	/**
	 * Returns a customers default token or NULL if there is no default token.
	 * @since 2.6.0
	 * @param  int $customer_id
	 * @return WC_Payment_Token|null
	 */
	public static function get_customer_default_token( $customer_id ) {
		if ( $customer_id < 1 ) {
			return null;
		}

		global $wpdb;

		$token = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE user_id = %d AND is_default = 1",
			$customer_id
		) );

		if ( $token ) {
			return self::get( $token->token_id, $token );
		} else {
			return null;
		}
	}

	/**
	 * Returns an array of payment token objects associated with the passed order ID.
	 * @since 2.6.0
	 * @param int $order_id Order ID
	 * @return array Array of token objects
	 */
	public static function get_order_tokens( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		$token_ids = get_post_meta( $order_id, '_payment_tokens', true );
		if ( empty( $token_ids ) ) {
			return array();
		}

		$tokens = self::get_tokens( array(
			'token_id' => $token_ids
		) );

		return apply_filters( 'woocommerce_get_order_payment_tokens', $tokens, $order_id );
	}

	/**
	 * Get a token object by ID.
	 * @since 2.6.0
	 * @param  int $token_id Token ID
	 * @return WC_Payment_Token|null Returns a valid payment token or null if no token can be found
	 */
	public static function get( $token_id, $token_result = null ) {
		global $wpdb;

		if ( is_null( $token_result ) ) {
			$token_result = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
				$token_id
			) );

			// Still empty? Token doesn't exist? Don't continue
			if ( empty( $token_result ) ) {
				return null;
			}
		}

		$token_class = 'WC_Payment_Token_' . $token_result->type;

		if ( class_exists( $token_class ) ) {
			$meta =  get_metadata( 'payment_token', $token_id );
			$passed_meta = array();
			if ( ! empty( $meta ) ) {
				foreach( $meta as $meta_key => $meta_value ) {
					$passed_meta[ $meta_key ] = $meta_value[0];
				}
			}
			return new $token_class( $token_id, (array) $token_result, $passed_meta );
		}

		return null;
	}

	/**
	 * Remove a payment token from the database by ID.
	 * @since 2.6.0
	 * @param WC_Payment_Token $token_id Token ID
	 */
	public static function delete( $token_id ) {
		$type = self::get_token_type_by_id( $token_id );
		if ( ! empty ( $type ) ) {
			$class = 'WC_Payment_Token_' . $type;
			$token = new $class( $token_id );
			$token->delete();
		}
	}

	/**
	 * Loops through all of a users payment tokens and sets is_default to false for all but a specific token.
	 * @since 2.6.0
	 * @param int $user_id  User to set a default for
	 * @param int $token_id The ID of the token that should be default
	 */
	public static function set_users_default( $user_id, $token_id ) {
		global $wpdb; // DB queries so we avoid an  infinite loop (update & create use this function)
		$users_tokens = self::get_customer_tokens( $user_id );
		foreach ( $users_tokens as $token ) {
			if ( $token_id === $token->get_id() ) {
				$token->set_default( true );
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_payment_tokens',
					array( 'is_default' => 1 ),
					array( 'token_id' => $token->get_id(),
				) );

				do_action( 'woocommerce_payment_token_set_default', $token_id, $token );
			} else {
				$token->set_default( false );
				$wpdb->update(
					$wpdb->prefix . 'woocommerce_payment_tokens',
					array( 'is_default' => 0 ),
					array( 'token_id' => $token->get_id(),
				) );
			}
		}
	}

	/**
	 * Returns what type (credit card, echeck, etc) of token a token is by ID.
	 * @since 2.6.0
	 * @param  int $token_id Token ID
	 * @return string        Type
	 */
	public static function get_token_type_by_id( $token_id ) {
		global $wpdb;
		$type = $wpdb->get_var( $wpdb->prepare(
			"SELECT type FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
			$token_id
		) );
		return $type;
	}

}
