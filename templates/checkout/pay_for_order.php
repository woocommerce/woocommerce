<?php global $order, $woocommerce; ?>
<form id="order_review" method="post">
	
	<table class="shop_table">
		<thead>
			<tr>
				<th><?php _e('Product', 'woothemes'); ?></th>
				<th><?php _e('Qty', 'woothemes'); ?></th>
				<th><?php _e('Totals', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="2"><?php _e('Subtotal', 'woothemes'); ?></td>
				<td><?php echo $order->get_subtotal_to_display(); ?></td>
			</tr>
			<?php if ($order->order_shipping>0) : ?><tr>
				<td colspan="2"><?php _e('Shipping', 'woothemes'); ?></td>
				<td><?php echo $order->get_shipping_to_display(); ?></small></td>
			</tr><?php endif; ?>
			<?php if ($order->get_total_tax()>0) : ?><tr>
				<td colspan="2"><?php _e('Tax', 'woothemes'); ?></td>
				<td><?php echo woocommerce_price($order->get_total_tax()); ?></td>
			</tr><?php endif; ?>
			<?php if ($order->order_discount>0) : ?><tr class="discount">
				<td colspan="2"><?php _e('Discount', 'woothemes'); ?></td>
				<td>-<?php echo woocommerce_price($order->order_discount); ?></td>
			</tr><?php endif; ?>
			<tr>
				<td colspan="2"><strong><?php _e('Grand Total', 'woothemes'); ?></strong></td>
				<td><strong><?php echo woocommerce_price($order->order_total); ?></strong></td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			if (sizeof($order->items)>0) : 
				foreach ($order->items as $item) :
					echo '
						<tr>
							<td>'.$item['name'].'</td>
							<td>'.$item['qty'].'</td>
							<td>'.woocommerce_price( $item['cost']*$item['qty'] ).'</td>
						</tr>';
				endforeach; 
			endif;
			?>
		</tbody>
	</table>
	
	<div id="payment">
		<?php if ($order->order_total > 0) : ?>
		<ul class="payment_methods methods">
			<?php 
				$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
				if ($available_gateways) : 
					// Chosen Method
					if (sizeof($available_gateways)) current($available_gateways)->set_current();
					foreach ($available_gateways as $gateway ) :
						?>
						<li>
							<input type="radio" id="payment_method_<?php echo $gateway->id; ?>" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php if ($gateway->chosen) echo 'checked="checked"'; ?> />
							<label for="payment_method_<?php echo $gateway->id; ?>"><?php echo $gateway->title; ?> <?php echo $gateway->icon(); ?></label> 
							<?php
								if ($gateway->has_fields || $gateway->description) : 
									echo '<div class="payment_box payment_method_'.$gateway->id.'" style="display:none;">';
									$gateway->payment_fields();
									echo '</div>';
								endif;
							?>
						</li>
						<?php
					endforeach;
				else :
				
					echo '<p>'.__('Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woothemes').'</p>';
					
				endif;
			?>
		</ul>
		<?php endif; ?>

		<div class="form-row">
			<?php $woocommerce->nonce_field('pay')?>
			<input type="submit" class="button-alt" name="pay" id="place_order" value="<?php _e('Pay for order', 'woothemes'); ?>" />

		</div>

	</div>
	
</form>