<?php
/**
 * Flat Rate Shipping Method
 * 
 * A simple shipping method for a flat fee per item or per order
 *
 * @class 		flat_rate
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class flat_rate extends woocommerce_shipping_method {
	
	function __construct() { 
        $this->id 			= 'flat_rate';
        $this->method_title = __('Flat rate', 'woothemes');

		// Load the form fields.
		$this->init_form_fields();
		
		// Load the settings.
		$this->init_settings();
		
		// Define user set variables
        $this->enabled		= $this->settings['enabled'];
		$this->title 		= $this->settings['title'];
		$this->availability = $this->settings['availability'];
		$this->countries 	= $this->settings['countries'];
		$this->type 		= $this->settings['type'];
		$this->tax_status	= $this->settings['tax_status'];
		$this->cost 		= $this->settings['cost'];
		$this->fee 			= $this->settings['fee']; 
		
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
							'label' 		=> __( 'Enable Flat Rate shipping', 'woothemes' ), 
							'default' 		=> 'yes'
						), 
			'title' => array(
							'title' 		=> __( 'Method Title', 'woothemes' ), 
							'type' 			=> 'text', 
							'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
							'default'		=> __( 'Flat Rate', 'woothemes' )
						),
			'type' => array(
							'title' 		=> __( 'Type', 'woothemes' ), 
							'type' 			=> 'select', 
							'description' 	=> '', 
							'default' 		=> 'order',
							'options' 		=> array(
								'order' 	=> __('Per Order', 'woothemes'),
								'item' 		=> __('Per Item', 'woothemes')
							)
						),
			'tax_status' => array(
							'title' 		=> __( 'Tax Status', 'woothemes' ), 
							'type' 			=> 'select', 
							'description' 	=> '', 
							'default' 		=> 'taxable',
							'options'		=> array(
								'taxable' 	=> __('Taxable', 'woothemes'),
								'none' 		=> __('None', 'woothemes')
							)
						),
			'cost' => array(
							'title' 		=> __( 'Cost', 'woothemes' ), 
							'type' 			=> 'text', 
							'description'	=> __('Cost excluding tax. Enter an amount, e.g. 2.50.', 'woothemes'),
							'default' 		=> ''
						), 
			'fee' => array(
							'title' 		=> __( 'Handling Fee', 'woothemes' ), 
							'type' 			=> 'text', 
							'description'	=> __('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%. Leave blank to disable.', 'woothemes'),
							'default'		=> ''
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
    
    function calculate_shipping() {
    	global $woocommerce;
    	
    	$_tax = &new woocommerce_tax();
    	
    	$this->shipping_total 	= 0;
		$this->shipping_tax 	= 0;
    	
    	if ($this->type=='order') :
			// Shipping for whole order
			$this->shipping_total = $this->cost + $this->get_fee( $this->fee, $woocommerce->cart->cart_contents_total );
			
			if ( get_option('woocommerce_calc_taxes')=='yes' && $this->tax_status=='taxable' ) :
				
				$rate = $_tax->get_shipping_tax_rate();
				if ($rate>0) :
					$tax_amount = $_tax->calc_shipping_tax( $this->shipping_total, $rate );

					$this->shipping_tax = $this->shipping_tax + $tax_amount;
				endif;
			endif;
		else :
			// Shipping per item
			if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $item_id => $values) :
				$_product = $values['data'];
				if ($_product->exists() && $values['quantity']>0) :
					
					$item_shipping_price = ($this->cost + $this->get_fee( $this->fee, $_product->get_price() )) * $values['quantity'];
					
					// Only count 'psysical' products
					if ( $_product->needs_shipping() ) :
						
						$this->shipping_total = $this->shipping_total + $item_shipping_price;
	
						if ( $_product->is_shipping_taxable() && $this->tax_status=='taxable' ) :
						
							$rate = $_tax->get_shipping_tax_rate( $_product->get_tax_class() );
							
							if ($rate>0) :
							
								$tax_amount = $_tax->calc_shipping_tax( $item_shipping_price, $rate );
							
								$this->shipping_tax = $this->shipping_tax + $tax_amount;
							
							endif;
						
						endif;
					
					endif;
					
				endif;
			endforeach; endif;
		endif;			
    } 

	/**
	 * Admin Panel Options 
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {

    	?>
    	<h3><?php _e('Flat Rates', 'woothemes'); ?></h3>
    	<p><?php _e('Flat rates let you define a standard rate per item, or per order.', 'woothemes'); ?></p>
    	<table class="form-table">
    	<?php
    		// Generate the HTML For the settings form.
    		$this->generate_settings_html();
    	?>
		</table><!--/.form-table-->
    	<?php
    } // End admin_options()

}

function add_flat_rate_method( $methods ) {
	$methods[] = 'flat_rate'; return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_flat_rate_method' );
