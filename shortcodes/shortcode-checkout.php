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
	global $woocommerce, $woocommerce_checkout;
	
	if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
	
	if (sizeof($woocommerce->cart->get_cart())==0) :
		wp_redirect(get_permalink(get_option('woocommerce_cart_page_id')));
		exit;
	endif;
	
	$non_js_checkout = (isset($_POST['update_totals']) && $_POST['update_totals']) ? true : false;
	
	$woocommerce_checkout = &new woocommerce_checkout();
	
	$woocommerce_checkout->process_checkout();
	
	do_action('woocommerce_check_cart_items');
	
	if ( $woocommerce->error_count()==0 && $non_js_checkout) $woocommerce->add_message( __('The order totals have been updated. Please confirm your order by pressing the Place Order button at the bottom of the page.', 'woothemes') );
	
	$woocommerce->show_messages();
	
	woocommerce_get_template('checkout/form.php', false);
	
}