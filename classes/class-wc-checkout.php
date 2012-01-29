<?php
/**
 * Checkout
 * 
 * The WooCommerce checkout class handles the checkout process, collecting user data and processing the payment.
 *
 * @class 		WC_Checkout
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class WC_Checkout {
	
	var $posted;
	var $checkout_fields;
	var $must_create_account;
	var $creating_account;
	var $localisation;
	
	/** constructor */
	function __construct () {
		global $woocommerce;
		
		add_action('woocommerce_checkout_billing',array(&$this,'checkout_form_billing'));
		add_action('woocommerce_checkout_shipping',array(&$this,'checkout_form_shipping'));
		
		$this->must_create_account = (get_option('woocommerce_enable_guest_checkout')=='yes' || is_user_logged_in()) ? false : true;

		// Define all Checkout fields
		$this->checkout_fields['billing'] 	= $woocommerce->countries->get_address_fields( $this->get_value('billing_country'), 'billing_' );
		$this->checkout_fields['shipping'] 	= $woocommerce->countries->get_address_fields( $this->get_value('shipping_country'), 'shipping_' );
		$this->checkout_fields['account']	= array(
			'account_username' => array( 
				'type' => 'text', 
				'label' => __('Account username', 'woocommerce'), 
				'placeholder' => __('Username', 'woocommerce') 
				),
			'account_password' => array( 
				'type' => 'password', 
				'label' => __('Account password', 'woocommerce'), 
				'placeholder' => __('Password', 'woocommerce'),
				'class' => array('form-row-first')
				),
			'account_password-2' => array( 
				'type' => 'password', 
				'label' => __('Account password', 'woocommerce'), 
				'placeholder' => __('Password', 'woocommerce'),
				'class' => array('form-row-last'), 
				'label_class' => array('hidden')
				)
			);
		$this->checkout_fields['order']	= array(
			'order_comments' => array( 
				'type' => 'textarea', 
				'class' => array('notes'), 
				'label' => __('Order Notes', 'woocommerce'), 
				'placeholder' => __('Notes about your order, e.g. special notes for delivery.', 'woocommerce') 
				)
			);
		$this->checkout_fields = apply_filters('woocommerce_checkout_fields', $this->checkout_fields);
	}
		
	/** Output the billing information form */
	function checkout_form_billing() {
		global $woocommerce;
		
		if ($woocommerce->cart->ship_to_billing_address_only()) :
			echo '<h3>'.__('Billing &amp Shipping', 'woocommerce').'</h3>';
		else : 
			echo '<h3>'.__('Billing Address', 'woocommerce').'</h3>';
		endif;
		
		// Output billing form fields
		do_action('woocommerce_before_checkout_billing_form', $this);
		foreach ($this->checkout_fields['billing'] as $key => $field) :
			woocommerce_form_field( $key, $field, $this->get_value( $key ) );
		endforeach;
		do_action('woocommerce_after_checkout_billing_form', $this);
		
		// Registration Form Fields
		if (!is_user_logged_in() && get_option('woocommerce_enable_signup_and_login_from_checkout')=="yes") :
		
			if (get_option('woocommerce_enable_guest_checkout')=='yes') :
				
				echo '<p class="form-row"><input class="input-checkbox" id="createaccount" '.checked($this->get_value('createaccount'), true).' type="checkbox" name="createaccount" value="1" /> <label for="createaccount" class="checkbox">'.__('Create an account?', 'woocommerce').'</label></p>';
				
			endif;
			
			echo '<div class="create-account">';
			
			echo '<p>'.__('Create an account by entering the information below. If you are a returning customer please login with your username at the top of the page.', 'woocommerce').'</p>'; 
			
			foreach ($this->checkout_fields['account'] as $key => $field) :
				woocommerce_form_field( $key, $field, $this->get_value( $key ) );
			endforeach;
			
			echo '</div>';
							
		endif;
		
	}
	
	/** Output the shipping information form */
	function checkout_form_shipping() {
		global $woocommerce;
		
		// Shipping Details
		if ($woocommerce->cart->needs_shipping() && !$woocommerce->cart->ship_to_billing_address_only()) :
			
			if (!isset($_POST) || !$_POST) :
			
				$shiptobilling = (get_option('woocommerce_ship_to_same_address')=='yes') ? 1 : 0;
				$shiptobilling = apply_filters('woocommerce_shiptobilling_default', $shiptobilling);
			
			else :
			
				$shiptobilling = $this->get_value('shiptobilling');
			
			endif;

			echo '<p class="form-row" id="shiptobilling"><input class="input-checkbox" '.checked($shiptobilling, 1, false).' type="checkbox" name="shiptobilling" value="1" /> <label for="shiptobilling" class="checkbox">'.__('Ship to same address?', 'woocommerce').'</label></p>';
			
			echo '<h3>'.__('Shipping Address', 'woocommerce').'</h3>';
			
			echo'<div class="shipping_address">';
					
				// Output shipping form fields
				do_action('woocommerce_before_checkout_shipping_form', $this);
				foreach ($this->checkout_fields['shipping'] as $key => $field) :
					woocommerce_form_field( $key, $field, $this->get_value( $key ) );
				endforeach;
				do_action('woocommerce_after_checkout_shipping_form', $this);
								
			echo '</div>';
		
		endif;
		
		do_action('woocommerce_before_order_notes', $this);
		
		if (get_option('woocommerce_enable_order_comments')!='no') :
		
			if ($woocommerce->cart->ship_to_billing_address_only()) :
				echo '<h3>'.__('Additional Information', 'woocommerce').'</h3>';
			endif;
			
			foreach ($this->checkout_fields['order'] as $key => $field) :
				woocommerce_form_field( $key, $field, $this->get_value( $key ) );
			endforeach;
								
		endif;
		
		do_action('woocommerce_after_order_notes', $this);
	}

	/**
	 * Process the checkout after the confirm order button is pressed
	 */
	function process_checkout() {
		global $wpdb, $woocommerce;

		if (!defined('WOOCOMMERCE_CHECKOUT')) define('WOOCOMMERCE_CHECKOUT', true);
		
		$woocommerce->verify_nonce('process_checkout');
		
		do_action('woocommerce_before_checkout_process');

		if (sizeof($woocommerce->cart->get_cart())==0) :
			$woocommerce->add_error( sprintf(__('Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'woocommerce'), home_url()) );
		endif;
		
		do_action('woocommerce_checkout_process');

		// Checkout fields (not defined in checkout_fields)
		$this->posted['shiptobilling'] 		= isset($_POST['shiptobilling']) ? 1 : 0;
		$this->posted['terms'] 				= isset($_POST['terms']) ? 1 : 0;
		$this->posted['createaccount'] 		= isset($_POST['createaccount']) ? 1 : 0;
		$this->posted['payment_method'] 	= isset($_POST['payment_method']) ? woocommerce_clean($_POST['payment_method']) : '';
		$this->posted['shipping_method']	= isset($_POST['shipping_method']) ? woocommerce_clean($_POST['shipping_method']) : '';
		
		// Ship to billing only option
		if ($woocommerce->cart->ship_to_billing_address_only()) $this->posted['shiptobilling'] = 1;
		
		// Update customer shipping and payment method to posted method
		$_SESSION['_chosen_shipping_method'] = $this->posted['shipping_method'];
		$_SESSION['_chosen_payment_method'] = $this->posted['payment_method'];
		
		// Update cart totals
		$woocommerce->cart->calculate_totals();
		
		// Note if we skip shipping
		$skipped_shipping = false;
		
		// Get validation class
		$validation = $woocommerce->validation();
		
		// Get posted checkout_fields and do validation
		foreach ($this->checkout_fields as $fieldset_key => $fieldset) :
			
			// Skip shipping if its not needed
			if ($fieldset_key=='shipping' && (!$woocommerce->cart->needs_shipping() || $woocommerce->cart->ship_to_billing_address_only() || $this->posted['shiptobilling'])) :
				$skipped_shipping = true;
				continue;
			endif;
			
			foreach ($fieldset as $key => $field) :
				
				if (!isset($field['type'])) $field['type'] = 'text';
				
				// Get Value
				switch ($field['type']) :
					case "checkbox" :
						$this->posted[$key] = isset($_POST[$key]) ? 1 : 0;
					break;
					default :
						$this->posted[$key] = isset($_POST[$key]) ? woocommerce_clean($_POST[$key]) : '';
					break;
				endswitch;
				
				// Hook to allow modification of value
				$this->posted[$key] = apply_filters('woocommerce_process_checkout_field_' . $key, $this->posted[$key]);
				
				// Validation: Required fields
				if ( isset($field['required']) && $field['required'] && empty($this->posted[$key]) ) $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __('is a required field.', 'woocommerce') );
				
				if (!empty($this->posted[$key])) :
					// Special handling for validation and formatting
					switch ($key) :
						case "billing_postcode" :
						case "shipping_postcode" :
							$this->posted[$key] = strtolower(str_replace(' ', '', $this->posted[$key]));
							
							if (!$validation->is_postcode( $this->posted[$key], $_POST['billing_country'] )) : $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __('(billing) is not a valid postcode/ZIP.', 'woocommerce') ); 
							else :
								$this->posted[$key] = $validation->format_postcode( $this->posted[$key], $_POST['billing_country'] );
							endif;
						break;
						case "billing_phone" :
							if (!$validation->is_phone( $this->posted[$key] )) : $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __('is not a valid number.', 'woocommerce') ); endif;
						break;
						case "billing_email" :
							if (!$validation->is_email( $this->posted[$key] )) : $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __('is not a valid email address.', 'woocommerce') ); endif;
						break;
					endswitch;
				endif;
				
			endforeach;
			
		endforeach;
		
		// Update customer location to posted location so we can correctly check available shipping methods
		$woocommerce->customer->set_country( $this->posted['billing_country'] );
		$woocommerce->customer->set_state( $this->posted['billing_state'] );
		$woocommerce->customer->set_postcode( $this->posted['billing_postcode'] );
		
		// Shipping Information
		if (!$skipped_shipping) :
			
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
		
			if ( empty($this->posted['account_username']) ) $woocommerce->add_error( __('Please enter an account username.', 'woocommerce') );
			if ( empty($this->posted['account_password']) ) $woocommerce->add_error( __('Please enter an account password.', 'woocommerce') );
			if ( $this->posted['account_password-2'] !== $this->posted['account_password'] ) $woocommerce->add_error( __('Passwords do not match.', 'woocommerce') );
		
			// Check the username
			if ( !validate_username( $this->posted['account_username'] ) ) :
				$woocommerce->add_error( __('Invalid email/username.', 'woocommerce') );
			elseif ( username_exists( $this->posted['account_username'] ) ) :
				$woocommerce->add_error( __('An account is already registered with that username. Please choose another.', 'woocommerce') );
			endif;
			
			// Check the e-mail address
			if ( email_exists( $this->posted['billing_email'] ) ) :
				$woocommerce->add_error( __('An account is already registered with your email address. Please login.', 'woocommerce') );
			endif;
			
		endif;
		
		// Terms
		if (!isset($_POST['woocommerce_checkout_update_totals']) && empty($this->posted['terms']) && woocommerce_get_page_id('terms')>0 ) $woocommerce->add_error( __('You must accept our Terms &amp; Conditions.', 'woocommerce') );
		
		if ($woocommerce->cart->needs_shipping()) :
			
			// Shipping Method
			$available_methods = $woocommerce->shipping->get_available_shipping_methods();
			
			if (!isset($available_methods[$this->posted['shipping_method']])) :
				$woocommerce->add_error( __('Invalid shipping method.', 'woocommerce') );
			endif;	
		
		endif;	
		
		if ($woocommerce->cart->needs_payment()) :
			// Payment Method
			$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
			if (!isset($available_gateways[$this->posted['payment_method']])) :
				$woocommerce->add_error( __('Invalid payment method.', 'woocommerce') );
			else :
				// Payment Method Field Validation
				$available_gateways[$this->posted['payment_method']]->validate_fields();
			endif;
		endif;
		
		do_action( 'woocommerce_after_checkout_validation', $this->posted );
				
		if (!isset($_POST['woocommerce_checkout_update_totals']) && $woocommerce->error_count()==0) :
			
			$user_id = get_current_user_id();
			
			while (1) :
				
				// Create customer account and log them in
				if ($this->creating_account && !$user_id) :
			
					$reg_errors = new WP_Error();
					
					do_action('woocommerce_register_post', $this->posted['account_username'], $this->posted['billing_email'], $reg_errors);
					
					$errors = apply_filters( 'woocommerce_registration_errors', $reg_errors, $this->posted['account_username'], $this->posted['billing_email'] );
			
	                // if there are no errors, let's create the user account
					if ( !$reg_errors->get_error_code() ) :
	
		                $user_pass = $this->posted['account_password'];
		                $user_id = wp_create_user( $this->posted['account_username'], $user_pass, $this->posted['billing_email'] );
		               
		               if ( !$user_id ) :
		                	$woocommerce->add_error( '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Couldn&#8217;t register you... please contact us if you continue to have problems.', 'woocommerce') );
		                    break;
		                endif;
	
	                    // Change role
	                    wp_update_user( array ('ID' => $user_id, 'role' => 'customer') ) ;
	
	                    // send the user a confirmation and their login details
	                    $mailer = $woocommerce->mailer();
						$mailer->customer_new_account( $user_id, $password );
	
	                    // set the WP login cookie
	                    $secure_cookie = is_ssl() ? true : false;
	                    wp_set_auth_cookie($user_id, true, $secure_cookie);
					
					else :
						$woocommerce->add_error( $reg_errors->get_error_message() );
	                	break;                    
					endif;
					
				endif;
				
				// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected last time.
				$_tax = new WC_Tax();
				
				$order_data = array(
					'post_type' 	=> 'shop_order',
					'post_title' 	=> 'Order &ndash; '.date('F j, Y @ h:i A'),
					'post_status' 	=> 'publish',
					'post_excerpt' 	=> $this->posted['order_comments'],
					'post_author' 	=> 1
				);

				// Cart items
				$order_items = array();
				
				foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) :
					
					$_product = $values['data'];

					// Store any item meta data - item meta class lets plugins add item meta in a standardized way
					$item_meta = new order_item_meta();
					
					$item_meta->new_order_item( $values );
					
					// Store variation data in meta so admin can view it
					if ($values['variation'] && is_array($values['variation'])) :
						foreach ($values['variation'] as $key => $value) :
							$item_meta->add( esc_attr(str_replace('attribute_', '', $key)), $value );
						endforeach;
					endif;
					
					$order_items[] = apply_filters('new_order_item', array(
				 		'id' 				=> $values['product_id'],
				 		'variation_id' 		=> $values['variation_id'],
				 		'name' 				=> $_product->get_title(),
				 		'qty' 				=> (int) $values['quantity'],
				 		'item_meta'			=> $item_meta->meta,
				 		'line_subtotal' 	=> rtrim(rtrim(number_format($values['line_subtotal'], 4, '.', ''), '0'), '.'),	// Line subtotal (before discounts)
				 		'line_subtotal_tax' => rtrim(rtrim(number_format($values['line_subtotal_tax'], 4, '.', ''), '0'), '.'), // Line tax (before discounts)
				 		'line_total'		=> rtrim(rtrim(number_format($values['line_total'], 4, '.', ''), '0'), '.'), 		// Line total (after discounts)
				 		'line_tax' 			=> rtrim(rtrim(number_format($values['line_tax'], 4, '.', ''), '0'), '.'), 		// Line Tax (after discounts)
				 		'tax_class'			=> $_product->get_tax_class()								// Tax class (adjusted by filters)
				 	), $values);
				endforeach;
				
				// Check order items for errors
				do_action('woocommerce_check_new_order_items', $order_items);
				
				if ($woocommerce->error_count()>0) break;
				
				// Insert or update the post data
				$create_new_order = true;
				
				if (isset($_SESSION['order_awaiting_payment']) && $_SESSION['order_awaiting_payment'] > 0) :
					
					$order_id = (int) $_SESSION['order_awaiting_payment'];
					
					/* Check order is unpaid by getting its status */
					$terms = wp_get_object_terms( $order_id, 'shop_order_status', array('fields' => 'slugs') );
					$order_status = (isset($terms[0])) ? $terms[0] : 'pending';
					
					if ( $order_status == 'pending' ) :
						
						// Resume the unpaid order
						$order_data['ID'] = $order_id;
						wp_update_post( $order_data );
						do_action('woocommerce_resume_order', $order_id);
						
						$create_new_order = false;
					
					endif;
					
				endif;
				
				if ($create_new_order) :
					$order_id = wp_insert_post( $order_data );
					
					if (is_wp_error($order_id)) :
						$woocommerce->add_error( 'Error: Unable to create order. Please try again.' );
		                break;
					else :
						// Inserted successfully 
						do_action('woocommerce_new_order', $order_id);
					endif;
				endif;
				
				// Get better formatted billing method (title)
				$shipping_method = $this->posted['shipping_method'];
				if (isset($available_methods) && isset($available_methods[$this->posted['shipping_method']])) :
					$shipping_method = $available_methods[$this->posted['shipping_method']]->label;
				endif;
				
				// Get better formatted shipping method (title/label)
				$payment_method = $this->posted['payment_method'];
				if (isset($available_gateways) && isset($available_gateways[$this->posted['payment_method']])) :
					$payment_method = $available_gateways[$this->posted['payment_method']]->title;
				endif;
				
				// UPDATE ORDER META
				
				// Save billing and shipping first, also save to user meta if logged in
				if ($this->checkout_fields['billing']) :
					foreach ($this->checkout_fields['billing'] as $key => $field) :
						
						// Post
						update_post_meta( $order_id, '_' . $key, $this->posted[$key] );
						
						// User
						if ($user_id>0) :
							update_user_meta( $user_id, $key, $this->posted[$key] );
						endif;
						
					endforeach;
				endif;
				if ($this->checkout_fields['shipping'] && $woocommerce->cart->needs_shipping()) :
					foreach ($this->checkout_fields['shipping'] as $key => $field) :
						
						if ($this->posted['shiptobilling']) :
							
							$field_key = str_replace('shipping_', 'billing_', $key);
							
							// Post
							update_post_meta( $order_id, '_' . $key, $this->posted[$field_key] );
						else :
							// Post
							update_post_meta( $order_id, '_' . $key, $this->posted[$key] );
							
							// User
							if ($user_id>0) :
								update_user_meta( $user_id, $key, $this->posted[$key] );
							endif;
						endif;
						
					endforeach;
				endif;
				
				// Save any other user meta
				if ($user_id) do_action('woocommerce_checkout_update_user_meta', $user_id, $this->posted);
				
				// Prepare order taxes for storage
				$order_taxes = array();

				foreach (array_keys($woocommerce->cart->taxes + $woocommerce->cart->shipping_taxes) as $key) {
					
					$is_compound = ($woocommerce->cart->tax->is_compound( $key )) ? 1 : 0;
					
					$cart_tax = (isset($woocommerce->cart->taxes[$key])) ? $woocommerce->cart->taxes[$key] : 0;
					$shipping_tax = (isset($woocommerce->cart->shipping_taxes[$key])) ? $woocommerce->cart->shipping_taxes[$key] : 0;
					
					$order_taxes[] = array(
						'label' => $woocommerce->cart->tax->get_rate_label( $key ),
						'compound' => $is_compound,
						'cart_tax' => number_format($cart_tax, 2, '.', ''),
						'shipping_tax' => number_format($shipping_tax, 2, '.', '')
					);
				}
								
				// Save other order meta fields
				update_post_meta( $order_id, '_shipping_method', 		$this->posted['shipping_method']);
				update_post_meta( $order_id, '_payment_method', 		$this->posted['payment_method']);
				update_post_meta( $order_id, '_shipping_method_title', 	$shipping_method);
				update_post_meta( $order_id, '_payment_method_title', 	$payment_method);
				update_post_meta( $order_id, '_order_shipping', 		number_format($woocommerce->cart->shipping_total, 2, '.', ''));
				update_post_meta( $order_id, '_order_discount', 		number_format($woocommerce->cart->get_order_discount_total(), 2, '.', ''));
				update_post_meta( $order_id, '_cart_discount', 			number_format($woocommerce->cart->get_cart_discount_total(), 2, '.', ''));
				update_post_meta( $order_id, '_order_tax', 				number_format($woocommerce->cart->tax_total, 2, '.', ''));
				update_post_meta( $order_id, '_order_shipping_tax', 	number_format($woocommerce->cart->shipping_tax_total, 2, '.', ''));
				update_post_meta( $order_id, '_order_total', 			number_format($woocommerce->cart->total, 2, '.', ''));
				update_post_meta( $order_id, '_order_key', 				apply_filters('woocommerce_generate_order_key', uniqid('order_') ));
				update_post_meta( $order_id, '_customer_user', 			(int) $user_id );
				update_post_meta( $order_id, '_order_items', 			$order_items );
				update_post_meta( $order_id, '_order_taxes', 			$order_taxes );
				update_post_meta( $order_id, '_order_currency', 		get_option('woocommerce_currency') );
				update_post_meta( $order_id, '_prices_include_tax', 	get_option('woocommerce_prices_include_tax') );
				
				do_action('woocommerce_checkout_update_order_meta', $order_id, $this->posted);
				
				// Order status
				wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
					
				// Discount code meta
				if ($applied_coupons = $woocommerce->cart->get_applied_coupons()) update_post_meta($order_id, 'coupons', implode(', ', $applied_coupons));
				
				// Order is saved
				do_action('woocommerce_checkout_order_processed', $order_id, $this->posted);
				
				// Process payment
				if ($woocommerce->cart->needs_payment()) :
					
					// Store Order ID in session so it can be re-used after payment failure
					$_SESSION['order_awaiting_payment'] = $order_id;

					// Process Payment
					$result = $available_gateways[$this->posted['payment_method']]->process_payment( $order_id );
					
					// Redirect to success/confirmation/payment page
					if ($result['result']=='success') :
						
						$result = apply_filters('woocommerce_payment_successful_result', $result );
						
						if (is_ajax()) : 
							echo json_encode( $result );
							exit;
						else :
							wp_safe_redirect( $result['redirect'] );
							exit;
						endif;
						
					endif;
				
				else :
					
					$order = new WC_Order($order_id);
					
					// No payment was required for order
					$order->payment_complete();
					
					// Empty the Cart
					$woocommerce->cart->empty_cart();
					
					// Redirect to success/confirmation/payment page
					if (is_ajax()) : 
						echo json_encode( 
							array(
								'redirect' => apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', get_permalink(woocommerce_get_page_id('thanks')), $order)
							) 
						);
						exit;
					else :
						wp_safe_redirect( 
							apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', get_permalink(woocommerce_get_page_id('thanks')), $order) 
						);
						exit;
					endif;
					
				endif;
				
				// Break out of loop
				break;
			
			endwhile;

		endif;
		
		// If we reached this point then there were errors
		if (is_ajax()) : 
			$woocommerce->show_messages();
			exit;
		endif;
	}
	
	/** Gets the value either from the posted data, or from the users meta data */
	function get_value( $input ) {
		global $woocommerce;
		
		if (isset( $_POST[$input] ) && !empty($_POST[$input])) :
			return esc_attr($_POST[$input]);
		elseif (is_user_logged_in()) :
			if ($meta = get_user_meta( get_current_user_id(), $input, true )) return $meta;
			
			$current_user = wp_get_current_user();
			if ($input == "billing_email") : 
				return $current_user->user_email;
			endif;
			
		else :

			if ($input == "billing_country") :
				return $woocommerce->countries->get_base_country();
			endif;
			
		endif;
	}
}

/** Depreciated */
class woocommerce_checkout extends WC_Checkout {
	public function __construct() { 
		_deprecated_function( 'woocommerce_checkout', '1.4', 'WC_Checkout()' );
		parent::__construct(); 
	} 
}