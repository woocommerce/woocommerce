<?php
/**
 * Cart Totals
 */
 
global $woocommerce;

$available_methods = $woocommerce->shipping->get_available_shipping_methods();
?>
<div class="cart_totals <?php if (isset($_SESSION['calculated_shipping']) && $_SESSION['calculated_shipping']) echo 'calculated_shipping'; ?>">
	
	<?php if ( !$woocommerce->shipping->enabled || $available_methods || !$woocommerce->customer->get_shipping_country() || !isset($_SESSION['calculated_shipping']) || !$_SESSION['calculated_shipping'] ) : ?>
	
		<h2><?php _e('Cart Totals', 'woocommerce'); ?></h2>
		<table cellspacing="0" cellpadding="0">
			<tbody>
				
				<tr class="cart-subtotal">
					<th><strong><?php _e('Cart Subtotal', 'woocommerce'); ?></strong></th>
					<td><strong><?php echo $woocommerce->cart->get_cart_subtotal(); ?></strong></td>
				</tr>
				
				<?php if ($woocommerce->cart->get_discounts_before_tax()) : ?>
				
				<tr class="discount">
					<th><?php _e('Cart Discount', 'woocommerce'); ?> <a href="<?php echo add_query_arg('remove_discounts', '1') ?>"><?php _e('[Remove]', 'woocommerce'); ?></a></th>
					<td>-<?php echo $woocommerce->cart->get_discounts_before_tax(); ?></td>
				</tr>
				
				<?php endif; ?>
				
				<?php if ($woocommerce->cart->needs_shipping()) : ?>
				
				<tr class="shipping">
					<th><?php _e('Shipping', 'woocommerce'); ?></th>
					<td>
					<?php
						if (sizeof($available_methods)>0) :
							
							echo '<select name="shipping_method" id="shipping_method">';
							
							foreach ($available_methods as $method ) :
								
								echo '<option value="'.esc_attr($method->id).'" '.selected($method->id, $_SESSION['_chosen_shipping_method'], false).'>'.$method->label.' &mdash; ';
								
								if ($method->cost>0) :
									
									if ($woocommerce->cart->display_totals_ex_tax || !$woocommerce->cart->prices_include_tax) :
	
										echo woocommerce_price($method->cost);
										if ( $method->get_shipping_tax()>0 && $woocommerce->cart->prices_include_tax ) :
											echo ' ' . $woocommerce->countries->ex_tax_or_vat();
										endif;
	
									else :
	
										echo woocommerce_price($method->cost + $method->get_shipping_tax());
										if ( $method->get_shipping_tax()>0 && !$woocommerce->cart->prices_include_tax ) :
											echo ' ' . $woocommerce->countries->inc_tax_or_vat();
										endif;
	
									endif;
									
								else :
									echo __('Free', 'woocommerce');
								endif;
								
								echo '</option>';
	
							endforeach;
							
							echo '</select>';
							
						else :
							
							if ( !$woocommerce->customer->get_shipping_country() || !$woocommerce->customer->get_shipping_state() || !$woocommerce->customer->get_shipping_postcode() ) : 
								echo '<p>'.__('Please fill in your details above to see available shipping methods.', 'woocommerce').'</p>';
							else :
								echo '<p>'.__('Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce').'</p>';
							endif;
							
						endif;
				
					?></td>
					
				</tr>
	
				<?php endif; ?>
				
				<?php 
					if ($woocommerce->cart->get_cart_tax()) :
						
						if (isset($woocommerce->cart->taxes) && sizeof($woocommerce->cart->taxes)>0) :
						
							$has_compound_tax = false;
							
							foreach ($woocommerce->cart->taxes as $key => $tax) : if ($woocommerce->cart->tax->is_compound( $key )) : $has_compound_tax = true; continue; endif;
	
								?>
								<tr class="tax-rate tax-rate-<?php echo $key; ?>">
									<th><?php echo $woocommerce->cart->tax->get_rate_label( $key ); ?></th>
									<td><?php echo woocommerce_price($tax); ?></td>
								</tr>
								<?php
								
							endforeach;
							
							if ($has_compound_tax && !$woocommerce->cart->prices_include_tax) :
								?>
								<tr class="order-subtotal">
									<th><strong><?php _e('Subtotal', 'woocommerce'); ?></strong></th>
									<td><strong><?php echo $woocommerce->cart->get_cart_subtotal( true ); ?></strong></td>
								</tr>
								<?php
							endif;
							
							foreach ($woocommerce->cart->taxes as $key => $tax) : if (!$woocommerce->cart->tax->is_compound( $key )) continue;
	
								?>
								<tr class="tax-rate tax-rate-<?php echo $key; ?>">
									<th><?php echo $woocommerce->cart->tax->get_rate_label( $key ); ?></th>
									<td><?php echo woocommerce_price($tax); ?></td>
								</tr>
								<?php
								
							endforeach;
						
						else :
						
							?>
							<tr class="tax">
								<th><?php _e('Tax', 'woocommerce'); ?></th>
								<td><?php echo $woocommerce->cart->get_cart_tax(); ?></td>
							</tr>
							<?php
						
						endif;	
					endif;
				?>
	
				<?php if ($woocommerce->cart->get_discounts_after_tax()) : ?>
				
				<tr class="discount">
					<th><?php _e('Order Discount', 'woocommerce'); ?> <a href="<?php echo add_query_arg('remove_discounts', '2') ?>"><?php _e('[Remove]', 'woocommerce'); ?></a></th>
					<td>-<?php echo $woocommerce->cart->get_discounts_after_tax(); ?></td>
				</tr>
				
				<?php endif; ?>
				
				<tr class="total">
					<th><strong><?php _e('Order Total', 'woocommerce'); ?></strong></th>
					<td><strong><?php echo $woocommerce->cart->get_total(); ?></strong></td>
				</tr>
				
			</tbody>
		</table>
		<p><small><?php 
			if ($woocommerce->customer->is_customer_outside_base()) : 
				
				$estimated_text = ' ' . sprintf(__('(for %s)', 'woocommerce'), $woocommerce->countries->estimated_for_prefix() . __($woocommerce->countries->countries[ $woocommerce->countries->get_base_country() ], 'woocommerce') ); 
			
			else :
			
				$estimated_text = '';
				
			endif;
			
			echo sprintf(__('Note: Shipping and taxes are estimated%s and will be updated during checkout based on your billing and shipping information.', 'woocommerce'), $estimated_text ); 
		?></small></p>
	
	<?php else : ?>
	
		<div class="woocommerce_error">
			<p><?php if (!$woocommerce->customer->get_shipping_state() || !$woocommerce->customer->get_shipping_postcode()) : ?><?php _e('No shipping methods were found; please recalculate your shipping and enter your state/county and zip/postcode to ensure their are no other available methods for your location.', 'woocommerce'); ?><?php else : ?><?php printf(__('Sorry, it seems that there are no available shipping methods for your location (%s).', 'woocommerce'), $woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ]); ?><?php endif; ?></p>
			<p><?php _e('If you require assistance or wish to make alternate arrangements please contact us.', 'woocommerce'); ?></p>
		</div>
		
	<?php endif; ?>
</div>