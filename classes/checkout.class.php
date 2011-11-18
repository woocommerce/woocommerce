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
		
		// Define billing fields in an array. This can be hooked into and filtered if you wish to change/add anything.
		$this->billing_fields = apply_filters('woocommerce_billing_fields', array(
			'billing_first_name' => array( 
				'name'			=>'billing_first_name', 
				'label' 		=> __('First Name', 'woothemes'), 
				'placeholder' 	=> __('First Name', 'woothemes'), 
				'required' 		=> true, 
				'class'			=> array('form-row-first') 
				),
			'billing_last_name' => array( 
				'label' 		=> __('Last Name', 'woothemes'), 
				'placeholder' 	=> __('Last Name', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last') 
				),
			'billing_company' 	=> array( 
				'label' 		=> __('Company', 'woothemes'), 
				'placeholder' 	=> __('Company', 'woothemes') 
				),
			'billing_address' 	=> array( 
				'label' 		=> __('Address', 'woothemes'), 
				'placeholder' 	=> __('Address 1', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'billing_address-2' => array( 
				'label' 		=> __('Address 2', 'woothemes'), 
				'placeholder' 	=> __('Address 2', 'woothemes'), 
				'class' 		=> array('form-row-last'), 
				'label_class' 	=> array('hidden') 
				),
			'billing_city' 		=> array( 
				'label' 		=> __('City', 'woothemes'), 
				'placeholder' 	=> __('City', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'billing_postcode' 	=> array( 
				'label' 		=> __('Postcode', 'woothemes'), 
				'placeholder' 	=> __('Postcode', 'woothemes'), 
				'required' 		=> true, 
				'class'			=> array('form-row-last update_totals_on_change') 
				),
			'billing_country' 	=> array( 
				'type'			=> 'country', 
				'label' 		=> __('Country', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first update_totals_on_change'), 
				'rel' 			=> 'billing_state' 
				),
			'billing_state' 	=> array( 
				'type'			=> 'state', 
				'name'			=>'billing_state', 
				'label' 		=> __('State/County', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last update_totals_on_change'), 
				'rel' 			=> 'billing_country' 
				),
			'billing_email' 	=> array( 
				'label' 		=> __('Email Address', 'woothemes'), 
				'placeholder' 	=> __('you@yourdomain.com', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'billing_phone' 	=> array( 
				'label' 		=> __('Phone', 'woothemes'), 
				'placeholder' 	=> __('Phone number', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last') 
				)
		));
		
		// Define shipping fields in an array. This can be hooked into and filtered if you wish to change/add anything.
		$this->shipping_fields = apply_filters('woocommerce_shipping_fields', array(
			'shipping_first_name' => array( 
				'label' 		=> __('First Name', 'woothemes'), 
				'placeholder' 	=> __('First Name', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'shipping_last_name' => array( 
				'label' 		=> __('Last Name', 'woothemes'), 
				'placeholder' 	=> __('Last Name', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last') 
				),
			'shipping_company' 	=> array( 
				'label' 		=> __('Company', 'woothemes'), 
				'placeholder' 	=> __('Company', 'woothemes') 
				),
			'shipping_address' 	=> array( 
				'label' 		=> __('Address', 'woothemes'), 
				'placeholder' 	=> __('Address 1', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'shipping_address-2' => array( 
				'label' 		=> __('Address 2', 'woothemes'), 
				'placeholder' 	=> __('Address 2', 'woothemes'), 
				'class' 		=> array('form-row-last'), 
				'label_class' 	=> array('hidden') 
				),
			'shipping_city' 	=> array( 
				'label' 		=> __('City', 'woothemes'), 
				'placeholder' 	=> __('City', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first') 
				),
			'shipping_postcode' => array( 
				'label' 		=> __('Postcode', 'woothemes'), 
				'placeholder' 	=> __('Postcode', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last update_totals_on_change') 
				),
			'shipping_country' 	=> array( 
				'type'			=> 'country', 
				'label' 		=> __('Country', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-first update_totals_on_change'), 
				'rel' 			=> 'shipping_state' 
				),
			'shipping_state' 	=> array( 
				'type'			=> 'state', 
				'label' 		=> __('State/County', 'woothemes'), 
				'required' 		=> true, 
				'class' 		=> array('form-row-last update_totals_on_change'), 
				'rel' 			=> 'shipping_country' 
				)
		));
	}
		
	/** Output the billing information form */
	function checkout_form_billing() {
		global $woocommerce;
		
		if ($woocommerce->cart->ship_to_billing_address_only()) :
			echo '<h3>'.__('Billing &amp Shipping', 'woothemes').'</h3>';
		else : 
			echo '<h3>'.__('Billing Address', 'woothemes').'</h3>';
		endif;
		
		// Output billing form fields
		do_action('woocommerce_before_checkout_billing_form', $this);
		foreach ($this->billing_fields as $key => $field) :
			$this->checkout_form_field( $key, $field );
		endforeach;
		do_action('woocommerce_after_checkout_billing_form', $this);
		
		// Registration Form Fields
		if (!is_user_logged_in() && get_option('woocommerce_enable_signup_and_login_from_checkout')=="yes") :
		
			if (get_option('woocommerce_enable_guest_checkout')=='yes') :
				
				echo '<p class="form-row"><input class="input-checkbox" id="createaccount" '.checked($this->get_value('createaccount'), true).' type="checkbox" name="createaccount" value="1" /> <label for="createaccount" class="checkbox">'.__('Create an account?', 'woothemes').'</label></p>';
				
			endif;
			
			echo '<div class="create-account">';
			
			echo '<p>'.__('Create an account by entering the information below. If you are a returning customer please login with your username at the top of the page.', 'woothemes').'</p>'; 
			
			$this->checkout_form_field( 'account_username', array( 
				'type' => 'text', 
				'label' => __('Account username', 'woothemes'), 
				'placeholder' => __('Username', 'woothemes') 
				));
			$this->checkout_form_field( 'account_password', array( 
				'type' => 'password', 
				'label' => __('Account password', 'woothemes'), 
				'placeholder' => __('Password', 'woothemes'),
				'class' => array('form-row-first')
				));
			$this->checkout_form_field( 'account_password-2', array( 
				'type' => 'password', 
				'label' => __('Account password', 'woothemes'), 
				'placeholder' => __('Password', 'woothemes'),
				'class' => array('form-row-last'), 
				'label_class' => array('hidden')
				));
			
			echo '</div>';
							
		endif;
		
	}
	
	/** Output the shipping information form */
	function checkout_form_shipping() {
		global $woocommerce;
		
		// Shipping Details
		if ($woocommerce->cart->needs_shipping() && !$woocommerce->cart->ship_to_billing_address_only()) :
			
			if (!isset($_POST) || !$_POST) $shiptobilling = apply_filters('woocommerce_shiptobilling_default', 1); else $shiptobilling = $this->get_value('shiptobilling');

			echo '<p class="form-row" id="shiptobilling"><input class="input-checkbox" '.checked($shiptobilling, 1, false).' type="checkbox" name="shiptobilling" value="1" /> <label for="shiptobilling" class="checkbox">'.__('Ship to same address?', 'woothemes').'</label></p>';
			
			echo '<h3>'.__('Shipping Address', 'woothemes').'</h3>';
			
			echo'<div class="shipping_address">';
					
				// Output shipping form fields
				do_action('woocommerce_before_checkout_shipping_form', $this);
				foreach ($this->shipping_fields as $key => $field) :
					$this->checkout_form_field( $key, $field );
				endforeach;
				do_action('woocommerce_after_checkout_shipping_form', $this);
								
			echo '</div>';
		
		endif;
		
		do_action('woocommerce_before_order_notes', $this);
		
		if (get_option('woocommerce_enable_order_comments')!='no') :
		
			if ($woocommerce->cart->ship_to_billing_address_only()) :
				echo '<h3>'.__('Additional Information', 'woothemes').'</h3>';
			endif;
			
			$this->checkout_form_field( 'order_comments', array( 
				'type' => 'textarea', 
				'class' => array('notes'), 
				'label' => __('Order Notes', 'woothemes'), 
				'placeholder' => __('Notes about your order, e.g. special notes for delivery.', 'woothemes') 
				));
					
		endif;
		
		do_action('woocommerce_after_order_notes', $this);
	}

	/**
	 * Outputs a checkout form field
	 */
	function checkout_form_field( $key, $args ) {
		global $woocommerce;
		
		$defaults = array(
			'type' => 'text',
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
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<select name="'.$key.'" id="'.$key.'" class="country_to_state" rel="'.$args['rel'].'">
						<option value="">'.__('Select a country&hellip;', 'woothemes').'</option>';
				
				foreach($woocommerce->countries->get_allowed_countries() as $ckey=>$value) :
					$field .= '<option value="'.$ckey.'" '.selected($this->get_value($key), $ckey, false).'>'.__($value, 'woothemes').'</option>';
				endforeach;
				
				$field .= '</select></p>'.$after;

			break;
			case "state" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>';
					
				$current_cc = $this->get_value($args['rel']);
				if (!$current_cc) $current_cc = $woocommerce->customer->get_country();
				
				$current_r = $this->get_value($key);
				if (!$current_r) $current_r = $woocommerce->customer->get_state();
				
				$states = $woocommerce->countries->states;	
					
				if (isset( $states[$current_cc][$current_r] )) :
					// Dropdown
					$field .= '<select name="'.$key.'" id="'.$key.'"><option value="">'.__('Select a state&hellip;', 'woothemes').'</option>';
					foreach($states[$current_cc] as $ckey=>$value) :
						$field .= '<option value="'.$ckey.'" '.selected($current_r, $ckey, false).'>'.__($value, 'woothemes').'</option>';
					endforeach;
					$field .= '</select>';
				else :
					// Input
					$field .= '<input type="text" class="input-text" value="'.$current_r.'" placeholder="'.__('State/County', 'woothemes').'" name="'.$key.'" id="'.$key.'" />';
				endif;
	
				$field .= '</p>'.$after;
				
			break;
			case "textarea" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<textarea name="'.$key.'" class="input-text" id="'.$key.'" placeholder="'.$args['placeholder'].'" cols="5" rows="2">'. esc_textarea( $this->get_value( $key ) ).'</textarea>
				</p>'.$after;
				
			break;
			case "checkbox" :
				
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<input type="'.$args['type'].'" class="input-checkbox" name="'.$key.'" id="'.$key.'" value="1" '.checked($this->get_value( $key ), 1, false).' />
					<label for="'.$key.'" class="checkbox '.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
				</p>'.$after;
				
			break;
			default :
			
				$field = '<p class="form-row '.implode(' ', $args['class']).'">
					<label for="'.$key.'" class="'.implode(' ', $args['label_class']).'">'.$args['label'].$required.'</label>
					<input type="text" class="input-text" name="'.$key.'" id="'.$key.'" placeholder="'.$args['placeholder'].'" value="'. $this->get_value( $key ).'" />
				</p>'.$after;
				
			break;
		endswitch;
		
		if ($args['return']) return $field; else echo $field;
	}


	/**
	 * Process the checkout after the confirm order button is pressed
	 */
	function process_checkout() {
	
		global $wpdb, $woocommerce;
		$validation = &new woocommerce_validation();
		
		if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);

		do_action('woocommerce_before_checkout_process');
		
		if (isset($_POST) && $_POST && !isset($_POST['login'])) :

			$woocommerce->verify_nonce('process_checkout');

			if (sizeof($woocommerce->cart->get_cart())==0) :
				$woocommerce->add_error( sprintf(__('Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'woothemes'), home_url()) );
			endif;
			
			do_action('woocommerce_checkout_process');
						
			// Checkout fields (non-shipping/billing)
			$this->posted['shiptobilling'] 		= isset($_POST['shiptobilling']) ? 1 : 0;
			$this->posted['terms'] 				= isset($_POST['terms']) ? 1 : 0;
			$this->posted['createaccount'] 		= isset($_POST['createaccount']) ? 1 : 0;
			$this->posted['payment_method'] 	= isset($_POST['payment_method']) ? woocommerce_clean($_POST['payment_method']) : '';
			$this->posted['shipping_method']	= isset($_POST['shipping_method']) ? woocommerce_clean($_POST['shipping_method']) : '';
			$this->posted['order_comments'] 	= isset($_POST['order_comments']) ? woocommerce_clean($_POST['order_comments']) : '';
			$this->posted['account_username']	= isset($_POST['account_username']) ? woocommerce_clean($_POST['account_username']) : '';
			$this->posted['account_password'] 	= isset($_POST['account_password']) ? woocommerce_clean($_POST['account_password']) : '';
			$this->posted['account_password-2'] = isset($_POST['account_password-2']) ? woocommerce_clean($_POST['account_password-2']) : '';
			
			if ($woocommerce->cart->ship_to_billing_address_only()) $this->posted['shiptobilling'] = 1;
			
			// Update customer shipping method to posted method
			$_SESSION['_chosen_shipping_method'] = $this->posted['shipping_method'];
			
			// Update cart totals
			$woocommerce->cart->calculate_totals();
			
			// Billing Information
			foreach ($this->billing_fields as $key => $field) :
				
				$this->posted[$key] = isset($_POST[$key]) ? woocommerce_clean($_POST[$key]) : '';
				
				// Hook
				$this->posted[$key] = apply_filters('woocommerce_process_checkout_field_' . $key, $this->posted[$key]);
				
				// Special handling for validation and formatting
				switch ($key) :
					case "billing_postcode" :
						$this->posted[$key] = strtolower(str_replace(' ', '', $this->posted[$key]));
						
						if (!$validation->is_postcode( $this->posted[$key], $_POST['billing_country'] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid postcode/ZIP.', 'woothemes') ); 
						else :
							$this->posted[$key] = $validation->format_postcode( $this->posted[$key], $_POST['billing_country'] );
						endif;
					break;
					case "billing_phone" :
						if (!$validation->is_phone( $this->posted[$key] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid number.', 'woothemes') ); endif;
					break;
					case "billing_email" :
						if (!$validation->is_email( $this->posted[$key] )) : $woocommerce->add_error( $field['label'] . __(' (billing) is not a valid email address.', 'woothemes') ); endif;
					break;
				endswitch;
				
				// Required
				if ( isset($field['required']) && $field['required'] && empty($this->posted[$key]) ) $woocommerce->add_error( $field['label'] . __(' (billing) is a required field.', 'woothemes') );
				
			endforeach;
			
			// Update customer location to posted location so we can correctly check available shipping methods
			$woocommerce->customer->set_country( $this->posted['billing_country'] );
			$woocommerce->customer->set_state( $this->posted['billing_state'] );
			$woocommerce->customer->set_postcode( $this->posted['billing_postcode'] );
			
			// Shipping Information
			if ($woocommerce->cart->needs_shipping() && !$woocommerce->cart->ship_to_billing_address_only() && empty($this->posted['shiptobilling'])) :
				
				foreach ($this->shipping_fields as $key => $field) :
				
					if (isset( $_POST[$key] )) $this->posted[$key] = woocommerce_clean($_POST[$key]); else $this->posted[$key] = '';
					
					// Hook
					$this->posted[$key] = apply_filters('woocommerce_process_checkout_field_' . $key, $this->posted[$key]);
					
					// Special handling for validation and formatting
					switch ($key) :
						case "shipping_postcode" :
							$this->posted[$key] = strtolower(str_replace(' ', '', $this->posted[$key]));
							
							if (!$validation->is_postcode( $this->posted[$key], $this->posted['shipping_country'] )) : $woocommerce->add_error( $field['label'] . __(' (shipping) is not a valid postcode/ZIP.', 'woothemes') ); 
							else :
								$this->posted[$key] = $validation->format_postcode( $this->posted[$key], $this->posted['shipping_country'] );
							endif;
						break;
					endswitch;
					
					// Required
					if ( isset($field['required']) && $field['required'] && empty($this->posted[$key]) ) $woocommerce->add_error( $field['label'] . __(' (shipping) is a required field.', 'woothemes') );
					
				endforeach;
				
				// Update customer location to posted location so we can correctly check available shipping methods
				$woocommerce->customer->set_shipping_country( $this->posted['shipping_country'] );
				$woocommerce->customer->set_shipping_state( $this->posted['shipping_state'] );  
				$woocommerce->customer->set_shipping_postcode( $this->posted['shipping_postcode'] ); 
				
			else :
			
				// Update customer location to posted location so we can correctly check available shipping methods
				$woocommerce->customer->set_shipping_country( $this->posted['billing_country'] );
				$woocommerce->customer->set_shipping_state( $this->posted['billing_state'] );
				$woocommerce->customer->set_shipping_postcode( $this->posted['billing_postcode'] );
				
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
			
			if ($this->creating_account) :
			
				if ( empty($this->posted['account_username']) ) $woocommerce->add_error( __('Please enter an account username.', 'woothemes') );
				if ( empty($this->posted['account_password']) ) $woocommerce->add_error( __('Please enter an account password.', 'woothemes') );
				if ( $this->posted['account_password-2'] !== $this->posted['account_password'] ) $woocommerce->add_error( __('Passwords do not match.', 'woothemes') );
			
				// Check the username
				if ( !validate_username( $this->posted['account_username'] ) ) :
					$woocommerce->add_error( __('Invalid email/username.', 'woothemes') );
				elseif ( username_exists( $this->posted['account_username'] ) ) :
					$woocommerce->add_error( __('An account is already registered with that username. Please choose another.', 'woothemes') );
				endif;
				
				// Check the e-mail address
				if ( email_exists( $this->posted['billing_email'] ) ) :
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
			
			do_action( 'woocommerce_after_checkout_validation', $this->posted );
					
			if (!isset($_POST['update_totals']) && $woocommerce->error_count()==0) :
				
				$user_id = get_current_user_id();
				
				while (1) :
					
					// Create customer account and log them in
					if ($this->creating_account && !$user_id) :
				
						$reg_errors = new WP_Error();
						do_action('register_post', $this->posted['account_username'], $this->posted['billing_email'], $reg_errors);
						$errors = apply_filters( 'registration_errors', $reg_errors, $this->posted['account_username'], $this->posted['billing_email'] );
				
		                // if there are no errors, let's create the user account
						if ( !$reg_errors->get_error_code() ) :
		
			                $user_pass = $this->posted['account_password'];
			                $user_id = wp_create_user( $this->posted['account_username'], $user_pass, $this->posted['billing_email'] );
			                if ( !$user_id ) {
			                	$woocommerce->add_error( sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', 'woothemes'), get_option('admin_email')));
			                    break;
			                }
		
		                    // Change role
		                    wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;
		
		                    // send the user a confirmation and their login details
		                    woocommerce_customer_new_account( $user_id, $user_pass );
		                    //wp_new_user_notification( $user_id, $user_pass );
		
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
					
						$shipping_first_name = $this->posted['billing_first_name'];
						$shipping_last_name = $this->posted['billing_last_name'];
						$shipping_company = $this->posted['billing_company'];
						$shipping_address_1 = $this->posted['billing_address'];
						$shipping_address_2 = $this->posted['billing_address-2'];
						$shipping_city = $this->posted['billing_city'];							
						$shipping_state = $this->posted['billing_state'];
						$shipping_postcode = $this->posted['billing_postcode'];	
						$shipping_country = $this->posted['billing_country'];
						
					elseif ( $woocommerce->cart->needs_shipping() ) :
								
						$shipping_first_name = $this->posted['shipping_first_name'];
						$shipping_last_name = $this->posted['shipping_last_name'];
						$shipping_company = $this->posted['shipping_company'];
						$shipping_address_1 = $this->posted['shipping_address'];
						$shipping_address_2 = $this->posted['shipping_address-2'];
						$shipping_city = $this->posted['shipping_city'];							
						$shipping_state = $this->posted['shipping_state'];
						$shipping_postcode = $this->posted['shipping_postcode'];	
						$shipping_country = $this->posted['shipping_country'];
						
					endif;
					
					// Save billing/shipping to user meta fields
					if ($user_id>0) :
					
						update_user_meta( $user_id, 'billing_first_name', $this->posted['billing_first_name'] );
						update_user_meta( $user_id, 'billing_last_name', $this->posted['billing_last_name'] );
						update_user_meta( $user_id, 'billing_company', $this->posted['billing_company'] );
						update_user_meta( $user_id, 'billing_email', $this->posted['billing_email'] );
						update_user_meta( $user_id, 'billing_address', $this->posted['billing_address'] );
						update_user_meta( $user_id, 'billing_address-2', $this->posted['billing_address-2'] );
						update_user_meta( $user_id, 'billing_city', $this->posted['billing_city'] );
						update_user_meta( $user_id, 'billing_postcode', $this->posted['billing_postcode'] );
						update_user_meta( $user_id, 'billing_country', $this->posted['billing_country'] );
						update_user_meta( $user_id, 'billing_state', $this->posted['billing_state'] );
						update_user_meta( $user_id, 'billing_phone', $this->posted['billing_phone'] );

						if ( empty($this->posted['shiptobilling']) && $woocommerce->cart->needs_shipping() ) :
							update_user_meta( $user_id, 'shipping_first_name', $this->posted['shipping_first_name'] );
							update_user_meta( $user_id, 'shipping_last_name', $this->posted['shipping_last_name'] );
							update_user_meta( $user_id, 'shipping_company', $this->posted['shipping_company'] );
							update_user_meta( $user_id, 'shipping_address', $this->posted['shipping_address'] );
							update_user_meta( $user_id, 'shipping_address-2', $this->posted['shipping_address-2'] );
							update_user_meta( $user_id, 'shipping_city', $this->posted['shipping_city'] );
							update_user_meta( $user_id, 'shipping_postcode', $this->posted['shipping_postcode'] );
							update_user_meta( $user_id, 'shipping_country', $this->posted['shipping_country'] );
							update_user_meta( $user_id, 'shipping_state', $this->posted['shipping_state'] );
						elseif ( $this->posted['shiptobilling'] && $woocommerce->cart->needs_shipping() ) :
							update_user_meta( $user_id, 'shipping_first_name', $this->posted['billing_first_name'] );
							update_user_meta( $user_id, 'shipping_last_name', $this->posted['billing_last_name'] );
							update_user_meta( $user_id, 'shipping_company', $this->posted['billing_company'] );
							update_user_meta( $user_id, 'shipping_address', $this->posted['billing_address'] );
							update_user_meta( $user_id, 'shipping_address-2', $this->posted['billing_address-2'] );
							update_user_meta( $user_id, 'shipping_city', $this->posted['billing_city'] );
							update_user_meta( $user_id, 'shipping_postcode', $this->posted['billing_postcode'] );
							update_user_meta( $user_id, 'shipping_country', $this->posted['billing_country'] );
							update_user_meta( $user_id, 'shipping_state', $this->posted['billing_state'] );
						endif;
						
						do_action('woocommerce_checkout_update_user_meta', $user_id, $this->posted);
						
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
					
					foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) :
						
						$_product = $values['data'];
			
						// Calc item tax to store
						$rate = '';
						if ( $_product->is_taxable()) :
							$rate = $_tax->get_rate( $_product->get_tax_class() );
						endif;
						
						// Store any item meta data - item meta class lets plugins add item meta in a standardized way
						$item_meta = &new order_item_meta();
						
						$item_meta->new_order_item( $values );
						
						// Store variation data in meta so admin can view it
						if ($values['variation'] && is_array($values['variation'])) :
							foreach ($values['variation'] as $key => $value) :
								$item_meta->add( esc_attr(str_replace('attribute_', '', $key)), $value );
							endforeach;
						endif;
						
						$order_items[] = apply_filters('new_order_item', array(
					 		'id' 			=> $values['product_id'],
					 		'variation_id' 	=> $values['variation_id'],
					 		'name' 			=> $_product->get_title(),
					 		'qty' 			=> (int) $values['quantity'],
					 		'cost' 			=> $_product->get_price_excluding_tax(),
					 		'taxrate' 		=> $rate,
					 		'item_meta'		=> $item_meta->meta
					 	), $values);
					 	
					 	// Check cart items for errors
					 	do_action('woocommerce_check_cart_items');
					 	
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
					update_post_meta( $order_id, '_billing_first_name', 	$this->posted['billing_first_name']);
					update_post_meta( $order_id, '_billing_last_name', 		$this->posted['billing_last_name']);
					update_post_meta( $order_id, '_billing_company', 		$this->posted['billing_company']);
					update_post_meta( $order_id, '_billing_address_1', 		$this->posted['billing_address']);
					update_post_meta( $order_id, '_billing_address_2', 		$this->posted['billing_address-2']);
					update_post_meta( $order_id, '_billing_city', 			$this->posted['billing_city']);
					update_post_meta( $order_id, '_billing_postcode', 		$this->posted['billing_postcode']);
					update_post_meta( $order_id, '_billing_country', 		$this->posted['billing_country']);
					update_post_meta( $order_id, '_billing_state', 			$this->posted['billing_state']);
					update_post_meta( $order_id, '_billing_email', 			$this->posted['billing_email']);
					update_post_meta( $order_id, '_billing_phone', 			$this->posted['billing_phone']);
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
					
					do_action('woocommerce_checkout_update_order_meta', $order_id, $this->posted);
					
					// Order status
					wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
						
					// Discount code meta
					if ($applied_coupons = $woocommerce->cart->get_applied_coupons()) update_post_meta($order_id, 'coupons', implode(', ', $applied_coupons));
					
					// Order is saved
					do_action('woocommerce_checkout_order_processed', $order_id, $this->posted);
					
					// Process payment
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
		global $woocommerce;
		
		if (isset( $this->posted[$input] ) && !empty($this->posted[$input])) :
			return $this->posted[$input];
		elseif (is_user_logged_in()) :
			if (get_user_meta( get_current_user_id(), $input, true )) return get_user_meta( get_current_user_id(), $input, true );
			
			$current_user = wp_get_current_user();

			switch ( $input ) :
				
				case "billing_email" :
					return $current_user->user_email;
				break;
				
			endswitch;
		else :
			
			switch ( $input ) :
				
				case "billing_country" :
					return $woocommerce->countries->get_base_country();
				break;
				
			endswitch;
			
		endif;
	}
}