<?php
/**
 * My Account Shortcode
 * 
 * Shows the 'my account' section where the customer can view past orders and update their information.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
function get_woocommerce_my_account ( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_my_account', $atts); 
}	
function woocommerce_my_account( $atts ) {
	global $woocommerce;
	
	extract(shortcode_atts(array(
    	'recent_orders' => 5
	), $atts));

  	$recent_orders = ('all' == $recent_orders) ? -1 : $recent_orders;
	
	global $post, $current_user;

	get_currentuserinfo();
	
	$woocommerce->show_messages();
	
	if (is_user_logged_in()) :
	
		?>
		<p><?php echo sprintf( __('Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'woothemes'), $current_user->display_name, get_permalink(get_option('woocommerce_change_password_page_id'))); ?></p>
		
		<?php do_action('woocommerce_before_my_account'); ?>
		
		<?php if ($downloads = $woocommerce->customer->get_downloadable_products()) : ?>
		<h2><?php _e('Available downloads', 'woothemes'); ?></h2>
		<ul class="digital-downloads">
			<?php foreach ($downloads as $download) : ?>
				<li><?php if (is_numeric($download['downloads_remaining'])) : ?><span class="count"><?php echo $download['downloads_remaining'] . _n(' download Remaining', ' downloads Remaining', $download['downloads_remaining'], 'woothemes'); ?></span><?php endif; ?> <a href="<?php echo esc_url( $download['download_url'] ); ?>"><?php echo $download['download_name']; ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>	
		
		
		<h2><?php _e('Recent Orders', 'woothemes'); ?></h2>
		<?php
		$woocommerce_orders = &new woocommerce_orders();
		$woocommerce_orders->get_customer_orders( get_current_user_id(), $recent_orders );
		if ($woocommerce_orders->orders) :
		?>
			<table class="shop_table my_account_orders">
			
				<thead>
					<tr>
						<th><span class="nobr"><?php _e('#', 'woothemes'); ?></span></th>
						<th><span class="nobr"><?php _e('Date', 'woothemes'); ?></span></th>
						<th><span class="nobr"><?php _e('Ship to', 'woothemes'); ?></span></th>
						<th><span class="nobr"><?php _e('Total', 'woothemes'); ?></span></th>
						<th colspan="2"><span class="nobr"><?php _e('Status', 'woothemes'); ?></span></th>
					</tr>
				</thead>
				
				<tbody><?php
					foreach ($woocommerce_orders->orders as $order) :
						?><tr class="order">
							<td><?php echo $order->id; ?></td>
							<td><time title="<?php echo esc_attr( strtotime($order->order_date) ); ?>"><?php echo date(get_option('date_format'), strtotime($order->order_date)); ?></time></td>
							<td><address><?php if ($order->formatted_shipping_address) echo $order->formatted_shipping_address; else echo '&ndash;'; ?></address></td>
							<td><?php echo woocommerce_price($order->order_total); ?></td>
							<td><?php 
								$status = get_term_by('slug', $order->status, 'shop_order_status');
								echo $status->name; 
							?></td>
							<td style="text-align:right; white-space:nowrap;">
								<?php if (in_array($order->status, array('pending', 'failed'))) : ?>
									<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php _e('Pay', 'woothemes'); ?></a>
									<a href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>" class="button cancel"><?php _e('Cancel', 'woothemes'); ?></a>
								<?php endif; ?>
								<a href="<?php echo esc_url( add_query_arg('order', $order->id, get_permalink(get_option('woocommerce_view_order_page_id'))) ); ?>" class="button"><?php _e('View', 'woothemes'); ?></a>
							</td>
						</tr><?php
					endforeach;
				?></tbody>
			
			</table>
		<?php
		else : 
			_e('You have no recent orders.', 'woothemes');
		endif;
		?>
		
		<h2><?php _e('My Addresses', 'woothemes'); ?></h2>	
		<p><?php _e('The following addresses will be used on the checkout page by default.', 'woothemes'); ?></p>
		<div class="col2-set addresses">

			<div class="col-1">
			
				<header class="title">				
					<h3><?php _e('Billing Address', 'woothemes'); ?></h3>
					<a href="<?php echo esc_url( add_query_arg('address', 'billing', get_permalink(get_option('woocommerce_edit_address_page_id'))) ); ?>" class="edit"><?php _e('Edit', 'woothemes'); ?></a>	
				</header>
				<address>
					<?php
						if (isset($woocommerce->countries->countries[get_user_meta( get_current_user_id(), 'billing_country', true )])) $country = $woocommerce->countries->countries[get_user_meta( get_current_user_id(), 'billing_country', true )]; else $country = '';
						$address = array(
							get_user_meta( get_current_user_id(), 'billing_first_name', true ) . ' ' . get_user_meta( get_current_user_id(), 'billing_last_name', true )
							,get_user_meta( get_current_user_id(), 'billing_company', true )
							,get_user_meta( get_current_user_id(), 'billing_address', true )
							,get_user_meta( get_current_user_id(), 'billing_address-2', true )
							,get_user_meta( get_current_user_id(), 'billing_city', true )					
							,get_user_meta( get_current_user_id(), 'billing_state', true )
							,get_user_meta( get_current_user_id(), 'billing_postcode', true )
							,$country
						);
						$address = array_map('trim', $address);
						$formatted_address = array();
						foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
						$formatted_address = implode(', ', $formatted_address);
						if (!$formatted_address) _e('You have not set up a billing address yet.', 'woothemes'); else echo $formatted_address;
					?>
				</address>
			
			</div><!-- /.col-1 -->
			
			<div class="col-2">
			
				<header class="title">
					<h3><?php _e('Shipping Address', 'woothemes'); ?></h3>
					<a href="<?php echo esc_url( add_query_arg('address', 'shipping', get_permalink(get_option('woocommerce_edit_address_page_id'))) ); ?>" class="edit"><?php _e('Edit', 'woothemes'); ?></a>
				</header>
				<address>
					<?php
						if (isset($woocommerce->countries->countries[get_user_meta( get_current_user_id(), 'shipping_country', true )])) $country = $woocommerce->countries->countries[get_user_meta( get_current_user_id(), 'shipping_country', true )]; else $country = '';
						$address = array(
							get_user_meta( get_current_user_id(), 'shipping_first_name', true ) . ' ' . get_user_meta( get_current_user_id(), 'shipping_last_name', true )
							,get_user_meta( get_current_user_id(), 'shipping_company', true )
							,get_user_meta( get_current_user_id(), 'shipping_address', true )
							,get_user_meta( get_current_user_id(), 'shipping_address-2', true )
							,get_user_meta( get_current_user_id(), 'shipping_city', true )					
							,get_user_meta( get_current_user_id(), 'shipping_state', true )
							,get_user_meta( get_current_user_id(), 'shipping_postcode', true )
							,$country
						);
						$address = array_map('trim', $address);
						$formatted_address = array();
						foreach ($address as $part) if (!empty($part)) $formatted_address[] = $part;
						$formatted_address = implode(', ', $formatted_address);
						if (!$formatted_address) _e('You have not set up a shipping address yet.', 'woothemes'); else echo $formatted_address;
					?>
				</address>
			
			</div><!-- /.col-2 -->
		
		</div><!-- /.col2-set -->
		<?php
		do_action('woocommerce_after_my_account');
		
	else :
		
		// Login/register template
		woocommerce_get_template( 'myaccount/login.php' );
		
	endif;
		
}

function get_woocommerce_edit_address () {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_edit_address'); 
}	
function woocommerce_edit_address() {
	global $woocommerce;
	
	$validation = &new woocommerce_validation();
	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
		
		if (isset($_GET['address'])) $load_address = $_GET['address']; else $load_address = 'billing';
		if ($load_address == 'billing') $load_address = 'billing'; else $load_address = 'shipping';
		
		if ($_POST) :
			
			/* Validate */
			$_POST = array_map('woocommerce_clean', $_POST);
			
			// Required Fields
			if (empty($_POST['address_first_name'])) : $woocommerce->add_error( __('First name is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_last_name'])) : $woocommerce->add_error( __('Last name is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_address'])) : $woocommerce->add_error( __('Address is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_city'])) : $woocommerce->add_error( __('City is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_postcode'])) : $woocommerce->add_error( __('Postcode is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_country'])) : $woocommerce->add_error( __('Country is a required field.', 'woothemes') ); endif;
			if (empty($_POST['address_state'])) : $woocommerce->add_error( __('State is a required field.', 'woothemes') ); endif;
			
			// Billing only
			if ($load_address == 'billing') :
				if (empty($_POST['address_email'])) : $woocommerce->add_error( __('Email is a required field.', 'woothemes') ); endif;
				if (empty($_POST['address_phone'])) : $woocommerce->add_error( __('Phone number is a required field.', 'woothemes') ); endif;
						
				// Email
				if (!$validation->is_email( $_POST['address_email'] )) : $woocommerce->add_error( __('Please enter a valid email address.', 'woothemes') ); endif;
				
				// Phone
				if (!$validation->is_phone( $_POST['address_phone'] )) : $woocommerce->add_error( __('Please enter a valid phone number.', 'woothemes') ); endif;
			endif;
			
			// Postcode
			if (!$validation->is_postcode( $_POST['address_postcode'], $_POST['address_country'] )) : $woocommerce->add_error( __('Please enter a valid postcode/ZIP.', 'woothemes') ); 
			else :
				$_POST['address_postcode'] = $validation->format_postcode( $_POST['address_postcode'], $_POST['address_country'] );
			endif;
			
			/* Save */
			if ($user_id>0 && $woocommerce->verify_nonce('edit_address') && $woocommerce->error_count() == 0 ) :
				update_user_meta( $user_id, $load_address . '_first_name', $_POST['address_first_name'] );
				update_user_meta( $user_id, $load_address . '_last_name', $_POST['address_last_name'] );
				update_user_meta( $user_id, $load_address . '_company', $_POST['address_company'] );
				update_user_meta( $user_id, $load_address . '_address', $_POST['address_address'] );
				update_user_meta( $user_id, $load_address . '_address2', $_POST['address_address2'] );
				update_user_meta( $user_id, $load_address . '_city', $_POST['address_city'] );
				update_user_meta( $user_id, $load_address . '_postcode', $_POST['address_postcode'] );
				update_user_meta( $user_id, $load_address . '_country', $_POST['address_country'] );
				update_user_meta( $user_id, $load_address . '_state', $_POST['address_state'] );
				
				if ($load_address == 'billing') :
					update_user_meta( $user_id, $load_address . '_email', $_POST['address_email'] );
					update_user_meta( $user_id, $load_address . '_phone', $_POST['address_phone'] );
					update_user_meta( $user_id, $load_address . '_fax', $_POST['address_fax'] );
				endif;
				
				wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
				exit;
			else :
			
				$address = array(
					'first_name' => $_POST['address_first_name'],
					'last_name' => $_POST['address_last_name'],
					'company' => $_POST['address_company'],
					'email' => $_POST['address_email'],
					'phone' => $_POST['address_phone'],
					'fax' => $_POST['address_fax'],
					'address' => $_POST['address_address'],
					'address2' => $_POST['address_address2'],
					'city' => $_POST['address_city'],		
					'state' => $_POST['address_state'],
					'postcode' => $_POST['address_postcode'],
					'country' => $_POST['address_country']
				);
			
			endif;
		
		else :
		
			$address = array(
				'first_name' => get_user_meta( get_current_user_id(), $load_address . '_first_name', true ),
				'last_name' => get_user_meta( get_current_user_id(), $load_address . '_last_name', true ),
				'company' => get_user_meta( get_current_user_id(), $load_address . '_company', true ),
				'email' => get_user_meta( get_current_user_id(), $load_address . '_email', true ),
				'phone' => get_user_meta( get_current_user_id(), $load_address . '_phone', true ),
				'fax' => get_user_meta( get_current_user_id(), $load_address . '_fax', true ),
				'address' => get_user_meta( get_current_user_id(), $load_address . '_address', true ),
				'address2' => get_user_meta( get_current_user_id(), $load_address . '_address2', true ),
				'city' => get_user_meta( get_current_user_id(), $load_address . '_city', true ),		
				'state' => get_user_meta( get_current_user_id(), $load_address . '_state', true ),
				'postcode' => get_user_meta( get_current_user_id(), $load_address . '_postcode', true ),
				'country' => get_user_meta( get_current_user_id(), $load_address . '_country', true )
			);
		
		endif;

		$woocommerce->show_messages();
		?>
		<form action="<?php echo esc_url( add_query_arg('address', $load_address, get_permalink(get_option('woocommerce_edit_address_page_id'))) ); ?>" method="post">
	
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
				<label for="address_address"><?php _e('Address', 'woothemes'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" name="address_address" id="address_address" placeholder="<?php _e('Address line 1', 'woothemes'); ?>" value="<?php echo esc_attr( $address['address'] ); ?>" />
			</p>
			<p class="form-row form-row-last">
				<label for="address_address2" class="hidden"><?php _e('Address 2', 'woothemes'); ?></label>
				<input type="text" class="input-text" name="address_address2" id="address_address2" placeholder="<?php _e('Address line 2', 'woothemes'); ?>" value="<?php echo esc_attr( $address['address2'] ); ?>" />
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
				<p class="form-row columned">
					<label for="address_email"><?php _e('Email Address', 'woothemes'); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="address_email" id="address_email" placeholder="<?php _e('you@yourdomain.com', 'woothemes'); ?>" value="<?php echo esc_attr( $address['email'] ); ?>" />
				</p>
				
				<p class="form-row form-row-first">
					<label for="address_phone"><?php _e('Phone', 'woothemes'); ?> <span class="required">*</span></label>
					<input type="text" class="input-text" name="address_phone" id="address_phone" placeholder="0123456789" value="<?php echo esc_attr( $address['phone'] ); ?>" />
				</p>
				<p class="form-row form-row-last">	
					<label for="address_fax"><?php _e('Fax', 'woothemes'); ?></label>
					<input type="text" class="input-text" name="address_fax" id="address_fax" placeholder="0123456789" value="<?php echo esc_attr( $address['fax'] ); ?>" />
				</p>
				<div class="clear"></div>
			<?php endif; ?>
			<?php $woocommerce->nonce_field('edit_address') ?>
			<input type="submit" class="button" name="save_address" value="<?php _e('Save Address', 'woothemes'); ?>" />
	
		</form>
		<?php
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
		exit;
		
	endif;
}

function get_woocommerce_change_password() {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_change_password'); 
}	
function woocommerce_change_password() {
	global $woocommerce;
	
	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
		
		if ($_POST) :
			
			if ($user_id>0 && $woocommerce->verify_nonce('change_password')) :
				
				if ( $_POST['password-1'] && $_POST['password-2']  ) :
					
					if ( $_POST['password-1']==$_POST['password-2'] ) :
	
						wp_update_user( array ('ID' => $user_id, 'user_pass' => $_POST['password-1']) ) ;
						
						wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
						exit;
						
					else :
					
						$woocommerce->add_error( __('Passwords do not match.', 'woothemes') );
					
					endif;
				
				else :
				
					$woocommerce->add_error( __('Please enter your password.', 'woothemes') );
					
				endif;			
				
			endif;
		
		endif;
		
		$woocommerce->show_messages();

		?>
		<form action="<?php echo esc_url( get_permalink(get_option('woocommerce_change_password_page_id')) ); ?>" method="post">
	
			<p class="form-row form-row-first">
				<label for="password-1"><?php _e('New password', 'woothemes'); ?> <span class="required">*</span></label>
				<input type="password" class="input-text" name="password-1" id="password-1" />
			</p>
			<p class="form-row form-row-last">
				<label for="password-2"><?php _e('Re-enter new password', 'woothemes'); ?> <span class="required">*</span></label>
				<input type="password" class="input-text" name="password-2" id="password-2" />
			</p>
			<div class="clear"></div>
			<?php $woocommerce->nonce_field('change_password')?>
			<p><input type="submit" class="button" name="save_password" value="<?php _e('Save', 'woothemes'); ?>" /></p>
	
		</form>
		<?php
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
		exit;
		
	endif;
	
}

function get_woocommerce_view_order () {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_view_order'); 
}	

function woocommerce_view_order() {
	global $woocommerce;
	
	$user_id = get_current_user_id();
	
	if (is_user_logged_in()) :
	
		if (isset($_GET['order'])) $order_id = (int) $_GET['order']; else $order_id = 0;
	
		$order = &new woocommerce_order( $order_id );
		
		if ( $order_id>0 && $order->user_id == get_current_user_id() ) :
			
			echo '<p>' . sprintf( __('Order <mark>#%s</mark> made on <mark>%s</mark>', 'woothemes'), $order->id, date(get_option('date_format'), strtotime($order->order_date)) );
			
			$status = get_term_by('slug', $order->status, 'shop_order_status');
			
			echo sprintf( __('. Order status: <mark>%s</mark>', 'woothemes'), $status->name );
			
			echo '.</p>';

			$notes = $order->get_customer_order_notes();
			if ($notes) :
				?>
				<h2><?php _e('Order Updates', 'woothemes'); ?></h2>
				<ol class="commentlist notes">	
					<?php foreach ($notes as $note) : ?>
					<li class="comment note">
						<div class="comment_container">			
							<div class="comment-text">
								<p class="meta"><?php echo date_i18n('l jS \of F Y, h:ia', strtotime($note->comment_date)); ?></p>
								<div class="description">
									<?php echo wpautop(wptexturize($note->comment_content)); ?>
								</div>
				  				<div class="clear"></div>
				  			</div>
							<div class="clear"></div>			
						</div>
					</li>
					<?php endforeach; ?>
				</ol>
				<?php
			endif;
			
			do_action( 'woocommerce_view_order', $order_id );
		
		else :
		
			wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
			exit;
			
		endif;
		
	else :
	
		wp_safe_redirect( get_permalink(get_option('woocommerce_myaccount_page_id')) );
		exit;
		
	endif;
}
