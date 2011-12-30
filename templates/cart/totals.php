<?php
/**
 * Cart Totals
 */
 
global $woocommerce;

$available_methods = $woocommerce->shipping->get_available_shipping_methods();
?>
<div class="cart_totals <?php if (isset($_SESSION['calculated_shipping']) && $_SESSION['calculated_shipping']) echo 'calculated_shipping'; ?>">
	
	<?php if ( !$woocommerce->shipping->enabled || $available_methods || !$woocommerce->customer->get_shipping_country() || !isset($_SESSION['calculated_shipping']) || !$_SESSION['calculated_shipping'] ) : ?>
	
		<h2><?php _e('Cart Totals', 'woothemes'); ?></h2>
		<table cellspacing="0" cellpadding="0">
			<tbody>
				<?php 
					// Define total rows to display
					$total_rows = array(
						'subtotal' => array(
							'label' => __('Cart Subtotal', 'woothemes'),
							'value' => '<strong>' . $woocommerce->cart->get_cart_subtotal() . '</strong>'
						)
					);
					
					// Cart Discount
					if ($woocommerce->cart->get_discounts_before_tax()) :
						$total_rows[ 'cart-discount' ] = array(
							'label' => __('Cart Discount', 'woothemes') . ' <a href="'.add_query_arg('remove_discounts', '1').'">'.__('[Remove]', 'woothemes').'</a>',
							'value' => '&ndash;'.$woocommerce->cart->get_discounts_before_tax()
						);
					endif;
					
					// Shipping
					if ($woocommerce->cart->get_cart_shipping_total() && sizeof($available_methods)>0) :
					
						$shipping_method_select = '<select name="shipping_method" id="shipping_method">';

						foreach ($available_methods as $method ) :

							$shipping_method_select .= '<option value="'.$method->id.'" '.selected($method->id, $_SESSION['_chosen_shipping_method'], false).'>'.$method->title.' &mdash; ';
							
							if ($method->shipping_total>0) :

								if ($woocommerce->cart->display_totals_ex_tax || !$woocommerce->cart->prices_include_tax) :

									$shipping_method_select .= woocommerce_price($method->shipping_total);
									if ($method->shipping_tax>0 && $woocommerce->cart->prices_include_tax) :
										$shipping_method_select .= ' ' . $woocommerce->countries->ex_tax_or_vat();
									endif;

								else :

									$shipping_method_select .= woocommerce_price($method->shipping_total + $method->shipping_tax);
									if ($method->shipping_tax>0 && !$woocommerce->cart->prices_include_tax) :
										$shipping_method_select .= ' ' . $woocommerce->countries->inc_tax_or_vat();
									endif;

								endif;

							else :
								$shipping_method_select .= __('Free', 'woothemes');
							endif;

							$shipping_method_select .= '</option>';

						endforeach;

						$shipping_method_select .= '</select>';
						
						$total_rows[ 'shipping' ] = array(
							'label' => __('Shipping', 'woothemes') . ' <small>' . $woocommerce->countries->shipping_to_prefix() . ' ' . __($woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ], 'woothemes') . '</small>',
							'value' => $shipping_method_select
						);	
					endif;
					
					// Tax Rows
					if ($woocommerce->cart->get_cart_tax()) :
						foreach ($woocommerce->cart->taxes->get_taxes() as $taxes) : if ($taxes['compound']) continue;
							$total_rows[ sanitize_title($taxes['label']) ] = array(
								'label' => $taxes['label'],
								'value' => woocommerce_price($taxes['total'])
							);
						endforeach;
						
						if ($woocommerce->cart->has_compound_tax) :
							$total_rows[ 'order-subtotal' ] = array(
								'label' => __('Order Subtotal', 'woothemes'),
								'value' => '<strong>' . $woocommerce->cart->get_cart_subtotal( true ) . '</strong>'
							);
						endif;
						
						foreach ($woocommerce->cart->taxes->get_taxes() as $taxes) : if (!$taxes['compound']) continue;
							$total_rows[ sanitize_title($taxes['label']) ] = array(
								'label' => $taxes['label'],
								'value' => woocommerce_price($taxes['total'])
							);
						endforeach;
					endif;	
					
					// Order Discount
					if ($woocommerce->cart->get_discounts_after_tax()) :
						$total_rows[ 'order-discount' ] = array(
							'label' => __('Order Discount', 'woothemes') . ' <a href="'.add_query_arg('remove_discounts', '2') . '">'.__('[Remove]', 'woothemes').'</a>',
							'value' => $woocommerce->cart->get_discounts_after_tax()
						);
					endif;
					
					// Total
					$total_rows[ 'total' ] = array(
						'label' => __('Order Total', 'woothemes'),
						'value' => '<strong>' . $woocommerce->cart->get_total() . '</strong>'
					);
					
					// Loop through and display rows
					if (sizeof($total_rows)>0) 
						foreach ($total_rows as $row => $value) 
							echo '<tr><th class="'.$row.'">'.$value['label'].'</th><td>'.$value['value'].'</td></tr>';
				?>
			</tbody>
		</table>
		<p><small><?php 
			if ($woocommerce->customer->is_customer_outside_base()) : 
				
				$estimated_text = ' ' . sprintf(__('(for %s)', 'woothemes'), $woocommerce->countries->estimated_for_prefix() . __($woocommerce->countries->countries[ $woocommerce->countries->get_base_country() ], 'woothemes') ); 
			
			else :
			
				$estimated_text = '';
				
			endif;
			
			echo sprintf(__('Note: Shipping and taxes are estimated%s and will be updated during checkout based on your billing and shipping information.', 'woothemes'), $estimated_text ); 
		?></small></p>
	
	<?php else : ?>
	
		<div class="woocommerce_error">
			<p><?php if (!$woocommerce->customer->get_shipping_state() || !$woocommerce->customer->get_shipping_postcode()) : ?><?php _e('No shipping methods were found; please recalculate your shipping and enter your state/county and zip/postcode to ensure their are no other available methods for your location.', 'woothemes'); ?><?php else : ?><?php printf(__('Sorry, it seems that there are no available shipping methods for your location (%s).', 'woothemes'), $woocommerce->countries->countries[ $woocommerce->customer->get_shipping_country() ]); ?><?php endif; ?></p>
			<p><?php _e('If you require assistance or wish to make alternate arrangements please contact us.', 'woothemes'); ?></p>
		</div>
		
	<?php endif; ?>
</div>