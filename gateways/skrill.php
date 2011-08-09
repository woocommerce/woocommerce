<?php

/** Skrill Gateway **/

class skrill extends jigoshop_payment_gateway {
		
	public function __construct() { 
        $this->id			= 'skrill';
        $this->title 		= 'Skrill';
        $this->icon 		= jigoshop::plugin_url() . '/assets/images/icons/skrill.png';
        $this->has_fields 	= false;
      	$this->enabled		= get_option('jigoshop_skrill_enabled');
		$this->title 		= get_option('jigoshop_skrill_title');
		$this->email 		= get_option('jigoshop_skrill_email');
		
		add_action( 'init', array(&$this, 'check_status_response') );
		
		if(isset($_GET['skrillPayment']) && $_GET['skrillPayment'] == true):
			add_action( 'init', array(&$this, 'generate_skrill_form') );
		endif;
		
		add_action('valid-skrill-status-report', array(&$this, 'successful_request') );
		
		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		add_option('jigoshop_skrill_enabled', 'yes');
		add_option('jigoshop_skrill_email', '');
		add_option('jigoshop_skrill_title', 'skrill');
		
		add_action('receipt_skrill', array(&$this, 'receipt_skrill'));
    }
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('Skrill (Moneybookers)', 'jigoshop'); ?></th><th scope="col" class="desc"><?php _e('Skrill works by using an iFrame to submit payment information securely to Moneybookers.', 'jigoshop'); ?></th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable Skrill', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_skrill_enabled" id="jigoshop_skrill_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('jigoshop_skrill_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (get_option('jigoshop_skrill_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_skrill_title" id="jigoshop_skrill_title" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_skrill_title')) echo $value; else echo 'skrill'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Please enter your skrill email address; this is needed in order to take payment!','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Skrill merchant e-mail', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_skrill_email" id="jigoshop_skrill_email" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_skrill_email')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	    	<td class="titledesc"><a href="#" tip="<?php _e('Please enter your skrill secretword; this is needed in order to take payment!','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Skrill Secret Word', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_skrill_secret_word" id="jigoshop_skrill_secret_word" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_skrill_secret_word')) echo $value; ?>" />
	        </td>
	    </tr>
	    <tr>
	    	<td class="titledesc"><a href="#" tip="<?php _e('Please enter your skrill Customer ID; this is needed in order to take payment!','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Skrill Customer ID', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_skrill_customer_id" id="jigoshop_skrill_customer_id" style="min-width:50px;" value="<?php if ($value = get_option('jigoshop_skrill_customer_id')) echo $value; ?>" />
	        </td>
	    </tr>
    	<?php
    }
    
	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 **/
    public function process_admin_options() {
   		if(isset($_POST['jigoshop_skrill_enabled'])) update_option('jigoshop_skrill_enabled', jigowatt_clean($_POST['jigoshop_skrill_enabled'])); else @delete_option('jigoshop_skrill_enabled');
   		if(isset($_POST['jigoshop_skrill_title'])) update_option('jigoshop_skrill_title', jigowatt_clean($_POST['jigoshop_skrill_title'])); else @delete_option('jigoshop_skrill_title');
   		if(isset($_POST['jigoshop_skrill_email'])) update_option('jigoshop_skrill_email', jigowatt_clean($_POST['jigoshop_skrill_email'])); else @delete_option('jigoshop_skrill_email');
   		if(isset($_POST['jigoshop_skrill_secret_word'])) update_option('jigoshop_skrill_secret_word', jigowatt_clean($_POST['jigoshop_skrill_secret_word'])); else @delete_option('jigoshop_skrill_secret_word');
   		if(isset($_POST['jigoshop_skrill_customer_id'])) update_option('jigoshop_skrill_customer_id', jigowatt_clean($_POST['jigoshop_skrill_customer_id'])); else @delete_option('jigoshop_skrill_customer_id');
    }
    
	/**
	 * Generate the skrill button link
	 **/
    public function generate_skrill_form() {
    
    	$order_id = $_GET['orderId'];
		
		$order = &new jigoshop_order( $order_id );
		
		$skrill_adr = 'https://www.moneybookers.com/app/payment.pl';
		
		$shipping_name = explode(' ', $order->shipping_method);
				
		$order_total = trim($order->order_total, 0);

		if( substr($order_total, -1) == '.' ) $order_total = str_replace('.', '', $order_total);
					
		$skrill_args = array(
			'merchant_fields' => 'partner',
			'partner' => '21890813',
			'pay_to_email' => $this->email,
			'recipient_description' => get_bloginfo('name'),
			'transaction_id' => $order_id,
			'return_url' => get_permalink(get_option('jigoshop_thanks_page_id')),
			'return_url_text' => 'Return to Merchant',
			'new_window_redirect' => 0,
			'rid' => 20521479,
			'prepare_only' => 0,
			'return_url_target' => 1,
			'cancel_url' => trailingslashit(get_bloginfo('wpurl')).'?skrillListener=skrill_cancel',
			'cancel_url_target' => 1,
			'status_url' => trailingslashit(get_bloginfo('wpurl')).'?skrillListener=skrill_status',
			'dynamic_descriptor' => 'Description',
			'language' => 'EN',
			'hide_login' => 1,
			'confirmation_note' => 'Thank you for your custom',
			'pay_from_email' => $order->billing_email,
						
			//'title' => 'Mr',
			'firstname' => $order->billing_first_name,
			'lastname' => $order->billing_last_name,
			'address' => $order->billing_address_1,
			'address2' => $order->billing_address_2,
			'phone_number' => $order->billing_phone,
			'postal_code' => $order->billing_postcode,
			'city' => $order->billing_city,
			'state' => $order->billing_state,
			'country' => 'GBR',

			'amount' => $order_total,
			'currency' => get_option('jigoshop_currency'),
			'detail1_description' => 'Order ID',
			'detail1_text'=> $order_id
				
		);
		
		// Cart Contents
		$item_loop = 0;
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :
			$_product = &new jigoshop_product($item['id']);
			if ($_product->exists() && $item['qty']) :
				
				$item_loop++;
				
				$skrill_args['item_name_'.$item_loop] = $_product->get_title();
				$skrill_args['quantity_'.$item_loop] = $item['qty'];
				$skrill_args['amount_'.$item_loop] = $_product->get_price_excluding_tax();
				
			endif;
		endforeach; endif;
		
		// Shipping Cost
		$item_loop++;
		$skrill_args['item_name_'.$item_loop] = __('Shipping cost', 'jigoshop');
		$skrill_args['quantity_'.$item_loop] = '1';
		$skrill_args['amount_'.$item_loop] = number_format($order->order_shipping, 2);
		
		$skrill_args_array = array();

		foreach ($skrill_args as $key => $value) {
			$skrill_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		
		// Skirll MD5 concatenation
				
		$skrill_md = get_option('jigoshop_skrill_customer_id') . $skrill_args['transaction_id'] . strtoupper(md5(get_option('jigoshop_skrill_secret_word'))) . $order_total . get_option('jigoshop_currency') . '2';
		$skrill_md = md5($skrill_md);
		
		add_post_meta($order_id, '_skrillmd', $skrill_md);
		
		echo '<form name="moneybookers" id="moneybookers_place_form" action="'.$skrill_adr.'" method="POST">' . implode('', $skrill_args_array) . '</form>';
				
		echo '<script type="text/javascript">
		//<![CDATA[
    	var paymentform = document.getElementById(\'moneybookers_place_form\');
   		window.onload = paymentform.submit();
		//]]>
		</script>';

		exit();
		
	}
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		
		$order = &new jigoshop_order( $order_id );
		
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('jigoshop_pay_page_id'))))
		);
		
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_skrill( $order ) {
		
		echo '<p>'.__('Thank you for your order, please complete the secure (SSL) form below to pay with Skrill.', 'jigoshop').'</p>';
						
		echo '<iframe class="skrill-loader" width="100%" height="700"  id="2" src ="'.home_url('?skrillPayment=1&orderId='.$order).'">';
		echo '<p>Your browser does not support iFrames, please contact us to place an order.</p>';
		echo '</iframe>';
		
	}
	
	/**
	 * Check Skrill status report validity
	 **/
	function check_status_report_is_valid() {
	
		// Get Skrill post data array        
        $params = $_POST;
        
        if(!isset($params['transaction_id'])) return false;
        
        $order_id = $params['transaction_id'];
        $_skrillmd = strtoupper(get_post_meta($order_id, '_skrillmd', true));
                
        // Check MD5 signiture
        if($params['md5sig'] == $_skrillmd) return true;
        	
		return false;
      
    }
	
	/**
	 * Check for Skrill Status Response
	 **/
	function check_status_response() {
			
		if (isset($_GET['skrillListener']) && $_GET['skrillListener'] == 'skrill_status'):
		
        	$_POST = stripslashes_deep($_POST);

        	if (self::check_status_report_is_valid()) :
        	
            	do_action("valid-skrill-status-report", $_POST);
            	
       		endif;
       	
       	endif;
			
	}
	
	/**
	 * Successful Payment!
	 **/
	function successful_request( $posted ) {
		
		// Custom holds post ID
	    if ( !empty($posted['mb_transaction_id']) ) {
			
			$order = new jigoshop_order( (int) $posted['transaction_id'] );
				
			if ($order->status !== 'completed') :
		        // We are here so lets check status and do actions
		        switch ($posted['status']) :
		            case '2' : // Processed		                
		                $order->add_order_note( __('Skrill payment completed', 'jigoshop') );
		                $order->payment_complete();
		            break;
		            case '0' : // Pending
		            case '-2' : // Failed
		                $order->update_status('on-hold', sprintf(__('Skrill payment failed (%s)', 'jigoshop'), strtolower(sanitize($posted['status'])) ) );
		            break;
		            case '-1' : // Cancelled
		            	$order->update_status('cancelled', __('Skrill payment cancelled', 'jigoshop'));
		            break;
		            default:
		            	$order->update_status('cancelled', __('Skrill exception', 'jigoshop'));
		            break;
		        endswitch;
			endif;
			
			exit;
			
	    }
		
	}

}

/** Add the gateway to JigoShop **/

function add_skrill_gateway( $methods ) {
	$methods[] = 'skrill'; return $methods;
}

add_filter('jigoshop_payment_gateways', 'add_skrill_gateway' );
