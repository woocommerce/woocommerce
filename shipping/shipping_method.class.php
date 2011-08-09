<?php
class jigoshop_shipping_method {
	
	var $id;
	var $title;
	var $availability;
	var $countries;
	var $type;
	var $cost				= 0;
	var $fee				= 0;
	var $min_amount			= null;
	var $enabled			= false;
	var $chosen				= false;
	var $shipping_total 	= 0;
	var $shipping_tax 		= 0;
	
    public function is_available() {
    	
    	if ($this->enabled=="no") return false;
    	
		if (isset(jigoshop_cart::$cart_contents_total) && isset($this->min_amount) && $this->min_amount && $this->min_amount > jigoshop_cart::$cart_contents_total) return false;
		
		$ship_to_countries = '';
		
		if ($this->availability == 'specific') :
			$ship_to_countries = $this->countries;
		else :
			if (get_option('jigoshop_allowed_countries')=='specific') :
				$ship_to_countries = get_option('jigoshop_specific_allowed_countries');
			endif;
		endif; 
		
		if (is_array($ship_to_countries)) :
			if (!in_array(jigoshop_customer::get_shipping_country(), $ship_to_countries)) return false;
		endif;
		
		return true;
		
    } 
    
    public function get_fee( $fee, $total ) {
		if (strstr($fee, '%')) :
			return ($total/100) * str_replace('%', '', $fee);
		else :
			return $fee;
		endif;
	}
	
	public function is_chosen() {
    	if ($this->chosen) return true;
    	return false;
    }
    
    public function choose() {
    	$this->chosen = true;
    	$_SESSION['_chosen_method_id'] = $this->id;
    }
    
    public function admin_options() {}
    
    public function process_admin_options() {}
    	
}