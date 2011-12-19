<?php
/**
 * Edit Address Form
 */
 
global $woocommerce, $address, $load_address;
?>

<?php $woocommerce->show_messages(); ?>

<form action="<?php echo esc_url( add_query_arg( 'address', $load_address, get_permalink( get_option( 'woocommerce_edit_address_page_id' ) ) ) ); ?>" method="post">
	
	<h3><?php if ($load_address=='billing') _e('Billing Address', 'woothemes'); else _e('Shipping Address', 'woothemes'); ?></h3>
	
	<p class="form-row form-row-first">
		<label for="address_first_name"><?php _e('First Name', 'woothemes'); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="address_first_name" id="address_first_name" placeholder="<?php _e('First Name', 'woothemes'); ?>" value="<?php echo esc_attr( $address['first_name'] ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="address_last_name"><?php _e('Last Name', 'woothemes'); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="address_last_name" id="address_last_name" placeholder="<?php _e('Last Name', 'woothemes'); ?>" value="<?php echo esc_attr( $address['last_name'] ); ?>" />
	</p>
	<div class="clear"></div>
	
	<p class="form-row columned">
		<label for="address_company"><?php _e('Company', 'woothemes'); ?></label>
		<input type="text" class="input-text" name="address_company" id="address_company" placeholder="<?php _e('Company', 'woothemes'); ?>" value="<?php echo esc_attr( $address['company'] ); ?>" />
	</p>
	
	<p class="form-row form-row-first">
		<label for="address_address_1"><?php _e('Address', 'woothemes'); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="address_address_1" id="address_address_1" placeholder="<?php _e('Address line 1', 'woothemes'); ?>" value="<?php echo esc_attr( $address['address'] ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="address_address_2" class="hidden"><?php _e('Address 2', 'woothemes'); ?></label>
		<input type="text" class="input-text" name="address_address_2" id="address_address_2" placeholder="<?php _e('Address line 2', 'woothemes'); ?>" value="<?php echo esc_attr( $address['address2'] ); ?>" />
	</p>
	<div class="clear"></div>
	
	<p class="form-row form-row-first">
		<label for="address_city"><?php _e('City', 'woothemes'); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="address_city" id="address_city" placeholder="<?php _e('City', 'woothemes'); ?>" value="<?php echo esc_attr( $address['city'] ); ?>" />
	</p>
	<p class="form-row form-row-last">
		<label for="address_postcode"><?php _e('Postcode', 'woothemes'); ?> <span class="required">*</span></label>
		<input type="text" class="input-text" name="address_postcode" id="address_postcode" placeholder="123456" value="<?php echo esc_attr( $address['postcode'] ); ?>" />
	</p>
	<div class="clear"></div>
	
	<p class="form-row form-row-first">
		<label for="address_country"><?php _e('Country', 'woothemes'); ?> <span class="required">*</span></label>
		<select name="address_country" id="address_country" class="country_to_state" rel="address_state">
			<option value=""><?php _e('Select a country&hellip;', 'woothemes'); ?></option>
			<?php						
				foreach($woocommerce->countries->countries as $key=>$value) :
					echo '<option value="'.$key.'"';
					if ($address['country']==$key) echo 'selected="selected"';
					elseif (!$address['country'] && $woocommerce->customer->get_country()==$key) echo 'selected="selected"';
					echo '>'.$value.'</option>';
				endforeach;
			?>
		</select>
	</p>
	<p class="form-row form-row-last">	
		<label for="address_state"><?php _e('State', 'woothemes'); ?> <span class="required">*</span></label>
		<?php 
			$current_cc = $address['country'];
			if (!$current_cc) $current_cc = $woocommerce->customer->get_country();
			
			$current_r = $address['state'];
			if (!$current_r) $current_r = $woocommerce->customer->get_state();
			
			$states = $woocommerce->countries->states;
			
			if (isset( $states[$current_cc][$current_r] )) :
				// Dropdown
				?>
				<select name="address_state" id="address_state"><option value=""><?php _e('Select a state&hellip;', 'woothemes'); ?></option><?php
						foreach($states[$current_cc] as $key=>$value) :
							echo '<option value="'.$key.'"';
							if ($current_r==$key) echo 'selected="selected"';
							echo '>'.$value.'</option>';
						endforeach;
				?></select>
				<?php
			else :
				// Input
				?><input type="text" class="input-text" value="<?php echo esc_attr( $current_r ); ?>" placeholder="<?php _e('state', 'woothemes'); ?>" name="address_state" id="address_state" /><?php
			endif;
		?>
	</p>
	<div class="clear"></div>
	
	<?php if ($load_address=='billing') : ?>
		<p class="form-row form-row-first">
			<label for="address_email"><?php _e('Email Address', 'woothemes'); ?> <span class="required">*</span></label>
			<input type="text" class="input-text" name="address_email" id="address_email" placeholder="<?php _e('you@yourdomain.com', 'woothemes'); ?>" value="<?php echo esc_attr( $address['email'] ); ?>" />
		</p>
		<p class="form-row form-row-last">
			<label for="address_phone"><?php _e('Phone', 'woothemes'); ?> <span class="required">*</span></label>
			<input type="text" class="input-text" name="address_phone" id="address_phone" placeholder="0123456789" value="<?php echo esc_attr( $address['phone'] ); ?>" />
		</p>
		<div class="clear"></div>
	<?php endif; ?>
	
	<input type="submit" class="button" name="save_address" value="<?php _e('Save Address', 'woothemes'); ?>" />
	
	<?php $woocommerce->nonce_field('edit_address') ?>
	<input type="hidden" name="action" value="edit_address" />

</form>