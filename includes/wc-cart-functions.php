<?php
/**
 * WooCommerce Cart Functions
 *
 * Functions for cart specific things.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Prevent password protected products being added to the cart
 *
 * @param  bool $passed
 * @param  int $product_id
 * @return bool
 */
function wc_protected_product_add_to_cart( $passed, $product_id ) {
	if ( post_password_required( $product_id ) ) {
		$passed = false;
		wc_add_notice( __( 'This product is protected and cannot be purchased.', 'woocommerce' ), 'error' );
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wc_protected_product_add_to_cart', 10, 2 );

/**
 * Clears the cart session when called
 *
 * @return void
 */
function wc_empty_cart() {
	global $woocommerce;

	if ( ! isset( $woocommerce->cart ) || $woocommerce->cart == '' )
		$woocommerce->cart = new WC_Cart();

	$woocommerce->cart->empty_cart( false );
}
add_action( 'wp_logout', 'wc_empty_cart' );


/**
 * Load the cart upon login
 *
 * @param mixed $user_login
 * @param mixed $user
 * @return void
 */
function wc_load_persistent_cart( $user_login, $user = 0 ) {
	global $woocommerce;

	if ( ! $user )
		return;

	$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart', true );

	if ( $saved_cart )
		if ( empty( $woocommerce->session->cart ) || ! is_array( $woocommerce->session->cart ) || sizeof( $woocommerce->session->cart ) == 0 )
			$woocommerce->session->cart = $saved_cart['cart'];
}
add_action( 'wp_login', 'wc_load_persistent_cart', 1, 2 );


/**
 * Add to cart messages.
 *
 * @access public
 * @param int $product_id
 * @return void
 */
function wc_add_to_cart_message( $product_id ) {
	global $woocommerce;

	if ( is_array( $product_id ) ) {

		$titles = array();

		foreach ( $product_id as $id ) {
			$titles[] = get_the_title( $id );
		}

		$added_text = sprintf( __( 'Added &quot;%s&quot; to your cart.', 'woocommerce' ), join( __( '&quot; and &quot;', 'woocommerce' ), array_filter( array_merge( array( join( '&quot;, &quot;', array_slice( $titles, 0, -1 ) ) ), array_slice( $titles, -1 ) ) ) ) );

	} else {
		$added_text = sprintf( __( '&quot;%s&quot; was successfully added to your cart.', 'woocommerce' ), get_the_title( $product_id ) );
	}

	// Output success messages
	if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) :

		$return_to 	= apply_filters( 'woocommerce_continue_shopping_redirect', wp_get_referer() ? wp_get_referer() : home_url() );

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', $return_to, __( 'Continue Shopping &rarr;', 'woocommerce' ), $added_text );

	else :

		$message 	= sprintf('<a href="%s" class="button">%s</a> %s', get_permalink( woocommerce_get_page_id( 'cart' ) ), __( 'View Cart &rarr;', 'woocommerce' ), $added_text );

	endif;

	wc_add_notice( apply_filters( 'wc_add_to_cart_message', $message ) );
}

/**
 * Clear cart after payment.
 *
 * @access public
 * @return void
 */
function wc_clear_cart_after_payment() {
	global $woocommerce, $wp;

	if ( ! empty( $wp->query_vars['order-received'] ) ) {

		$order_id = absint( $wp->query_vars['order-received'] );

		if ( isset( $_GET['key'] ) )
			$order_key = $_GET['key'];
		else
			$order_key = '';

		if ( $order_id > 0 ) {
			$order = new WC_Order( $order_id );

			if ( $order->order_key == $order_key ) {
				$woocommerce->cart->empty_cart();
			}
		}

	}

	if ( $woocommerce->session->order_awaiting_payment > 0 ) {

		$order = new WC_Order( $woocommerce->session->order_awaiting_payment );

		if ( $order->id > 0 ) {
			// If the order has not failed, or is not pending, the order must have gone through
			if ( $order->status != 'failed' && $order->status != 'pending' )
				$woocommerce->cart->empty_cart();
		}
	}
}
add_action( 'get_header', 'wc_clear_cart_after_payment' );

