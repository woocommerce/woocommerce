<?php

function get_jigoshop_order_tracking ($atts) {
	return jigoshop::shortcode_wrapper('jigoshop_order_tracking', $atts); 
}

function jigoshop_order_tracking( $atts ) {
	
	extract(shortcode_atts(array(
	), $atts));
	
	global $post;
	
	if ($_POST) :
		
		$order = &new jigoshop_order();
		
		if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order->id = (int) $_POST['orderid']; else $order->id = 0;
		if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';
		
		if ( !jigoshop::verify_nonce('order_tracking') ):
		
			echo '<p>'.__('You have taken too long. Please refresh the page and retry.', 'jigoshop').'</p>';
				
		elseif ($order->id && $order_email && $order->get_order( $order->id )) :

			if ($order->billing_email == $order_email) :
		
				echo '<p>'.sprintf( __('Order #%s which was made %s has the status &ldquo;%s&rdquo;', 'jigoshop'), $order->id, human_time_diff(strtotime($order->order_date), current_time('timestamp')).__(' ago', 'jigoshop'), $order->status );
			
				if ($order->status == 'completed') echo __(' and was completed ', 'jigoshop').human_time_diff(strtotime($order->completed_date), current_time('timestamp')).__(' ago', 'jigoshop');
			
				echo '.</p>';

				?>
				<h2><?php _e('Order Details', 'jigoshop'); ?></h2>
				<table class="shop_table">
					<thead>
						<tr>
							<th><?php _e('Title', 'jigoshop'); ?></th>
							<th><?php _e('SKU', 'jigoshop'); ?></th>
							<th><?php _e('Price', 'jigoshop'); ?></th>
							<th><?php _e('Quantity', 'jigoshop'); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="3"><?php _e('Subtotal', 'jigoshop'); ?></td>
							<td><?php echo $order->get_subtotal_to_display(); ?></td>
						</tr>
						<?php if ($order->order_shipping>0) : ?><tr>
							<td colspan="3"><?php _e('Shipping', 'jigoshop'); ?></td>
							<td><?php echo $order->get_shipping_to_display(); ?></small></td>
						</tr><?php endif; ?>
						<?php if ($order->get_total_tax()>0) : ?><tr>
							<td colspan="3"><?php _e('Tax', 'jigoshop'); ?></td>
							<td><?php echo jigoshop_price($order->get_total_tax()); ?></td>
						</tr><?php endif; ?>
						<?php if ($order->order_discount>0) : ?><tr class="discount">
							<td colspan="3"><?php _e('Discount', 'jigoshop'); ?></td>
							<td>-<?php echo jigoshop_price($order->order_discount); ?></td>
						</tr><?php endif; ?>
						<tr>
							<td colspan="3"><strong><?php _e('Grand Total', 'jigoshop'); ?></strong></td>
							<td><strong><?php echo jigoshop_price($order->order_total); ?></strong></td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($order->items as $order_item) : 
						
							if (isset($order_item['variation_id']) && $order_item['variation_id'] > 0) :
								$_product = &new jigoshop_product_variation( $order_item['variation_id'] );
							else :
								$_product = &new jigoshop_product( $order_item['id'] );
							endif;
							
							echo '<tr>';
							echo '<td class="product-name">'.$_product->get_title();
							
							if (isset($_product->variation_data)) :
								echo jigoshop_get_formatted_variation( $_product->variation_data );
							endif;
							
							echo '</td>';
							
							echo '<td>'.$_product->sku.'</td>';
							echo '<td>'.jigoshop_price($_product->get_price()).'</td>';
							echo '<td>'.$order_item['qty'].'</td>';
							
							echo '</tr>';
								
						endforeach;
						?>
					</tbody>
				</table>
				
				<div style="width: 49%; float:left;">
					<h2><?php _e('Billing Address', 'jigoshop'); ?></h2>
					<p><?php
					$address = $order->billing_first_name.' '.$order->billing_last_name.'<br/>';
					if ($order->billing_company) $address .= $order->billing_company.'<br/>';
					$address .= $order->formatted_billing_address;
					echo $address;
					?></p>
				</div>
				<div style="width: 49%; float:right;">
					<h2><?php _e('Shipping Address', 'jigoshop'); ?></h2>
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
				echo '<p>'.__('Sorry, we could not find that order id in our database. <a href="'.get_permalink($post->ID).'">Want to retry?</a>', 'jigoshop').'</p>';
			endif;
		else :
			echo '<p>'.__('Sorry, we could not find that order id in our database. <a href="'.get_permalink($post->ID).'">Want to retry?</a>', 'jigoshop').'</p>';
		endif;	
	
	else :
	
		?>
		<form action="<?php echo get_permalink($post->ID); ?>" method="post" class="track_order">
			
			<p><?php _e('To track your order please enter your Order ID in the box below and press return. This was given to you on your receipt and in the confirmation email you should have received.', 'jigoshop'); ?></p>
			
			<p class="form-row form-row-first"><label for="orderid"><?php _e('Order ID', 'jigoshop'); ?></label> <input class="input-text" type="text" name="orderid" id="orderid" placeholder="<?php _e('Found in your order confirmation email.', 'jigoshop'); ?>" /></p>
			<p class="form-row form-row-last"><label for="order_email"><?php _e('Billing Email', 'jigoshop'); ?></label> <input class="input-text" type="text" name="order_email" id="order_email" placeholder="<?php _e('Email you used during checkout.', 'jigoshop'); ?>" /></p>
			<div class="clear"></div>
			<p class="form-row"><input type="submit" class="button" name="track" value="<?php _e('Track"', 'jigoshop'); ?>" /></p>
			<?php jigoshop::nonce_field('order_tracking') ?>
		</form>
		<?php
		
	endif;	
	
}