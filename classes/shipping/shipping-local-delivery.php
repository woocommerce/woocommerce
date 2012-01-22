<?php
/**
 * Local Delivery Shipping Method
 * 
 * A simple shipping method allowing local delivery as a shipping method
 *
 * @class 		Local_Delivery
 * @package		WooCommerce
 * @category	Shipping
 * @author		Patrick Garman (www.patrickgarman.com)
 */  

class Local_Delivery extends Woocommerce_Shipping_Method {

	function __construct() { 
		$this->id 			= 'local-delivery';
		$this->method_title = __('Local Delivery', 'woothemes');

		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
		$this->enabled		= $this->settings['enabled'];
		$this->title		= $this->settings['title'];
		$this->fee			= $this->settings['fee'];
		$this->type			= $this->settings['type'];	
		
		add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
		
	} 

	 function calculate_shipping() {
		global $woocommerce;
		$_tax = new Woocommerce_Tax();
		if ($this->type=='free') 		$shipping_total 	= 0;
		if ($this->type=='fixed') 		$shipping_total 	= $this->fee;
		if ($this->type=='percent') 	$shipping_total 	= $woocommerce->cart->cart_contents_total * ($this->fee/100);
		
		$rate = array(
			'id' 		=> $this->id,
			'label' 	=> $this->title,
			'cost' 		=> $shipping_total
		);
		
		$this->add_rate($rate);  
	}
	
	function init_form_fields() {
    	global $woocommerce;
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable', 'woocommerce' ), 
				'type' 			=> 'checkbox', 
				'label' 		=> __( 'Enable local delivery', 'woocommerce' ), 
				'default' 		=> 'yes'
			), 
			'title' => array(
				'title' 		=> __( 'Title', 'woocommerce' ), 
				'type' 			=> 'text', 
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ), 
				'default'		=> __( 'Local Delivery', 'woocommerce' )
			),
			'type' => array(
				'title' 		=> __( 'Fee Type', 'woocommerce' ), 
				'type' 			=> 'select', 
				'description' 	=> __( 'How to calculate delivery charges', 'woocommerce' ),  
				'default' 		=> 'fixed',
				'options' 		=> array(
					'free'		=> __('Free Delivery', 'woocommerce'),
					'fixed' 	=> __('Fixed Amount', 'woocommerce'),
					'percent'	=> __('Percentage of Cart Total', 'woocommerce'),
				),
			),
			'fee' => array(
				'title' 		=> __( 'Fee', 'woocommerce' ), 
				'type' 			=> 'text', 
				'description' 	=> __( 'What fee do you want to charge for local delivery, disregarded if you choose free.', 'woocommerce' ), 
				'default'		=> '5'
			),
		);
	}

	function admin_options() {
		global $woocommerce; ?>
		<h3><?php echo $this->method_title; ?></h3>
		<p><?php _e('Local delivery is a simple shipping method for delivering orders locally.', 'woocommerce'); ?></p>
		<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
    	</table> <?php
	}

}

function add_local_delivery_method($methods) { $methods[] = 'local_delivery'; return $methods; }
add_filter('woocommerce_shipping_methods','add_local_delivery_method');