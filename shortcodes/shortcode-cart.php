<?php
/**
 * Cart Shortcode
 * 
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_cart( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_cart', $atts);
}

function woocommerce_cart( $atts ) {
	global $woocommerce;
	$errors = array();
	$validation = &new woocommerce_validation();
	
	// Process Discount Codes
	if (isset($_POST['apply_coupon']) && $_POST['apply_coupon'] && $woocommerce->verify_nonce('cart')) :
	
		$coupon_code = stripslashes(trim($_POST['coupon_code']));
		$woocommerce->cart->add_discount($coupon_code);
	
	// Remvoe Discount Codes
	elseif (isset($_GET['remove_discounts'])) :
		
		$woocommerce->cart->remove_coupons( $_GET['remove_discounts'] );
		
		// Re-calc price
		$woocommerce->cart->calculate_totals();
	
	// Update Shipping
	elseif (isset($_POST['calc_shipping']) && $_POST['calc_shipping'] && $woocommerce->verify_nonce('cart')) :
		
		$_SESSION['calculated_shipping'] = true;
		unset($_SESSION['_chosen_shipping_method']);
		$country 	= $_POST['calc_shipping_country'];
		$state 		= $_POST['calc_shipping_state'];
		$postcode 	= $_POST['calc_shipping_postcode'];
		
		if ($postcode && !$validation->is_postcode( $postcode, $country )) : 
			$woocommerce->add_error( __('Please enter a valid postcode/ZIP.', 'woothemes') ); 
			$postcode = '';
		elseif ($postcode) :
			$postcode = $validation->format_postcode( $postcode, $country );
		endif;
		
		if ($country) :
		
			// Update customer location
			$woocommerce->customer->set_location( $country, $state, $postcode );
			$woocommerce->customer->set_shipping_location( $country, $state, $postcode );
			
			// Re-calc price
			$woocommerce->cart->calculate_totals();
			
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
		
		else :
		
			$woocommerce->customer->set_shipping_location( '', '', '' );
			
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
			
		endif;

	endif;
	
	do_action('woocommerce_check_cart_items');
	
	$woocommerce->show_messages();
	
	if (sizeof($woocommerce->cart->get_cart())==0) :
		echo '<p>'.__('Your cart is currently empty.', 'woothemes').'</p>';
		do_action('woocommerce_cart_is_empty');
		echo '<p><a class="button" href="'.get_permalink(get_option('woocommerce_shop_page_id')).'">'.__('&larr; Return To Shop', 'woothemes').'</a></p>';
		return;
	endif;
	
	?>
	<form action="<?php echo esc_url( $woocommerce->cart->get_cart_url() ); ?>" method="post">
	<table class="shop_table cart" cellspacing="0">
		<thead>
			<tr>
				<th class="product-remove"></th>
				<th class="product-thumbnail"></th>
				<th class="product-name"><span class="nobr"><?php _e('Product Name', 'woothemes'); ?></span></th>
				<th class="product-price"><span class="nobr"><?php _e('Unit Price', 'woothemes'); ?></span></th>
				<th class="product-quantity"><?php _e('Quantity', 'woothemes'); ?></th>
				<th class="product-subtotal"><?php _e('Price', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			if (sizeof($woocommerce->cart->get_cart())>0) : 
				foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) :
					$_product = $values['data'];
					if ($_product->exists() && $values['quantity']>0) :
					
						?>
						<tr>
							<td class="product-remove"><a href="<?php echo esc_url( $woocommerce->cart->get_remove_url($cart_item_key) ); ?>" class="remove" title="<?php _e('Remove this item', 'woothemes'); ?>">&times;</a></td>
							<td class="product-thumbnail">
								<a href="<?php echo esc_url( get_permalink($values['product_id']) ); ?>">
								<?php 
									echo $_product->get_image();
								?>
								</a>
							</td>
							<td class="product-name">
								<a href="<?php echo esc_url( get_permalink($values['product_id']) ); ?>"><?php echo $_product->get_title(); ?></a>
								<?php
									// Meta data
									echo $woocommerce->cart->get_item_data( $values );
                       				
                       				// Backorder notification
                       				if ($_product->backorders_require_notification() && $_product->get_total_stock()<1) echo '<p class="backorder_notification">'.__('Available on backorder.', 'woothemes').'</p>';
								?>
							</td>
							<td class="product-price"><?php 
							
								if (get_option('woocommerce_display_cart_prices_excluding_tax')=='yes') :
									echo woocommerce_price( $_product->get_price_excluding_tax() ); 
								else :
									echo woocommerce_price( $_product->get_price() ); 
								endif;
								
							?></td>
							<td class="product-quantity"><div class="quantity"><input name="cart[<?php echo $cart_item_key; ?>][qty]" value="<?php echo esc_attr( $values['quantity'] ); ?>" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div></td>
							<td class="product-subtotal"><?php 

								echo $woocommerce->cart->get_product_subtotal( $_product, $values['quantity'] )	;
														
							?></td>
						</tr>
						<?php
					endif;
				endforeach; 
			endif;
			
			do_action( 'woocommerce_cart_contents' );
			?>
			<tr>
				<td colspan="6" class="actions">
					<div class="coupon">
						<label for="coupon_code"><?php _e('Coupon', 'woothemes'); ?>:</label> <input name="coupon_code" class="input-text" id="coupon_code" value="" /> <input type="submit" class="button" name="apply_coupon" value="<?php _e('Apply Coupon', 'woothemes'); ?>" />
					</div>
					<?php $woocommerce->nonce_field('cart') ?>
					<input type="submit" class="button" name="update_cart" value="<?php _e('Update Shopping Cart', 'woothemes'); ?>" /> <a href="<?php echo esc_url( $woocommerce->cart->get_checkout_url() ); ?>" class="checkout-button button alt"><?php _e('Proceed to Checkout &rarr;', 'woothemes'); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
	</form>
	<div class="cart-collaterals">
		
		<?php do_action('woocommerce_cart_collaterals'); ?>
		
		<?php woocommerce_cart_totals(); ?>
		
		<?php woocommerce_shipping_calculator(); ?>
		
	</div>
	<?php		
}