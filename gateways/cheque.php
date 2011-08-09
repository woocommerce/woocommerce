<?php
/*
Provides a Cheque Payment Gateway for testing purposes.
Created by Andrew Benbow (andrew@chromeorange.co.uk)
*/
class jigoshop_cheque extends jigoshop_payment_gateway {
		
	public function __construct() { 
        $this->id				= 'cheque';
        $this->icon 			= '';
        $this->has_fields 		= false;
		
		$this->enabled			= get_option('jigoshop_cheque_enabled');
		$this->title 			= get_option('jigoshop_cheque_title');
		$this->description 		= get_option('jigoshop_cheque_description');

		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		add_option('jigoshop_cheque_enabled', 'yes');
		add_option('jigoshop_cheque_title', __('Cheque Payment', 'jigoshop') );
		add_option('jigoshop_cheque_description', __('Please send your cheque to Store Name, Store Street, Store Town, Store State / County, Store Postcode.', 'jigoshop'));
    
    	add_action('thankyou_cheque', array(&$this, 'thankyou_page'));
    } 
    
	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 **/
	public function admin_options() {
    	?>
    	<thead><tr><th scope="col" width="200px"><?php _e('Cheque Payment', 'jigoshop'); ?></th><th scope="col" class="desc"><?php _e('Allows cheque payments. Why would you take cheques in this day and age? Well you probably wouldn\'t but it does allow you to make test purchases without having to use the sandbox area of a payment gateway which is useful for demonstrating to clients and for testing order emails and the \'success\' pages etc.', 'jigoshop'); ?></th></tr></thead>
    	<tr>
	        <td class="titledesc"><?php _e('Enable Cheque Payment', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <select name="jigoshop_cheque_enabled" id="jigoshop_cheque_enabled" style="min-width:100px;">
		            <option value="yes" <?php if (get_option('jigoshop_cheque_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop'); ?></option>
		            <option value="no" <?php if (get_option('jigoshop_cheque_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop'); ?></option>
		        </select>
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text" type="text" name="jigoshop_cheque_title" id="jigoshop_cheque_title" value="<?php if ($value = get_option('jigoshop_cheque_title')) echo $value; else echo 'Cheque Payment'; ?>" />
	        </td>
	    </tr>
	    <tr>
	        <td class="titledesc"><a href="#" tip="<?php _e('Let the customer know the payee and where they should be sending the cheque too and that their order won\'t be shipping until you receive it.','jigoshop') ?>" class="tips" tabindex="99"></a><?php _e('Customer Message', 'jigoshop') ?>:</td>
	        <td class="forminp">
		        <input class="input-text wide-input" type="text" name="jigoshop_cheque_description" id="jigoshop_cheque_description" value="<?php if ($value = get_option('jigoshop_cheque_description')) echo $value; ?>" />
	        </td>
	    </tr>

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
   		if(isset($_POST['jigoshop_cheque_enabled'])) 	update_option('jigoshop_cheque_enabled', 	jigowatt_clean($_POST['jigoshop_cheque_enabled'])); else @delete_option('jigoshop_cheque_enabled');
   		if(isset($_POST['jigoshop_cheque_title'])) 	update_option('jigoshop_cheque_title', 	jigowatt_clean($_POST['jigoshop_cheque_title'])); else @delete_option('jigoshop_cheque_title');
   		if(isset($_POST['jigoshop_cheque_description'])) 	update_option('jigoshop_cheque_description', 	jigowatt_clean($_POST['jigoshop_cheque_description'])); else @delete_option('jigoshop_cheque_description');
    }
	
	/**
	 * Process the payment and return the result
	 **/
	function process_payment( $order_id ) {
		
		$order = &new jigoshop_order( $order_id );
		
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __('Awaiting cheque payment', 'jigoshop'));
		
		// Remove cart
		jigoshop_cart::empty_cart();
			
		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('jigoshop_thanks_page_id'))))
		);
		
	}
	
}

/**
 * Add the gateway to JigoShop
 **/
function add_cheque_gateway( $methods ) {
	$methods[] = 'jigoshop_cheque'; return $methods;
}

add_filter('jigoshop_payment_gateways', 'add_cheque_gateway' );
