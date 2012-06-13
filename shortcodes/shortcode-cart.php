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
	
	$woocommerce->nocache();
	
	if ( ! defined( 'WOOCOMMERCE_CART' ) ) define( 'WOOCOMMERCE_CART', true );
	
	// Add Discount
	if ( ! empty( $_POST['apply_coupon'] ) ) {
		
		if ( ! empty( $_POST['coupon_code'] ) ) {
			$woocommerce->cart->add_discount( stripslashes( trim( $_POST['coupon_code'] ) ) );
		} else {
			$woocommerce->add_error( __('Please enter a coupon code.', 'woocommerce') ); 
		}
		
	// Remove Discount Codes
	} elseif ( isset( $_GET['remove_discounts'] ) ) {
		
		$woocommerce->cart->remove_coupons( $_GET['remove_discounts'] );
	
	// Update Shipping
	} elseif ( ! empty( $_POST['calc_shipping'] ) && $woocommerce->verify_nonce('cart') ) {
		
		$validation = $woocommerce->validation();
		
		$_SESSION['calculated_shipping'] = true;
		unset($_SESSION['_chosen_shipping_method']);
		$country 	= $_POST['calc_shipping_country'];
		$state 		= $_POST['calc_shipping_state'];
		$postcode 	= $_POST['calc_shipping_postcode'];
		
		if ($postcode && ! $validation->is_postcode( $postcode, $country )) : 
			$woocommerce->add_error( __('Please enter a valid postcode/ZIP.', 'woocommerce') ); 
			$postcode = '';
		elseif ($postcode) :
			$postcode = $validation->format_postcode( $postcode, $country );
		endif;
		
		if ($country) :
		
			// Update customer location
			$woocommerce->customer->set_location( $country, $state, $postcode );
			$woocommerce->customer->set_shipping_location( $country, $state, $postcode );
			$woocommerce->add_message(  __('Shipping costs updated.', 'woocommerce') );
		
		else :
			
			$woocommerce->customer->set_to_base();
			$woocommerce->customer->set_shipping_to_base();
			$woocommerce->add_message(  __('Shipping costs updated.', 'woocommerce') );
			
		endif;

	}
	
	// Check cart items are valid
	do_action('woocommerce_check_cart_items');
	
	// Calc totals
	$woocommerce->cart->calculate_totals();
	
	if (sizeof($woocommerce->cart->get_cart())==0) :
		
		woocommerce_get_template( 'cart/empty.php' );
	
	else :
	
		woocommerce_get_template( 'cart/cart.php' );
		
	endif;		
}