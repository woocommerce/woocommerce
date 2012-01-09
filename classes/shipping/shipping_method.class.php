<?php
/**
 * WooCommerce Shipping Method Class
 * 
 * Extended by shipping methods to handle shipping calculations etc.
 *
 * @class 		woocommerce_shipping_method
 * @package		WooCommerce
 * @category	Shipping
 * @author		WooThemes
 */  
class woocommerce_shipping_method extends woocommerce_settings_api {
	
	var $id;
	var $method_title; 	// Method title
	var $title;			// User set title
	var $tax_status;
	var $availability;
	var $countries;
	var $type;
	var $fee				= 0;
	var $min_amount			= null;
	var $enabled			= false;
	
	/**
	 * Rates
	 *
	 * This is an array of rates - methods must populate this array to register shipping costs
	 */
	var $rates 				= array(); // This is an array of rates - methods must populate this array to register shipping costs
	
	/**
	 * Add a rate
	 *
	 * A a shipping rate. If taxes are not set they will be calculated based on cost.
	 */
	function add_rate( $args = array() ) {
		global $woocommerce;
		
		$defaults = array(
			'id' 		=> '',
			'label' 	=> '',
			'cost' 		=> '0',
			'taxes' 	=> '',
			'calc_tax'	=> 'per_order'
		);

		$args = wp_parse_args( $args, $defaults );
					
		extract( $args );
		
		// Id and label are required
		if (!$id || !$label) return;
		
		// Taxes - if not an array and not set to false, calc tax based on cost and passed calc_tax variable
		// This saves shipping methods having to do compelex tax calculations
		if (!is_array($taxes) && $taxes!==false && $cost>0 && get_option('woocommerce_calc_taxes')=='yes' && $this->tax_status=='taxable' ) :
			
			$_tax 	= &new woocommerce_tax();
			$taxes 	= array();
			
			switch ($calc_tax) :
				
				case "per_item" :
					if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $item_id => $values) :
						
						$_product = $values['data'];
						
						if ($values['quantity']>0 && $_product->needs_shipping() && $_product->is_shipping_taxable()) :
							
							$rates = $_tax->get_shipping_tax_rates( $_product->get_tax_class() );	
							$item_taxes = $_tax->calc_shipping_tax( $cost, $rates );
								
							// Sum the item taxes
							foreach (array_keys($taxes + $item_taxes) as $key) :
								$taxes[$key] = (isset($item_taxes[$key]) ? $item_taxes[$key] : 0) + (isset($taxes[$key]) ? $taxes[$key] : 0);
							endforeach;
							
						endif;
					endforeach; endif;
				break;
				
				case "per_order" :
				default :
					
					$rates = $_tax->get_shipping_tax_rates();
					$taxes = $_tax->calc_shipping_tax( $cost, $rates );
					
				break;
				
			endswitch;
			
		endif;

		$this->rates[] = new woocommerce_shipping_rate( $id, $label, $cost, $taxes );
	}
	
    function is_available() {
    	global $woocommerce;
    	
    	if ($this->enabled=="no") 
    		return false;
    	
		if (isset($woocommerce->cart->cart_contents_total) && isset($this->min_amount) && $this->min_amount && $this->min_amount > $woocommerce->cart->cart_contents_total) 
			return false;
		
		$ship_to_countries = '';
		
		if ($this->availability == 'specific') :
			$ship_to_countries = $this->countries;
		else :
			if (get_option('woocommerce_allowed_countries')=='specific') :
				$ship_to_countries = get_option('woocommerce_specific_allowed_countries');
			endif;
		endif; 
		
		if (is_array($ship_to_countries)) :
			if (!in_array($woocommerce->customer->get_shipping_country(), $ship_to_countries)) return false;
		endif;
		
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true );
    } 
    
    function get_fee( $fee, $total ) {
		if (strstr($fee, '%')) :
			return ($total/100) * str_replace('%', '', $fee);
		else :
			return $fee;
		endif;
	}	
}

/**
 * WooCommerce Shipping Rate Class
 * 
 * Simple Class for storing rates. 
 */ 
class woocommerce_shipping_rate {

	var $id 	= '';
	var $label 	= '';
	var $cost 	= 0;
	var $taxes 	= array();
	
	function woocommerce_shipping_rate( $id, $label, $cost, $taxes ) {
		$this->id 		= $id;
		$this->label 	= $label;
		$this->cost 	= $cost;
		$this->taxes 	= ($taxes) ? $taxes : array();
	}
	
	function get_shipping_tax() {
		$taxes = 0;
		if ($this->taxes && sizeof($this->taxes)>0) :
			$taxes = array_sum($this->taxes);
		endif;
		return $taxes;
	}
}