<?php
/**
 * Cheque Payment Gateway
 * 
 * Provides a Cheque Payment Gateway for testing purposes. Created by Andrew Benbow (andrew@chromeorange.co.uk)
 *
 * @class 		woocommerce_cheque
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_cheque extends woocommerce_payment_gateway {
		
	public function __construct() { 
        $this->id				= 'cheque';
        $this->icon 			= '';
        $this->has_fields 		= false;
		
		$this->enabled			= get_option('woocommerce_cheque_enabled');
		$this->title 			= get_option('woocommerce_cheque_title');
		$this->description 		= get_option('woocommerce_cheque_description');

		add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
		add_option('woocommerce_cheque_enabled', 'yes');
		add_option('woocommerce_cheque_title', __('Cheque Payment', 'woothemes') );
		add_option('woocommerce_cheque_description', __('Please send your cheque to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'woothemes'));
    
    	add_action('thankyou_cheque', array(&$this, 'thankyou_page'));
    } 
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
    	?>
    	<h3><?php _e('Cheque Payment', 'woothemes'); ?></h3>
    	<p><?php _e('Allows cheque payments. Why would you take cheques in this day and age? Well you probably wouldn\'t but it does allow you to make test purchases without having to use the sandbox area of a payment gateway which is useful for demonstrating to clients and for testing order emails and the \'success\' pages etc.', 'woothemes'); ?></p>
    	<table class="form-table">
	    	<tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Enable/disable', 'woothemes') ?></th>
		        <td class="forminp">
		        	<fieldset><legend class="screen-reader-text"><span><?php _e('Enable/disable', 'woothemes') ?></span></legend>
						<label for="woocommerce_cheque_enabled">
						<input name="woocommerce_cheque_enabled" id="woocommerce_cheque_enabled" type="checkbox" value="1" <?php checked(get_option('woocommerce_cheque_enabled'), 'yes'); ?> /> <?php _e('Enable Cheque Payment', 'woothemes') ?></label><br>
					</fieldset>
		        </td>
		    </tr>
		    <tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Method Title', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text" type="text" name="woocommerce_cheque_title" id="woocommerce_cheque_title" value="<?php if ($value = get_option('woocommerce_cheque_title')) echo $value; else echo 'Cheque Payment'; ?>" /> <span class="description"><?php _e('This controls the title which the user sees during checkout.', 'woothemes') ?></span>
		        </td>
		    </tr>
		    <tr valign="top">
		        <th scope="row" class="titledesc"><?php _e('Customer Message', 'woothemes') ?></th>
		        <td class="forminp">
			        <input class="input-text wide-input" type="text" name="woocommerce_cheque_description" id="woocommerce_cheque_description" value="<?php if ($value = get_option('woocommerce_cheque_description')) echo $value; ?>" /> <span class="description"><?php _e('Let the customer know the payee and where they should be sending the cheque too and that their order won\'t be shipping until you receive it.', 'woothemes') ?></span>
		        </td>
		    </tr>
		</table>
    	<?php
    }
    
	/**
	* There are no payment fields for cheques, but we want to show the description if set.
	**/
	function payment_fields() {
		if ($this->description) echo wpautop(wptexturize($this->description));
	}
	
	function thankyou_page() {
		if ($this->description) echo wpautop(wptexturize($this->description));
	}
    
	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 **/
    public function process_admin_options() {
    
   		if(isset($_POST['woocommerce_cheque_enabled'])) update_option('woocommerce_cheque_enabled', 'yes'); else update_option('woocommerce_cheque_enabled', 'no');
   		
   		if(isset($_POST['woocommerce_cheque_title'])) 	update_option('woocommerce_cheque_title', 	woocommerce_clean($_POST['woocommerce_cheque_title'])); else delete_option('woocommerce_cheque_title');
   		if(isset($_POST['woocommerce_cheque_description'])) 	update_option('woocommerce_cheque_description', 	woocommerce_clean($_POST['woocommerce_cheque_description'])); else delete_option('woocommerce_cheque_description');
    }
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		
		$order = &new woocommerce_order( $order_id );
		
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __('Awaiting cheque payment', 'woothemes'));
		
		// Remove cart
		woocommerce_cart::empty_cart();
		
		// Empty awaiting payment session
		unset($_SESSION['order_awaiting_payment']);
			
		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
		);
		
	}
	
}

/**
 * Add the gateway to WooCommerce
 **/
function add_cheque_gateway( $methods ) {
	$methods[] = 'woocommerce_cheque'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_cheque_gateway' );
