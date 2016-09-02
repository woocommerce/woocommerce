<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart Session Handler.
 *
 * @class 		WC_Cart_Session
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Cart_Session {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'woocommerce_cart_emptied', 'destroy_cart_session' );
	}

	/// @todo
	public function destroy_cart_session() {
		unset( WC()->session->order_awaiting_payment, WC()->session->applied_coupons, WC()->session->coupon_discount_amounts, WC()->session->coupon_discount_tax_amounts, WC()->session->cart );

		if ( $clear_persistent_cart && get_current_user_id() ) {
			$this->persistent_cart_destroy();
		}
	}

	/**
	 * Will set cart cookies if needed, once, during WP hook.
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Set cart hash cookie and items in cart.
	 *
	 * @access private
	 * @param bool $set (default: true)
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			wc_setcookie( 'woocommerce_items_in_cart', 1 );
			wc_setcookie( 'woocommerce_cart_hash', md5( json_encode( $this->get_cart_for_session() ) ) );
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 */
	public function get_cart_from_session() {
		// Load cart session data from session
		foreach ( $this->session_data as $key => $default ) {
			$this->$key = WC()->session->get( $key, $default );
		}

		$update_cart_session         = false;
		$this->removed_cart_contents = array_filter( WC()->session->get( 'removed_cart_contents', array() ) );
		$this->applied_coupons       = array_filter( WC()->session->get( 'applied_coupons', array() ) );

		/**
		 * Load the cart object. This defaults to the persistent cart if null.
		 */
		$cart = WC()->session->get( 'cart', null );

		if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', true ) ) ) {
			$cart                = $saved_cart['cart'];
			$update_cart_session = true;
		} elseif ( is_null( $cart ) ) {
			$cart = array();
		}

		if ( is_array( $cart ) ) {
			// Prime meta cache to reduce future queries
			update_meta_cache( 'post', wp_list_pluck( $cart, 'product_id' ) );

			foreach ( $cart as $key => $values ) {
				$_product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );

				if ( ! empty( $_product ) && $_product->exists() && $values['quantity'] > 0 ) {

					if ( ! $_product->is_purchasable() ) {

						// Flag to indicate the stored cart should be update
						$update_cart_session = true;
						wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $_product->get_title() ), 'error' );
						do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

					} else {

						// Put session data into array. Run through filter so other plugins can load their own session data
						$session_data = array_merge( $values, array( 'data' => $_product ) );
						$this->cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );

					}
				}
			}
		}

		// Trigger action
		do_action( 'woocommerce_cart_loaded_from_session', $this );

		if ( $update_cart_session ) {
			WC()->session->cart = $this->get_cart_for_session();
		}

		// Queue re-calc if subtotal is not set
		if ( ( ! $this->subtotal && ! $this->is_empty() ) || $update_cart_session ) {
			$this->calculate_totals();
		}
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		if ( $this->get_cart() ) {
			foreach ( $this->get_cart() as $key => $values ) {
				$cart_session[ $key ] = $values;
				unset( $cart_session[ $key ]['data'] ); // Unset product object
			}
		}

		return $cart_session;
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		// Set cart and coupon session data
		$cart_session = $this->get_cart_for_session();

		WC()->session->set( 'cart', $cart_session );
		WC()->session->set( 'applied_coupons', $this->applied_coupons );
		WC()->session->set( 'coupon_discount_amounts', $this->coupon_discount_amounts );
		WC()->session->set( 'coupon_discount_tax_amounts', $this->coupon_discount_tax_amounts );
		WC()->session->set( 'removed_cart_contents', $this->removed_cart_contents );

		foreach ( $this->session_data as $key => $default ) {
			WC()->session->set( $key, $this->$key );
		}

		if ( get_current_user_id() ) {
			$this->persistent_cart_update();
		}

		do_action( 'woocommerce_cart_updated' );
	}

	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart', array(
			'cart' => WC()->session->get( 'cart' ),
		) );
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart' );
	}
}
