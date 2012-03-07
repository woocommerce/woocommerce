<?php
/**
 * Checkout Shortcode
 * 
 * Used on the checkout page, the checkout shortcode displays the checkout process.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_checkout( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_checkout', $atts);
}

function woocommerce_checkout( $atts ) {
	global $woocommerce;
	
	$woocommerce->nocache();

	if (sizeof($woocommerce->cart->get_cart())==0) return;
	
	do_action('woocommerce_check_cart_items');
	
	if ( (!isset($_POST) || !$_POST) && $woocommerce->error_count()>0 ) {
		
		woocommerce_get_template('checkout/cart-errors.php');
		
	} else {
	
		$non_js_checkout = (isset($_POST['woocommerce_checkout_update_totals']) && $_POST['woocommerce_checkout_update_totals']) ? true : false;
		
		if ( $woocommerce->error_count()==0 && $non_js_checkout) $woocommerce->add_message( __('The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'woocommerce') );
		
		woocommerce_get_template('checkout/form-checkout.php');
	
	}
	
}