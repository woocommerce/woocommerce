<?php
/**
 * Moneybookers Payment Gateway
 * 
 * Provides a Moneybookers Payment Gateway.
 *
 * @class 		woocommerce_moneybookers
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_moneybookers extends woocommerce_payment_gateway {
		
	public function __construct() { 
		global $woocommerce;
		
        $this->id			= 'moneybookers';
        $this->title 		= 'Moneybookers';
        $this->icon 		= $woocommerce->plugin_url() . '/assets/images/icons/moneybookers.png';
        $this->has_fields 	= false;
      	$this->enabled		= get_option('woocommerce_moneybookers_enabled');
		$this->title 		= get_option('woocommerce_moneybookers_title');
		$this->email 		= get_option('woocommerce_moneybookers_email');
		
		add_action( 'init', array(&$this, 'check_status_response') );
		
		if(isset($_GET['moneybookersPayment']) && $_GET['moneybookersPayment'] == true):
			add_action( 'init', array(&$this, 'generate_moneybookers_form') );
		endif;
		
		add_action('valid-moneybookers-status-report', array(&$this, 'successful_request') );
		
		add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
		add_option('woocommerce_moneybookers_enabled', 'yes');
		add_option('woocommerce_moneybookers_email', '');
		add_option('woocommerce_moneybookers_title', 'moneybookers');
		
		add_action('receipt_moneybookers', array(&$this, 'receipt_moneybookers'));
    }
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
    	?>
    	<h3><?php _e('Moneybookers', 'woothemes'); ?></h3>
    	<p><?php _e('Moneybookers works by using an iFrame to submit payment information securely to Moneybookers.', 'woothemes'); ?></p>
    	<table class="form-table">
	    	<tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Enable/disable', 'woothemes') ?></th>
		        <td class="forminp">
		        	<fieldset><legend class="screen-reader-text"><span><?php _e('Enable/disable', 'woothemes') ?></span></legend>
						<label for="woocommerce_moneybookers_enabled">
						<input name="woocommerce_moneybookers_enabled" id="woocommerce_moneybookers_enabled" type="checkbox" value="1" <?php checked(get_option('woocommerce_moneybookers_enabled'), 'yes'); ?> /> <?php _e('Enable Moneybookers', 'woothemes') ?></label><br>
					</fieldset>
		        </td>
		    </tr>
		    <tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Method Title', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text" type="text" name="woocommerce_moneybookers_title" id="woocommerce_moneybookers_title" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_moneybookers_title')) echo $value; else echo 'moneybookers'; ?>" /> <span class="description"><?php _e('This controls the title which the user sees during checkout.', 'woothemes') ?></span>
		        </td>
		    </tr>
		    <tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Moneybookers merchant e-mail', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text" type="text" name="woocommerce_moneybookers_email" id="woocommerce_moneybookers_email" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_moneybookers_email')) echo $value; ?>" /> <span class="description"><?php _e('Please enter your moneybookers email address; this is needed in order to take payment!', 'woothemes') ?></span>
		        </td>
		    </tr>
		    <tr valign="top">
		    	<th scope="row" class="titledesc"><?php _e('Moneybookers Secret Word', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text" type="text" name="woocommerce_moneybookers_secret_word" id="woocommerce_moneybookers_secret_word" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_moneybookers_secret_word')) echo $value; ?>" /> <span class="description"><?php _e('Please enter your moneybookers secretword; this is needed in order to take payment!', 'woothemes') ?></span>
		        </td>
		    </tr>
		    <tr valign="top">
		    	<th scope="row" class="titledesc"><?php _e('Moneybookers Customer ID', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text" type="text" name="woocommerce_moneybookers_customer_id" id="woocommerce_moneybookers_customer_id" style="min-width:50px;" value="<?php if ($value = get_option('woocommerce_moneybookers_customer_id')) echo $value; ?>" /> <span class="description"><?php _e('Please enter your moneybookers Customer ID; this is needed in order to take payment!', 'woothemes') ?></span>
		        </td>
		    </tr>
		</table>
    	<?php
    }
    
	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 **/
    public function process_admin_options() {
   		if(isset($_POST['woocommerce_moneybookers_enabled'])) update_option('woocommerce_moneybookers_enabled', 'yes'); else update_option('woocommerce_moneybookers_enabled', 'no');
   		
   		if(isset($_POST['woocommerce_moneybookers_title'])) update_option('woocommerce_moneybookers_title', woocommerce_clean($_POST['woocommerce_moneybookers_title'])); else delete_option('woocommerce_moneybookers_title');
   		if(isset($_POST['woocommerce_moneybookers_email'])) update_option('woocommerce_moneybookers_email', woocommerce_clean($_POST['woocommerce_moneybookers_email'])); else delete_option('woocommerce_moneybookers_email');
   		if(isset($_POST['woocommerce_moneybookers_secret_word'])) update_option('woocommerce_moneybookers_secret_word', woocommerce_clean($_POST['woocommerce_moneybookers_secret_word'])); else delete_option('woocommerce_moneybookers_secret_word');
   		if(isset($_POST['woocommerce_moneybookers_customer_id'])) update_option('woocommerce_moneybookers_customer_id', woocommerce_clean($_POST['woocommerce_moneybookers_customer_id'])); else delete_option('woocommerce_moneybookers_customer_id');
    }
    
	/**
	 * Generate the moneybookers button link
	 **/
    public function generate_moneybookers_form() {
    
    	$order_id = $_GET['orderId'];
		
		$order = &new woocommerce_order( $order_id );
		
		$moneybookers_adr = 'https://www.moneybookers.com/app/payment.pl';
		
		$shipping_name = explode(' ', $order->shipping_method);
				
		$order_total = trim($order->order_total, 0);

		if( substr($order_total, -1) == '.' ) $order_total = str_replace('.', '', $order_total);
					
		$moneybookers_args = array(
			'merchant_fields' => 'partner',
			'partner' => '21890813',
			'pay_to_email' => $this->email,
			'recipient_description' => get_bloginfo('name'),
			'transaction_id' => $order_id,
			'return_url' => get_permalink(get_option('woocommerce_thanks_page_id')),
			'return_url_text' => 'Return to Merchant',
			'new_window_redirect' => 0,
			'rid' => 20521479,
			'prepare_only' => 0,
			'return_url_target' => 1,
			'cancel_url' => trailingslashit(get_bloginfo('wpurl')).'?moneybookersListener=moneybookers_cancel',
			'cancel_url_target' => 1,
			'status_url' => trailingslashit(get_bloginfo('wpurl')).'?moneybookersListener=moneybookers_status',
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
			'currency' => get_option('woocommerce_currency'),
			'detail1_description' => 'Order ID',
			'detail1_text'=> $order_id
				
		);
		
		// Cart Contents
		$item_loop = 0;
		if (sizeof($order->items)>0) : foreach ($order->items as $item) :
			$_product = &new woocommerce_product($item['id']);
			if ($_product->exists() && $item['qty']) :
				
				$item_loop++;
				
				$moneybookers_args['item_name_'.$item_loop] = $_product->get_title();
				$moneybookers_args['quantity_'.$item_loop] = $item['qty'];
				$moneybookers_args['amount_'.$item_loop] = $_product->get_price_excluding_tax();
				
			endif;
		endforeach; endif;
		
		// Shipping Cost
		$item_loop++;
		$moneybookers_args['item_name_'.$item_loop] = __('Shipping cost', 'woothemes');
		$moneybookers_args['quantity_'.$item_loop] = '1';
		$moneybookers_args['amount_'.$item_loop] = number_format($order->order_shipping, 2);
		
		$moneybookers_args_array = array();

		foreach ($moneybookers_args as $key => $value) {
			$moneybookers_args_array[] = '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
		}
		
		// Skirll MD5 concatenation
				
		$moneybookers_md = get_option('woocommerce_moneybookers_customer_id') . $moneybookers_args['transaction_id'] . strtoupper(md5(get_option('woocommerce_moneybookers_secret_word'))) . $order_total . get_option('woocommerce_currency') . '2';
		$moneybookers_md = md5($moneybookers_md);
		
		add_post_meta($order_id, '_moneybookersmd', $moneybookers_md);
		
		echo '<form name="moneybookers" id="moneybookers_place_form" action="'.$moneybookers_adr.'" method="POST">' . implode('', $moneybookers_args_array) . '</form>';
				
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
		
		$order = &new woocommerce_order( $order_id );
		
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
		);
		
	}
	
	/**
	 * receipt_page
	 **/
	function receipt_moneybookers( $order ) {
		
		echo '<p>'.__('Thank you for your order, please complete the secure (SSL) form below to pay with Moneybookers.', 'woothemes').'</p>';
						
		echo '<iframe class="moneybookers-loader" width="100%" height="700"  id="2" src ="'.home_url('?moneybookersPayment=1&orderId='.$order).'">';
		echo '<p>Your browser does not support iFrames, please contact us to place an order.</p>';
		echo '</iframe>';
		
	}
	
	/**
	 * Check Moneybookers status report validity
	 **/
	function check_status_report_is_valid() {
	
		// Get Moneybookers post data array        
        $params = $_POST;
        
        if(!isset($params['transaction_id'])) return false;
        
        $order_id = $params['transaction_id'];
        $_moneybookersmd = strtoupper(get_post_meta($order_id, '_moneybookersmd', true));
                
        // Check MD5 signiture
        if($params['md5sig'] == $_moneybookersmd) return true;
        	
		return false;
      
    }
	
	/**
	 * Check for Moneybookers Status Response
	 **/
	function check_status_response() {
			
		if (isset($_GET['moneybookersListener']) && $_GET['moneybookersListener'] == 'moneybookers_status'):
		
        	$_POST = stripslashes_deep($_POST);

        	if ($this->check_status_report_is_valid()) :
        	
            	do_action("valid-moneybookers-status-report", $_POST);
            	
       		endif;
       	
       	endif;
			
	}
	
	/**
	 * Successful Payment!
	 **/
	function successful_request( $posted ) {
		
		// Custom holds post ID
	    if ( !empty($posted['mb_transaction_id']) ) {
			
			$order = new woocommerce_order( (int) $posted['transaction_id'] );
				
			if ($order->status !== 'completed') :
		        // We are here so lets check status and do actions
		        switch ($posted['status']) :
		            case '2' : // Processed		                
		                $order->add_order_note( __('Moneybookers payment completed', 'woothemes') );
		                $order->payment_complete();
		            break;
		            case '0' : // Pending
		            case '-2' : // Failed
		                $order->update_status('on-hold', sprintf(__('Moneybookers payment failed (%s)', 'woothemes'), strtolower(sanitize($posted['status'])) ) );
		            break;
		            case '-1' : // Cancelled
		            	$order->update_status('cancelled', __('Moneybookers payment cancelled', 'woothemes'));
		            break;
		            default:
		            	$order->update_status('cancelled', __('Moneybookers exception', 'woothemes'));
		            break;
		        endswitch;
			endif;
			
			exit;
			
	    }
		
	}

}

/** Add the gateway to WooCommerce **/

function add_moneybookers_gateway( $methods ) {
	$methods[] = 'woocommerce_moneybookers'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_moneybookers_gateway' );
