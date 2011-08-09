<?php

function get_jigoshop_my_account ( $atts ) {
	return jigoshop::shortcode_wrapper('jigoshop_my_account', $atts); 
}	
function jigoshop_my_account( $atts ) {
	
	extract(shortcode_atts(array(
    'recent_orders' => 5
	), $atts));

  	$recent_orders = ('all' == $recent_orders) ? -1 : $recent_orders;
	
	global $post, $current_user;

	get_currentuserinfo();
	
	jigoshop::show_messages();
	
	if (is_user_logged_in()) :
	
		?>
		<p><?php echo sprintf( __('Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'jigoshop'), $current_user->display_name, get_permalink(get_option('jigoshop_change_password_page_id'))); ?></p>
		
		
		<?php if ($downloads = jigoshop_customer::get_downloadable_products()) : ?>
		<h2><?php _e('Available downloads', 'jigoshop'); ?></h2>
		<ul class="digital-downloads">
			<?php foreach ($downloads as $download) : ?>
				<li><?php if (is_numeric($download['downloads_remaining'])) : ?><span class="count"><?php echo $download['downloads_remaining'] . _n(' download Remaining', ' downloads Remaining', $download['downloads_remaining'], 'jigoshop'); ?></span><?php endif; ?> <a href="<?php echo $download['download_url']; ?>"><?php echo $download['download_name']; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>	
		
		
		<h2><?php _e('Recent Orders', 'jigoshop'); ?></h2>		
		<table class="shop_table my_account_orders">
		
			<thead>
				<tr>
					<th><span class="nobr"><?php _e('#', 'jigoshop'); ?></span></th>
					<th><span class="nobr"><?php _e('Date', 'jigoshop'); ?></span></th>
					<th><span class="nobr"><?php _e('Ship to', 'jigoshop'); ?></span></th>
					<th><span class="nobr"><?php _e('Total', 'jigoshop'); ?></span></th>
					<th colspan="2"><span class="nobr"><?php _e('Status', 'jigoshop'); ?></span></th>
				</tr>
			</thead>
			
			<tbody><?php
				$jigoshop_orders = &new jigoshop_orders();
				$jigoshop_orders->get_customer_orders( get_current_user_id(), $recent_orders );
				if ($jigoshop_orders->orders) foreach ($jigoshop_orders->orders as $order) :
					?><tr class="order">
						<td><?php echo $order->id; ?></td>
						<td><time title="<?php echo strtotime($order->order_date); ?>"><?php echo date('d.m.Y', strtotime($order->order_date)); ?></time></td>
						<td><address><?php if ($order->formatted_shipping_address) echo $order->formatted_shipping_address; else echo '&ndash;'; ?></address></td>
						<td><?php echo jigoshop_price($order->order_total); ?></td>
						<td><?php echo $order->status; ?></td>
						<td style="text-align:right; white-space:nowrap;">
							<?php if ($order->status=='pending') : ?>
								<a href="<?php echo $order->get_checkout_payment_url(); ?>" class="button pay"><?php _e('Pay', 'jigoshop'); ?></a>
								<a href="<?php echo $order->get_cancel_order_url(); ?>" class="button cancel"><?php _e('Cancel', 'jigoshop'); ?></a>
							<?php endif; ?>
							<a href="<?php echo add_query_arg('order', $order->id, get_permalink(get_option('jigoshop_view_order_page_id'))); ?>" class="button"><?php _e('View', 'jigoshop'); ?></a>
						</td>
					</tr><?php
				endforeach;
			?></tbody>
		
		</table>
		
		<h2><?php _e('My Addresses', 'jigoshop'); ?></h2>	
		<p><?php _e('The following addresses will be used on the checkout page by default.', 'jigoshop'); ?></p>
		<div class="col2-set addresses">

			<div class="col-1">
			
				<header class="title">				
					<h3><?php _e('Billing Address', 'jigoshop'); ?></h3>
					<a href="<?php echo add_query_arg('address', 'billing', get_permalink(get_option('jigoshop_edit_address_page_id'))); ?>" class="edit"><?php _e('Edit', 'jigoshop'); ?></a>	
				</header>
				<address>
					<?php
						if (isset(jigoshop_countries::$countries->countries[get_user_meta( get_current_user_id(), 'billing-country', true )])) $country = jigoshop_countries::$countries->countries[get_user_meta( get_current_user_id(), 'billing-country', true )]; else $country = '';
						$address = array(
							get_user_meta( get_current_user_id(), 'billing-first_name', true ) . ' ' . get_user_meta( get_current_user_id(), 'billing-last_name', true )
							,get_user_meta( get_current_user_id(), 'billing-company', true )
							,get_user_meta( get_current_user_id(), 'billing-address', true )
							,get_user_meta( get_current_user_id(), 'billing-address-2', true )
							,get_user_meta( get_current_user_id(), 'billing-city', true )					
							,get_user_meta( get_current_user_id(), 'billing-state', true )
							,get_user_meta( get_current_user_id(), 'billing-postcode', true )
							,$country
						);
						$address = array_map('trim', $address);
						$formatted_address = array();
						foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
						$formatted_address = implode(', ', $formatted_address);
						if (!$formatted_address) _e('You have not set up a billing address yet.', 'jigoshop'); else echo $formatted_address;
					?>
				</address>
			
			</div><!-- /.col-1 -->
			
			<div class="col-2">
			
				<header class="title">
					<h3><?php _e('Shipping Address', 'jigoshop'); ?></h3>
					<a href="<?php echo add_query_arg('address', 'shipping', get_permalink(get_option('jigoshop_edit_address_page_id'))); ?>" class="edit"><?php _e('Edit', 'jigoshop'); ?></a>
				</header>
				<address>
					<?php
						if (isset(jigoshop_countries::$countries->countries[get_user_meta( get_current_user_id(), 'shipping-country', true )])) $country = jigoshop_countries::$countries->countries[get_user_meta( get_current_user_id(), 'shipping-country', true )]; else $country = '';
						$address = array(
							get_user_meta( get_current_user_id(), 'shipping-first_name', true ) . ' ' . get_user_meta( get_current_user_id(), 'shipping-last_name', true )
							,get_user_meta( get_current_user_id(), 'shipping-company', true )
							,get_user_meta( get_current_user_id(), 'shipping-address', true )
							,get_user_meta( get_current_user_id(), 'shipping-address-2', true )
							,get_user_meta( get_current_user_id(), 'shipping-city', true )					
							,get_user_meta( get_current_user_id(), 'shipping-state', true )
							,get_user_meta( get_current_user_id(), 'shipping-postcode', true )
							,$country
						);
						$address = array_map('trim', $address);
						$formatted_address = array();
						foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
						$formatted_address = implode(', ', $formatted_address);
						if (!$formatted_address) _e('You have not set up a shipping address yet.', 'jigoshop'); else echo $formatted_address;
					?>
				</address>
			
			</div><!-- /.col-2 -->
		
		</div><!-- /.col2-set -->
		<?php
		
	else :
		
		jigoshop_login_form();
		
	endif;
		
}

function get_jigoshop_edit_address () {
	return jigoshop::shortcode_wrapper('jigoshop_edit_address'); 
}	
function jigoshop_edit_address() {
	
	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
		
		if (isset($_GET['address'])) $load_address = $_GET['address']; else $load_address = 'billing';
		if ($load_address == 'billing') $load_address = 'billing'; else $load_address = 'shipping';
		
		if ($_POST) :
		
			if ($user_id>0 && jigoshop::verify_nonce('edit_address') ) :
				update_user_meta( $user_id, $load_address . '-first_name', jigowatt_clean($_POST['address-first_name']) );
				update_user_meta( $user_id, $load_address . '-last_name', jigowatt_clean($_POST['address-last_name']) );
				update_user_meta( $user_id, $load_address . '-company', jigowatt_clean($_POST['address-company']) );
				update_user_meta( $user_id, $load_address . '-email', jigowatt_clean($_POST['address-email']) );
				update_user_meta( $user_id, $load_address . '-address', jigowatt_clean($_POST['address-address']) );
				update_user_meta( $user_id, $load_address . '-address2', jigowatt_clean($_POST['address-address2']) );
				update_user_meta( $user_id, $load_address . '-city', jigowatt_clean($_POST['address-city']) );
				update_user_meta( $user_id, $load_address . '-postcode', jigowatt_clean($_POST['address-postcode']) );
				update_user_meta( $user_id, $load_address . '-country', jigowatt_clean($_POST['address-country']) );
				update_user_meta( $user_id, $load_address . '-state', jigowatt_clean($_POST['address-state']) );
				update_user_meta( $user_id, $load_address . '-phone', jigowatt_clean($_POST['address-phone']) );
				update_user_meta( $user_id, $load_address . '-fax', jigowatt_clean($_POST['address-fax']) );
			endif;
			
			wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
			exit;
		
		endif;
		
		$address = array(
			'first_name' => get_user_meta( get_current_user_id(), $load_address . '-first_name', true ),
			'last_name' => get_user_meta( get_current_user_id(), $load_address . '-last_name', true ),
			'company' => get_user_meta( get_current_user_id(), $load_address . '-company', true ),
			'email' => get_user_meta( get_current_user_id(), $load_address . '-email', true ),
			'phone' => get_user_meta( get_current_user_id(), $load_address . '-phone', true ),
			'fax' => get_user_meta( get_current_user_id(), $load_address . '-fax', true ),
			'address' => get_user_meta( get_current_user_id(), $load_address . '-address', true ),
			'address2' => get_user_meta( get_current_user_id(), $load_address . '-address2', true ),
			'city' => get_user_meta( get_current_user_id(), $load_address . '-city', true ),		
			'state' => get_user_meta( get_current_user_id(), $load_address . '-state', true ),
			'postcode' => get_user_meta( get_current_user_id(), $load_address . '-postcode', true ),
			'country' => get_user_meta( get_current_user_id(), $load_address . '-country', true )
		);
		?>
		<form action="<?php echo add_query_arg('address', $load_address, get_permalink(get_option('jigoshop_edit_address_page_id'))); ?>" method="post">
	
			<h3><?php if ($load_address=='billing') _e('Billing Address', 'jigoshop'); else _e('Shipping Address', 'jigoshop'); ?></h3>
			
			<p class="form-row form-row-first">
				<label for="address-first_name"><?php _e('First Name', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address-first_name" id="address-first_name" placeholder="<?php _e('First Name', 'jigoshop'); ?>" value="<?php echo $address['first_name']; ?>" />
			</p>
			<p class="form-row form-row-last">
				<label for="address-last_name"><?php _e('Last Name', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address-last_name" id="address-last_name" placeholder="<?php _e('Last Name', 'jigoshop'); ?>" value="<?php echo $address['last_name']; ?>" />
			</p>
			<div class="clear"></div>
			
			<p class="form-row columned">
				<label for="address-company"><?php _e('Company', 'jigoshop'); ?></label>
				<input type="text" class="input-text" name="address-company" id="address-company" placeholder="<?php _e('Company', 'jigoshop'); ?>" value="<?php echo $address['company']; ?>" />
			</p>
			
			<p class="form-row form-row-first">
				<label for="address-address"><?php _e('Address', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address-address" id="address-address" placeholder="<?php _e('1 Infinite Loop', 'jigoshop'); ?>" value="<?php echo $address['address']; ?>" />
			</p>
			<p class="form-row form-row-last">
				<label for="address-address2" class="hidden"><?php _e('Address 2', 'jigoshop'); ?></label>
				<input type="text" class="input-text" name="address-address2" id="address-address2" placeholder="<?php _e('Cupertino', 'jigoshop'); ?>" value="<?php echo $address['address2']; ?>" />
			</p>
			<div class="clear"></div>
			
			<p class="form-row form-row-first">
				<label for="address-city"><?php _e('City', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address-city" id="address-city" placeholder="<?php _e('City', 'jigoshop'); ?>" value="<?php echo $address['city']; ?>" />
			</p>
			<p class="form-row form-row-last">
				<label for="address-postcode"><?php _e('Postcode', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address-postcode" id="address-postcode" placeholder="123456" value="<?php echo $address['postcode']; ?>" />
			</p>
			<div class="clear"></div>
			
			<p class="form-row form-row-first">
				<label for="address-country"><?php _e('Country', 'jigoshop'); ?> <span class="required">*</span></label>
				<select name="address-country" id="address-country" class="country_to_state" rel="address-state">
					<option value=""><?php _e('Select a country&hellip;', 'jigoshop'); ?></option>
					<?php						
						foreach(jigoshop_countries::$countries as $key=>$value) :
							echo '<option value="'.$key.'"';
							if ($address['country']==$key) echo 'selected="selected"';
							elseif (!$address['country'] && jigoshop_customer::get_country()==$key) echo 'selected="selected"';
							echo '>'.$value.'</option>';
						endforeach;
					?>
				</select>
			</p>
			<p class="form-row form-row-last">	
				<label for="address-state"><?php _e('state', 'jigoshop'); ?> <span class="required">*</span></label>
				<?php 
					$current_cc = $address['country'];
					if (!$current_cc) $current_cc = jigoshop_customer::get_country();
					
					$current_r = $address['state'];
					if (!$current_r) $current_r = jigoshop_customer::get_state();
					
					$states = jigoshop_countries::$states;
					
					if (isset( $states[$current_cc][$current_r] )) :
						// Dropdown
						?>
						<select name="address-state" id="address-state"><option value=""><?php _e('Select a state&hellip;', 'jigoshop'); ?></option><?php
								foreach($states[$current_cc] as $key=>$value) :
									echo '<option value="'.$key.'"';
									if ($current_r==$key) echo 'selected="selected"';
									echo '>'.$value.'</option>';
								endforeach;
						?></select>
						<?php
					else :
						// Input
						?><input type="text" class="input-text" value="<?php echo $current_r; ?>" placeholder="<?php _e('state', 'jigoshop'); ?>" name="address-state" id="address-state" /><?php
					endif;
				?>
			</p>
			<div class="clear"></div>
			
			<?php if ($load_address=='billing') : ?>
				<p class="form-row columned">
					<label for="address-email"><?php _e('Email Address', 'jigoshop'); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="address-email" id="address-email" placeholder="<?php _e('you@yourdomain.com', 'jigoshop'); ?>" value="<?php echo $address['email']; ?>" />
				</p>
				
				<p class="form-row form-row-first">
					<label for="address-phone"><?php _e('Phone', 'jigoshop'); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="address-phone" id="address-phone" placeholder="0123456789" value="<?php echo $address['phone']; ?>" />
				</p>
				<p class="form-row form-row-last">	
					<label for="address-fax"><?php _e('Fax', 'jigoshop'); ?></label>
					<input type="text" class="input-text" name="address-fax" id="address-fax" placeholder="0123456789" value="<?php echo $address['fax']; ?>" />
				</p>
				<div class="clear"></div>
			<?php endif; ?>
			<?php jigoshop::nonce_field('edit_address') ?>
			<input type="submit" class="button" name="save_address" value="<?php _e('Save Address', 'jigoshop'); ?>" />
	
		</form>
		<?php
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
		exit;
		
	endif;
}

function get_jigoshop_change_password () {
	return jigoshop::shortcode_wrapper('jigoshop_change_password'); 
}	
function jigoshop_change_password() {

	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
		
		if ($_POST) :
			
			if ($user_id>0 && jigoshop::verify_nonce('change_password')) :
				
				if ( $_POST['password-1'] && $_POST['password-2']  ) :
					
					if ( $_POST['password-1']==$_POST['password-2'] ) :
	
						wp_update_user( array ('ID' => $user_id, 'user_pass' => $_POST['password-1']) ) ;
						
						wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
						exit;
						
					else :
					
						jigoshop::add_error( __('Passwords do not match.','jigoshop') );
					
					endif;
				
				else :
				
					jigoshop::add_error( __('Please enter your password.','jigoshop') );
					
				endif;			
				
			endif;
		
		endif;
		
		jigoshop::show_messages();

		?>
		<form action="<?php echo get_permalink(get_option('jigoshop_change_password_page_id')); ?>" method="post">
	
			<p class="form-row form-row-first">
				<label for="password-1"><?php _e('New password', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="password" class="input-text" name="password-1" id="password-1" />
			</p>
			<p class="form-row form-row-last">
				<label for="password-2"><?php _e('Re-enter new password', 'jigoshop'); ?> <span class="required">*</span></label>
				<input type="password" class="input-text" name="password-2" id="password-2" />
			</p>
			<div class="clear"></div>
			<?php jigoshop::nonce_field('change_password')?>
			<p><input type="submit" class="button" name="save_password" value="<?php _e('Save', 'jigoshop'); ?>" /></p>
	
		</form>
		<?php
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
		exit;
		
	endif;
	
}

function get_jigoshop_view_order () {
	return jigoshop::shortcode_wrapper('jigoshop_view_order'); 
}	

function jigoshop_view_order() {
	
	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
	
		if (isset($_GET['order'])) $order_id = (int) $_GET['order']; else $order_id = 0;
	
		$order = &new jigoshop_order( $order_id );
		
		if ( $order_id>0 && $order->user_id == get_current_user_id() ) :
			
			echo '<p>' . sprintf( __('Order <mark>#%s</mark> made on <mark>%s</mark>', 'jigoshop'), $order->id, date('d.m.Y', strtotime($order->order_date)) );
			
			echo sprintf( __('. Order status: <mark>%s</mark>', 'jigoshop'), $order->status );
			
			echo '.</p>';
			
			?>
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
						<td><?php echo $order->get_subtotal_to_display(); ?></td>
					</tr>
					<?php if ($order->order_shipping>0) : ?><tr>
						<td colspan="2"><?php _e('Shipping', 'jigoshop'); ?></td>
						<td><?php echo $order->get_shipping_to_display(); ?></small></td>
					</tr><?php endif; ?>
					<?php if ($order->get_total_tax()>0) : ?><tr>
						<td colspan="2"><?php _e('Tax', 'jigoshop'); ?></td>
						<td><?php echo jigoshop_price($order->get_total_tax()); ?></td>
					</tr><?php endif; ?>
					<?php if ($order->order_discount>0) : ?><tr class="discount">
						<td colspan="2"><?php _e('Discount', 'jigoshop'); ?></td>
						<td>-<?php echo jigoshop_price($order->order_discount); ?></td>
					</tr><?php endif; ?>
					<tr>
						<td colspan="2"><strong><?php _e('Grand Total', 'jigoshop'); ?></strong></td>
						<td><strong><?php echo jigoshop_price($order->order_total); ?></strong></td>
					</tr>
					<?php if ($order->customer_note) : ?>
					<tr>
						<td><?php _e('Note:', 'jigoshop'); ?></td>
						<td colspan="2"><?php echo wpautop(wptexturize($order->customer_note)); ?></td>
					</tr>
					<?php endif; ?>
				</tfoot>
				<tbody>
					<?php
					if (sizeof($order->items)>0) : 
					
						foreach($order->items as $item) : 
						
							if (isset($item['variation_id']) && $item['variation_id'] > 0) :
								$_product = &new jigoshop_product_variation( $item['variation_id'] );
							else :
								$_product = &new jigoshop_product( $item['id'] );
							endif;
						
							echo '
								<tr>
									<td class="product-name">'.$item['name'];
							
							if (isset($_product->variation_data)) :
								echo jigoshop_get_formatted_variation( $_product->variation_data );
							endif;
							
							echo '	</td>
									<td>'.$item['qty'].'</td>
									<td>'.jigoshop_price( $item['cost']*$item['qty'], array('ex_tax_label' => 1) ).'</td>
								</tr>';
						endforeach; 
					endif;
					?>
				</tbody>
			</table>
			
			<header>
				<h2><?php _e('Customer details', 'jigoshop'); ?></h2>
			</header>
			<dl>
			<?php
				if ($order->billing_email) echo '<dt>'.__('Email:', 'jigoshop').'</dt><dd>'.$order->billing_email.'</dd>';
				if ($order->billing_phone) echo '<dt>'.__('Telephone:', 'jigoshop').'</dt><dd>'.$order->billing_phone.'</dd>';
			?>
			</dl>
			
			<div class="col2-set addresses">
	
				<div class="col-1">
				
					<header class="title">
						<h3><?php _e('Shipping Address', 'jigoshop'); ?></h3>
					</header>
					<address><p>
						<?php
							if (!$order->formatted_shipping_address) _e('N/A', 'jigoshop'); else echo $order->formatted_shipping_address;
						?>
					</p></address>
				
				</div><!-- /.col-1 -->
				
				<div class="col-2">
				
					<header class="title">				
						<h3><?php _e('Billing Address', 'jigoshop'); ?></h3>
					</header>
					<address><p>
						<?php
							if (!$order->formatted_billing_address) _e('N/A', 'jigoshop'); else echo $order->formatted_billing_address;
						?>
					</p></address>
				
				</div><!-- /.col-2 -->
			
			</div><!-- /.col2-set -->
			
			<div class="clear"></div>
		
			<?php
		
		else :
		
			wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
			exit;
			
		endif;
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('jigoshop_myaccount_page_id')) );
		exit;
		
	endif;
}
