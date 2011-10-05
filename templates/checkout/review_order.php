<?php global $woocommerce; ?>
<div id="order_review">
	
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
				<td><?php echo $woocommerce->cart->get_cart_subtotal(); ?></td>
			</tr>
			
			<?php  if ($woocommerce->cart->needs_shipping()) : ?>
				<td colspan="2"><?php _e('Shipping', 'woothemes'); ?></td>
				<td>
				<?php
				
					$available_methods = $woocommerce->shipping->get_available_shipping_methods();
					
					if (sizeof($available_methods)>0) :
						
						echo '<select name="shipping_method" id="shipping_method">';
						
						foreach ($available_methods as $method ) :
							
							echo '<option value="'.esc_attr($method->id).'" ';
							
							if ($method->id==$_SESSION['_chosen_shipping_method']) echo 'selected="selected"';
							
							echo '>'.esc_html($method->title).' &ndash; ';
							
							if ($method->shipping_total>0) :
								echo woocommerce_price($method->shipping_total);
								if ($method->shipping_tax>0) : __(' (ex. tax)', 'woothemes'); endif;
							else :
								echo __('Free', 'woothemes');
							endif;
							
							echo '</option>';

						endforeach;
						
						echo '</select>';
						
					else :
						
						if ( !$woocommerce->customer->get_country() ) :
							echo '<p>'.__('Please fill in your details above to see available shipping methods.', 'woothemes').'</p>';
						else :
							echo '<p>'.__('Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woothemes').'</p>';
						endif;
						
					endif;
			
				?></td>

			<?php endif; ?>
			
			<?php if ($woocommerce->cart->get_cart_tax()) : ?><tr>
				<td colspan="2"><?php _e('Tax', 'woothemes'); ?></td>
				<td><?php echo $woocommerce->cart->get_cart_tax(); ?></td>
			</tr><?php endif; ?>

			<?php if ($woocommerce->cart->get_total_discount()) : ?><tr class="discount">
				<td colspan="2"><?php _e('Discount', 'woothemes'); ?></td>
				<td>-<?php echo $woocommerce->cart->get_total_discount(); ?></td>
			</tr><?php endif; ?>
			<tr>
				<td colspan="2"><strong><?php _e('Grand Total', 'woothemes'); ?></strong></td>
				<td><strong><?php echo $woocommerce->cart->get_total(); ?></strong></td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			if (sizeof($woocommerce->cart->cart_contents)>0) : 
				foreach ($woocommerce->cart->cart_contents as $item_id => $values) :
					$_product = $values['data'];
					if ($_product->exists() && $values['quantity']>0) :
						echo '
							<tr>
								<td class="product-name">'.$_product->get_title().woocommerce_get_formatted_variation( $values['variation'] ).'</td>
								<td>'.$values['quantity'].'</td>
								<td>'.woocommerce_price($_product->get_price_excluding_tax()*$values['quantity'], array('ex_tax_label' => 1)).'</td>
							</tr>';
					endif;
				endforeach; 
			endif;
			?>
		</tbody>
	</table>
	
	<div id="payment">
		<?php if ($woocommerce->cart->needs_payment()) : ?>
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
						<label for="payment_method_<?php echo $gateway->id; ?>"><?php echo $gateway->title; ?> <?php echo apply_filters('woocommerce_gateway_icon', $gateway->icon(), $gateway->id); ?></label> 
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
				
					if ( !$woocommerce->customer->get_country() ) :
						echo '<p>'.__('Please fill in your details above to see available payment methods.', 'woothemes').'</p>';
					else :
						echo '<p>'.__('Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woothemes').'</p>';
					endif;
					
				endif;
			?>
		</ul>
		<?php endif; ?>

		<div class="form-row">
		
			<noscript><?php _e('Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woothemes'); ?><br/><input type="submit" class="button-alt" name="update_totals" value="<?php _e('Update totals', 'woothemes'); ?>" /></noscript>
		
			<?php $woocommerce->nonce_field('process_checkout')?>
			
			<?php do_action( 'woocommerce_review_order_before_submit' ); ?>
			
			<input type="submit" class="button alt" name="place_order" id="place_order" value="<?php _e('Place order', 'woothemes'); ?>" />
			
			<?php if (get_option('woocommerce_terms_page_id')>0) : ?>
			<p class="form-row terms">
				<label for="terms" class="checkbox"><?php _e('I accept the', 'woothemes'); ?> <a href="<?php echo esc_url( get_permalink(get_option('woocommerce_terms_page_id')) ); ?>" target="_blank"><?php _e('terms &amp; conditions', 'woothemes'); ?></a></label>
				<input type="checkbox" class="input-checkbox" name="terms" <?php if (isset($_POST['terms'])) echo 'checked="checked"'; ?> id="terms" />
			</p>
			<?php endif; ?>
			
			<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
			
		</div>

	</div>
	
</div>