<?php
/**
 * Cart session handling class.
 *
 * @package WooCommerce/Classes
 * @version 3.2.0
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
	 * @var WC_Cart
	 */
	protected $cart;

	/**
	 * Sets up the items provided, and calculate totals.
	 *
	 * @since 3.2.0
	 * @throws Exception If missing WC_Cart object.
	 * @param WC_Cart $cart Cart object to calculate totals for.
	 */
	public function __construct( &$cart ) {
		if ( ! is_a( $cart, 'WC_Cart' ) ) {
			throw new Exception( 'A valid WC_Cart object is required' );
		}

		$this->cart = $cart;
	}

	/**
	 * Register methods for this object on the appropriate WordPress hooks.
	 */
	public function init() {
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'set_session' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'set_session' ) );
		add_action( 'woocommerce_removed_coupon', array( $this, 'set_session' ) );
		add_action( 'woocommerce_cart_updated', array( $this, 'persistent_cart_update' ) );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 *
	 * @since 3.2.0
	 */
	public function get_cart_from_session() {
		// Flag to indicate the stored cart should be updated.
		$update_cart_session = false;
		$totals              = WC()->session->get( 'cart_totals', null );
		$cart                = WC()->session->get( 'cart', null );

		$this->cart->set_totals( $totals );
		$this->cart->set_applied_coupons( WC()->session->get( 'applied_coupons', array() ) );
		$this->cart->set_coupon_discount_totals( WC()->session->get( 'coupon_discount_totals', array() ) );
		$this->cart->set_coupon_discount_tax_totals( WC()->session->get( 'coupon_discount_tax_totals', array() ) );
		$this->cart->set_removed_cart_contents( WC()->session->get( 'removed_cart_contents', array() ) );

		if ( apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) {
			$saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
		} else {
			$saved_cart = false;
		}

		if ( is_null( $cart ) && $saved_cart ) {
			$cart                = $saved_cart['cart'];
			$update_cart_session = true;
		} elseif ( is_null( $cart ) ) {
			$cart = array();
		} elseif ( is_array( $cart ) && $saved_cart ) {
			$cart                = array_merge( $saved_cart['cart'], $cart );
			$update_cart_session = true;
		}

		if ( is_array( $cart ) ) {
			$cart_contents = array();

			// Prime caches to reduce future queries.
			if ( is_callable( '_prime_post_caches' ) ) {
				_prime_post_caches( wp_list_pluck( $cart, 'product_id' ) );
			}

			foreach ( $cart as $key => $values ) {
				$product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );

				if ( ! is_customize_preview() && 'customize-preview' === $key ) {
					continue;
				}

				if ( ! empty( $product ) && $product->exists() && $values['quantity'] > 0 ) {

					if ( ! $product->is_purchasable() ) {
						$update_cart_session = true;
						/* translators: %s: product name */
						wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $product->get_name() ), 'error' );
						do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

					} elseif ( ! empty( $values['data_hash'] ) && ! hash_equals( $values['data_hash'], wc_get_cart_item_data_hash( $product ) ) ) { // phpcs:ignore PHPCompatibility.PHP.NewFunctions.hash_equalsFound
						$update_cart_session = true;
						/* translators: %1$s: product name. %2$s product permalink */
						wc_add_notice( sprintf( __( '%1$s has been removed from your cart because it has since been modified. You can add it back to your cart <a href="%2$s">here</a>.', 'woocommerce' ), $product->get_name(), $product->get_permalink() ), 'notice' );
						do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

					} else {
						// Put session data into array. Run through filter so other plugins can load their own session data.
						$session_data          = array_merge(
							$values, array(
								'data' => $product,
							)
						);
						$cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );

						// Add to cart right away so the product is visible in woocommerce_get_cart_item_from_session hook.
						$this->cart->set_cart_contents( $cart_contents );
					}
				}
			}
		}

		do_action( 'woocommerce_cart_loaded_from_session', $this->cart );

		if ( $update_cart_session || is_null( WC()->session->get( 'cart_totals', null ) ) ) {
			WC()->session->set( 'cart', $this->get_cart_for_session() );
			$this->cart->calculate_totals();
		}
	}

	/**
	 * Destroy cart session data.
	 *
	 * @since 3.2.0
	 */
	public function destroy_cart_session() {
		WC()->session->set( 'cart', null );
		WC()->session->set( 'cart_totals', null );
		WC()->session->set( 'applied_coupons', null );
		WC()->session->set( 'coupon_discount_totals', null );
		WC()->session->set( 'coupon_discount_tax_totals', null );
		WC()->session->set( 'removed_cart_contents', null );
		WC()->session->set( 'order_awaiting_payment', null );
	}

	/**
	 * Will set cart cookies if needed and when possible.
	 *
	 * @since 3.2.0
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->cart->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) { // WPCS: input var ok.
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		WC()->session->set( 'cart', $this->get_cart_for_session() );
		WC()->session->set( 'cart_totals', $this->cart->get_totals() );
		WC()->session->set( 'applied_coupons', $this->cart->get_applied_coupons() );
		WC()->session->set( 'coupon_discount_totals', $this->cart->get_coupon_discount_totals() );
		WC()->session->set( 'coupon_discount_tax_totals', $this->cart->get_coupon_discount_tax_totals() );
		WC()->session->set( 'removed_cart_contents', $this->cart->get_removed_cart_contents() );

		do_action( 'woocommerce_cart_updated' );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		foreach ( $this->cart->get_cart() as $key => $values ) {
			$cart_session[ $key ] = $values;
			unset( $cart_session[ $key ]['data'] ); // Unset product object.
		}

		return $cart_session;
	}

	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		if ( get_current_user_id() && apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) {
			update_user_meta(
				get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), array(
					'cart' => $this->get_cart_for_session(),
				)
			);
		}
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		if ( get_current_user_id() && apply_filters( 'woocommerce_persistent_cart_enabled', true ) ) {
			delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id() );
		}
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
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) { // WPCS: input var ok.
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}
}
