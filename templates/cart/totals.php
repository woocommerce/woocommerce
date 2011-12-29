<?php
/**
 * Cart Totals
 */
 
global $woocommerce;

$available_methods = $woocommerce->shipping->get_available_shipping_methods();
$has_compound_tax = false;
?>
<div class="cart_totals <?php if (isset($_SESSION['calculated_shipping']) && $_SESSION['calculated_shipping']) echo 'calculated_shipping'; ?>">
	
	<?php if ( !$woocommerce->shipping->enabled || $available_methods || !$woocommerce->customer->get_shipping_country() || !isset($_SESSION['calculated_shipping']) || !$_SESSION['calculated_shipping'] ) : ?>
	
		<h2><?php _e('Cart Totals', 'woothemes'); ?></h2>
		<table cellspacing="0" cellpadding="0">
			<tbody>
			
				<?php if (!$woocommerce->cart->has_compound_tax) : ?>
					<tr>
						<th><?php _e('Cart Subtotal', 'woothemes'); ?></th>
						<td><?php echo $woocommerce->cart->get_cart_subtotal(); ?></td>
					</tr>
				<?php endif; ?>
	
				<?php if ($woocommerce->cart->get_discounts_before_tax()) : ?><tr class="discount">
					<th><?php _e('Cart Discount', 'woothemes'); ?> <a href="<?php echo add_query_arg('remove_discounts', '1') ?>"><?php _e('[Remove]', 'woothemes'); ?></a></th>
					<td>&ndash;<?php echo $woocommerce->cart->get_discounts_before_tax(); ?></td>
				</tr><?php endif; ?>
	
				<?php if ($woocommerce->cart->get_cart_shipping_total()) : ?><tr>
					<th><?php _e('Shipping', 'woothemes'); ?> <small><?php echo $woocommerce->countries->shipping_to_prefix().' '.__($woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ], 'woothemes'); ?></small></th>
					<td>
						<?php
							if (sizeof($available_methods)>0) :
	
								echo '<select name="shipping_method" id="shipping_method">';
	
								foreach ($available_methods as $method ) :
	
									echo '<option value="'.$method->id.'" '.selected($method->id, $_SESSION['_chosen_shipping_method'], false).'>'.$method->title.' &ndash; ';
									if ($method->shipping_total>0) :
	
										if (get_option('woocommerce_display_totals_excluding_tax')=='yes') :
	
											echo woocommerce_price($method->shipping_total);
											if ($method->shipping_tax>0) :
												echo ' ' . $woocommerce->countries->ex_tax_or_vat();
											endif;
	
										else :
	
											echo woocommerce_price($method->shipping_total + $method->shipping_tax);
											if ($method->shipping_tax>0) :
												echo ' ' . $woocommerce->countries->inc_tax_or_vat();
											endif;
	
										endif;
	
									else :
										echo __('Free', 'woothemes');
									endif;
	
									echo '</option>';
	
								endforeach;
	
								echo '</select>';
							endif;
						?>
					</td>
				</tr><?php endif; ?>
	
				<?php if ($woocommerce->cart->get_cart_tax()) : ?>
					
					<?php foreach ($woocommerce->cart->taxes as $taxes) : if ($taxes['compound']) continue; ?>
						<tr>	
							<th><?php echo $taxes['label']; ?> <?php if ($woocommerce->customer->is_customer_outside_base()) : ?><small><?php echo sprintf(__('estimated for %s', 'woothemes'), $woocommerce->countries->estimated_for_prefix() . __($woocommerce->countries->countries[ $woocommerce->countries->get_base_country() ], 'woothemes') ); ?></small><?php endif; ?></th>
							<td><?php echo woocommerce_price($taxes['total']); ?></td>
						</tr>
					<?php endforeach; ?>
					
					<?php if ($woocommerce->cart->has_compound_tax) : ?>
						<tr>
							<th><?php _e('Subtotal', 'woothemes'); ?></th>
							<td><?php echo $woocommerce->cart->get_cart_subtotal(); ?></td>
						</tr>
					<?php endif; ?>
					
					<?php foreach ($woocommerce->cart->taxes as $taxes) : if (!$taxes['compound']) continue; ?>
						<tr>	
							<th><?php echo $taxes['label']; ?> <?php if ($woocommerce->customer->is_customer_outside_base()) : ?><small><?php echo sprintf(__('estimated for %s', 'woothemes'), $woocommerce->countries->estimated_for_prefix() . __($woocommerce->countries->countries[ $woocommerce->countries->get_base_country() ], 'woothemes') ); ?></small><?php endif; ?></th>
							<td><?php echo woocommerce_price($taxes['total']); ?></td>
						</tr>
					<?php endforeach; ?>
					
				<?php endif; ?>
	
				<?php if ($woocommerce->cart->get_discounts_after_tax()) : ?><tr class="discount">
					<th><?php _e('Order Discount', 'woothemes'); ?> <a href="<?php echo add_query_arg('remove_discounts', '2') ?>"><?php _e('[Remove]', 'woothemes'); ?></a></th>
					<td>-<?php echo $woocommerce->cart->get_discounts_after_tax(); ?></td>
				</tr><?php endif; ?>
	
				<tr>
					<th><strong><?php _e('Order Total', 'woothemes'); ?></strong></th>
					<td><strong><?php echo $woocommerce->cart->get_total(); ?></strong></td>
				</tr>
			</tbody>
		</table>
		<p><small><?php _e('Note: Tax and shipping totals are estimated and will be updated during checkout based on your billing information.', 'woothemes'); ?></small></p>
	
	<?php else : ?>
	
		<div class="woocommerce_error">
			<p><?php if (!$woocommerce->customer->get_shipping_state() || !$woocommerce->customer->get_shipping_postcode()) : ?><?php _e('No shipping methods were found; please recalculate your shipping and enter your state/county and zip/postcode to ensure their are no other available methods for your location.', 'woothemes'); ?><?php else : ?><?php printf(__('Sorry, it seems that there are no available shipping methods for your location (%s).', 'woothemes'), $woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ]); ?><?php endif; ?></p>
			<p><?php _e('If you require assistance or wish to make alternate arrangements please contact us.', 'woothemes'); ?></p>
		</div>
		
	<?php endif; ?>
</div>