<?php
/**
 * Checkout
 *
 * The WooCommerce checkout class handles the checkout process, collecting user data and processing the payment.
 *
 * @class 		WC_Cart
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Checkout {

	/** @var array Array of posted form data. */
	public $posted;

	/** @var array Array of fields to display on the checkout. */
	public $checkout_fields;

	/** @var bool Whether or not the user must create an account to checkout. */
	public $must_create_account;

	/** @var bool Whether or not signups are allowed. */
	public $enable_signup;

	/** @var bool True when the user is creating an account. */
	public $creating_account;

	/** @var object The shipping method being used. */
	private $shipping_method;

	/** @var array The payment gateway being used. */
	private $payment_method;

	/** @var int ID of customer. */
	private $customer_id;

	/**
	 * Constructor for the checkout class. Hooks in methods and defines checkout fields.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct () {
		global $woocommerce;

		add_action( 'woocommerce_checkout_process', array( $this,'checkout_process' ) );
		add_action( 'woocommerce_checkout_billing', array( $this,'checkout_form_billing' ) );
		add_action( 'woocommerce_checkout_shipping', array( $this,'checkout_form_shipping' ) );

		$this->enable_signup         = get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) == 'yes' ? true : false;
		$this->enable_guest_checkout = get_option( 'woocommerce_enable_guest_checkout' ) == 'yes' ? true : false;
		$this->must_create_account   = $this->enable_guest_checkout || is_user_logged_in() ? false : true;

		// Define all Checkout fields
		$this->checkout_fields['billing'] 	= $woocommerce->countries->get_address_fields( $this->get_value('billing_country'), 'billing_' );
		$this->checkout_fields['shipping'] 	= $woocommerce->countries->get_address_fields( $this->get_value('shipping_country'), 'shipping_' );

		if ( get_option( 'woocommerce_registration_email_for_username' ) == 'no' ) {

			$this->checkout_fields['account']['account_username'] = array(
				'type' 			=> 'text',
				'label' 		=> __( 'Account username', 'woocommerce' ),
				'placeholder' 	=> _x( 'Username', 'placeholder', 'woocommerce' )
			);

		}

		$this->checkout_fields['account']['account_password'] = array(
			'type' 				=> 'password',
			'label' 			=> __( 'Account password', 'woocommerce' ),
			'placeholder' 		=> _x( 'Password', 'placeholder', 'woocommerce' ),
			'class' 			=> array( 'form-row-first' )
		);

		$this->checkout_fields['account']['account_password-2'] = array(
			'type' 				=> 'password',
			'label' 			=> __( 'Confirm password', 'woocommerce' ),
			'placeholder' 		=> _x( 'Confirm password', 'placeholder', 'woocommerce' ),
			'class' 			=> array( 'form-row-last' ),
			'label_class' 		=> array( 'hidden' )
		);

		$this->checkout_fields['order']	= array(
			'order_comments' => array(
				'type' => 'textarea',
				'class' => array('notes'),
				'label' => __( 'Order Notes', 'woocommerce' ),
				'placeholder' => _x('Notes about your order, e.g. special notes for delivery.', 'placeholder', 'woocommerce')
				)
			);

		$this->checkout_fields = apply_filters( 'woocommerce_checkout_fields', $this->checkout_fields );

		do_action( 'woocommerce_checkout_init', $this );
	}


	/**
	 * Checkout process
	 *
	 * @access public
	 * @return void
	 */
	public function checkout_process() {
		// When we process the checkout, lets ensure cart items are rechecked to prevent checkout
		do_action('woocommerce_check_cart_items');
	}


	/**
	 * Output the billing information form
	 *
	 * @access public
	 * @return void
	 */
	public function checkout_form_billing() {
		woocommerce_get_template( 'checkout/form-billing.php', array( 'checkout' => $this ) );
	}


	/**
	 * Output the shipping information form
	 *
	 * @access public
	 * @return void
	 */
	public function checkout_form_shipping() {
		woocommerce_get_template( 'checkout/form-shipping.php', array( 'checkout' => $this ) );
	}


	/**
	 * create_order function.
	 *
	 * @access public
	 * @return void
	 */
	public function create_order() {
		global $woocommerce, $wpdb;

		// Give plugins the opportunity to create an order themselves
		$order_id = apply_filters( 'woocommerce_create_order', null, $this );

		if ( is_numeric( $order_id ) )
			return $order_id;

		// Create Order (send cart variable so we can record items and reduce inventory). Only create if this is a new order, not if the payment was rejected.
		$order_data = apply_filters( 'woocommerce_new_order_data', array(
			'post_type' 	=> 'shop_order',
			'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) ),
			'post_status' 	=> 'publish',
			'ping_status'	=> 'closed',
			'post_excerpt' 	=> isset( $this->posted['order_comments'] ) ? $this->posted['order_comments'] : '',
			'post_author' 	=> 1,
			'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
		) );

		// Insert or update the post data
		$create_new_order = true;

		if ( $woocommerce->session->order_awaiting_payment > 0 ) {

			$order_id = absint( $woocommerce->session->order_awaiting_payment );

			/* Check order is unpaid by getting its status */
			$terms = wp_get_object_terms( $order_id, 'shop_order_status', array( 'fields' => 'slugs' ) );
			$order_status = isset( $terms[0] ) ? $terms[0] : 'pending';

			// Resume the unpaid order if its pending
			if ( $order_status == 'pending' || $order_status == 'failed' ) {

				// Update the existing order as we are resuming it
				$create_new_order = false;
				$order_data['ID'] = $order_id;
				wp_update_post( $order_data );

				// Clear the old line items - we'll add these again in case they changed
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d )", $order_id ) );

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $order_id ) );

				// Trigger an action for the resumed order
				do_action( 'woocommerce_resume_order', $order_id );
			}
		}

		if ( $create_new_order ) {
			$order_id = wp_insert_post( $order_data );

			if ( is_wp_error( $order_id ) )
				throw new MyException( 'Error: Unable to create order. Please try again.' );
			else
				do_action( 'woocommerce_new_order', $order_id );
		}

		// Store user data
		if ( $this->checkout_fields['billing'] ) {
			foreach ( $this->checkout_fields['billing'] as $key => $field ) {

				update_post_meta( $order_id, '_' . $key, $this->posted[ $key ] );

				// User
				if ( $this->customer_id && ! empty( $this->posted[ $key ] ) ) {
					update_user_meta( $this->customer_id, $key, $this->posted[ $key ] );

					// Special fields
					switch ( $key ) {
						case "billing_email" :
							if ( ! email_exists( $this->posted[ $key ] ) )
								wp_update_user( array ( 'ID' => $this->customer_id, 'user_email' => $this->posted[ $key ] ) ) ;
						break;
						case "billing_first_name" :
							wp_update_user( array ( 'ID' => $this->customer_id, 'first_name' => $this->posted[ $key ] ) ) ;
						break;
						case "billing_last_name" :
							wp_update_user( array ( 'ID' => $this->customer_id, 'last_name' => $this->posted[ $key ] ) ) ;
						break;
					}
				}
			}
		}

		if ( $this->checkout_fields['shipping'] && ( $woocommerce->cart->needs_shipping() || get_option('woocommerce_require_shipping_address') == 'yes' ) ) {
			foreach ( $this->checkout_fields['shipping'] as $key => $field ) {
				$postvalue = false;

				if ( $this->posted['shiptobilling'] ) {
					if ( isset( $this->posted[ str_replace( 'shipping_', 'billing_', $key ) ] ) ) {
						$postvalue = $this->posted[ str_replace( 'shipping_', 'billing_', $key ) ];
						update_post_meta( $order_id, '_' . $key, $postvalue );
					}
				} else {
					$postvalue = $this->posted[ $key ];
					update_post_meta( $order_id, '_' . $key, $postvalue );
				}

				// User
				if ( $postvalue && $this->customer_id )
					update_user_meta( $this->customer_id, $key, $postvalue );
			}
		}

		// Save any other user meta
		if ( $this->customer_id )
			do_action( 'woocommerce_checkout_update_user_meta', $this->customer_id, $this->posted );

		// Store the line items to the new/resumed order
		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {

			$_product = $values['data'];

           	// Add line item
           	$item_id = woocommerce_add_order_item( $order_id, array(
		 		'order_item_name' 		=> $_product->get_title(),
		 		'order_item_type' 		=> 'line_item'
		 	) );

		 	// Add line item meta
		 	if ( $item_id ) {
			 	woocommerce_add_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $values['quantity'] ) );
			 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $_product->get_tax_class() );
			 	woocommerce_add_order_item_meta( $item_id, '_product_id', $values['product_id'] );
			 	woocommerce_add_order_item_meta( $item_id, '_variation_id', $values['variation_id'] );
			 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', woocommerce_format_decimal( $values['line_subtotal'], 4 ) );
			 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $values['line_total'], 4 ) );
			 	woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $values['line_tax'], 4 ) );
			 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_format_decimal( $values['line_subtotal_tax'], 4 ) );

			 	// Store variation data in meta so admin can view it
				if ( $values['variation'] && is_array( $values['variation'] ) )
					foreach ( $values['variation'] as $key => $value )
						woocommerce_add_order_item_meta( $item_id, esc_attr( str_replace( 'attribute_', '', $key ) ), $value );

			 	// Add line item meta for backorder status
			 	if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $values['quantity'] ) )
			 		woocommerce_add_order_item_meta( $item_id, apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ), $cart_item_key, $order_id ), $values['quantity'] - max( 0, $_product->get_total_stock() ) );

			 	// Allow plugins to add order item meta
			 	do_action( 'woocommerce_add_order_item_meta', $item_id, $values );
		 	}
		}

		// Store fees
		foreach ( $woocommerce->cart->get_fees() as $fee ) {
			$item_id = woocommerce_add_order_item( $order_id, array(
		 		'order_item_name' 		=> $fee->name,
		 		'order_item_type' 		=> 'fee'
		 	) );

		 	if ( $fee->taxable )
		 		woocommerce_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
		 	else
		 		woocommerce_add_order_item_meta( $item_id, '_tax_class', '0' );

		 	woocommerce_add_order_item_meta( $item_id, '_line_total', woocommerce_format_decimal( $fee->amount ) );
			woocommerce_add_order_item_meta( $item_id, '_line_tax', woocommerce_format_decimal( $fee->tax ) );
		}

		// Store tax rows
		foreach ( array_keys( $woocommerce->cart->taxes + $woocommerce->cart->shipping_taxes ) as $key ) {

			$item_id = woocommerce_add_order_item( $order_id, array(
		 		'order_item_name' 		=> $woocommerce->cart->tax->get_rate_code( $key ),
		 		'order_item_type' 		=> 'tax'
		 	) );

		 	// Add line item meta
		 	if ( $item_id ) {
		 		woocommerce_add_order_item_meta( $item_id, 'rate_id', $key );
		 		woocommerce_add_order_item_meta( $item_id, 'label', $woocommerce->cart->tax->get_rate_label( $key ) );
			 	woocommerce_add_order_item_meta( $item_id, 'compound', absint( $woocommerce->cart->tax->is_compound( $key ) ? 1 : 0 ) );
			 	woocommerce_add_order_item_meta( $item_id, 'tax_amount', woocommerce_clean( isset( $woocommerce->cart->taxes[ $key ] ) ? $woocommerce->cart->taxes[ $key ] : 0 ) );
			 	woocommerce_add_order_item_meta( $item_id, 'shipping_tax_amount', woocommerce_clean( isset( $woocommerce->cart->shipping_taxes[ $key ] ) ? $woocommerce->cart->shipping_taxes[ $key ] : 0 ) );
			}
		}

		// Store coupons
		if ( $applied_coupons = $woocommerce->cart->get_applied_coupons() ) {
			foreach ( $applied_coupons as $code ) {

				$item_id = woocommerce_add_order_item( $order_id, array(
			 		'order_item_name' 		=> $code,
			 		'order_item_type' 		=> 'coupon'
			 	) );

			 	// Add line item meta
			 	if ( $item_id ) {
			 		woocommerce_add_order_item_meta( $item_id, 'discount_amount', isset( $woocommerce->cart->coupon_discount_amounts[ $code ] ) ? $woocommerce->cart->coupon_discount_amounts[ $code ] : 0 );
				}
			}
		}

		// Store meta
		if ( $this->shipping_method ) {
			update_post_meta( $order_id, '_shipping_method', 		$this->shipping_method->id );
			update_post_meta( $order_id, '_shipping_method_title', 	$this->shipping_method->label );
		}

		if ( $this->payment_method ) {
			update_post_meta( $order_id, '_payment_method', 		$this->payment_method->id );
			update_post_meta( $order_id, '_payment_method_title', 	$this->payment_method->get_title() );
		}

		update_post_meta( $order_id, '_order_shipping', 		woocommerce_format_total( $woocommerce->cart->shipping_total ) );
		update_post_meta( $order_id, '_order_discount', 		woocommerce_format_total( $woocommerce->cart->get_order_discount_total() ) );
		update_post_meta( $order_id, '_cart_discount', 			woocommerce_format_total( $woocommerce->cart->get_cart_discount_total() ) );
		update_post_meta( $order_id, '_order_tax', 				woocommerce_clean( $woocommerce->cart->tax_total ) );
		update_post_meta( $order_id, '_order_shipping_tax', 	woocommerce_clean( $woocommerce->cart->shipping_tax_total ) );
		update_post_meta( $order_id, '_order_total', 			woocommerce_format_total( $woocommerce->cart->total ) );
		update_post_meta( $order_id, '_order_key', 				apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
		update_post_meta( $order_id, '_customer_user', 			absint( $this->customer_id ) );
		update_post_meta( $order_id, '_order_currency', 		get_woocommerce_currency() );
		update_post_meta( $order_id, '_prices_include_tax', 	get_option( 'woocommerce_prices_include_tax' ) );
		update_post_meta( $order_id, '_customer_ip_address',	isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] );
		update_post_meta( $order_id, '_customer_user_agent', 	isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );

		// Let plugins add meta
		do_action( 'woocommerce_checkout_update_order_meta', $order_id, $this->posted );

		// Order status
		wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );

		return $order_id;
	}

	/**
	 * Process the checkout after the confirm order button is pressed
	 *
	 * @access public
	 * @return void
	 */
	public function process_checkout() {
		global $wpdb, $woocommerce, $current_user;

		$woocommerce->verify_nonce( 'process_checkout' );

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) )
			define( 'WOOCOMMERCE_CHECKOUT', true );

		// Prevent timeout
		@set_time_limit(0);

		do_action( 'woocommerce_before_checkout_process' );

		if ( sizeof( $woocommerce->cart->get_cart() ) == 0 )
			$woocommerce->add_error( sprintf( __( 'Sorry, your session has expired. <a href="%s">Return to homepage &rarr;</a>', 'woocommerce' ), home_url() ) );

		do_action( 'woocommerce_checkout_process' );

		// Checkout fields (not defined in checkout_fields)
		$this->posted['shiptobilling'] 		= isset( $_POST['shiptobilling'] ) ? 1 : 0;
		$this->posted['terms'] 				= isset( $_POST['terms'] ) ? 1 : 0;
		$this->posted['createaccount'] 		= isset( $_POST['createaccount'] ) ? 1 : 0;
		$this->posted['payment_method'] 	= isset( $_POST['payment_method'] ) ? woocommerce_clean( $_POST['payment_method'] ) : '';
		$this->posted['shipping_method']	= isset( $_POST['shipping_method'] ) ? woocommerce_clean( $_POST['shipping_method'] ) : '';

		// Ship to billing only option
		if ( $woocommerce->cart->ship_to_billing_address_only() )
			$this->posted['shiptobilling'] = 1;

		// Update customer shipping and payment method to posted method
		$woocommerce->session->chosen_shipping_method 	= $this->posted['shipping_method'];
		$woocommerce->session->chosen_payment_method	= $this->posted['payment_method'];

		// Note if we skip shipping
		$skipped_shipping = false;

		// Get validation class
		$validation = $woocommerce->validation();

		// Get posted checkout_fields and do validation
		foreach ( $this->checkout_fields as $fieldset_key => $fieldset ) {

			// Skip shipping if its not needed
			if ( $fieldset_key == 'shipping' && ( $woocommerce->cart->ship_to_billing_address_only() || $this->posted['shiptobilling'] || ( ! $woocommerce->cart->needs_shipping() && get_option('woocommerce_require_shipping_address') == 'no' ) ) ) {
				$skipped_shipping = true;
				continue;
			}

			foreach ( $fieldset as $key => $field ) {

				if ( ! isset( $field['type'] ) )
					$field['type'] = 'text';

				// Get Value
				switch ( $field['type'] ) {
					case "checkbox" :
						$this->posted[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
					break;
					case "multiselect" :
						$this->posted[ $key ] = isset( $_POST[ $key ] ) ? implode( ', ', array_map( 'woocommerce_clean', $_POST[ $key ] ) ) : '';
					break;
					default :
						$this->posted[ $key ] = isset( $_POST[ $key ] ) ? woocommerce_clean( $_POST[ $key ] ) : '';
					break;
				}

				// Hooks to allow modification of value
				$this->posted[ $key ] = apply_filters( 'woocommerce_process_checkout_' . sanitize_title( $field['type'] ) . '_field', $this->posted[ $key ] );
				$this->posted[ $key ] = apply_filters( 'woocommerce_process_checkout_field_' . $key, $this->posted[ $key ] );

				// Validation: Required fields
				if ( isset( $field['required'] ) && $field['required'] && empty( $this->posted[ $key ] ) ) $woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __( 'is a required field.', 'woocommerce' ) );

				if ( ! empty( $this->posted[ $key ] ) ) {

					// Special handling for validation and formatting
					switch ( $key ) {
						case "billing_postcode" :
						case "shipping_postcode" :

							$validate_against = $key == 'billing_postcode' ? 'billing_country' : 'shipping_country';
							$this->posted[ $key ] = strtoupper( str_replace( ' ', '', $this->posted[ $key ] ) );

							if ( ! $validation->is_postcode( $this->posted[ $key ], $_POST[ $validate_against ] ) )
								$woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . sprintf( __( '(%s) is not a valid postcode/ZIP.', 'woocommerce' ), $this->posted[ $key ] ) );
							else
								$this->posted[ $key ] = $validation->format_postcode( $this->posted[ $key ], $_POST[ $validate_against ] );

						break;
						case "billing_state" :
						case "shipping_state" :

							// Get valid states
							$validate_against = $key == 'billing_state' ? 'billing_country' : 'shipping_country';
							$valid_states = $woocommerce->countries->get_states( $_POST[ $validate_against ] );
							if ( $valid_states )
								$valid_state_values = array_flip( array_map( 'strtolower', $valid_states ) );

							// Convert value to key if set
							if ( isset( $valid_state_values[ strtolower( $this->posted[ $key ] ) ] ) )
								 $this->posted[ $key ] = $valid_state_values[ strtolower( $this->posted[ $key ] ) ];

							// Only validate if the country has specific state options
							if ( $valid_states && sizeof( $valid_states ) > 0 )
								if ( ! in_array( $this->posted[ $key ], array_keys( $valid_states ) ) )
									$woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __( 'is not valid. Please enter one of the following:', 'woocommerce' ) . ' ' . implode( ', ', $valid_states ) );

						break;
						case "billing_phone" :

							$this->posted[ $key ] = $validation->format_phone( $this->posted[ $key ] );

							if ( ! $validation->is_phone( $this->posted[ $key ] ) )
								$woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid number.', 'woocommerce' ) );
						break;
						case "billing_email" :

							$this->posted[ $key ] = strtolower( $this->posted[ $key ] );

							if ( ! $validation->is_email( $this->posted[ $key ] ) )
								$woocommerce->add_error( '<strong>' . $field['label'] . '</strong> ' . __( 'is not a valid email address.', 'woocommerce' ) );
						break;
					}
				}
			}
		}

		// Update customer location to posted location so we can correctly check available shipping methods
		if ( isset( $this->posted['billing_country'] ) )
			$woocommerce->customer->set_country( $this->posted['billing_country'] );
		if ( isset( $this->posted['billing_state'] ) )
			$woocommerce->customer->set_state( $this->posted['billing_state'] );
		if ( isset( $this->posted['billing_postcode'] ) )
			$woocommerce->customer->set_postcode( $this->posted['billing_postcode'] );

		// Shipping Information
		if ( ! $skipped_shipping ) {

			// Update customer location to posted location so we can correctly check available shipping methods
			if ( isset( $this->posted['shipping_country'] ) )
				$woocommerce->customer->set_shipping_country( $this->posted['shipping_country'] );
			if ( isset( $this->posted['shipping_state'] ) )
				$woocommerce->customer->set_shipping_state( $this->posted['shipping_state'] );
			if ( isset( $this->posted['shipping_postcode'] ) )
				$woocommerce->customer->set_shipping_postcode( $this->posted['shipping_postcode'] );

		} else {

			// Update customer location to posted location so we can correctly check available shipping methods
			if ( isset( $this->posted['billing_country'] ) )
				$woocommerce->customer->set_shipping_country( $this->posted['billing_country'] );
			if ( isset( $this->posted['billing_state'] ) )
				$woocommerce->customer->set_shipping_state( $this->posted['billing_state'] );
			if ( isset( $this->posted['billing_postcode'] ) )
				$woocommerce->customer->set_shipping_postcode( $this->posted['billing_postcode'] );

		}

		// Update cart totals now we have customer address
		$woocommerce->cart->calculate_totals();

		// Handle accounts
		if ( is_user_logged_in() )
			$this->creating_account = false;
		elseif ( ! empty( $this->posted['createaccount'] ) )
			$this->creating_account = true;
		elseif ($this->must_create_account)
			$this->creating_account = true;
		else
			$this->creating_account = false;

		if ( $this->creating_account ) {

			if ( get_option( 'woocommerce_registration_email_for_username' ) == 'no' ) {

				if ( empty( $this->posted['account_username'] ) )
					$woocommerce->add_error( __( 'Please enter an account username.', 'woocommerce' ) );

				// Check the username
				if ( ! validate_username( $this->posted['account_username'] ) )
					$woocommerce->add_error( __( 'Invalid email/username.', 'woocommerce' ) );

				elseif ( username_exists( $this->posted['account_username'] ) )
					$woocommerce->add_error( __( 'An account is already registered with that username. Please choose another.', 'woocommerce' ) );

			} else {

				$this->posted['account_username'] = $this->posted['billing_email'];

			}

			// Validate passwords
			if ( empty($this->posted['account_password']) )
				$woocommerce->add_error( __( 'Please enter an account password.', 'woocommerce' ) );

			if ( $this->posted['account_password-2'] !== $this->posted['account_password'] )
				$woocommerce->add_error( __( 'Passwords do not match.', 'woocommerce' ) );

			// Check the e-mail address
			if ( email_exists( $this->posted['billing_email'] ) )
				$woocommerce->add_error( __( 'An account is already registered with your email address. Please login.', 'woocommerce' ) );

		}

		// Terms
		if ( ! isset( $_POST['woocommerce_checkout_update_totals'] ) && empty( $this->posted['terms'] ) && woocommerce_get_page_id( 'terms' ) > 0 )
			$woocommerce->add_error( __( 'You must accept our Terms &amp; Conditions.', 'woocommerce' ) );

		if ( $woocommerce->cart->needs_shipping() ) {

			// Shipping Method
			$available_methods = $woocommerce->shipping->get_available_shipping_methods();

			if ( ! isset( $available_methods[ $this->posted['shipping_method'] ] ) ) {
				$this->shipping_method = '';
				$woocommerce->add_error( __( 'Invalid shipping method.', 'woocommerce' ) );
			} else {
				$this->shipping_method = $available_methods[ $this->posted['shipping_method'] ];
			}
		}

		if ( $woocommerce->cart->needs_payment() ) {

			// Payment Method
			$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();

			if ( ! isset( $available_gateways[ $this->posted['payment_method'] ] ) ) {
				$this->payment_method = '';
				$woocommerce->add_error( __( 'Invalid payment method.', 'woocommerce' ) );
			} else {
				$this->payment_method = $available_gateways[ $this->posted['payment_method'] ];
				$this->payment_method->validate_fields();
			}
		}

		// Action after validation
		do_action( 'woocommerce_after_checkout_validation', $this->posted );

		if ( ! isset( $_POST['woocommerce_checkout_update_totals'] ) && $woocommerce->error_count() == 0 ) {

			$this->customer_id = get_current_user_id();

			try {

				// Create customer account and log them in
				if ( $this->creating_account && ! $this->customer_id ) {

					$reg_errors = new WP_Error();

					do_action( 'woocommerce_register_post', $this->posted['account_username'], $this->posted['billing_email'], $reg_errors );

					$errors = apply_filters( 'woocommerce_registration_errors', $reg_errors, $this->posted['account_username'], $this->posted['billing_email'] );

	                // if there are no errors, let's create the user account
					if ( ! $reg_errors->get_error_code() ) {

		                $user_pass = esc_attr( $this->posted['account_password'] );

		                $new_customer_data = array(
		                	'user_login' => $this->posted['account_username'],
		                	'user_pass'  => $user_pass,
		                	'user_email' => $this->posted['billing_email'],
		                	'role'       => 'customer'
		                );

		                $this->customer_id = wp_insert_user( apply_filters( 'woocommerce_new_customer_data', $new_customer_data ) );

		                if ( is_wp_error( $this->customer_id ) ) {
		                	throw new MyException( '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
						}

                        // Set the global user object
                        $current_user = get_user_by ( 'id', $this->customer_id );

	                    // Action
	                    do_action( 'woocommerce_created_customer', $this->customer_id );

	                    // send the user a confirmation and their login details
	                    $mailer = $woocommerce->mailer();
						$mailer->customer_new_account( $this->customer_id, $user_pass );

	                    // set the WP login cookie
	                    $secure_cookie = is_ssl() ? true : false;
	                    wp_set_auth_cookie( $this->customer_id, true, $secure_cookie );

					} else {
						throw new MyException( $reg_errors->get_error_message() );
					}

				}

				// Abort if errors are present
				if ( $woocommerce->error_count() > 0 )
					throw new MyException();

				// Create the order
				$order_id = $this->create_order();

				// Order is saved
				do_action( 'woocommerce_checkout_order_processed', $order_id, $this->posted );

				// Process payment
				if ( $woocommerce->cart->needs_payment() ) {

					// Store Order ID in session so it can be re-used after payment failure
					$woocommerce->session->order_awaiting_payment = $order_id;

					// Process Payment
					$result = $available_gateways[ $this->posted['payment_method'] ]->process_payment( $order_id );

					// Redirect to success/confirmation/payment page
					if ( $result['result'] == 'success' ) {

						$result = apply_filters('woocommerce_payment_successful_result', $result );

						if ( is_ajax() ) {
							echo '<!--WC_START-->' . json_encode( $result ) . '<!--WC_END-->';
							exit;
						} else {
							wp_redirect( $result['redirect'] );
							exit;
						}

					}

				} else {

					if ( empty( $order ) )
						$order = new WC_Order( $order_id );

					// No payment was required for order
					$order->payment_complete();

					// Empty the Cart
					$woocommerce->cart->empty_cart();

					// Get redirect
					$return_url = get_permalink( woocommerce_get_page_id( 'thanks' ) );
					$return_url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order->id, $return_url ) );

					// Redirect to success/confirmation/payment page
					if ( is_ajax() ) {
						echo '<!--WC_START-->' . json_encode(
							array(
								'result' 	=> 'success',
								'redirect' => apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
							)
						) . '<!--WC_END-->';
						exit;
					} else {
						wp_safe_redirect(
							apply_filters( 'woocommerce_checkout_no_payment_needed_redirect', $return_url, $order)
						);
						exit;
					}

				}

			} catch ( Exception $e ) {

				if ( ! empty( $e ) )
					$woocommerce->add_error( $e );

			}

		} // endif

		// If we reached this point then there were errors
		if ( is_ajax() ) {

			ob_start();
			$woocommerce->show_messages();
			$messages = ob_get_clean();

			echo '<!--WC_START-->' . json_encode(
				array(
					'result'	=> 'failure',
					'messages' 	=> $messages,
					'refresh' 	=> isset( $woocommerce->session->refresh_totals ) ? 'true' : 'false'
				)
			) . '<!--WC_END-->';

			unset( $woocommerce->session->refresh_totals );
			exit;
		}
	}


	/**
	 * Gets the value either from the posted data, or from the users meta data
	 *
	 * @access public
	 * @param string $input
	 * @return string
	 */
	public function get_value( $input ) {
		global $woocommerce;

		if ( ! empty( $_POST[ $input ] ) ) {

			return esc_attr( $_POST[ $input ] );

		} else {

			if ( is_user_logged_in() ) {

				$current_user = wp_get_current_user();

				if ( $meta = get_user_meta( $current_user->ID, $input, true ) )
					return $meta;

				if ( $input == "billing_email" )
					return $current_user->user_email;
			}

			$default_billing_country = apply_filters('default_checkout_country', ($woocommerce->customer->get_country()) ? $woocommerce->customer->get_country() : $woocommerce->countries->get_base_country());

			$default_shipping_country = apply_filters('default_checkout_country', ($woocommerce->customer->get_shipping_country()) ? $woocommerce->customer->get_shipping_country() : $woocommerce->countries->get_base_country());

			if ( $woocommerce->customer->has_calculated_shipping() ) {
				$default_billing_state  = apply_filters('default_checkout_state', $woocommerce->customer->get_state());
				$default_shipping_state = apply_filters('default_checkout_state', $woocommerce->customer->get_shipping_state());
			} else {
				$default_billing_state  = apply_filters('default_checkout_state', '');
				$default_shipping_state = apply_filters('default_checkout_state', '');
			}

			if ( $input == "billing_country" )
				return $default_billing_country;

			if ( $input == "billing_state" )
				return $default_billing_state;

			if ( $input == "billing_postcode" )
				return $woocommerce->customer->get_postcode() ? $woocommerce->customer->get_postcode() : '';

			if ( $input == "shipping_country" )
				return $default_shipping_country;

			if ( $input == "shipping_state" )
				return $default_shipping_state;

			if ( $input == "shipping_postcode" )
				return $woocommerce->customer->get_shipping_postcode() ? $woocommerce->customer->get_shipping_postcode() : '';
		}
	}
}
