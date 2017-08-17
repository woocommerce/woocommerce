<?php
/**
 * Cart session handling class.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cart_Session class.
 *
 * @since 3.2.0
 */
final class WC_Cart_Session {

	/**
	 * Reference to cart object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	protected $cart;

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 3.2.0
	 * @param object $cart Cart object to calculate totals for.
	 */
	public function __construct( &$cart = null ) {
		$this->cart = $cart;

		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'set_session' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'set_session' ) );
		add_action( 'woocommerce_removed_coupon', array( $this, 'set_session' ) );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 *
	 * @since 3.2.0
	 */
	public function get_cart_from_session() {
		$cart = wp_parse_args( (array) WC()->session->get( 'cart', array() ), array(
			'items'         => array(),
			'removed_items' => array(),
			'coupons'       => array(),
			'totals'        => null,
		) );






		foreach ( $this->cart_session_data as $key => $default ) {
			$this->$key = WC()->session->get( $key, $default );
		}

		$update_cart_session         = false;
		$this->removed_cart_contents = array_filter( WC()->session->get( 'removed_cart_contents', array() ) );
		$this->applied_coupons       = array_filter( WC()->session->get( 'applied_coupons', array() ) );

		/**
		 * Load the cart object. This defaults to the persistent cart if null.
		 */
		$cart = WC()->session->get( 'cart', null );

		if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), true ) ) ) {
			$cart                = $saved_cart['cart'];
			$update_cart_session = true;
		} elseif ( is_null( $cart ) ) {
			$cart = array();
		}

		if ( is_array( $cart ) ) {
			// Prime meta cache to reduce future queries.
			update_meta_cache( 'post', wp_list_pluck( $cart, 'product_id' ) );
			update_object_term_cache( wp_list_pluck( $cart, 'product_id' ), 'product' );

			foreach ( $cart as $key => $values ) {
				$product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );

				if ( ! empty( $product ) && $product->exists() && $values['quantity'] > 0 ) {

					if ( ! $product->is_purchasable() ) {
						$update_cart_session = true; // Flag to indicate the stored cart should be updated.
						/* translators: %s: product name */
						wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $product->get_name() ), 'error' );
						do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

					} else {

						// Put session data into array. Run through filter so other plugins can load their own session data.
						$session_data = array_merge( $values, array( 'data' => $product ) );
						$this->cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );
					}
				}
			}
		}

		do_action( 'woocommerce_cart_loaded_from_session', $this );

		if ( $update_cart_session ) {
			WC()->session->cart = $this->get_cart_for_session();
		}

		// Queue re-calc if subtotal is not set.
		if ( ( ! $this->subtotal && ! $this->is_empty() ) || $update_cart_session ) {
			$this->calculate_totals();
		}
	}

	/**
	 * Destroy cart session data.
	 *
	 * @since 3.2.0
	 */
	public function destroy_cart_session( $deprecated = true ) {
		WC()->session->set( 'cart', null );
	}

	/**
	 * Will set cart cookies if needed and when possible.
	 *
	 * @since 3.2.0
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
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		$cart_session = $this->get_cart_for_session();

		WC()->session->set( 'cart', $cart_session );
		WC()->session->set( 'applied_coupons', $this->applied_coupons );
		WC()->session->set( 'coupon_discount_amounts', $this->coupon_discount_amounts );
		WC()->session->set( 'coupon_discount_tax_amounts', $this->coupon_discount_tax_amounts );
		WC()->session->set( 'removed_cart_contents', $this->removed_cart_contents );

		foreach ( $this->cart_session_data as $key => $default ) {
			WC()->session->set( $key, $this->$key );
		}

		if ( get_current_user_id() ) {
			$this->persistent_cart_update();
		}

		do_action( 'woocommerce_cart_updated' );
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
				unset( $cart_session[ $key ]['data'] ); // Unset product object.
			}
		}

		return $cart_session;
	}


	/**
	 * Set cart hash cookie and items in cart.
	 *
	 * @access private
	 * @param bool $set Should cookies be set (true) or unset.
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			wc_setcookie( 'woocommerce_items_in_cart', 1 );
			wc_setcookie( 'woocommerce_cart_hash', md5( wp_json_encode( $this->get_cart_for_session() ) ) );
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}



	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), array(
			'cart' => WC()->session->get( 'cart' ),
		) );
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id() );
	}
}
