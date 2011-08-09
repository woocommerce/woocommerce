<?php
	if (!defined('JIGOSHOP_CHECKOUT')) define('JIGOSHOP_CHECKOUT', true);
	
	if (!defined('ABSPATH')) :
		define('DOING_AJAX', true);
		$root = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
		require_once( $root.'/wp-load.php' );
	endif;
	
	if (sizeof(jigoshop_cart::$cart_contents)==0) :
		echo '<p class="error">'.__('Sorry, your session has expired.', 'jigoshop').' <a href="'.home_url().'">'.__('Return to homepage &rarr;', 'jigoshop').'</a></p>';
		exit;
	endif;
	
	if (isset($_POST['shipping_method'])) $_SESSION['_chosen_method_id'] = $_POST['shipping_method'];
	
	if (isset($_POST['country'])) jigoshop_customer::set_country( $_POST['country'] );
	if (isset($_POST['state'])) jigoshop_customer::set_state( $_POST['state'] );
	if (isset($_POST['postcode'])) jigoshop_customer::set_postcode( $_POST['postcode'] );
	
	if (isset($_POST['s_country'])) jigoshop_customer::set_shipping_country( $_POST['s_country'] );
	if (isset($_POST['s_state'])) jigoshop_customer::set_shipping_state( $_POST['s_state'] );
	if (isset($_POST['s_postcode'])) jigoshop_customer::set_shipping_postcode( $_POST['s_postcode'] );
	
	jigoshop_cart::calculate_totals();
?>
<div id="order_review">
	
	<table class="shop_table">
		<thead>
			<tr>
				<th><?php _e('Product', 'jigoshop'); ?></th>
				<th><?php _e('Qty', 'jigoshop'); ?></th>
				<th><?php _e('Totals', 'jigoshop'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="2"><?php _e('Subtotal', 'jigoshop'); ?></td>
				<td><?php echo jigoshop_cart::get_cart_subtotal(); ?></td>
			</tr>
			
			<?php  if (jigoshop_cart::needs_shipping()) : ?>
				<td colspan="2"><?php _e('Shipping', 'jigoshop'); ?></td>
				<td>
				<?php
				
					$available_methods = jigoshop_shipping::get_available_shipping_methods();
					
					if (sizeof($available_methods)>0) :
						
						echo '<select name="shipping_method" id="shipping_method">';
						
						foreach ($available_methods as $method ) :
							
							echo '<option value="'.$method->id.'" ';
							
							if ($method->chosen) echo 'selected="selected"';
							
							echo '>'.$method->title.' &ndash; ';
							
							if ($method->shipping_total>0) :
								echo jigoshop_price($method->shipping_total);
								if ($method->shipping_tax>0) : __(' (ex. tax)', 'jigoshop'); endif;
							else :
								echo __('Free', 'jigoshop');
							endif;
							
							echo '</option>';

						endforeach;
						
						echo '</select>';
						
					else :
						
						if ( !jigoshop_customer::get_country() ) :
							echo '<p>'.__('Please fill in your details above to see available shipping methods.', 'jigoshop').'</p>';
						else :
							echo '<p>'.__('Sorry, it seems that there are no available shipping methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'jigoshop').'</p>';
						endif;
						
					endif;
			
				?></td>

			<?php endif; ?>
			
			<?php if (jigoshop_cart::get_cart_tax()) : ?><tr>
				<td colspan="2"><?php _e('Tax', 'jigoshop'); ?></td>
				<td><?php echo jigoshop_cart::get_cart_tax(); ?></td>
			</tr><?php endif; ?>

			<?php if (jigoshop_cart::get_total_discount()) : ?><tr class="discount">
				<td colspan="2"><?php _e('Discount', 'jigoshop'); ?></td>
				<td>-<?php echo jigoshop_cart::get_total_discount(); ?></td>
			</tr><?php endif; ?>
			<tr>
				<td colspan="2"><strong><?php _e('Grand Total', 'jigoshop'); ?></strong></td>
				<td><strong><?php echo jigoshop_cart::get_total(); ?></strong></td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			if (sizeof(jigoshop_cart::$cart_contents)>0) : 
				foreach (jigoshop_cart::$cart_contents as $item_id => $values) :
					$_product = $values['data'];
					if ($_product->exists() && $values['quantity']>0) :
						echo '
							<tr>
								<td class="product-name">'.$_product->get_title().jigoshop_get_formatted_variation( $values['variation'] ).'</td>
								<td>'.$values['quantity'].'</td>
								<td>'.jigoshop_price($_product->get_price_excluding_tax()*$values['quantity'], array('ex_tax_label' => 1)).'</td>
							</tr>';
					endif;
				endforeach; 
			endif;
			?>
		</tbody>
	</table>
	
	<div id="payment">
		<?php if (jigoshop_cart::needs_payment()) : ?>
		<ul class="payment_methods methods">
			<?php 
				$available_gateways = jigoshop_payment_gateways::get_available_payment_gateways();
				if ($available_gateways) : 
					// Chosen Method
					if (sizeof($available_gateways)) current($available_gateways)->set_current();
					foreach ($available_gateways as $gateway ) :
						?>
						<li>
						<input type="radio" id="payment_method_<?php echo $gateway->id; ?>" class="input-radio" name="payment_method" value="<?php echo $gateway->id; ?>" <?php if ($gateway->chosen) echo 'checked="checked"'; ?> />
						<label for="payment_method_<?php echo $gateway->id; ?>"><?php echo $gateway->title; ?> <?php echo apply_filters('gateway_icon', $gateway->icon(), $gateway->id); ?></label> 
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
				
					if ( !jigoshop_customer::get_country() ) :
						echo '<p>'.__('Please fill in your details above to see available payment methods.', 'jigoshop').'</p>';
					else :
						echo '<p>'.__('Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'jigoshop').'</p>';
					endif;
					
				endif;
			?>
		</ul>
		<?php endif; ?>

		<div class="form-row">
		
			<noscript><?php _e('Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'jigoshop'); ?><br/><input type="submit" class="button-alt" name="update_totals" value="<?php _e('Update totals', 'jigoshop'); ?>" /></noscript>
		
			<?php jigoshop::nonce_field('process_checkout')?>
			<input type="submit" class="button-alt" name="place_order" id="place_order" value="<?php _e('Place order', 'jigoshop'); ?>" />
			
			<?php do_action( 'jigoshop_review_order_before_submit' ); ?>
			
			<?php if (get_option('jigoshop_terms_page_id')>0) : ?>
			<p class="form-row terms">
				<label for="terms" class="checkbox"><?php _e('I accept the', 'jigoshop'); ?> <a href="<?php echo get_permalink(get_option('jigoshop_terms_page_id')); ?>" target="_blank"><?php _e('terms &amp; conditions', 'jigoshop'); ?></a></label>
				<input type="checkbox" class="input-checkbox" name="terms" <?php if (isset($_POST['terms'])) echo 'checked="checked"'; ?> id="terms" />
			</p>
			<?php endif; ?>
			
			<?php do_action( 'jigoshop_review_order_after_submit' ); ?>
			
		</div>

	</div>
	
</div>