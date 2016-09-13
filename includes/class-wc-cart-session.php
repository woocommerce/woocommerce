<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/class-wc-legacy-cart.php' );

/**
 * Cart session class.
 * @version		2.7.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
abstract class WC_Cart_Session extends WC_Legacy_Cart {

	/**
	 * Cart items class.
	 * @var WC_Cart_Items
	 */
	protected $items;

	/**
	 * Cart coupons class.
	 * @var WC_Cart_Coupons
	 */
	protected $coupons;

	/**
	 * Cart fees class.
	 * @var WC_Cart_Fees
	 */
	public $fees;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->items   = new WC_Cart_Items;
		$this->coupons = new WC_Cart_Coupons;
		$this->fees    = new WC_Cart_Fees;
		add_action( 'wp_loaded', array( $this, 'get_cart_from_session' ) );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'maybe_set_cart_cookies' ) );
		add_action( 'woocommerce_cart_emptied', array( $this, 'destroy_cart_session' ) );
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'set_session' ) );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'set_session' ) );
		add_action( 'woocommerce_removed_coupon', array( $this, 'set_session' ) );
	}

	/**
	 * Destroy cart session data.
	 */
	public function destroy_cart_session( $deprecated = true ) {
		WC()->session->set( 'cart', null );
	}

	/**
	 * Will set cart cookies if needed, once, during WP hook.
	 */
	public function maybe_set_cart_cookies() {
		if ( headers_sent() || ! did_action( 'wp_loaded' ) ) {
			return;
		}
		if ( ! $this->is_empty() ) {
			$this->set_cart_cookies( true );
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			$this->set_cart_cookies( false );
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
		} else {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		return wc_list_pluck( $this->items->get_items(), 'get_data' );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 */
	public function get_cart_from_session() {
		$cart = wp_parse_args( (array) WC()->session->get( 'cart', array() ), array(
			'items'         => array(),
			'removed_items' => array(),
			'coupons'       => array(),
		) );

		foreach ( $cart['items'] as $key => $values ) {
			if ( ! isset( $values['product_id'], $values['quantity'] ) || ! ( $product = wc_get_product( $values['product_id'] ) ) ) {
				unset( $cart['items'][ $key ] );
				continue;
			}
			// Put session data into array. Run through filter so other plugins can load their own session data.
			$cart['items'][ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $values, $values, $key );
		}

		$this->items->set_items( $cart['items'] );
		$this->items->set_removed_items( $cart['removed_items'] );
		$this->coupons->set_coupons( $cart['coupons'] );

		do_action( 'woocommerce_cart_loaded_from_session', $this );
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		$session_data = array(
			'items'         => $this->get_cart_for_session(),
			'removed_items' => wc_list_pluck( $this->items->get_removed_items(), 'get_data' ),
			'coupons'       => $this->coupons->get_coupons(),
		);
		if ( WC()->session->set( 'cart', $session_data ) ) {
			do_action( 'woocommerce_cart_updated' );
		}
	}
}
