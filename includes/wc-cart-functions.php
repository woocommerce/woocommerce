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
function woocommerce_protected_product_add_to_cart( $passed, $product_id ) {
	if ( post_password_required( $product_id ) ) {
		$passed = false;
		wc_add_error( __( 'This product is protected and cannot be purchased.', 'woocommerce' ) );
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'woocommerce_protected_product_add_to_cart', 10, 2 );

/**
 * WooCommerce clear cart
 *
 * Clears the cart session when called
 */
function woocommerce_empty_cart() {
	global $woocommerce;

	if ( ! isset( $woocommerce->cart ) || $woocommerce->cart == '' )
		$woocommerce->cart = apply_filter( 'woocommerce_instance_cart', new WC_Cart() );

	$woocommerce->cart->empty_cart( false );
}
add_action( 'wp_logout', 'woocommerce_empty_cart' );


/**
 * Load the cart upon login
 * @param mixed $user_login
 * @param mixed $user
 */
function woocommerce_load_persistent_cart( $user_login, $user = 0 ) {
	global $woocommerce;

	if ( ! $user )
		return;

	$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart', true );

	if ( $saved_cart )
		if ( empty( $woocommerce->session->cart ) || ! is_array( $woocommerce->session->cart ) || sizeof( $woocommerce->session->cart ) == 0 )
			$woocommerce->session->cart = $saved_cart['cart'];
}
add_action( 'wp_login', 'woocommerce_load_persistent_cart', 1, 2 );


/**
 * Add to cart messages.
 *
 * @access public
 * @return void
 */
function woocommerce_add_to_cart_message( $product_id ) {
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

	wc_add_message( apply_filters('woocommerce_add_to_cart_message', $message) );
}

/**
 * Clear cart after payment.
 *
 * @access public
 * @return void
 */
function woocommerce_clear_cart_after_payment() {
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
			// If the order has failed, and the customer is logged in, they can try again from their account page
			if ( $order->status == 'failed' && is_user_logged_in() )
				$woocommerce->cart->empty_cart();

			// If the order has not failed, or is not pending, the order must have gone through
			if ( $order->status != 'failed' && $order->status != 'pending' )
				$woocommerce->cart->empty_cart();
		}
	}
}
add_action( 'get_header', 'woocommerce_clear_cart_after_payment' );

