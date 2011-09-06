<?php
/**
 * Checkout
 * 
 * The WooCommerce checkout class handles the checkout process, collecting user data and processing the payment.
 *
 * @class 		woocommerce_checkout
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_checkout {
	
	var $posted;
	var $billing_fields;
	var $shipping_fields;
	var $must_create_account;
	var $creating_account;
	
	/** constructor */
	function __construct () {
		
		add_action('woocommerce_checkout_billing',array(&$this,'checkout_form_billing'));
		add_action('woocommerce_checkout_shipping',array(&$this,'checkout_form_shipping'));
		
		$this->must_create_account = true;
		
		if (get_option('woocommerce_enable_guest_checkout')=='yes' || is_user_logged_in()) $this->must_create_account = false;
	
		$this->billing_fields = array(
			array( 'name'=>'billing-first_name', 'label' => __('First Name', 'woothemes'), 'placeholder' => __('First Name', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'name'=>'billing-last_name', 'label' => __('Last Name', 'woothemes'), 'placeholder' => __('Last Name', 'woothemes'), 'required' => true, 'class' => array('form-row-last') ),
			array( 'name'=>'billing-company', 'label' => __('Company', 'woothemes'), 'placeholder' => __('Company', 'woothemes') ),
			array( 'name'=>'billing-address', 'label' => __('Address', 'woothemes'), 'placeholder' => __('Address 1', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'name'=>'billing-address-2', 'label' => __('Address 2', 'woothemes'), 'placeholder' => __('Address 2', 'woothemes'), 'class' => array('form-row-last'), 'label_class' => array('hidden') ),
			array( 'name'=>'billing-city', 'label' => __('City', 'woothemes'), 'placeholder' => __('City', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'validate' => 'postcode', 'format' => 'postcode', 'name'=>'billing-postcode', 'label' => __('Postcode', 'woothemes'), 'placeholder' => __('Postcode', 'woothemes'), 'required' => true, 'class' => array('form-row-last') ),
			array( 'type'=> 'country', 'name'=>'billing-country', 'label' => __('Country', 'woothemes'), 'required' => true, 'class' => array('form-row-first'), 'rel' => 'billing-state' ),
			array( 'type'=> 'state', 'name'=>'billing-state', 'label' => __('State/County', 'woothemes'), 'required' => true, 'class' => array('form-row-last'), 'rel' => 'billing-country' ),
			array( 'name'=>'billing-email', 'validate' => 'email', 'label' => __('Email Address', 'woothemes'), 'placeholder' => __('you@yourdomain.com', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'name'=>'billing-phone', 'validate' => 'phone', 'label' => __('Phone', 'woothemes'), 'placeholder' => __('Phone number', 'woothemes'), 'required' => true, 'class' => array('form-row-last') )
		);
		
		$this->shipping_fields = array(
			array( 'name'=>'shipping-first_name', 'label' => __('First Name', 'woothemes'), 'placeholder' => __('First Name', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'name'=>'shipping-last_name', 'label' => __('Last Name', 'woothemes'), 'placeholder' => __('Last Name', 'woothemes'), 'required' => true, 'class' => array('form-row-last') ),
			array( 'name'=>'shipping-company', 'label' => __('Company', 'woothemes'), 'placeholder' => __('Company', 'woothemes') ),
			array( 'name'=>'shipping-address', 'label' => __('Address', 'woothemes'), 'placeholder' => __('Address 1', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'name'=>'shipping-address-2', 'label' => __('Address 2', 'woothemes'), 'placeholder' => __('Address 2', 'woothemes'), 'class' => array('form-row-last'), 'label_class' => array('hidden') ),
			array( 'name'=>'shipping-city', 'label' => __('City', 'woothemes'), 'placeholder' => __('City', 'woothemes'), 'required' => true, 'class' => array('form-row-first') ),
			array( 'validate' => 'postcode', 'format' => 'postcode', 'name'=>'shipping-postcode', 'label' => __('Postcode', 'woothemes'), 'placeholder' => __('Postcode', 'woothemes'), 'required' => true, 'class' => array('form-row-last') ),
			array( 'type'=> 'country', 'name'=>'shipping-country', 'label' => __('Country', 'woothemes'), 'required' => true, 'class' => array('form-row-first'), 'rel' => 'shipping-state' ),
			array( 'type'=> 'state', 'name'=>'shipping-state', 'label' => __('State/County', 'woothemes'), 'required' => true, 'class' => array('form-row-last'), 'rel' => 'shipping-country' )
		);
	}
		
	/** Output the billing information form */
	function checkout_form_billing() {
		global $woocommerce;
		
		if ($woocommerce->cart->ship_to_billing_address_only()) :
			
			echo '<h3>'.__('Billing &amp Shipping', 'woothemes').'</h3>';
			
		else : 
		
			echo '<h3>'.__('Billing Address', 'woothemes').'</h3>';
		
		endif;
		
		// Billing Details
		foreach ($this->billing_fields as $field) :
			$this->checkout_form_field( $field );
		endforeach;
		
		// Registration Form
		if (!is_user_logged_in()) :
		
			if (get_option('woocommerce_enable_guest_checkout')=='yes') :
				
				echo '<p class="form-row"><input class="input-checkbox" id="createaccount" ';
				if ($this->get_value('createaccount')) echo 'checked="checked" '; 
				echo 'type="checkbox" name="createaccount" /> <label for="createaccount" class="checkbox">'.__('Create an account?', 'woothemes').'</label></p>';
				
			endif;
			
			echo '<div class="create-account">';
			
			echo '<p>'.__('Create an account by entering the information below. If you are a returning customer please login with your username at the top of the page.', 'woothemes').'</p>'; 
			
			$this->checkout_form_field( array( 'type' => 'text', 'name' => 'account-username', 'label' => __('Account username', 'woothemes'), 'placeholder' => __('Username', 'woothemes') ) );
			$this->checkout_form_field( array( 'type' => 'password', 'name' => 'account-password', 'label' => __('Account password', 'woothemes'), 'placeholder' => __('Password', 'woothemes'),'class' => array('form-row-first')) );
			$this->checkout_form_field( array( 'type' => 'password', 'name' => 'account-password-2', 'label' => __('Account password', 'woothemes'), 'placeholder' => __('Password', 'woothemes'),'class' => array('form-row-last'), 'label_class' => array('hidden')) );
			
			echo '<p><small>'.__('Save time in the future and check the status of your order by creating an account.', 'woothemes').'</small></p></div>';
							
		endif;
		
	}
	
	/** Output the shipping information form */
	function checkout_form_shipping() {
		global $woocommerce;
		
		// Shipping Details
		if ($woocommerce->cart->needs_shipping() && !$woocommerce->cart->ship_to_billing_address_only()) :
			
			echo '<p class="form-row" id="shiptobilling"><input class="input-checkbox" ';
			
			if (!$_POST) $shiptobilling = apply_filters('shiptobilling_default', 1); else $shiptobilling = $this->get_value('shiptobilling');
			if ($shiptobilling) echo 'checked="checked" '; 
			echo 'type="checkbox" name="shiptobilling" /> <label for="shiptobilling" class="checkbox">'.__('Ship to same address?', 'woothemes').'</label></p>';
			
			echo '<h3>'.__('Shipping Address', 'woothemes').'</h3>';
			
			echo'<div class="shipping-address">';
					
				foreach ($this->shipping_fields as $field) :
					$this->checkout_form_field( $field );
				endforeach;
								
			echo'</div>';
		
		elseif ($woocommerce->cart->ship_to_billing_address_only()) :
		
			echo '<h3>'.__('Notes/Comments', 'woothemes').'</h3>';
		
		endif;
		
		$this->checkout_form_field( array( 'type' => 'textarea', 'class' => array('notes'),  'name' => 'order_comments', 'label' => __('Order Notes', 'woothemes'), 'placeholder' => __('Notes about your order, e.g. special notes for delivery.', 'woothemes') ) );
		
	}

	/**
	 * Outputs a form field
	 *
	 * @param   array	args	contains a list of args for showing the field, merged with defaults (below)
	 */
	function checkout_form_field( $args ) {
		global $woocommerce;
		
		$defaults = array(
			'type' => 'input',
			'name' => '',
			'label' => '',
			'placeholder' => '',
			'required' => false,
			'class' => array(),
			'label_class' => array(),
			'rel' => '',
			'return' => false
		);
		
		$args = wp_parse_args( $args, $defaults );

		if ($args['required']) $required = ' <span class="required">*</span>'; else $required = '';
		
		if (in_array('form-row-last', $args['class'])) $after = '<div class="clear"></div>'; else $after = '';
		
		$field = '';
		
		switch ($args['type']) :
			case "country" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$args['name'].'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<select name="'.$args['name'].'" id="'.$args['name'].'" class="country_to_state" rel="'.$args['rel'].'">
						<option value="">'.__('Select a country&hellip;', 'woothemes').'</option>';
				
				foreach($woocommerce->countries->get_allowed_countries() as $key=>$value) :
					$field .= '<option value="'.$key.'"';
					if ($this->get_value($args['name'])==$key) $field .= 'selected="selected"';
					elseif (!$this->get_value($args['name']) && $woocommerce->customer->get_country()==$key) $field .= 'selected="selected"';
					$field .= '>'.__($value, 'woothemes').'</option>';
				endforeach;
				
				$field .= '</select></p>'.$after;

			break;
			case "state" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$args['name'].'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>';
					
				$current_cc = $this->get_value($args['rel']);
				if (!$current_cc) $current_cc = $woocommerce->customer->get_country();
				
				$current_r = $this->get_value($args['name']);
				if (!$current_r) $current_r = $woocommerce->customer->get_state();
				
				$states = $woocommerce->countries->states;	
					
				if (isset( $states[$current_cc][$current_r] )) :
					// Dropdown
					$field .= '<select name="'.$args['name'].'" id="'.$args['name'].'"><option value="">'.__('Select a state&hellip;', 'woothemes').'</option>';
					foreach($states[$current_cc] as $key=>$value) :
						$field .= '<option value="'.$key.'"';
						if ($current_r==$key) $field .= 'selected="selected"';
						$field .= '>'.__($value, 'woothemes').'</option>';
					endforeach;
					$field .= '</select>';
				else :
					// Input
					$field .= '<input type="text" class="input-text" value="'.$current_r.'" placeholder="'.__('State/County', 'woothemes').'" name="'.$args['name'].'" id="'.$args['name'].'" />';
				endif;
	
				$field .= '</p>'.$after;
				
			break;
			case "textarea" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$args['name'].'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<textarea name="'.$args['name'].'" class="input-text" id="'.$args['name'].'" placeholder="'.$args['placeholder'].'" cols="5" rows="2">'. $this->get_value( $args['name'] ).'</textarea>
				</p>'.$after;
				
			break;
			default :
			
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$args['name'].'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<input type="'.$args['type'].'" class="input-text" name="'.$args['name'].'" id="'.$args['name'].'" placeholder="'.$args['placeholder'].'" value="'. $this->get_value( $args['name'] ).'" />
				</p>'.$after;
				
			break;
		endswitch;
		
		if ($args['return']) return $field; else echo $field;
	}

	/** Process the checkout after the confirm order button is pressed */
	function process_checkout() {
	
		global $wpdb, $woocommerce;
		$validation = &new woocommerce_validation();
		
		if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);

		do_action('woocommerce_before_checkout_process');
		
		if (isset($_POST) && $_POST && !isset($_POST['login'])) :

			$woocommerce->cart->calculate_totals();
			
			$woocommerce->verify_nonce('process_checkout');
			
			if (sizeof($woocommerce->cart->cart_contents)==0) :
				$woocommerce->add_error( sprintf(__('Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'woothemes'), home_url()) );
			endif;
						
			// Checkout fields
			$this->posted['shiptobilling'] = isset($_POST['shiptobilling']) ? woocommerce_clean($_POST['shiptobilling']) : '';
			$this->posted['payment_method'] = isset($_POST['payment_method']) ? woocommerce_clean($_POST['payment_method']) : '';
			$this->posted['shipping_method'] = isset($_POST['shipping_method']) ? woocommerce_clean($_POST['shipping_method']) : '';
			$this->posted['order_comments'] = isset($_POST['order_comments']) ? woocommerce_clean($_POST['order_comments']) : '';
			$this->posted['terms'] = isset($_POST['terms']) ? woocommerce_clean($_POST['terms']) : '';
			$this->posted['createaccount'] = isset($_POST['createaccount']) ? woocommerce_clean($_POST['createaccount']) : '';
			$this->posted['account-username'] = isset($_POST['account-username']) ? woocommerce_clean($_POST['account-username']) : '';
			$this->posted['account-password'] = isset($_POST['account-password']) ? woocommerce_clean($_POST['account-password']) : '';
			$this->posted['account-password-2'] = isset($_POST['account-password-2']) ? woocommerce_clean($_POST['account-password-2']) : '';
			
			if ($woocommerce->cart->ship_to_billing_address_only()) $this->posted['shiptobilling'] = 'true';
			
			// Update shipping method to posted
			$_SESSION['_chosen_shipping_method'] = $this->posted['shipping_method'];
			
			// Billing Information
			foreach ($this->billing_fields as $field) :
				
				$this->posted[$field['name']] = isset($_POST[$field['name']]) ? woocommerce_clean($_POST[$field['name']]) : '';
				
				// Format
				if (isset($field['format'])) switch ( $field['format'] ) :
					case 'postcode' : $this->posted[$field['name']] = strtolower(str_replace(' ', '', $this->posted[$field['name']])); break;
				endswitch;
				
				// Required
				if ( isset($field['required']) && $field['required'] && empty($this->posted[$field['name']]) ) $woocommerce->add_error( $field['label'] . __(' (billing) is a required field.', 'woothemes') );
	
				// Validation
				if (isset($field['validate']) && !empty($this->posted[$field['name']])) switch ( $field['validate'] ) :
					case 'phone' :
						if (!$validation->is_phone( $this->posted[$field['name']] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid number.', 'woothemes') ); endif;
					break;
					case 'email' :
						if (!$validation->is_email( $this->posted[$field['name']] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid email address.', 'woothemes') ); endif;
					break;
					case 'postcode' :
						if (!$validation->is_postcode( $this->posted[$field['name']], $_POST['billing-country'] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid postcode/ZIP.', 'woothemes') ); 
						else :
							$this->posted[$field['name']] = $validation->format_postcode( $this->posted[$field['name']], $_POST['billing-country'] );
						endif;
					break;
				endswitch;
				
			endforeach;
			
			// Shipping Information
			if ($woocommerce->cart->needs_shipping() && !$woocommerce->cart->ship_to_billing_address_only() && empty($this->posted['shiptobilling'])) :
				
				foreach ($this->shipping_fields as $field) :
					if (isset( $_POST[$field['name']] )) $this->posted[$field['name']] = woocommerce_clean($_POST[$field['name']]); else $this->posted[$field['name']] = '';
					
					// Format
					if (isset($field['format'])) switch ( $field['format'] ) :
						case 'postcode' : $this->posted[$field['name']] = strtolower(str_replace(' ', '', $this->posted[$field['name']])); break;
					endswitch;
					
					// Required
					if ( isset($field['required']) && $field['required'] && empty($this->posted[$field['name']]) ) $woocommerce->add_error( $field['label'] . __(' (shipping) is a required field.', 'woothemes') );
		
					// Validation
					if (isset($field['validate']) && !empty($this->posted[$field['name']])) switch ( $field['validate'] ) :
						case 'postcode' :
							if (!$validation->is_postcode( $this->posted[$field['name']], $this->posted['shipping-country'] )) : $woocommerce->add_error( $field['label'] . __(' (shipping) is not a valid postcode/ZIP.', 'woothemes') ); 
							else :
								$this->posted[$field['name']] = $validation->format_postcode( $this->posted[$field['name']], $this->posted['shipping-country'] );
							endif;
						break;
					endswitch;
					
				endforeach;
			
			endif;

			if (is_user_logged_in()) :
				$this->creating_account = false;
			elseif (isset($this->posted['createaccount']) && $this->posted['createaccount']) :
				$this->creating_account = true;
			elseif ($this->must_create_account) :
				$this->creating_account = true;
			else :
				$this->creating_account = false;
			endif;
			
			if ($this->creating_account && !$user_id) :
			
				if ( empty($this->posted['account-username']) ) $woocommerce->add_error( __('Please enter an account username.', 'woothemes') );
				if ( empty($this->posted['account-password']) ) $woocommerce->add_error( __('Please enter an account password.', 'woothemes') );
				if ( $this->posted['account-password-2'] !== $this->posted['account-password'] ) $woocommerce->add_error( __('Passwords do not match.', 'woothemes') );
			
				// Check the username
				if ( !validate_username( $this->posted['account-username'] ) ) :
					$woocommerce->add_error( __('Invalid email/username.', 'woothemes') );
				elseif ( username_exists( $this->posted['account-username'] ) ) :
					$woocommerce->add_error( __('An account is already registered with that username. Please choose another.', 'woothemes') );
				endif;
				
				// Check the e-mail address
				if ( email_exists( $this->posted['billing-email'] ) ) :
					$woocommerce->add_error( __('An account is already registered with your email address. Please login.', 'woothemes') );
				endif;
			endif;
			
			// Terms
			if (!isset($_POST['update_totals']) && empty($this->posted['terms']) && get_option('woocommerce_terms_page_id')>0 ) $woocommerce->add_error( __('You must accept our Terms &amp; Conditions.', 'woothemes') );
			
			if ($woocommerce->cart->needs_shipping()) :
			
				// Shipping Method
				$available_methods = $woocommerce->shipping->get_available_shipping_methods();
				if (!isset($available_methods[$this->posted['shipping_method']])) :
					$woocommerce->add_error( __('Invalid shipping method.', 'woothemes') );
				endif;	
			
			endif;	
			
			if ($woocommerce->cart->needs_payment()) :
				// Payment Method
				$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
				if (!isset($available_gateways[$this->posted['payment_method']])) :
					$woocommerce->add_error( __('Invalid payment method.', 'woothemes') );
				else :
					// Payment Method Field Validation
					$available_gateways[$this->posted['payment_method']]->validate_fields();
				endif;
			endif;
					
			if (!isset($_POST['update_totals']) && $woocommerce->error_count()==0) :
				
				$user_id = get_current_user_id();
				
				while (1) :
					
					// Create customer account and log them in
					if ($this->creating_account && !$user_id) :
				
						$reg_errors = new WP_Error();
						do_action('register_post', $this->posted['billing-email'], $this->posted['billing-email'], $reg_errors);
						$errors = apply_filters( 'registration_errors', $reg_errors, $this->posted['billing-email'], $this->posted['billing-email'] );
				
		                // if there are no errors, let's create the user account
						if ( !$reg_errors->get_error_code() ) :
		
			                $user_pass = $this->posted['account-password'];
			                $user_id = wp_create_user( $this->posted['account-username'], $user_pass, $this->posted['billing-email'] );
			                if ( !$user_id ) {
			                	$woocommerce->add_error( sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'woothemes'), get_option('admin_email')));
			                    break;
			                }
		
		                    // Change role
		                    wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;
		
		                    // send the user a confirmation and their login details
		                    wp_new_user_notification( $user_id, $user_pass );
		
		                    // set the WP login cookie
		                    $secure_cookie = is_ssl() ? true : false;
		                    wp_set_auth_cookie($user_id, true, $secure_cookie);
						
						else :
							$woocommerce->add_error( $reg_errors->get_error_message() );
		                	break;                    
						endif;
						
					endif;

					// Get shipping/billing
					if ( !empty($this->posted['shiptobilling']) ) :
					
						$shipping_first_name = $this->posted['billing-first_name'];
						$shipping_last_name = $this->posted['billing-last_name'];
						$shipping_company = $this->posted['billing-company'];
						$shipping_address_1 = $this->posted['billing-address'];
						$shipping_address_2 = $this->posted['billing-address-2'];
						$shipping_city = $this->posted['billing-city'];							
						$shipping_state = $this->posted['billing-state'];
						$shipping_postcode = $this->posted['billing-postcode'];	
						$shipping_country = $this->posted['billing-country'];
						
					elseif ( $woocommerce->cart->needs_shipping() ) :
								
						$shipping_first_name = $this->posted['shipping-first_name'];
						$shipping_last_name = $this->posted['shipping-last_name'];
						$shipping_company = $this->posted['shipping-company'];
						$shipping_address_1 = $this->posted['shipping-address'];
						$shipping_address_2 = $this->posted['shipping-address-2'];
						$shipping_city = $this->posted['shipping-city'];							
						$shipping_state = $this->posted['shipping-state'];
						$shipping_postcode = $this->posted['shipping-postcode'];	
						$shipping_country = $this->posted['shipping-country'];
						
					endif;
					
					// Save billing/shipping to user meta fields
					if ($user_id>0) :
					
						update_user_meta( $user_id, 'billing-first_name', $this->posted['billing-first_name'] );
						update_user_meta( $user_id, 'billing-last_name', $this->posted['billing-last_name'] );
						update_user_meta( $user_id, 'billing-company', $this->posted['billing-company'] );
						update_user_meta( $user_id, 'billing-email', $this->posted['billing-email'] );
						update_user_meta( $user_id, 'billing-address', $this->posted['billing-address'] );
						update_user_meta( $user_id, 'billing-address-2', $this->posted['billing-address-2'] );
						update_user_meta( $user_id, 'billing-city', $this->posted['billing-city'] );
						update_user_meta( $user_id, 'billing-postcode', $this->posted['billing-postcode'] );
						update_user_meta( $user_id, 'billing-country', $this->posted['billing-country'] );
						update_user_meta( $user_id, 'billing-state', $this->posted['billing-state'] );
						update_user_meta( $user_id, 'billing-phone', $this->posted['billing-phone'] );

						if ( empty($this->posted['shiptobilling']) && $woocommerce->cart->needs_shipping() ) :
							update_user_meta( $user_id, 'shipping-first_name', $this->posted['shipping-first_name'] );
							update_user_meta( $user_id, 'shipping-last_name', $this->posted['shipping-last_name'] );
							update_user_meta( $user_id, 'shipping-company', $this->posted['shipping-company'] );
							update_user_meta( $user_id, 'shipping-address', $this->posted['shipping-address'] );
							update_user_meta( $user_id, 'shipping-address-2', $this->posted['shipping-address-2'] );
							update_user_meta( $user_id, 'shipping-city', $this->posted['shipping-city'] );
							update_user_meta( $user_id, 'shipping-postcode', $this->posted['shipping-postcode'] );
							update_user_meta( $user_id, 'shipping-country', $this->posted['shipping-country'] );
							update_user_meta( $user_id, 'shipping-state', $this->posted['shipping-state'] );
						elseif ( $this->posted['shiptobilling'] && $woocommerce->cart->needs_shipping() ) :
							update_user_meta( $user_id, 'shipping-first_name', $this->posted['billing-first_name'] );
							update_user_meta( $user_id, 'shipping-last_name', $this->posted['billing-last_name'] );
							update_user_meta( $user_id, 'shipping-company', $this->posted['billing-company'] );
							update_user_meta( $user_id, 'shipping-address', $this->posted['billing-address'] );
							update_user_meta( $user_id, 'shipping-address-2', $this->posted['billing-address-2'] );
							update_user_meta( $user_id, 'shipping-city', $this->posted['billing-city'] );
							update_user_meta( $user_id, 'shipping-postcode', $this->posted['billing-postcode'] );
							update_user_meta( $user_id, 'shipping-country', $this->posted['billing-country'] );
							update_user_meta( $user_id, 'shipping-state', $this->posted['billing-state'] );
						endif;
						
					endif;
					
					// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected last time.
					$_tax = new woocommerce_tax();
					
					$order_data = array(
						'post_type' => 'shop_order',
						'post_title' => 'Order &ndash; '.date('F j, Y @ h:i A'),
						'post_status' => 'publish',
						'post_excerpt' => $this->posted['order_comments'],
						'post_author' => 1
					);

					// Cart items
					$order_items = array();
					
					foreach ($woocommerce->cart->cart_contents as $cart_item_key => $values) :
						
						$_product = $values['data'];
			
						// Calc item tax to store
						$rate = '';
						if ( $_product->is_taxable()) :
							$rate = $_tax->get_rate( $_product->tax_class );
						endif;
						
						// Store any item meta data
						$item_meta = array();
						
						// Variations meta
						if ($values['variation'] && is_array($values['variation'])) :
							foreach ($values['variation'] as $key => $value) :
								$item_meta[ sanitize_title(str_replace('tax_', '', $key)) ] = $value;
							endforeach;
						endif;

						// Run filter
						$item_meta = apply_filters('order_item_meta', $item_meta, $values);
						
						$order_items[] = apply_filters('new_order_item', array(
					 		'id' 			=> $values['product_id'],
					 		'variation_id' 	=> $values['variation_id'],
					 		'name' 			=> $_product->get_title(),
					 		'qty' 			=> (int) $values['quantity'],
					 		'cost' 			=> $_product->get_price_excluding_tax(),
					 		'taxrate' 		=> $rate,
					 		'item_meta'		=> $item_meta
					 	), $values);
					 	
					 	// Check stock levels
					 	if ($_product->managing_stock()) :
							if (!$_product->is_in_stock() || !$_product->has_enough_stock( $values['quantity'] )) :
								
								$woocommerce->add_error( sprintf(__('Sorry, we do not have enough "%s" in stock to fulfil your order. Please edit your cart and try again. We apologise for any inconvenience caused.', 'woothemes'), $_product->get_title() ) );
		                		break;
								
							endif;
						else :
						
							if (!$_product->is_in_stock()) :
							
								$woocommerce->add_error( sprintf(__('Sorry, we do not have enough "%s" in stock to fulfil your order. Please edit your cart and try again. We apologise for any inconvenience caused.', 'woothemes'), $_product->get_title() ) );
		                		break;

							endif;
							
						endif;
					 	
					endforeach;
					
					if ($woocommerce->error_count()>0) break;
					
					// Insert or update the post data
					if (isset($_SESSION['order_awaiting_payment']) && $_SESSION['order_awaiting_payment'] > 0) :
						
						$order_id = (int) $_SESSION['order_awaiting_payment'];
						$order_data['ID'] = $order_id;
						wp_update_post( $order_data );
						do_action('woocommerce_resume_order', $order_id);
						
					else :
						$order_id = wp_insert_post( $order_data );
						
						if (is_wp_error($order_id)) :
							$woocommerce->add_error( 'Error: Unable to create order. Please try again.' );
			                break;
						else :
							// Inserted successfully 
							do_action('woocommerce_new_order', $order_id);
						endif;
					endif;
					
					// Get better formatted shipping method (title/label)
					$shipping_method = $this->posted['shipping_method'];
					if (isset($available_methods) && isset($available_methods[$this->posted['shipping_method']])) :
						$shipping_method = $available_methods[$this->posted['shipping_method']]->title;
					endif;
					
					// Update order meta
					update_post_meta( $order_id, '_billing_first_name', 	$this->posted['billing-first_name']);
					update_post_meta( $order_id, '_billing_last_name', 		$this->posted['billing-last_name']);
					update_post_meta( $order_id, '_billing_company', 		$this->posted['billing-company']);
					update_post_meta( $order_id, '_billing_address_1', 		$this->posted['billing-address']);
					update_post_meta( $order_id, '_billing_address_2', 		$this->posted['billing-address-2']);
					update_post_meta( $order_id, '_billing_city', 			$this->posted['billing-city']);
					update_post_meta( $order_id, '_billing_postcode', 		$this->posted['billing-postcode']);
					update_post_meta( $order_id, '_billing_country', 		$this->posted['billing-country']);
					update_post_meta( $order_id, '_billing_state', 			$this->posted['billing-state']);
					update_post_meta( $order_id, '_billing_email', 			$this->posted['billing-email']);
					update_post_meta( $order_id, '_billing_phone', 			$this->posted['billing-phone']);
					update_post_meta( $order_id, '_shipping_first_name', 	$shipping_first_name);
					update_post_meta( $order_id, '_shipping_last_name', 	$shipping_last_name);
					update_post_meta( $order_id, '_shipping_company', 		$shipping_company);
					update_post_meta( $order_id, '_shipping_address_1', 	$shipping_address_1);
					update_post_meta( $order_id, '_shipping_address_2', 	$shipping_address_2);
					update_post_meta( $order_id, '_shipping_city', 			$shipping_city);
					update_post_meta( $order_id, '_shipping_postcode', 		$shipping_postcode);
					update_post_meta( $order_id, '_shipping_country', 		$shipping_country);
					update_post_meta( $order_id, '_shipping_state', 		$shipping_state);
					update_post_meta( $order_id, '_shipping_method', 		$shipping_method);
					update_post_meta( $order_id, '_payment_method', 		$this->posted['payment_method']);
					update_post_meta( $order_id, '_order_subtotal', 		number_format($woocommerce->cart->subtotal_ex_tax, 2, '.', ''));
					update_post_meta( $order_id, '_order_shipping', 		number_format($woocommerce->cart->shipping_total, 2, '.', ''));
					update_post_meta( $order_id, '_order_discount', 		number_format($woocommerce->cart->discount_total, 2, '.', ''));
					update_post_meta( $order_id, '_order_tax', 				number_format($woocommerce->cart->tax_total, 2, '.', ''));
					update_post_meta( $order_id, '_order_shipping_tax', 	number_format($woocommerce->cart->shipping_tax_total, 2, '.', ''));
					update_post_meta( $order_id, '_order_total', 			number_format($woocommerce->cart->total, 2, '.', ''));
					update_post_meta( $order_id, '_order_key', 				uniqid('order_') );
					update_post_meta( $order_id, '_customer_user', 			(int) $user_id );
					update_post_meta( $order_id, '_order_items', 			$order_items );
					
					// Order status
					wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
						
					// Discount code meta
					if ($woocommerce->cart->applied_coupons) update_post_meta($order_id, 'coupons', implode(', ', $woocommerce->cart->applied_coupons));
					
					$order = &new woocommerce_order($order_id);

					if ($woocommerce->cart->needs_payment()) :
						
						// Store Order ID in session so it can be re-used after payment failure
						$_SESSION['order_awaiting_payment'] = $order_id;
					
						// Process Payment
						$result = $available_gateways[$this->posted['payment_method']]->process_payment( $order_id );
						
						// Redirect to success/confirmation/payment page
						if ($result['result']=='success') :
						
							if (is_ajax()) : 
								ob_clean();
								echo json_encode($result);
								exit;
							else :
								wp_safe_redirect( $result['redirect'] );
								exit;
							endif;
							
						endif;
					
					else :
					
						// No payment was required for order
						$order->payment_complete();
						
						// Empty the Cart
						$woocommerce->cart->empty_cart();
						
						// Redirect to success/confirmation/payment page
						if (is_ajax()) : 
							ob_clean();
							echo json_encode( array('redirect'	=> get_permalink(get_option('woocommerce_thanks_page_id'))) );
							exit;
						else :
							wp_safe_redirect( get_permalink(get_option('woocommerce_thanks_page_id')) );
							exit;
						endif;
						
					endif;
					
					// Break out of loop
					break;
				
				endwhile;
	
			endif;
			
			// If we reached this point then there were errors
			if (is_ajax()) : 
				ob_clean();
				$woocommerce->show_messages();
				exit;
			else :
				$woocommerce->show_messages();
			endif;
		
		endif;
	}
	
	/** Gets the value either from the posted data, or from the users meta data */
	function get_value( $input ) {
		if (isset( $this->posted[$input] ) && !empty($this->posted[$input])) :
			return $this->posted[$input];
		elseif (is_user_logged_in()) :
			if (get_user_meta( get_current_user_id(), $input, true )) return get_user_meta( get_current_user_id(), $input, true );
			
			$current_user = wp_get_current_user();

			switch ( $input ) :
				
				case "billing-email" :
					return $current_user->user_email;
				break;
				
			endswitch;
		endif;
	}
}