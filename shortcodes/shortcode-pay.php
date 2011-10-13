<?php
/**
 * Pay Shortcode
 * 
 * The page page. Used for form based gateways to show payment forms and order info.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_pay( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_pay', $atts); 
}

/**
 * Outputs the pay page - payment gateways can hook in here to show payment forms etc
 **/
function woocommerce_pay() {
	global $woocommerce;
	
	if ( isset($_GET['pay_for_order']) && isset($_GET['order']) && isset($_GET['order_id']) ) :
		
		// Pay for existing order
		$order_key = urldecode( $_GET['order'] );
		$order_id = (int) $_GET['order_id'];
		$order = &new woocommerce_order( $order_id );
		
		if ($order->id == $order_id && $order->order_key == $order_key && in_array($order->status, array('pending', 'failed'))) :
			
			// Set customer location to order location
			if ($order->billing_country) $woocommerce->customer->set_country( $order->billing_country );
			if ($order->billing_state) $woocommerce->customer->set_state( $order->billing_state );
			if ($order->billing_postcode) $woocommerce->customer->set_postcode( $order->billing_postcode );
			
			// Pay form was posted - process payment
			if (isset($_POST['pay']) && $woocommerce->verify_nonce('pay')) :
			
				// Update payment method
				if ($order->order_total > 0 ) : 
					$payment_method 			= woocommerce_clean($_POST['payment_method']);
					update_post_meta( $order_id, '_payment_method', $payment_method);
			
					$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
				
					$result = $available_gateways[$payment_method]->process_payment( $order_id );
					
					// Redirect to success/confirmation/payment page
					if ($result['result']=='success') :
						wp_safe_redirect( $result['redirect'] );
						exit;
					endif;
				else :
					
					// No payment was required for order
					$order->payment_complete();
					wp_safe_redirect( get_permalink(get_option('woocommerce_thanks_page_id')) );
					exit;
					
				endif;
	
			endif;
			
			// Show messages
			$woocommerce->show_messages();
			
			// Show form
			woocommerce_pay_for_existing_order( $order );
		
		elseif (!in_array($order->status, array('pending', 'failed'))) :
			
			$woocommerce->add_error( __('Your order has already been paid for. Please contact us if you need assistance.', 'woothemes') );
			
			$woocommerce->show_messages();
			
		else :
		
			$woocommerce->add_error( __('Invalid order.', 'woothemes') );
			
			$woocommerce->show_messages();
			
		endif;
		
	else :
		
		// Pay for order after checkout step
		if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
		if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
		
		if ($order_id > 0) :
		
			$order = &new woocommerce_order( $order_id );
		
			if ($order->order_key == $order_key && in_array($order->status, array('pending', 'failed'))) :
		
				?>
				<ul class="order_details">
					<li class="order">
						<?php _e('Order:', 'woothemes'); ?>
						<strong># <?php echo $order->id; ?></strong>
					</li>
					<li class="date">
						<?php _e('Date:', 'woothemes'); ?>
						<strong><?php echo date(get_option('date_format'), strtotime($order->order_date)); ?></strong>
					</li>
					<li class="total">
						<?php _e('Total:', 'woothemes'); ?>
						<strong><?php echo woocommerce_price($order->order_total); ?></strong>
					</li>
					<li class="method">
						<?php _e('Payment method:', 'woothemes'); ?>
						<strong><?php 
							$gateways = $woocommerce->payment_gateways->payment_gateways();
							if (isset($gateways[$order->payment_method])) echo $gateways[$order->payment_method]->title;
							else echo $order->payment_method; 
						?></strong>
					</li>
				</ul>
				
				<?php do_action( 'woocommerce_receipt_' . $order->payment_method, $order_id ); ?>
				
				<div class="clear"></div>
				<?php
				
			else :
			
				wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
				exit;
				
			endif;
			
		else :
			
			wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
			exit;
			
		endif;

	endif;
}

/**
 * Outputs the payment page when a user comes to pay from a link (for an existing/past created order)
 **/
function woocommerce_pay_for_existing_order( $pay_for_order ) {
	
	global $order;
	
	$order = $pay_for_order;
	
	woocommerce_get_template('checkout/pay_for_order.php');
	
}