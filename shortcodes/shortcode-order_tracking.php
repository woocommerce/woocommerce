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
	global $woocommerce;
	
	$woocommerce->nocache();

	extract(shortcode_atts(array(
	), $atts));
	
	global $post;
	
	if ($_POST) :
		
		$woocommerce->verify_nonce( 'order_tracking' );
		
		if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order_id = (int) $_POST['orderid']; else $order_id = 0;
		if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';
		
		$order = new WC_Order( $order_id );
		
		if ($order->id && $order_email) :

			if (strtolower($order->billing_email) == strtolower($order_email)) :
			
				woocommerce_get_template( 'order/tracking.php', array(
					'order' => $order
				) );
				
				return;
				
			endif;
					
		endif;
		
		echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a>', 'woocommerce'), get_permalink($post->ID)).'</p>';
	
	else :
	
		woocommerce_get_template( 'order/form-tracking.php' );
		
	endif;	
	
}