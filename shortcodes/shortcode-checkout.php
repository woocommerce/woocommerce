<?php
/**
 * Checkout Shortcode
 *
 * Used on the checkout page, the checkout shortcode displays the checkout process.
 *
 * @author 		WooThemes
 * @category 	Shortcodes
 * @package 	WooCommerce/Shortcodes/Checkout
 * @version     1.6.4
 */

/**
 * Get the checkout shortcode content.
 *
 * @access public
 * @param array $atts
 * @return string
 */
function get_woocommerce_checkout( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_checkout', $atts);
}


/**
 * Output the checkout shortcode.
 *
 * @access public
 * @param array $atts
 * @return void
 */
function woocommerce_checkout( $atts ) {
	global $woocommerce;

	$woocommerce->nocache();

	// Show non-cart errors
	$woocommerce->show_messages();

	// Check cart has contents
	if ( sizeof( $woocommerce->cart->get_cart() ) == 0 ) return;

	// Calc totals
	$woocommerce->cart->calculate_totals();

	// Check cart contents for errors
	do_action('woocommerce_check_cart_items');

	if ( empty( $_POST ) && $woocommerce->error_count() > 0 ) {

		woocommerce_get_template('checkout/cart-errors.php');

	} else {

		$non_js_checkout = ! empty( $_POST['woocommerce_checkout_update_totals'] ) ? true : false;

		if ( $woocommerce->error_count() == 0 && $non_js_checkout )
			$woocommerce->add_message( __('The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'woocommerce') );

		woocommerce_get_template('checkout/form-checkout.php');

	}

}