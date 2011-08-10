<?php
/**
 * Bank Transfer Payment Gateway
 * 
 * Provides a Bank Transfer Payment Gateway. Adapted from code by Mike Pepper (https://github.com/takeover/jigoshop-bacs-gateway - mike@takeovermedia.co.uk)
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
		
		$this->enabled			    = get_option('jigoshop_bacs_enabled');
		$this->title 			      = get_option('jigoshop_bacs_title');
		$this->description      = get_option('jigoshop_bacs_description');
		$this->account_name     = get_option('jigoshop_bacs_account_name');
		$this->account_number   = get_option('jigoshop_bacs_account_number');
		$this->sort_code        = get_option('jigoshop_bacs_sort_code');
		$this->bank_name        = get_option('jigoshop_bacs_bank_name');
		$this->iban             = get_option('jigoshop_bacs_iban');
		$this->bic              = get_option('jigoshop_bacs_bic');    
		
		add_action('jigoshop_update_options', array(&$this, 'process_admin_options'));
		add_option('jigoshop_bacs_enabled', 'no');
		add_option('jigoshop_bacs_title', __('Direct Bank Transer', 'jigoshop-bacs-gateway'));
		add_option('jigoshop_bacs_description', __('Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order wont be shipped until the funds have cleared in our account.', 'jigoshop-bacs-gateway'));
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
      <thead>
        <tr>
          <th scope="col" width="200px"><?php _e('BACS Payment', 'jigoshop-bacs-gateway'); ?></th>
          <th scope="col" class="desc"><?php _e('Allows payments by BACS (Bank Account Clearing System), more commonly known as direct bank/wire transfer.', 'jigoshop-bacs-gateway'); ?></th>
        </tr>
      </thead>
      <tr>
        <td class="titledesc"><?php _e('Enable BACS Payment', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <select name="jigoshop_bacs_enabled" id="jigoshop_bacs_enabled" style="min-width:100px;">
            <option value="yes" <?php if (get_option('jigoshop_bacs_enabled') == 'yes') echo 'selected="selected"'; ?>><?php _e('Yes', 'jigoshop-bacs-gateway'); ?></option>
            <option value="no" <?php if (get_option('jigoshop_bacs_enabled') == 'no') echo 'selected="selected"'; ?>><?php _e('No', 'jigoshop-bacs-gateway'); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tip="<?php _e('This controls the title which the user sees during checkout.','jigoshop-bacs-gateway') ?>" class="tips" tabindex="99"></a><?php _e('Method Title', 'jigoshop') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_title" id="jigoshop_bacs_title" value="<?php if ($value = get_option('jigoshop_bacs_title')) echo $value; else echo 'BACS Payment'; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tip="<?php _e('Give the customer instructions for paying via BACS, and let them know that their order won\'t be shipping until the money is received.','jigoshop-bacs-gateway') ?>" class="tips" tabindex="99"></a><?php _e('Customer Message', 'jigoshop') ?>:</td>
        <td class="forminp">
          <input class="input-text wide-input" type="text" name="jigoshop_bacs_description" id="jigoshop_bacs_description" value="<?php if ($value = get_option('jigoshop_bacs_description')) echo $value; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tabindex="99"></a><?php _e('Account Name', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_account_name" id="jigoshop_bacs_account_name" value="<?php if ($value = get_option('jigoshop_bacs_account_name')) echo $value; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tabindex="99"></a><?php _e('Account Number', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_account_number" id="jigoshop_bacs_account_number" value="<?php if ($value = get_option('jigoshop_bacs_account_number')) echo $value; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tabindex="99"></a><?php _e('Sort Code', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_sort_code" id="jigoshop_bacs_sort_code" value="<?php if ($value = get_option('jigoshop_bacs_sort_code')) echo $value; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tabindex="99"></a><?php _e('Bank Name', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_bank_name" id="jigoshop_bacs_bank_name" value="<?php if ($value = get_option('jigoshop_bacs_bank_name')) echo $value; ?>" />
        </td>
      </tr>      
      <tr>
        <td class="titledesc"><a href="#" tip="<?php _e('Your bank may require this for international payments','jigoshop-bacs-gateway') ?>" class="tips" tabindex="99"></a><?php _e('IBAN', 'jigoshop-bacs-gateway') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_iban" id="jigoshop_bacs_iban" value="<?php if ($value = get_option('jigoshop_bacs_iban')) echo $value; ?>" />
        </td>
      </tr>
      <tr>
        <td class="titledesc"><a href="#" tip="<?php _e('Your bank may require this for international payments','jigoshop-bacs-gateway') ?>" class="tips" tabindex="99"></a><?php _e('BIC (formerly \'Swift\')', 'jigoshop') ?>:</td>
        <td class="forminp">
          <input class="input-text" type="text" name="jigoshop_bacs_bic" id="jigoshop_bacs_bic" value="<?php if ($value = get_option('jigoshop_bacs_bic')) echo $value; ?>" />
        </td>
      </tr>
    <?php }

    /**
    * There are no payment fields for bacs, but we want to show the description if set.
    **/
    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    function thankyou_page() {
      if ($this->description) echo wpautop(wptexturize($this->description));
      ?><h2><?php _e('Our Details', 'jigoshop-bacs-gateway') ?></h2><ul class="order_details bacs_details"><?php
      if ($this->account_name) { ?>
        <li class="account_name">
          <?php _e('Account Name', 'jigoshop-bacs-gateway') ?>:
          <strong><?php echo wptexturize($this->account_name) ?></strong>
        </li>
      <?php }
      if ($this->account_number) { ?>
        <li class="account_number">
          <?php _e('Account Number', 'jigoshop-bacs-gateway') ?>:
          <strong><?php echo wptexturize($this->account_number) ?></strong>
        </li>
      <?php }
      if ($this->sort_code) { ?>
        <li class="sort_code">
          <?php _e('Sort Code', 'jigoshop-bacs-gateway') ?>:
          <strong><?php echo wptexturize($this->sort_code) ?></strong>
        </li>
      <?php }
      if ($this->bank_name) { ?>
        <li class="bank_name">
          <?php _e('Bank Name', 'jigoshop-bacs-gateway') ?>:
          <strong><?php echo wptexturize($this->bank_name) ?></strong>
        </li>
      <?php }
      if ($this->iban) { ?>
        <li class="iban">
          <?php _e('IBAN', 'jigoshop-bacs-gateway') ?>:
          <strong><?php echo wptexturize($this->iban) ?></strong>
        </li>
      <?php }
      if ($this->bic) { ?>
        <li class="bic">
          <?php _e('BIC', 'jigoshop-bacs-gateway') ?>:
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
      if(isset($_POST['jigoshop_bacs_enabled'])) 	      update_option('jigoshop_bacs_enabled', 	      jigowatt_clean($_POST['jigoshop_bacs_enabled']));         else @delete_option('jigoshop_bacs_enabled');
      if(isset($_POST['jigoshop_bacs_title'])) 	        update_option('jigoshop_bacs_title', 	        jigowatt_clean($_POST['jigoshop_bacs_title']));           else @delete_option('jigoshop_bacs_title');
      if(isset($_POST['jigoshop_bacs_description']))    update_option('jigoshop_bacs_description',  	jigowatt_clean($_POST['jigoshop_bacs_description']));     else @delete_option('jigoshop_bacs_description');
      if(isset($_POST['jigoshop_bacs_account_name']))   update_option('jigoshop_bacs_account_name',   jigowatt_clean($_POST['jigoshop_bacs_account_name']));    else @delete_option('jigoshop_bacs_account_name');
      if(isset($_POST['jigoshop_bacs_account_number'])) update_option('jigoshop_bacs_account_number', jigowatt_clean($_POST['jigoshop_bacs_account_number']));  else @delete_option('jigoshop_bacs_account_number');
      if(isset($_POST['jigoshop_bacs_sort_code']))      update_option('jigoshop_bacs_sort_code',      jigowatt_clean($_POST['jigoshop_bacs_sort_code']));       else @delete_option('jigoshop_bacs_sort_code');
      if(isset($_POST['jigoshop_bacs_bank_name']))      update_option('jigoshop_bacs_bank_name',      jigowatt_clean($_POST['jigoshop_bacs_bank_name']));       else @delete_option('jigoshop_bacs_bank_name');
      if(isset($_POST['jigoshop_bacs_iban']))           update_option('jigoshop_bacs_iban',           jigowatt_clean($_POST['jigoshop_bacs_iban']));            else @delete_option('jigoshop_bacs_iban');
      if(isset($_POST['jigoshop_bacs_bic']))            update_option('jigoshop_bacs_bic',            jigowatt_clean($_POST['jigoshop_bacs_bic']));             else @delete_option('jigoshop_bacs_bic');
    }

    /**
    * Process the payment and return the result
    **/
    function process_payment( $order_id ) {
      $order = &new jigoshop_order( $order_id );

      // Mark as on-hold (we're awaiting the payment)
      $order->update_status('on-hold', __('Awaiting BACS payment', 'jigoshop-bacs-gateway'));

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
 * Add the gateway to WooCommerce
 **/
function add_bacs_gateway( $methods ) {
	$methods[] = 'woocommerce_bacs'; return $methods;
}