<?php
/**
 * Free Shipping Method
 * 
 * A simple shipping method for free shipping
 *
 * @class 		free_shipping
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */ 
class free_shipping extends woocommerce_shipping_method {
	
	function __construct() { 
        $this->id 			= 'free_shipping';
        $this->method_title = __('Free shipping', 'woothemes');

		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
        $this->enabled		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->min_amount 	= $this->settings['min_amount'];
		$this->availability = $this->settings['availability'];
		$this->countries 	= $this->settings['countries'];
		
		// Actions
		add_action('woocommerce_update_options_shipping_methods', array(&$this, 'process_admin_options'));
    } 

	/**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
    	global $woocommerce;
    
    	$this->form_fields = array(
			'enabled' => array(
							'title' 		=> __( 'Enable/Disable', 'woothemes' ), 
							'type' 			=> 'checkbox', 
							'label' 		=> __( 'Enable Free Shipping', 'woothemes' ), 
							'default' 		=> 'yes'
						), 
			'title' => array(
							'title' 		=> __( 'Method Title', 'woothemes' ), 
							'type' 			=> 'text', 
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default'		=> __( 'Free Shipping', 'woothemes' )
						),
			'min_amount' => array(
							'title' 		=> __( 'Minimum Order Amount', 'woothemes' ), 
							'type' 			=> 'text', 
							'description' 	=> __('Users will need to spend this amount to get free shipping. Leave blank to disable.', 'woothemes'),
							'default' 		=> ''
						),
			'availability' => array(
							'title' 		=> __( 'Method availability', 'woothemes' ), 
							'type' 			=> 'select', 
							'default' 		=> 'all',
							'class'			=> 'availability',
							'options'		=> array(
								'all' 		=> __('All allowed countries', 'woothemes'),
								'specific' 	=> __('Specific Countries', 'woothemes')
							)
						),
			'countries' => array(
							'title' 		=> __( 'Specific Countries', 'woothemes' ), 
							'type' 			=> 'multiselect', 
							'class'			=> 'chosen_select',
							'css'			=> 'width:50%;',
							'default' 		=> '',
							'options'		=> $woocommerce->countries->countries
						)	
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
    	<h3><?php _e('Free Shipping', 'woothemes'); ?></h3>
    	<p><?php _e('Free Shipping - does what it says on the tin.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()
    
    
    function calculate_shipping() {
		$this->shipping_total 	= 0;
		$this->shipping_tax 	= 0;
		$this->shipping_label 	= $this->title;	    	
    }
    	
}

function add_free_shipping_method( $methods ) {
	$methods[] = 'free_shipping'; return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_free_shipping_method' );