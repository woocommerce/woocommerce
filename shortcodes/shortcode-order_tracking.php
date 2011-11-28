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
function get_woocommerce_order_tracking ($atts) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_order_tracking', $atts); 
}

function woocommerce_order_tracking( $atts ) {
	global $woocommerce;
	
	extract(shortcode_atts(array(
	), $atts));
	
	global $post;
	
	if ($_POST) :
		
		$order = &new woocommerce_order();
		
		if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order->id = (int) $_POST['orderid']; else $order->id = 0;
		if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';
		
		if ( !$woocommerce->verify_nonce('order_tracking') ):
		
			echo '<p>'.__('You have taken too long. Please refresh the page and retry.', 'woothemes').'</p>';
				
		elseif ($order->id && $order_email && $order->get_order( $order->id )) :

			if ($order->billing_email == $order_email) :
			
				$status = get_term_by('slug', $order->status, 'shop_order_status');
		
				$order_status_text = sprintf( __('Order #%s which was made %s has the status &ldquo;%s&rdquo;', 'woothemes'), $order->id, human_time_diff(strtotime($order->order_date), current_time('timestamp')).__(' ago', 'woothemes'), __($status->name, 'woothemes') );
				
				if ($order->status == 'completed') $order_status_text .= ' ' . __('and was completed', 'woothemes') . ' ' . human_time_diff(strtotime($order->completed_date), current_time('timestamp')).__(' ago', 'woothemes');
				
				$order_status_text .= '.';
				
				echo wpautop(apply_filters('woocommerce_order_tracking_status', $order_status_text, $order));
				?>
				
				<?php
					$notes = $order->get_customer_order_notes();
					if ($notes) :
						?>
						<h2><?php _e('Order Updates', 'woothemes'); ?></h2>
						<ol class="commentlist notes">	
							<?php foreach ($notes as $note) : ?>
							<li class="comment note">
								<div class="comment_container">			
									<div class="comment-text">
										<p class="meta"><?php echo date_i18n('l jS \of F Y, h:ia', strtotime($note->comment_date)); ?></p>
										<div class="description">
											<?php echo wpautop(wptexturize($note->comment_content)); ?>
										</div>
						  				<div class="clear"></div>
						  			</div>
									<div class="clear"></div>			
								</div>
							</li>
							<?php endforeach; ?>
						</ol>
						<?php
					endif;
				?>
				
				<?php do_action( 'woocommerce_view_order', $order->id ); ?>
				
				<div style="width: 49%; float:left;">
					<h2><?php _e('Billing Address', 'woothemes'); ?></h2>
					<p><?php
					$address = $order->billing_first_name.' '.$order->billing_last_name.'<br/>';
					if ($order->billing_company) $address .= $order->billing_company.'<br/>';
					$address .= $order->formatted_billing_address;
					echo $address;
					?></p>
				</div>
				<div style="width: 49%; float:right;">
					<h2><?php _e('Shipping Address', 'woothemes'); ?></h2>
					<p><?php
					$address = $order->shipping_first_name.' '.$order->shipping_last_name.'<br/>';
					if ($order->shipping_company) $address .= $order->shipping_company.'<br/>';
					$address .= $order->formatted_shipping_address;
					echo $address;
					?></p>
				</div>
				<div class="clear"></div>
				<?php
				
			else :
				echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a>', 'woothemes'), get_permalink($post->ID)).'</p>';
			endif;
		else :
			echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a>', 'woothemes'), get_permalink($post->ID)).'</p>';
		endif;	
	
	else :
	
		?>
		<form action="<?php echo esc_url( get_permalink($post->ID) ); ?>" method="post" class="track_order">
			
			<p><?php _e('To track your order please enter your Order ID in the box below and press return. This was given to you on your receipt and in the confirmation email you should have received.', 'woothemes'); ?></p>
			
			<p class="form-row form-row-first"><label for="orderid"><?php _e('Order ID', 'woothemes'); ?></label> <input class="input-text" type="text" name="orderid" id="orderid" placeholder="<?php _e('Found in your order confirmation email.', 'woothemes'); ?>" /></p>
			<p class="form-row form-row-last"><label for="order_email"><?php _e('Billing Email', 'woothemes'); ?></label> <input class="input-text" type="text" name="order_email" id="order_email" placeholder="<?php _e('Email you used during checkout.', 'woothemes'); ?>" /></p>
			<div class="clear"></div>
			<p class="form-row"><input type="submit" class="button" name="track" value="<?php _e('Track"', 'woothemes'); ?>" /></p>
			<?php $woocommerce->nonce_field('order_tracking') ?>
		</form>
		<?php
		
	endif;	
	
}