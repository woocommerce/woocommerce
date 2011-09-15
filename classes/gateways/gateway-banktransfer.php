<?php
/**
 * Bank Transfer Payment Gateway
 * 
 * Provides a Bank Transfer Payment Gateway. Adapted from code by Mike Pepper (https://github.com/takeover/woocommerce-bacs-gateway - mike@takeovermedia.co.uk)
 *
 * @class 		woocommerce_bacs
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_bacs extends woocommerce_payment_gateway {

    public function __construct() { 
		$this->id				  = 'bacs';
		$this->icon 			= '';
		$this->has_fields = false;
		
		$this->enabled			    = get_option('woocommerce_bacs_enabled');
		$this->title 			      = get_option('woocommerce_bacs_title');
		$this->description      = get_option('woocommerce_bacs_description');
		$this->account_name     = get_option('woocommerce_bacs_account_name');
		$this->account_number   = get_option('woocommerce_bacs_account_number');
		$this->sort_code        = get_option('woocommerce_bacs_sort_code');
		$this->bank_name        = get_option('woocommerce_bacs_bank_name');
		$this->iban             = get_option('woocommerce_bacs_iban');
		$this->bic              = get_option('woocommerce_bacs_bic');    
		
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		add_option('woocommerce_bacs_enabled', 'no');
		add_option('woocommerce_bacs_title', __('Direct Bank Transer', 'woothemes'));
		add_option('woocommerce_bacs_description', __('Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order wont be shipped until the funds have cleared in our account.', 'woothemes'));
		add_action('thankyou_bacs', array(&$this, 'thankyou_page'));
    } 

    public function is_available(){
		return ($this->enabled == 'yes') ? true : false;
    }

    public function set_current(){ true; }
    public function icon(){}
    public function validate_fields(){ true; }

    /**
    * Admin Panel Options 
    **/
    public function admin_options() { ?>
      <h3><?php _e('BACS Payment', 'woothemes'); ?></h3>
      <p><?php _e('Allows payments by BACS (Bank Account Clearing System), more commonly known as direct bank/wire transfer.', 'woothemes'); ?></p>
      <table class="form-table">
	      <tr valign="top">
	        <th scope="row" class="titledesc"><?php _e('Enable/disable', 'woothemes') ?></th>
	        <td class="forminp">
	        	<fieldset><legend class="screen-reader-text"><span><?php _e('Enable/disable', 'woothemes') ?></span></legend>
					<label for="woocommerce_bacs_enabled">
					<input name="woocommerce_bacs_enabled" id="woocommerce_bacs_enabled" type="checkbox" value="1" <?php checked(get_option('woocommerce_bacs_enabled'), 'yes'); ?> /> <?php _e('Enable BACS payment', 'woothemes') ?></label><br>
				</fieldset>
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><?php _e('Method Title', 'woocommerce') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_title" id="woocommerce_bacs_title" value="<?php if ($value = get_option('woocommerce_bacs_title')) echo $value; else echo 'BACS Payment'; ?>" /> <span class="description"><?php _e('This controls the title which the user sees during checkout.','woothemes') ?></span>
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><?php _e('Customer Message', 'woocommerce') ?></th>
	        <td class="forminp">
	          <input class="input-text wide-input" type="text" name="woocommerce_bacs_description" id="woocommerce_bacs_description" value="<?php if ($value = get_option('woocommerce_bacs_description')) echo $value; ?>" /> <span class="description"><?php _e('Give the customer instructions for paying via BACS, and let them know that their order won\'t be shipping until the money is received.','woothemes') ?></span>
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><a href="#" tabindex="99"></a><?php _e('Account Name', 'woothemes') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_account_name" id="woocommerce_bacs_account_name" value="<?php if ($value = get_option('woocommerce_bacs_account_name')) echo $value; ?>" />
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><a href="#" tabindex="99"></a><?php _e('Account Number', 'woothemes') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_account_number" id="woocommerce_bacs_account_number" value="<?php if ($value = get_option('woocommerce_bacs_account_number')) echo $value; ?>" />
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><a href="#" tabindex="99"></a><?php _e('Sort Code', 'woothemes') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_sort_code" id="woocommerce_bacs_sort_code" value="<?php if ($value = get_option('woocommerce_bacs_sort_code')) echo $value; ?>" />
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><a href="#" tabindex="99"></a><?php _e('Bank Name', 'woothemes') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_bank_name" id="woocommerce_bacs_bank_name" value="<?php if ($value = get_option('woocommerce_bacs_bank_name')) echo $value; ?>" />
	        </td>
	      </tr>      
	      <tr valign="top">
	        <th scope="row" class="titledesc"><?php _e('IBAN', 'woothemes') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_iban" id="woocommerce_bacs_iban" value="<?php if ($value = get_option('woocommerce_bacs_iban')) echo $value; ?>" /> <span class="description"><?php _e('Your bank may require this for international payments','woothemes') ?></span>
	        </td>
	      </tr>
	      <tr valign="top">
	        <th scope="row" class="titledesc"><?php _e('BIC (formerly \'Swift\')', 'woocommerce') ?></th>
	        <td class="forminp">
	          <input class="input-text" type="text" name="woocommerce_bacs_bic" id="woocommerce_bacs_bic" value="<?php if ($value = get_option('woocommerce_bacs_bic')) echo $value; ?>" /> <span class="description"><?php _e('Your bank may require this for international payments','woothemes') ?></span>
	        </td>
	      </tr>
		</table>
    <?php }

    /**
    * There are no payment fields for bacs, but we want to show the description if set.
    **/
    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    function thankyou_page() {
      if ($this->description) echo wpautop(wptexturize($this->description));
      ?><h2><?php _e('Our Details', 'woothemes') ?></h2><ul class="order_details bacs_details"><?php
      if ($this->account_name) { ?>
        <li class="account_name">
          <?php _e('Account Name', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->account_name) ?></strong>
        </li>
      <?php }
      if ($this->account_number) { ?>
        <li class="account_number">
          <?php _e('Account Number', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->account_number) ?></strong>
        </li>
      <?php }
      if ($this->sort_code) { ?>
        <li class="sort_code">
          <?php _e('Sort Code', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->sort_code) ?></strong>
        </li>
      <?php }
      if ($this->bank_name) { ?>
        <li class="bank_name">
          <?php _e('Bank Name', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->bank_name) ?></strong>
        </li>
      <?php }
      if ($this->iban) { ?>
        <li class="iban">
          <?php _e('IBAN', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->iban) ?></strong>
        </li>
      <?php }
      if ($this->bic) { ?>
        <li class="bic">
          <?php _e('BIC', 'woothemes') ?>:
          <strong><?php echo wptexturize($this->bic) ?></strong>
        </li>
      <?php }
      echo "</ul>";
    }

    /**
    * Admin Panel Options Processing
    * - Saves the options to the DB
    **/
    public function process_admin_options() {
    
      if(isset($_POST['woocommerce_bacs_enabled'])) update_option('woocommerce_bacs_enabled', 'yes'); else update_option('woocommerce_bacs_enabled', 'no');
      
      if(isset($_POST['woocommerce_bacs_title'])) 	        update_option('woocommerce_bacs_title', 	        woocommerce_clean($_POST['woocommerce_bacs_title']));           else delete_option('woocommerce_bacs_title');
      if(isset($_POST['woocommerce_bacs_description']))    update_option('woocommerce_bacs_description',  	woocommerce_clean($_POST['woocommerce_bacs_description']));     else delete_option('woocommerce_bacs_description');
      if(isset($_POST['woocommerce_bacs_account_name']))   update_option('woocommerce_bacs_account_name',   woocommerce_clean($_POST['woocommerce_bacs_account_name']));    else delete_option('woocommerce_bacs_account_name');
      if(isset($_POST['woocommerce_bacs_account_number'])) update_option('woocommerce_bacs_account_number', woocommerce_clean($_POST['woocommerce_bacs_account_number']));  else delete_option('woocommerce_bacs_account_number');
      if(isset($_POST['woocommerce_bacs_sort_code']))      update_option('woocommerce_bacs_sort_code',      woocommerce_clean($_POST['woocommerce_bacs_sort_code']));       else delete_option('woocommerce_bacs_sort_code');
      if(isset($_POST['woocommerce_bacs_bank_name']))      update_option('woocommerce_bacs_bank_name',      woocommerce_clean($_POST['woocommerce_bacs_bank_name']));       else delete_option('woocommerce_bacs_bank_name');
      if(isset($_POST['woocommerce_bacs_iban']))           update_option('woocommerce_bacs_iban',           woocommerce_clean($_POST['woocommerce_bacs_iban']));            else delete_option('woocommerce_bacs_iban');
      if(isset($_POST['woocommerce_bacs_bic']))            update_option('woocommerce_bacs_bic',            woocommerce_clean($_POST['woocommerce_bacs_bic']));             else delete_option('woocommerce_bacs_bic');
    }

    /**
    * Process the payment and return the result
    **/
    function process_payment( $order_id ) {
    	global $woocommerce;
    	
		$order = &new woocommerce_order( $order_id );
		
		// Mark as on-hold (we're awaiting the payment)
		$order->update_status('on-hold', __('Awaiting BACS payment', 'woothemes'));
		
		// Remove cart
		$woocommerce->cart->empty_cart();
		
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
function add_bacs_gateway( $methods ) {
	$methods[] = 'woocommerce_bacs'; return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_bacs_gateway' );
