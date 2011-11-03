<?php
/**
 * Bank Transfer Payment Gateway
 * 
 * Provides a Bank Transfer Payment Gateway. Based on code by Mike Pepper.
 *
 * @class 		woocommerce_bacs
 * @package		WooCommerce
 * @category	Payment Gateways
 * @author		WooThemes
 */
class woocommerce_bacs extends woocommerce_payment_gateway {

    public function __construct() { 
		$this->id				= 'bacs';
		$this->icon 			= apply_filters('woocommerce_bacs_icon', '');
		$this->has_fields 		= false;
		
		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->title 			= $this->settings['title'];
		$this->description      = $this->settings['description'];
		$this->account_name     = $this->settings['account_name'];
		$this->account_number   = $this->settings['account_number'];
		$this->sort_code        = $this->settings['sort_code'];
		$this->bank_name        = $this->settings['bank_name'];
		$this->iban             = $this->settings['iban'];
		$this->bic              = $this->settings['bic'];  
		
		// Actions
		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
    	add_action('woocommerce_thankyou_bacs', array(&$this, 'thankyou_page'));
    	
    	// Customer Emails
    	add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 2);
    } 

	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woothemes' ), 
							'type' => 'checkbox', 
							'label' => __( 'Enable Bank Transfer', 'woothemes' ), 
							'default' => 'yes'
						), 
			'title' => array(
							'title' => __( 'Title', 'woothemes' ), 
							'type' => 'text', 
							'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default' => __( 'Direct Bank Transfer', 'woothemes' )
						),
			'description' => array(
							'title' => __( 'Customer Message', 'woothemes' ), 
							'type' => 'textarea', 
							'description' => __( 'Give the customer instructions for paying via BACS, and let them know that their order won\'t be shipping until the money is received.', 'woothemes' ), 
							'default' => __('Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order wont be shipped until the funds have cleared in our account.', 'woothemes')
						),
			'account_name' => array(
							'title' => __( 'Account Name', 'woothemes' ), 
							'type' => 'text', 
							'description' => '', 
							'default' => ''
						),
			'account_number' => array(
							'title' => __( 'Account Number', 'woothemes' ), 
							'type' => 'text', 
							'description' => '', 
							'default' => ''
						),
			'sort_code' => array(
							'title' => __( 'Sort Code', 'woothemes' ), 
							'type' => 'text', 
							'description' => '', 
							'default' => ''
						),
			'bank_name' => array(
							'title' => __( 'Bank Name', 'woothemes' ), 
							'type' => 'text', 
							'description' => '', 
							'default' => ''
						),
			'iban' => array(
							'title' => __( 'IBAN', 'woothemes' ), 
							'type' => 'text', 
							'description' => __('Your bank may require this for international payments','woothemes'), 
							'default' => ''
						),
			'bic' => array(
							'title' => __( 'BIC (formerly Swift)', 'woothemes' ), 
							'type' => 'text', 
							'description' => __('Your bank may require this for international payments','woothemes'), 
							'default' => ''
						),

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
    	<h3><?php _e('BACS Payment', 'woothemes'); ?></h3>
    	<p><?php _e('Allows payments by BACS (Bank Account Clearing System), more commonly known as direct bank/wire transfer.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()


    /**
    * There are no payment fields for bacs, but we want to show the description if set.
    **/
    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    function thankyou_page() {
		if ($this->description) echo wpautop(wptexturize($this->description));
		
		?><h2><?php _e('Our Details', 'woothemes') ?></h2><ul class="order_details bacs_details"><?php
		
		$fields = array(
			'account_name' 	=> __('Account Name', 'woothemes'), 
			'account_number'=> __('Account Number', 'woothemes'),  
			'sort_code'		=> __('Sort Code', 'woothemes'),  
			'bank_name'		=> __('Bank Name', 'woothemes'),  
			'iban'			=> __('IBAN', 'woothemes'), 
			'bic'			=> __('BIC', 'woothemes')
		);
		
		foreach ($fields as $key=>$value) :
			echo '<li class="'.$key.'">'.$key.': <strong>'.wptexturize($this->$key).'</strong></li>';
		endforeach;
		
		?></ul><?php
    }
    
    /**
    * Add text to user email
    **/
    function email_instructions( $order, $sent_to_admin ) {
    	
    	if ( $sent_to_admin ) return;
    	
    	if ( $order->status !== 'on-hold') return;
    	
    	if ( $order->payment_method !== 'bacs') return;
    	
		if ($this->description) echo wpautop(wptexturize($this->description));
		
		?><h2><?php _e('Our Details', 'woothemes') ?></h2><ul class="order_details bacs_details"><?php
		
		$fields = array(
			'account_name' 	=> __('Account Name', 'woothemes'), 
			'account_number'=> __('Account Number', 'woothemes'),  
			'sort_code'		=> __('Sort Code', 'woothemes'),  
			'bank_name'		=> __('Bank Name', 'woothemes'),  
			'iban'			=> __('IBAN', 'woothemes'), 
			'bic'			=> __('BIC', 'woothemes')
		);
		
		foreach ($fields as $key=>$value) :
			echo '<li class="'.$key.'">'.$key.': <strong>'.wptexturize($this->$key).'</strong></li>';
		endforeach;
		
		?></ul><?php
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
