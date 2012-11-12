<?php
/**
 * PayPal Standard Payment Gateway
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		WC_Paypal
 * @extends		WC_Payment_Gateway
 * @version		1.6.4
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_Paypal extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
	public function __construct() {
		global $woocommerce;

        $this->id			= 'paypal';
        $this->icon 		= apply_filters('woocommerce_paypal_icon', $woocommerce->plugin_url() . '/assets/images/icons/paypal.png');
        $this->has_fields 	= false;
        $this->liveurl 		= 'https://www.paypal.com/webscr';
		$this->testurl 		= 'https://www.sandbox.paypal.com/webscr';
        $this->method_title     = __( 'PayPal', 'woocommerce' );


		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->title 			= $this->settings['title'];
		$this->description 		= $this->settings['description'];
		$this->email 			= $this->settings['email'];
		$this->testmode			= $this->settings['testmode'];
		$this->send_shipping	= $this->settings['send_shipping'];
		$this->address_override	= isset( $this->settings['address_override'] ) ? $this->settings['address_override'] : 'no';
		$this->debug			= $this->settings['debug'];
		$this->form_submission_method = ( isset( $this->settings['form_submission_method'] ) && $this->settings['form_submission_method'] == 'yes' ) ? true : false;
		$this->page_style 		= ( isset( $this->settings['page_style'] ) ) ? $this->settings['page_style'] : '';
		$this->invoice_prefix	= ! empty( $this->settings['invoice_prefix'] ) ? $this->settings['invoice_prefix'] : 'WC-';

		// Logs
		if ($this->debug=='yes') $this->log = $woocommerce->logger();

		// Actions
		add_action( 'init', array(&$this, 'check_ipn_response') );
		add_action('valid-paypal-standard-ipn-request', array(&$this, 'successful_request') );
		add_action('woocommerce_receipt_paypal', array(&$this, 'receipt_page'));
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));

		if ( !$this->is_valid_for_use() ) $this->enabled = false;
    }


    /**
     * Check if this gateway is enabled and available in the user's country
     *
     * @access public
     * @return bool
     */
    function is_valid_for_use() {
        if (!in_array(get_woocommerce_currency(), array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB'))) return false;

        return true;
    }

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('PayPal standard', 'woocommerce'); ?></h3>
    	<p><?php _e('PayPal standard works by sending the user to PayPal to enter their payment information.', 'woocommerce'); ?></p>
    	<table class="form-table">
    	<?php
    		if ( $this->is_valid_for_use() ) :

    			// Generate the HTML For the settings form.
    			$this->generate_settings_html();

    		else :

    			?>
            		<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PayPal does not support your store currency.', 'woocommerce' ); ?></p></div>
        		<?php

    		endif;
    	?>
		</table><!--/.form-table-->
    	<?php
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable PayPal standard', 'woocommerce' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default' => __( 'PayPal', 'woocommerce' )
						),
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'woocommerce')
						),
			'email' => array(
							'title' => __( 'PayPal Email', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'Please enter your PayPal email address; this is needed in order to take payment.', 'woocommerce' ),
							'default' => ''
						),
			'invoice_prefix' => array(
							'title' => __( 'Invoice Prefix', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unqiue as PayPal will not allow orders with the same invoice number.', 'woocommerce' ),
							'default' => 'WC-'
						),
			'form_submission_method' => array(
							'title' => __( 'Submission method', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Use form submission method.', 'woocommerce' ),
							'description' => __( 'Enable this to post order data to PayPal via a form instead of using a redirect/querystring.', 'woocommerce' ),
							'default' => 'no'
						),
			'page_style' => array(
							'title' => __( 'Page Style', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'Optionally enter the name of the page style you wish to use. These are defined within your PayPal account.', 'woocommerce' ),
							'default' => ''
						),
			'shipping' => array(
							'title' => __( 'Shipping options', 'woocommerce' ),
							'type' => 'title',
							'description' => '',
						),
			'send_shipping' => array(
							'title' => __( 'Shipping details', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Send shipping details to PayPal instead of billing.', 'woocommerce' ),
							'description' => '',
							'description' => __( 'PayPal allows us to send 1 address. If you are using PayPal for shipping labels you may prefer to send the shipping address rather than billing.', 'woocommerce' ),
							'default' => 'no'
						),
			'address_override' => array(
							'title' => __( 'Address override', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable "address_override" to prevent address information from being changed.', 'woocommerce' ),
							'description' => __( 'PayPal verifies addresses therefore this setting can cause errors (we recommend keeping it disabled).', 'woocommerce' ),
							'default' => 'no'
						),
			'testing' => array(
							'title' => __( 'Gateway Testing', 'woocommerce' ),
							'type' => 'title',
							'description' => '',
						),
			'testmode' => array(
							'title' => __( 'PayPal sandbox', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable PayPal sandbox', 'woocommerce' ),
							'default' => 'yes',
							'description' => sprintf( __( 'PayPal sandbox can be used to test payments. Sign up for a developer account <a href="%s">here</a>.', 'woocommerce' ), 'https://developer.paypal.com/' ),
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'woocommerce' ),
							'default' => 'no',
							'description' => __( 'Log PayPal events, such as IPN requests, inside <code>woocommerce/logs/paypal.txt</code>' ),
						)
			);

    }


	/**
	 * Get PayPal Args for passing to PP
	 *
	 * @access public
	 * @param mixed $order
	 * @return array
	 */
	function get_paypal_args( $order ) {
		global $woocommerce;

		$order_id = $order->id;

		if ($this->debug=='yes') $this->log->add( 'paypal', 'Generating payment form for order #' . $order_id . '. Notify URL: ' . trailingslashit(home_url()).'?paypalListener=paypal_standard_IPN');

		if (in_array($order->billing_country, array('US','CA'))) :
			$order->billing_phone = str_replace( array( '(', '-', ' ', ')', '.' ), '', $order->billing_phone );
			$phone_args = array(
				'night_phone_a' => substr($order->billing_phone,0,3),
				'night_phone_b' => substr($order->billing_phone,3,3),
				'night_phone_c' => substr($order->billing_phone,6,4),
				'day_phone_a' 	=> substr($order->billing_phone,0,3),
				'day_phone_b' 	=> substr($order->billing_phone,3,3),
				'day_phone_c' 	=> substr($order->billing_phone,6,4)
			);
		else :
			$phone_args = array(
				'night_phone_b' => $order->billing_phone,
				'day_phone_b' 	=> $order->billing_phone
			);
		endif;

		// PayPal Args
		$paypal_args = array_merge(
			array(
				'cmd' 					=> '_cart',
				'business' 				=> $this->email,
				'no_note' 				=> 1,
				'currency_code' 		=> get_woocommerce_currency(),
				'charset' 				=> 'UTF-8',
				'rm' 					=> is_ssl() ? 2 : 1,
				'upload' 				=> 1,
				'return' 				=> add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ),
				'cancel_return'			=> $order->get_cancel_order_url(),
				'page_style'			=> $this->page_style,

				// Order key + ID
				'invoice'				=> $this->invoice_prefix . $order_id,
				'custom' 				=> $order->order_key,

				// IPN
				'notify_url'			=> trailingslashit( home_url() ) . '?paypalListener=paypal_standard_IPN',

				// Billing Address info
				'first_name'			=> $order->billing_first_name,
				'last_name'				=> $order->billing_last_name,
				'company'				=> $order->billing_company,
				'address1'				=> $order->billing_address_1,
				'address2'				=> $order->billing_address_2,
				'city'					=> $order->billing_city,
				'state'					=> $order->billing_state,
				'zip'					=> $order->billing_postcode,
				'country'				=> $order->billing_country,
				'email'					=> $order->billing_email
			),
			$phone_args
		);

		// Shipping
		if ( $this->send_shipping=='yes' ) {
			$paypal_args['address_override'] = ( $this->address_override == 'yes' ) ? 1 : 0;

			$paypal_args['no_shipping'] = 0;

			// If we are sending shipping, send shipping address instead of billing
			$paypal_args['first_name']		= $order->shipping_first_name;
			$paypal_args['last_name']		= $order->shipping_last_name;
			$paypal_args['company']			= $order->shipping_company;
			$paypal_args['address1']		= $order->shipping_address_1;
			$paypal_args['address2']		= $order->shipping_address_2;
			$paypal_args['city']			= $order->shipping_city;
			$paypal_args['state']			= $order->shipping_state;
			$paypal_args['country']			= $order->shipping_country;
			$paypal_args['zip']				= $order->shipping_postcode;
		} else {
			$paypal_args['no_shipping'] = 1;
		}

		// If prices include tax or have order discounts, send the whole order as a single item
		if ( get_option('woocommerce_prices_include_tax')=='yes' || $order->get_order_discount() > 0 ) :

			// Discount
			$paypal_args['discount_amount_cart'] = $order->get_order_discount();

			// Don't pass items - paypal borks tax due to prices including tax. PayPal has no option for tax inclusive pricing sadly. Pass 1 item for the order items overall
			$item_names = array();

			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				if ($item['qty']) $item_names[] = $item['name'] . ' x ' . $item['qty'];
			endforeach; endif;

			$paypal_args['item_name_1'] 	= sprintf( __('Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode(', ', $item_names);
			$paypal_args['quantity_1'] 		= 1;
			$paypal_args['amount_1'] 		= number_format($order->get_total() - $order->get_shipping() - $order->get_shipping_tax() + $order->get_order_discount(), 2, '.', '');

			// Shipping Cost
			// No longer using shipping_1 because
			//		a) paypal ignore it if *any* shipping rules are within paypal
			//		b) paypal ignore anyhing over 5 digits, so 999.99 is the max
			// $paypal_args['shipping_1']		= number_format( $order->get_shipping() + $order->get_shipping_tax() , 2, '.', '' );

			if ( ( $order->get_shipping() + $order->get_shipping_tax() ) > 0 ) :
				$paypal_args['item_name_2'] = __( 'Shipping via', 'woocommerce' ) . ' ' . ucwords( $order->shipping_method_title );
				$paypal_args['quantity_2'] 	= '1';
				$paypal_args['amount_2'] 	= number_format( $order->get_shipping() + $order->get_shipping_tax() , 2, '.', '' );
			endif;

		else :

			// Tax
			$paypal_args['tax_cart'] = $order->get_total_tax();

			// Cart Contents
			$item_loop = 0;
			if (sizeof($order->get_items())>0) : foreach ($order->get_items() as $item) :
				if ($item['qty']) :

					$item_loop++;

					$product = $order->get_product_from_item($item);

					$item_name 	= $item['name'];

					$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
					if ($meta = $item_meta->display( true, true )) :
						$item_name .= ' ('.$meta.')';
					endif;

					$paypal_args['item_name_'.$item_loop] = $item_name;
					if ($product->get_sku()) $paypal_args['item_number_'.$item_loop] = $product->get_sku();
					$paypal_args['quantity_'.$item_loop] = $item['qty'];
					$paypal_args['amount_'.$item_loop] = $order->get_item_total( $item, false );

				endif;
			endforeach; endif;

			// Shipping Cost item - paypal only allows shipping per item, we want to send shipping for the order
			if ($order->get_shipping()>0) :
				$item_loop++;
				$paypal_args['item_name_'.$item_loop] = __('Shipping via', 'woocommerce') . ' ' . ucwords($order->shipping_method_title);
				$paypal_args['quantity_'.$item_loop] = '1';
				$paypal_args['amount_'.$item_loop] = number_format($order->get_shipping(), 2, '.', '');
			endif;

		endif;

		$paypal_args = apply_filters( 'woocommerce_paypal_args', $paypal_args );

		return $paypal_args;
	}


    /**
	 * Generate the paypal button link
     *
     * @access public
     * @param mixed $order_id
     * @return string
     */
    function generate_paypal_form( $order_id ) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl . '?test_ipn=1&';
		else :
			$paypal_adr = $this->liveurl . '?';
		endif;

		$paypal_args = $this->get_paypal_args( $order );

		$paypal_args_array = array();

		foreach ($paypal_args as $key => $value) {
			$paypal_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
		}

		$woocommerce->add_inline_js('
			jQuery("body").block({
					message: "<img src=\"' . esc_url( apply_filters( 'woocommerce_ajax_loader_url', $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif' ) ) . '\" alt=\"Redirecting&hellip;\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'woocommerce').'",
					overlayCSS:
					{
						background: "#fff",
						opacity: 0.6
					},
					css: {
				        padding:        20,
				        textAlign:      "center",
				        color:          "#555",
				        border:         "3px solid #aaa",
				        backgroundColor:"#fff",
				        cursor:         "wait",
				        lineHeight:		"32px"
				    }
				});
			jQuery("#submit_paypal_payment_form").click();
		');

		return '<form action="'.esc_url( $paypal_adr ).'" method="post" id="paypal_payment_form" target="_top">
				' . implode('', $paypal_args_array) . '
				<input type="submit" class="button-alt" id="submit_paypal_payment_form" value="'.__('Pay via PayPal', 'woocommerce').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
			</form>';

	}


    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
	function process_payment( $order_id ) {

		$order = new WC_Order( $order_id );

		if ( ! $this->form_submission_method ) {

			$paypal_args = $this->get_paypal_args( $order );

			$paypal_args = http_build_query( $paypal_args, '', '&' );

			if ( $this->testmode == 'yes' ):
				$paypal_adr = $this->testurl . '?test_ipn=1&';
			else :
				$paypal_adr = $this->liveurl . '?';
			endif;

			return array(
				'result' 	=> 'success',
				'redirect'	=> $paypal_adr . $paypal_args
			);

		} else {

			return array(
				'result' 	=> 'success',
				'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);

		}

	}


    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
	function receipt_page( $order ) {

		echo '<p>'.__('Thank you for your order, please click the button below to pay with PayPal.', 'woocommerce').'</p>';

		echo $this->generate_paypal_form( $order );

	}

	/**
	 * Check PayPal IPN validity
	 **/
	function check_ipn_request_is_valid() {
		global $woocommerce;

		if ($this->debug=='yes') $this->log->add( 'paypal', 'Checking IPN response is valid...' );

    	// Get recieved values from post data
		$received_values = (array) stripslashes_deep( $_POST );

		 // Add cmd to the post array
		$received_values['cmd'] = '_notify-validate';

        // Send back post vars to paypal
        $params = array(
        	'body' 			=> $received_values,
        	'sslverify' 	=> false,
        	'timeout' 		=> 30,
        	'user-agent'	=> 'WooCommerce/' . $woocommerce->version
        );

        // Get url
       	if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl;
		else :
			$paypal_adr = $this->liveurl;
		endif;

		// Post back to get a response
        $response = wp_remote_post( $paypal_adr, $params );

        if ($this->debug=='yes') $this->log->add( 'paypal', 'IPN Response: ' . print_r($response, true) );

        // check to see if the request was valid
        if ( !is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && (strcmp( $response['body'], "VERIFIED") == 0)) {
            if ($this->debug=='yes') $this->log->add( 'paypal', 'Received valid response from PayPal' );
            return true;
        }

        if ($this->debug=='yes') :
        	$this->log->add( 'paypal', 'Received invalid response from PayPal' );
        	if (is_wp_error($response)) :
        		$this->log->add( 'paypal', 'Error response: ' . $result->get_error_message() );
        	endif;
        endif;

        return false;
    }


	/**
	 * Check for PayPal IPN Response
	 *
	 * @access public
	 * @return void
	 */
	function check_ipn_response() {

		if (isset($_GET['paypalListener']) && $_GET['paypalListener'] == 'paypal_standard_IPN'):

			@ob_clean();

        	$_POST = stripslashes_deep($_POST);

        	if ($this->check_ipn_request_is_valid()) :

        		header('HTTP/1.1 200 OK');

            	do_action("valid-paypal-standard-ipn-request", $_POST);

			else :

				wp_die("PayPal IPN Request Failure");

       		endif;

       	endif;

	}


	/**
	 * Successful Payment!
	 *
	 * @access public
	 * @param array $posted
	 * @return void
	 */
	function successful_request( $posted ) {
		global $woocommerce;

		// Custom holds post ID
	    if ( !empty($posted['invoice']) && !empty($posted['custom']) ) {

	    	// Backwards comp for IPN requests
	    	if ( is_numeric( $posted['custom'] ) ) {
		    	$order_id = $posted['custom'];
		    	$order_key = $posted['invoice'];
	    	} else {
		    	$order_id = (int) str_replace( $this->invoice_prefix, '', $posted['invoice'] );
		    	$order_key = $posted['custom'];
	    	}

			$order = new WC_Order( $order_id );

	        if ( $order->order_key !== $order_key ) :
	        	if ($this->debug=='yes') $this->log->add( 'paypal', 'Error: Order Key does not match invoice.' );
	        	exit;
	        endif;

	        // Lowercase
	        $posted['payment_status'] = strtolower($posted['payment_status']);
	        $posted['txn_type'] = strtolower($posted['txn_type']);

	        // Sandbox fix
	        if ($posted['test_ipn']==1 && $posted['payment_status']=='pending') $posted['payment_status'] = 'completed';

	        if ($this->debug=='yes') $this->log->add( 'paypal', 'Payment status: ' . $posted['payment_status'] );

	        // We are here so lets check status and do actions
	        switch ($posted['payment_status']) :
	            case 'completed' :

	            	// Check order not already completed
	            	if ($order->status == 'completed') :
	            		 if ($this->debug=='yes') $this->log->add( 'paypal', 'Aborting, Order #' . $order_id . ' is already complete.' );
	            		 exit;
	            	endif;

	            	// Check valid txn_type
	            	$accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');
					if (!in_array($posted['txn_type'], $accepted_types)) :
						if ($this->debug=='yes') $this->log->add( 'paypal', 'Aborting, Invalid type:' . $posted['txn_type'] );
						exit;
					endif;
					
					// Validate Amount
				    if ( $order->get_total() != $posted['mc_gross'] ) {
				    	
				    	if ( $this->debug == 'yes' ) 
				    		$this->log->add( 'paypal', 'Payment error: Amounts do not match (gross ' . $posted['mc_gross'] . ')' );
				    
				    	// Put this order on-hold for manual checking
				    	$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'woocommerce' ), $posted['mc_gross'] ) );
				    	
				    	exit;
				    }

					 // Store PP Details
	                if ( ! empty( $posted['payer_email'] ) )
	                	update_post_meta( $order_id, 'Payer PayPal address', $posted['payer_email'] );
	                if ( ! empty( $posted['txn_id'] ) )
	                	update_post_meta( $order_id, 'Transaction ID', $posted['txn_id'] );
	                if ( ! empty( $posted['first_name'] ) )
	                	update_post_meta( $order_id, 'Payer first name', $posted['first_name'] );
	                if ( ! empty( $posted['last_name'] ) )
	                	update_post_meta( $order_id, 'Payer last name', $posted['last_name'] );
	                if ( ! empty( $posted['payment_type'] ) )
	                	update_post_meta( $order_id, 'Payment type', $posted['payment_type'] );

	            	// Payment completed
	                $order->add_order_note( __('IPN payment completed', 'woocommerce') );
	                $order->payment_complete();

	                if ($this->debug=='yes') $this->log->add( 'paypal', 'Payment complete.' );

	            break;
	            case 'denied' :
	            case 'expired' :
	            case 'failed' :
	            case 'voided' :
	                // Order failed
	                $order->update_status('failed', sprintf(__('Payment %s via IPN.', 'woocommerce'), strtolower($posted['payment_status']) ) );
	            break;
	            case "refunded" :

	            	// Only handle full refunds, not partial
	            	if ($order->get_total() == ($posted['mc_gross']*-1)) {

		            	// Mark order as refunded
		            	$order->update_status('refunded', sprintf(__('Payment %s via IPN.', 'woocommerce'), strtolower($posted['payment_status']) ) );

		            	$mailer = $woocommerce->mailer();

		            	$mailer->wrap_message(
		            		__('Order refunded/reversed', 'woocommerce'),
		            		sprintf(__('Order %s has been marked as refunded - PayPal reason code: %s', 'woocommerce'), $order->get_order_number(), $posted['reason_code'] )
						);

						$mailer->send( get_option('woocommerce_new_order_email_recipient'), sprintf( __('Payment for order %s refunded/reversed', 'woocommerce'), $order->get_order_number() ), $message );

					}

	            break;
	            case "reversed" :
	            case "chargeback" :

	            	// Mark order as refunded
	            	$order->update_status('refunded', sprintf( __('Payment %s via IPN.', 'woocommerce'), strtolower( $posted['payment_status'] ) ) );

	            	$mailer = $woocommerce->mailer();

	            	$mailer->wrap_message(
	            		__('Order refunded/reversed', 'woocommerce'),
	            		sprintf(__('Order %s has been marked as refunded - PayPal reason code: %s', 'woocommerce'), $order->get_order_number(), $posted['reason_code'] )
					);

					$mailer->send( get_option('woocommerce_new_order_email_recipient'), sprintf( __('Payment for order %s refunded/reversed', 'woocommerce'), $order->get_order_number() ), $message );

	            break;
	            default:
	            	// No action
	            break;
	        endswitch;

			exit;

	    }

	}

}


/**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @param array $methods
 * @package		WooCommerce/Classes/Payment
 * @return array
 */
function add_paypal_gateway( $methods ) {
	$methods[] = 'WC_Paypal';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_paypal_gateway' );