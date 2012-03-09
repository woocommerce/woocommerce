<?php
/**
 * Thankyou Shortcode
 * 
 * The thankyou page displays after successful checkout and can be hooked into by payment gateways.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_thankyou( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_thankyou', $atts); 
}

/**
 * Outputs the order received page
 **/
function woocommerce_thankyou( $atts ) {
	global $woocommerce;
	
	$woocommerce->nocache();
	
	$woocommerce->show_messages();

	$order = false;
	
	// Pay for order after checkout step
	if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
	if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
	
	// Empty awaiting payment session
	unset($_SESSION['order_awaiting_payment']);
	
	if ($order_id > 0) :
		$order = new WC_Order( $order_id );
		if ($order->order_key != $order_key) :
			unset($order);
		endif;
	endif;

	woocommerce_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
}