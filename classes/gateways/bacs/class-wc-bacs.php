<?php
/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank Transfer Payment Gateway. Based on code by Mike Pepper.
 *
 * @class 		WC_BACS
 * @extends		WC_Payment_Gateway
 * @version		1.6.4
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_BACS extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
    public function __construct() {
		$this->id				= 'bacs';
		$this->icon 			= apply_filters('woocommerce_bacs_icon', '');
		$this->has_fields 		= false;
		$this->method_title     = __( 'Bacs', 'woocommerce' );
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
     *
     * @access public
     * @return void
     */
    function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Bank Transfer', 'woocommerce' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
							'default' => __( 'Direct Bank Transfer', 'woocommerce' )
						),
			'description' => array(
							'title' => __( 'Customer Message', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'Give the customer instructions for paying via BACS, and let them know that their order won\'t be shipping until the money is received.', 'woocommerce' ),
							'default' => __('Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order wont be shipped until the funds have cleared in our account.', 'woocommerce')
						),
			'account_details' => array(
							'title' => __( 'Account Details', 'woocommerce' ),
							'type' => 'title',
							'description' => __( 'Optionally enter your bank details below for customers to pay into.', 'woocommerce' ),
							'default' => ''
						),
			'account_name' => array(
							'title' => __( 'Account Name', 'woocommerce' ),
							'type' => 'text',
							'description' => '',
							'default' => ''
						),
			'account_number' => array(
							'title' => __( 'Account Number', 'woocommerce' ),
							'type' => 'text',
							'description' => '',
							'default' => ''
						),
			'sort_code' => array(
							'title' => __( 'Sort Code', 'woocommerce' ),
							'type' => 'text',
							'description' => '',
							'default' => ''
						),
			'bank_name' => array(
							'title' => __( 'Bank Name', 'woocommerce' ),
							'type' => 'text',
							'description' => '',
							'default' => ''
						),
			'iban' => array(
							'title' => __( 'IBAN', 'woocommerce' ),
							'type' => 'text',
							'description' => __('Your bank may require this for international payments','woocommerce'),
							'default' => ''
						),
			'bic' => array(
							'title' => __( 'BIC (formerly Swift)', 'woocommerce' ),
							'type' => 'text',
							'description' => __('Your bank may require this for international payments','woocommerce'),
							'default' => ''
						),

			);

    }


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e('BACS Payment', 'woocommerce'); ?></h3>
    	<p><?php _e('Allows payments by BACS (Bank Account Clearing System), more commonly known as direct bank/wire transfer.', 'woocommerce'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    }


    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    function thankyou_page() {
		if ( $description = $this->get_description() )
        	echo wpautop( wptexturize( $description ) );

		?><h2><?php _e('Our Details', 'woocommerce') ?></h2><ul class="order_details bacs_details"><?php

		$fields = apply_filters('woocommerce_bacs_fields', array(
			'account_name' 	=> __('Account Name', 'woocommerce'),
			'account_number'=> __('Account Number', 'woocommerce'),
			'sort_code'		=> __('Sort Code', 'woocommerce'),
			'bank_name'		=> __('Bank Name', 'woocommerce'),
			'iban'			=> __('IBAN', 'woocommerce'),
			'bic'			=> __('BIC', 'woocommerce')
		));

		foreach ($fields as $key=>$value) :
		    if(!empty($this->$key)) :
		    	echo '<li class="'.$key.'">'.$value.': <strong>'.wptexturize($this->$key).'</strong></li>';
		    endif;
		endforeach;

		?></ul><?php
    }


    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @return void
     */
    function email_instructions( $order, $sent_to_admin ) {

    	if ( $sent_to_admin ) return;

    	if ( $order->status !== 'on-hold') return;

    	if ( $order->payment_method !== 'bacs') return;

		if ( $description = $this->get_description() )
        	echo wpautop( wptexturize( $description ) );

		?><h2><?php _e('Our Details', 'woocommerce') ?></h2><ul class="order_details bacs_details"><?php

		$fields = apply_filters('woocommerce_bacs_fields', array(
			'account_name' 	=> __('Account Name', 'woocommerce'),
			'account_number'=> __('Account Number', 'woocommerce'),
			'sort_code'		=> __('Sort Code', 'woocommerce'),
			'bank_name'		=> __('Bank Name', 'woocommerce'),
			'iban'			=> __('IBAN', 'woocommerce'),
			'bic'			=> __('BIC', 'woocommerce')
		));

		foreach ($fields as $key=>$value) :
		    if(!empty($this->$key)) :
		    	echo '<li class="'.$key.'">'.$value.': <strong>'.wptexturize($this->$key).'</strong></li>';
		    endif;
		endforeach;

		?></ul><?php
    }


    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    function process_payment( $order_id ) {
    	global $woocommerce;

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status('on-hold', __('Awaiting BACS payment', 'woocommerce'));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Empty awaiting payment session
		unset($_SESSION['order_awaiting_payment']);

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order->id, get_permalink(woocommerce_get_page_id('thanks'))))
		);
    }

}


/**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @param array $methods
 * @package		WooCommerce/Classes/Payment
 * @return array
 */
function add_bacs_gateway( $methods ) {
	$methods[] = 'WC_BACS';
	return $methods;
}

add_filter('woocommerce_payment_gateways', 'add_bacs_gateway' );