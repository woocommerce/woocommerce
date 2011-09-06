<?php
/**
 * PayFast Payment Gateway
 * 
 * Provides a PayFast Payment Gateway.
 *
 * @class 		woocommerce_payfast
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_payfast extends woocommerce_payment_gateway {
	
	var $plugin_id = 'woocommerce_';
	var $settings = array(
						'enabled' => 'yes', 
						'title' => 'PayFast', 
						'description' => '', 
						'testmode' => 'yes', 
						'merchant_id' => '', 
						'merchant_key' => ''
					);
	
	var $form_fields = array();
	var $errors = array();
	var $sanitized_fields = array();
	
	public function __construct() { 
        global $woocommerce;
        
        $this->id			= 'payfast';
        $this->icon 		= $woocommerce->plugin_url() . '/assets/images/icons/payfast.png';
        $this->has_fields 	= true;
		
		// Load the settings.
		$this->init_settings();
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Setup default merchant data.
		$this->merchant_id = $this->settings['merchant_id'];
		$this->merchant_key = $this->settings['merchant_key'];
		$this->url = 'https://www.payfast.co.za/eng/process';
		
		// Setup the test data, if in test mode.
		if ( $this->settings['testmode'] == 'yes' ) {
			$this->merchant_id = '10000100';
			$this->merchant_key = '46f0cd694581a';
			$this->url = 'https://sandbox.payfast.co.za/eng/process';
		}
		
		// add_action( 'init', array( &$this, 'check_ipn_response' ) );
		// add_action( 'valid-paypal-standard-ipn-request', array( &$this, 'successful_request' ) );
		
		add_action( 'woocommerce_update_options', array( &$this, 'process_admin_options' ) );
		
		// add_action( 'receipt_payfast', array( &$this, 'receipt_page' ) );
    } 

	/**
     * Initialise Gateway Settings Form Fields
     *
     * Generate form fields HTML.
     *
     * @since 1.0.0
     * @uses get_option()
     */
    
    private function init_form_fields () {
    
    	$this->form_fields = array(
    						'title' => array(
    										'title' => __( 'Title', 'woothemes' ), 
    										'type' => 'text', 
    										'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' )
    									), 
							'enabled' => array(
											'title' => __( 'Enable/Disable', 'woothemes' ), 
											'type' => 'checkbox', 
											'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' )
										), 
							'description' => array(
											'title' => __( 'Description', 'woothemes' ), 
											'type' => 'text', 
											'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' )
										),  
							'testmode' => array(
											'title' => __( 'PayFast Sandbox', 'woothemes' ), 
											'type' => 'checkbox', 
											'description' => __( 'Place the payment gateway in development mode.', 'woothemes' )
										), 
							'merchant_id' => array(
											'title' => __( 'Merchant ID', 'woothemes' ), 
											'type' => 'text', 
											'description' => __( 'This is the merchant ID, received from PayFast.', 'woothemes' )
										), 
							'merchant_key' => array(
											'title' => __( 'Merchant Key', 'woothemes' ), 
											'type' => 'text', 
											'description' => __( 'This is the merchant key, received from PayFast.', 'woothemes' )
										)
							);
    
    } // End init_form_fields()
    
    /**
     * Initialise Gateway Settings
     *
     * Store all settings in a single database entry
     * and make sure the $settings array is either the default
     * or the settings stored in the database.
     *
     * @since 1.0.0
     * @uses get_option(), add_option()
     */
    
    private function init_settings () {
    	if ( ! is_array( $this->settings ) ) { return; }
    	
    	$settings = array();
    	$existing_settings = get_option( $this->plugin_id . $this->id . '_settings' );
    	
    	if ( ! $existing_settings ) {
    		add_option( $this->plugin_id . $this->id . '_settings' );
    	} else {
    		// Prevent "undefined index" errors.
    		foreach ( $this->settings as $k => $v ) {
    			if ( ! isset( $existing_settings[$k] ) ) {
    				$existing_settings[$k] = $v;
    			}  
    		}
    	
    		$this->settings = $existing_settings;
    	}
    	
    	if ( isset( $this->settings['enabled'] ) && ( $this->settings['enabled'] == 'yes' ) ) { $this->enabled = 'yes'; }
    } // End init_settings()
    
    /**
     * Generate Settings HTML.
     *
     * Generate the HTML for the fields on the "settings" screen.
     *
     * @since 1.0.0
     * @uses method_exists()
     */
    
    private function generate_settings_html () {
    	$html = '';
    	foreach ( $this->form_fields as $k => $v ) {
    		if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] == 'text'; } // Default to "text" field type.
    		
    		if ( method_exists( $this, 'generate_' . $v['type'] . '_html' ) ) {
    			$html .= $this->{'generate_' . $v['type'] . '_html'}( $k, $v );
    		}
    	}
    	
    	echo $html;
    } // End generate_settings_html()
    
    /**
     * Generate Text Input HTML.
     *
     * @since 1.0.0
     * @return $html string
     */
    
    private function generate_text_html ( $key, $data ) {
    	$html = '';
    	
    	if ( isset( $data['title'] ) && $data['title'] != '' ) { $title = $data['title']; }
    	
		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">' . $title . '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . $title . '</span></legend>' . "\n";
				$html .= '<label for="' . $this->plugin_id . $this->id . '_' . $key . '">';
				$html .= '<input class="input-text wide-input" type="text" name="' . $this->plugin_id . $this->id . '_' . $key . '" id="' . $this->plugin_id . $this->id . '_' . $key . '" style="min-width:50px;" value="' . $this->settings[$key] . '" />';
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= '<span class="description">' . $data['description'] . '</span>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
    	
    	return $html;
    } // End generate_checkbox_html()
    
    /**
     * Generate Checkbox HTML.
     *
     * @since 1.0.0
     * @return $html string
     */
    
    private function generate_checkbox_html ( $key, $data ) {
    	$html = '';
    	
    	if ( isset( $data['title'] ) && $data['title'] != '' ) { $title = $data['title']; }
    	
		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">' . $title . '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . $title . '</span></legend>' . "\n";
				$html .= '<label for="' . $this->plugin_id . $this->id . '_' . $key . '">';
				$html .= '<input name="' . $this->plugin_id . $this->id . '_' . $key . '" id="' . $this->plugin_id . $this->id . '_' . $key . '" type="checkbox" value="1" ' . checked( $this->settings[$key], 'yes', false ) . ' />' . $title . '</label><br />' . "\n";
				if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= '<span class="description">' . $data['description'] . '</span>' . "\n"; }
			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
    	
    	return $html;
    } // End generate_checkbox_html()
    
    /**
     * Validate Settings Field Data.
     *
     * Validate the data on the "Settings" form.
     *
     * @since 1.0.0
     * @uses method_exists()
     */
    
    private function validate_settings_fields () {
    	// TO DO: NONCE SECURITY CHECK
    	
    	foreach ( $this->form_fields as $k => $v ) {
    		if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] == 'text'; } // Default to "text" field type.
    		
    		if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
    			$field = $this->{'validate_' . $v['type'] . '_field'}( $k );
    			$this->sanitized_fields[$k] = $field;
    		} else {
    			$this->sanitized_fields[$k] = $this->settings[$k];
    		}
    	}
    } // End validate_settings_fields()
    
    /**
     * Validate Checkbox Field.
     *
     * If not set, return "no", otherwise return "yes".
     * 
     * @since 1.0.0
     * @return $status string
     */
     
    private function validate_checkbox_field ( $key ) {
    	// TO DO: NONCE SECURITY CHECK
    	
    	$status = 'no';
    	if ( isset( $_POST[$this->plugin_id . $this->id . '_' . $key] ) && ( 1 == $_POST[$this->plugin_id . $this->id . '_' . $key] ) ) {
    		$status = 'yes';
    	}
    	
    	return $status;
    } // End validate_checkbox_field()
    
    /**
     * Validate Text Field.
     *
     * Make sure the data is escaped correctly, etc.
     * 
     * @since 1.0.0
     * @return $text string
     */
     
    private function validate_text_field ( $key ) {
    	// TO DO: NONCE SECURITY CHECK
    	$text = $this->settings[$key];
    	
    	if ( isset( $_POST[$this->plugin_id . $this->id . '_' . $key] ) && ( '' != $_POST[$this->plugin_id . $this->id . '_' . $key] ) ) {
    		$text = esc_attr( woocommerce_clean( $_POST[$this->plugin_id . $this->id . '_' . $key] ) );
    	}
    	
    	return $text;
    } // End validate_text_field()
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e( 'PayFast', 'woothemes' ); ?></h3>
    	<p><?php printf( __( 'PayFast works by sending the user to %sPayFast%s to enter their payment information.', 'woothemes' ), '<a href="http://payfast.co.za/">', '</a>' ); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()
    
    /**
	 * There are no payment fields for PayFast, but we want to show the description if set.
	 *
	 * @since 1.0.0
	 */
    function payment_fields() {
    	if ( isset( $this->settings['description'] ) && ( '' != $this->settings['description'] ) ) {
    		echo wpautop( wptexturize( $this->settings['description'] ) );
    	}
    } // End payment_fields()
    
	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 */
    public function process_admin_options() {
    	// TO DO: NONCE SECURITY CHECK
    	
    	$this->validate_settings_fields();
    	
    	if ( count( $this->errors ) > 0 ) {
    		$this->display_errors();
    	} else {
    		update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
    	}
    }
    
    private function display_errors() {
    	// TO DO - Generate errors HTML.
    } // End display_errors()
    
	/**
	 * Generate the paypal button link
	 **/
    public function generate_paypal_form( $order_id ) {
	/*	
		$order = &new woocommerce_order( $order_id );
		
		if ( $this->testmode == 'yes' ):
			$paypal_adr = $this->testurl . '?test_ipn=1&';		
		else :
			$paypal_adr = $this->liveurl . '?';		
		endif;
		
		$shipping_name = explode(' ', $order->shipping_method);
		
		if (in_array($order->billing_country, array('US','CA'))) :
			$phone_args = array(
				'night_phone_a' => substr($order->billing_phone,0,3),
				'night_phone_b' => substr($order->billing_phone,0,3),
				'night_phone_c' => substr($order->billing_phone,0,3),
				'day_phone_a' 	=> substr($order->billing_phone,0,3),
				'day_phone_b' 	=> substr($order->billing_phone,0,3),
				'day_phone_c' 	=> substr($order->billing_phone,0,3)
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
				//'tax'					=> $order->get_total_tax(),
				'tax_cart'				=> $order->get_total_tax(),
				//'amount' 				=> $order->order_total,
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
			$_product = &new woocommerce_product($item['id']);
			if ($_product->exists() && $item['qty']) :
				
				$item_loop++;
				
				$paypal_args['item_name_'.$item_loop] = $_product->get_title();
				$paypal_args['quantity_'.$item_loop] = $item['qty'];
				$paypal_args['amount_'.$item_loop] = $_product->get_price_excluding_tax();
				
			endif;
		endforeach; endif;
		
		// Shipping Cost
		$item_loop++;
		$paypal_args['item_name_'.$item_loop] = __('Shipping cost', 'woothemes');
		$paypal_args['quantity_'.$item_loop] = '1';
		$paypal_args['amount_'.$item_loop] = number_format($order->order_shipping, 2);
		
		$paypal_args_array = array();

		foreach ($paypal_args as $key => $value) {
			$paypal_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		
		return '<form action="'.$paypal_adr.'" method="post" id="paypal_payment_form">
				' . implode('', $paypal_args_array) . '
				<input type="submit" class="button-alt" id="submit_paypal_payment_form" value="'.__('Pay via PayPal', 'woothemes').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woothemes').'</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{ 
								message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'woothemes').'", 
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
							        cursor:         "wait" 
							    } 
							});
						jQuery("#submit_paypal_payment_form").click();
					});
				</script>
			</form>';
	*/
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
	/*	
		echo '<p>'.__('Thank you for your order, please click the button below to pay with PayPal.', 'woothemes').'</p>';
		
		echo $this->generate_paypal_form( $order );
	*/	
	}
	
	/**
	 * Check PayPal IPN validity
	 **/
	function check_ipn_request_is_valid() {
    /*
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
    */
    }
	
	/**
	 * Check for PayPal IPN Response
	 **/
	function check_ipn_response() {
	/*		
		if (isset($_GET['paypalListener']) && $_GET['paypalListener'] == 'paypal_standard_IPN'):
		
        	$_POST = stripslashes_deep($_POST);
        	
        	if ($this->check_ipn_request_is_valid()) :
        	
            	do_action("valid-paypal-standard-ipn-request", $_POST);

       		endif;
       		
       	endif;
	*/		
	}
	
	/**
	 * Successful Payment!
	 **/
	function successful_request( $posted ) {
	/*	
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
		                // Hold order
		                $order->update_status('on-hold', sprintf(__('Payment %s via IPN.', 'woothemes'), strtolower(sanitize($posted['payment_status'])) ) );
		            break;
		            default:
		            	// No action
		            break;
		        endswitch;
			endif;
			
			exit;
			
	    }
	*/	
	}

}

/**
 * Add the gateway to WooCommerce
 **/
function add_payfast_gateway( $methods ) {
	$methods[] = 'woocommerce_payfast'; return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_payfast_gateway' );
