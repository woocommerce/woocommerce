<?php
/**
 * Cart Shortcode
 * 
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_cart( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_cart', $atts);
}

function woocommerce_cart( $atts ) {
	global $woocommerce;
	
	// Remove Discount Codes
	if (isset($_GET['remove_discounts'])) :
		
		$woocommerce->cart->remove_coupons( $_GET['remove_discounts'] );
		$woocommerce->cart->calculate_totals();
	
	// Update Shipping
	elseif (isset($_POST['calc_shipping']) && $_POST['calc_shipping'] && $woocommerce->verify_nonce('cart')) :
		
		$_SESSION['calculated_shipping'] = true;
		unset($_SESSION['_chosen_shipping_method']);
		$country 	= $_POST['calc_shipping_country'];
		$state 		= $_POST['calc_shipping_state'];
		$postcode 	= $_POST['calc_shipping_postcode'];
		
		if ($postcode && !$woocommerce->validation->is_postcode( $postcode, $country )) : 
			$woocommerce->add_error( __('Please enter a valid postcode/ZIP.', 'woothemes') ); 
			$postcode = '';
		elseif ($postcode) :
			$postcode = $woocommerce->validation->format_postcode( $postcode, $country );
		endif;
		
		if ($country) :
		
			// Update customer location
			$woocommerce->customer->set_location( $country, $state, $postcode );
			$woocommerce->customer->set_shipping_location( $country, $state, $postcode );
			$woocommerce->cart->calculate_totals();
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
		
		else :
		
			$woocommerce->customer->set_shipping_to_base();
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
			
		endif;

	endif;
	
	do_action('woocommerce_check_cart_items');
	
	if (sizeof($woocommerce->cart->get_cart())==0) :
		
		woocommerce_get_template( 'cart/empty.php' );
	
	else :
	
		woocommerce_get_template( 'cart/cart.php' );
		
	endif;		
}