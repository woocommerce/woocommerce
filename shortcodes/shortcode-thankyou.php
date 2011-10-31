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

	// Pay for order after checkout step
	if (isset($_GET['order'])) $order_id = $_GET['order']; else $order_id = 0;
	if (isset($_GET['key'])) $order_key = $_GET['key']; else $order_key = '';
	
	// Empty awaiting payment session
	unset($_SESSION['order_awaiting_payment']);
	
	if ($order_id > 0) :
	
		$order = &new woocommerce_order( $order_id );
		
		if ($order->order_key == $order_key) :
		
			if (in_array($order->status, array('failed'))) :
				
				echo '<p>' . __('Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction.', 'woothemes') . '</p>';

				echo '<p>';

					if (is_user_logged_in()) :
						_e('Please attempt your purchase again or go to your account page.', 'woothemes');
					else :
						_e('Please attempt your purchase again.', 'woothemes');
					endif;
				
				echo '</p>';
				
				echo '<a href="'.esc_url( $order->get_checkout_payment_url() ).'" class="button pay">'.__('Pay', 'woothemes').'</a> ';
				
				if (is_user_logged_in()) :
					echo '<a href="'.esc_url( get_permalink(get_option('woocommerce_myaccount_page_id')) ).'" class="button pay">'.__('My Account', 'woothemes').'</a>';
				endif;

			else :
				
				echo '<p>' . __('Thank you. Your order has been received.', 'woothemes') . '</p>';
				
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
				<div class="clear"></div>
				<?php
			
			endif;
			
			do_action( 'woocommerce_thankyou_' . $order->payment_method, $order_id );
			do_action( 'woocommerce_thankyou', $order_id );
			
		endif;
	
	else :
	
		echo '<p>' . __('Thank you. Your order has been received.', 'woothemes') . '</p>';
		
	endif;
	
}