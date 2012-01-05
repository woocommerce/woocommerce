<?php
/**
 * Order Tracking Shortcode
 * 
 * Lets a user see the status of an order by entering their order details.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
function get_woocommerce_order_tracking($atts) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_order_tracking', $atts); 
}

function woocommerce_order_tracking( $atts ) {
	global $woocommerce, $order;
	
	extract(shortcode_atts(array(
	), $atts));
	
	global $post;
	
	if ($_POST) :
		
		$order = &new woocommerce_order();
		
		if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order->id = (int) $_POST['orderid']; else $order->id = 0;
		if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';
		
		$woocommerce->verify_nonce( 'order_tracking' );
		
		if ($order->id && $order_email && $order->get_order( $order->id )) :

			if ($order->billing_email == $order_email) :
			
				woocommerce_get_template( 'order/tracking.php' );
				
				return;
				
			endif;
					
		endif;
		
		echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a>', 'woocommerce'), get_permalink($post->ID)).'</p>';
	
	else :
	
		woocommerce_get_template( 'order/tracking-form.php' );
		
	endif;	
	
}