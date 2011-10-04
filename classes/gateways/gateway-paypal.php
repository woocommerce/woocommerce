<?php
/**
 * PayPal Standard Payment Gateway
 * 
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		woocommerce_moneybookers
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_paypal extends woocommerce_payment_gateway {
		
	public function __construct() { 
		global $woocommerce;
		
        $this->id			= 'paypal';
        $this->icon 		= $woocommerce->plugin_url() . '/assets/images/icons/paypal.png';
        $this->has_fields 	= false;
        $this->liveurl 		= 'https://www.paypal.com/webscr';
		$this->testurl 		= 'https://www.sandbox.paypal.com/webscr';
        
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->title 		= $this->settings['title'];
		$this->description 	= $this->settings['description'];
		$this->email 		= $this->settings['email'];
		$this->testmode		= $this->settings['testmode'];		
		$this->send_shipping = $this->settings['send_shipping'];
		
		// Actions
		add_action( 'init', array(&$this, 'check_ipn_response') );
		add_action('valid-paypal-standard-ipn-request', array(&$this, 'successful_request') );
		add_action('woocommerce_receipt_paypal', array(&$this, 'receipt_page'));
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    } 
    
	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable PayPal standard', 'woothemes' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default' => __( 'PayPal', 'woothemes' )
						),
			'description' => array(
							'title' => __( 'Description', 'woothemes' ), 
							'type' => 'textarea', 
							'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
							'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'woothemes')
						),
			'email' => array(
							'title' => __( 'PayPal Email', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'Please enter your PayPal email address; this is needed in order to take payment.', 'woothemes' ), 
							'default' => ''
						),
			'send_shipping' => array(
							'title' => __( 'Shipping details', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Send shipping details to PayPal', 'woothemes' ), 
							'default' => 'no'
						), 
			'testmode' => array(
							'title' => __( 'PayPal sandbox', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable PayPal sandbox', 'woothemes' ), 
							'default' => 'yes'
						)
			);
    
    } // End init_form_fields()
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('PayPal standard', 'woothemes'); ?></h3>
    	<p><?php _e('PayPal standard works by sending the user to PayPal to enter their payment information.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()
    
    /**
	 * There are no payment fields for paypal, but we want to show the description if set.
	 **/
    function payment_fields() {
    	if ($this->description) echo wpautop(wptexturize($this->description));
    }
    
	/**
	 * Generate the paypal button link
	 **/
    public function generate_paypal_form( $order_id ) {
		global $woocommerce;
		
		$order = &new woocommerce_order( $order_id );
		
		if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl . '?test_ipn=1&';		
		else :
			$paypal_adr = $this->liveurl . '?';		
		endif;
		
		$shipping_name = explode(' ', $order->shipping_method);
		
		if (in_array($order->billing_country, array('US','CA'))) :
			$order->billing_phone = str_replace(array('(', '-', ' ', ')'), '', $order->billing_phone);
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
		
		$paypal_args = array_merge(
			array(
				'cmd' 					=> '_cart',
				'business' 				=> $this->email,
				'no_note' 				=> 1,
				'currency_code' 		=> get_option('woocommerce_currency'),
				'charset' 				=> 'UTF-8',
				'rm' 					=> 2,
				'upload' 				=> 1,
				'return' 				=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))),
				'cancel_return'			=> $order->get_cancel_order_url(),
				//'cancel_return'			=> home_url(),
				
				// Order key
				'custom'				=> $order_id,
				
				// IPN
				'notify_url'			=> trailingslashit(get_bloginfo('wpurl')).'?paypalListener=paypal_standard_IPN',
				
				// Address info
				'first_name'			=> $order->billing_first_name,
				'last_name'				=> $order->billing_last_name,
				'company'				=> $order->billing_company,
				'address1'				=> $order->billing_address_1,
				'address2'				=> $order->billing_address_2,
				'city'					=> $order->billing_city,
				'state'					=> $order->billing_state,
				'zip'					=> $order->billing_postcode,
				'country'				=> $order->billing_country,
				'email'					=> $order->billing_email,
	
				// Payment Info
				'invoice' 				=> $order->order_key,
				'tax_cart'				=> $order->get_total_tax(),
				'discount_amount_cart' 	=> $order->order_discount
			), 
			$phone_args
		);
		
		if ($this->send_shipping=='yes') :
			$paypal_args['no_shipping'] = 0;
			$paypal_args['address_override'] = 1;
		else :
			$paypal_args['no_shipping'] = 1;
		endif;
		
		// Cart Contents
		$item_loop = 0;
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :
			if ($item['qty']) :
				
				$item_loop++;
				
				$item_name = $item['name'];
				
				if (isset($item['item_meta'])) :
					if ($meta = woocommerce_get_formatted_variation( $item['item_meta'], true )) :
						$item_name .= ' ('.$meta.')';
					endif;
				endif;
				
				$paypal_args['item_name_'.$item_loop] = $item_name;
				$paypal_args['quantity_'.$item_loop] = $item['qty'];
				$paypal_args['amount_'.$item_loop] = $item['cost'];
				
			endif;
		endforeach; endif;
		
		// Shipping Cost
		$item_loop++;
		$paypal_args['item_name_'.$item_loop] = __('Shipping cost', 'woothemes');
		$paypal_args['quantity_'.$item_loop] = '1';
		$paypal_args['amount_'.$item_loop] = number_format($order->order_shipping, 2);
		
		$paypal_args_array = array();

		foreach ($paypal_args as $key => $value) {
			$paypal_args_array[] = '<input type="hidden" name="'.esc_attr( $key ).'" value="'.esc_attr( $value ).'" />';
		}
		
		return '<form action="'.esc_url( $paypal_adr ).'" method="post" id="paypal_payment_form">
				' . implode('', $paypal_args_array) . '
				<input type="submit" class="button-alt" id="submit_paypal_payment_form" value="'.__('Pay via PayPal', 'woothemes').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{ 
								message: "<img src=\"'.esc_url( $woocommerce->plugin_url() ).'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'woothemes').'", 
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
					});
				</script>
			</form>';
		
	}
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		
		$order = &new woocommerce_order( $order_id );
		
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
		);
		
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order, please click the button below to pay with PayPal.', 'woothemes').'</p>';
		
		echo $this->generate_paypal_form( $order );
		
	}
	
	/**
	 * Check PayPal IPN validity
	 **/
	function check_ipn_request_is_valid() {
    
    	 // Add cmd to the post array
        $_POST['cmd'] = '_notify-validate';

        // Send back post vars to paypal
        $params = array( 'body' => $_POST );

        // Get url
       	if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl;		
		else :
			$paypal_adr = $this->liveurl;		
		endif;
		
		// Post back to get a response
        $response = wp_remote_post( $paypal_adr, $params );
		
		 // Clean
        unset($_POST['cmd']);
        
        // check to see if the request was valid
        if ( !is_wp_error($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 && (strcmp( $response['body'], "VERIFIED") == 0)) {
            return true;
        } 
        
        return false;
    }
	
	/**
	 * Check for PayPal IPN Response
	 **/
	function check_ipn_response() {
			
		if (isset($_GET['paypalListener']) && $_GET['paypalListener'] == 'paypal_standard_IPN'):
		
        	$_POST = stripslashes_deep($_POST);
        	
        	if ($this->check_ipn_request_is_valid()) :
        	
            	do_action("valid-paypal-standard-ipn-request", $_POST);

       		endif;
       		
       	endif;
			
	}
	
	/**
	 * Successful Payment!
	 **/
	function successful_request( $posted ) {
		
		// Custom holds post ID
	    if ( !empty($posted['txn_type']) && !empty($posted['invoice']) ) {
	
	        $accepted_types = array('cart', 'instant', 'express_checkout', 'web_accept', 'masspay', 'send_money');
	
	        if (!in_array(strtolower($posted['txn_type']), $accepted_types)) exit;
			
			$order = new woocommerce_order( (int) $posted['custom'] );
	
	        if ($order->order_key!==$posted['invoice']) exit;
	        
	        // Sandbox fix
	        if ($posted['test_ipn']==1 && $posted['payment_status']=='Pending') $posted['payment_status'] = 'completed';
	        			
			if ($order->status !== 'completed') :
		        // We are here so lets check status and do actions
		        switch (strtolower($posted['payment_status'])) :
		            case 'completed' :
		            	// Payment completed
		                $order->add_order_note( __('IPN payment completed', 'woothemes') );
		                $order->payment_complete();
		            break;
		            case 'denied' :
		            case 'expired' :
		            case 'failed' :
		            case 'voided' :
		                // Order failed
		                $order->update_status('failed', sprintf(__('Payment %s via IPN.', 'woothemes'), strtolower($posted['payment_status']) ) );
		            break;
		            default:
		            	// No action
		            break;
		        endswitch;
			endif;
			
			exit;
			
	    }
		
	}

}

/**
 * Add the gateway to WooCommerce
 **/
function add_paypal_gateway( $methods ) {
	$methods[] = 'woocommerce_paypal'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_paypal_gateway' );
