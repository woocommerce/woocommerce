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
        $this->id			= 'paypal';
        $this->icon 		= woocommerce::plugin_url() . '/assets/images/icons/paypal.png';
        $this->has_fields 	= false;
      	$this->enabled		= get_option('woocommerce_paypal_enabled');
		$this->title 		= get_option('woocommerce_paypal_title');
		$this->email 		= get_option('woocommerce_paypal_email');
		$this->description  = get_option('woocommerce_paypal_description');
		
		$this->liveurl 		= 'https://www.paypal.com/webscr';
		$this->testurl 		= 'https://www.sandbox.paypal.com/webscr';
		$this->testmode		= get_option('woocommerce_paypal_testmode');		
		
		$this->send_shipping = get_option('woocommerce_paypal_send_shipping');
		
		add_action( 'init', array(&$this, 'check_ipn_response') );
		add_action('valid-paypal-standard-ipn-request', array(&$this, 'successful_request') );
		
		add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
		add_option('woocommerce_paypal_enabled', 'yes');
		add_option('woocommerce_paypal_email', '');
		add_option('woocommerce_paypal_title', __('PayPal', 'woothemes') );
		add_option('woocommerce_paypal_description', __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'woothemes') );
		add_option('woocommerce_paypal_testmode', 'no');
		add_option('woocommerce_paypal_send_shipping', 'no');
		
		add_action('receipt_paypal', array(&$this, 'receipt_page'));
    } 
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('PayPal standard', 'woothemes'); ?></th><th scope="col" class="desc"><?php _e('PayPal standard works by sending the user to <a href="https://www.paypal.com/uk/mrb/pal=JFC9L8JJUZZK2">PayPal</a> to enter their payment information.', 'woothemes'); ?></th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable PayPal standard', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <select name="woocommerce_paypal_enabled" id="woocommerce_paypal_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('woocommerce_paypal_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'woothemes'); ?></option>
		            <option value="no" <?php if (get_option('woocommerce_paypal_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'woothemes'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.', 'woothemes') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="woocommerce_paypal_title" id="woocommerce_paypal_title" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_paypal_title')) echo $value; else echo 'PayPal'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the description which the user sees during checkout.', 'woothemes') ?>" class="tips" tabindex="99"></a><?php _e('Description', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <input class="input-text wide-input" type="text" name="woocommerce_paypal_description" id="woocommerce_paypal_description" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_paypal_description')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Please enter your PayPal email address; this is needed in order to take payment!', 'woothemes') ?>" class="tips" tabindex="99"></a><?php _e('PayPal email address', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="woocommerce_paypal_email" id="woocommerce_paypal_email" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_paypal_email')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('If your checkout page does not ask for shipping details, or if you do not want to send shipping information to PayPal, set this option to no. If you enable this option PayPal may restrict where things can be sent, and will prevent some orders going through for your protection.', 'woothemes') ?>" class="tips" tabindex="99"></a><?php _e('Send shipping details to PayPal', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <select name="woocommerce_paypal_send_shipping" id="woocommerce_paypal_send_shipping" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('woocommerce_paypal_send_shipping') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'woothemes'); ?></option>
		            <option value="no" <?php if (get_option('woocommerce_paypal_send_shipping') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'woothemes'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><?php _e('Enable PayPal sandbox', 'woothemes') ?>:</td>
	        <td class="forminp">
		        <select name="woocommerce_paypal_testmode" id="woocommerce_paypal_testmode" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('woocommerce_paypal_testmode') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'woothemes'); ?></option>
		            <option value="no" <?php if (get_option('woocommerce_paypal_testmode') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'woothemes'); ?></option>
		        </select>
	        </td>
	    </tr>
    	<?php
    }
    
    /**
	 * There are no payment fields for paypal, but we want to show the description if set.
	 **/
    function payment_fields() {
    	if ($woocommerce_paypal_description = get_option('woocommerce_paypal_description')) echo wpautop(wptexturize($woocommerce_paypal_description));
    }
    
	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 **/
    public function process_admin_options() {
   		if(isset($_POST['woocommerce_paypal_enabled'])) update_option('woocommerce_paypal_enabled', woocommerce_clean($_POST['woocommerce_paypal_enabled'])); else @delete_option('woocommerce_paypal_enabled');
   		if(isset($_POST['woocommerce_paypal_title'])) update_option('woocommerce_paypal_title', woocommerce_clean($_POST['woocommerce_paypal_title'])); else @delete_option('woocommerce_paypal_title');
   		if(isset($_POST['woocommerce_paypal_email'])) update_option('woocommerce_paypal_email', woocommerce_clean($_POST['woocommerce_paypal_email'])); else @delete_option('woocommerce_paypal_email');
   		if(isset($_POST['woocommerce_paypal_description'])) update_option('woocommerce_paypal_description', woocommerce_clean($_POST['woocommerce_paypal_description'])); else @delete_option('woocommerce_paypal_description');
   		if(isset($_POST['woocommerce_paypal_testmode'])) update_option('woocommerce_paypal_testmode', woocommerce_clean($_POST['woocommerce_paypal_testmode'])); else @delete_option('woocommerce_paypal_testmode');
   		if(isset($_POST['woocommerce_paypal_send_shipping'])) update_option('woocommerce_paypal_send_shipping', woocommerce_clean($_POST['woocommerce_paypal_send_shipping'])); else @delete_option('woocommerce_paypal_send_shipping');
    }
    
	/**
	 * Generate the paypal button link
	 **/
    public function generate_paypal_form( $order_id ) {
		
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
				'tax'					=> $order->get_total_tax(),
				'tax_cart'				=> $order->get_total_tax(),
				'amount' 				=> $order->order_total,
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
								message: "<img src=\"'.woocommerce::plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to PayPal to make payment.', 'woothemes').'", 
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
        	
        	if (self::check_ipn_request_is_valid()) :
        	
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
		
	}

}

/**
 * Add the gateway to WooCommerce
 **/
function add_paypal_gateway( $methods ) {
	$methods[] = 'woocommerce_paypal'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_paypal_gateway' );
