<?php      
class jigoshop_shipping {
	
	private static $_instance;
	
	public static $enabled			= false;
	public static $shipping_methods 	= array();
	public static $chosen_method		= null;
	public static $shipping_total 	= 0;
	public static $shipping_tax 		= 0;
	public static $shipping_label		= null;
	
    public static function init() {
		
		if (get_option('jigoshop_calc_shipping')!='no') self::$enabled = true; 
		
		do_action('jigoshop_shipping_init');
		
		$load_methods = apply_filters('jigoshop_shipping_methods', array());
		
		foreach ($load_methods as $method) :
		
			self::$shipping_methods[] = &new $method();
			
		endforeach;
		
	}
    
    public static function get() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
	
	function get_available_shipping_methods() {

		if (self::$enabled=='yes') :
		
			$_available_methods = array();
		
			foreach ( self::$shipping_methods as $method ) :
				
				if ($method->is_available()) $_available_methods[$method->id] = $method;
				
			endforeach;

			return $_available_methods;
			
		endif;
	}
	
	function reset_shipping_methods() {
		foreach ( self::$shipping_methods as $method ) :
			$method->chosen = false;
			$method->shipping_total = 0;
			$method->shipping_tax = 0;
		endforeach;
	}
	
	function calculate_shipping() {
		
		if (self::$enabled=='yes') :
		
			self::$shipping_total = 0;
			self::$shipping_tax = 0;
			self::$shipping_label = null;
			$_cheapest_fee = '';
			$_cheapest_method = '';
			if (isset($_SESSION['_chosen_method_id'])) $chosen_method = $_SESSION['_chosen_method_id']; else $chosen_method = '';
			$calc_cheapest = false;
			
			if (!$chosen_method || empty($chosen_method)) $calc_cheapest = true;
			
			self::reset_shipping_methods();
			
			$_available_methods = self::get_available_shipping_methods();
			
			if (sizeof($_available_methods)>0) :
			
				foreach ($_available_methods as $method) :
					
					$method->calculate_shipping();
					$fee = $method->shipping_total;
					if ($fee < $_cheapest_fee || !is_numeric($_cheapest_fee)) :
						$_cheapest_fee = $fee;
						$_cheapest_method = $method->id;
					endif;
					
				endforeach;
				
				// Default to cheapest
				if ($calc_cheapest || !isset($_available_methods[$chosen_method])) :
					$chosen_method = $_cheapest_method;
				endif;
				
				if ($chosen_method) :
					
					$_available_methods[$chosen_method]->choose();
					self::$shipping_total 	= $_available_methods[$chosen_method]->shipping_total;
					self::$shipping_tax 	= $_available_methods[$chosen_method]->shipping_tax;
					self::$shipping_label 	= $_available_methods[$chosen_method]->title;
					
				endif;
			endif;

		endif;
		
	}
	
	function reset_shipping() {
		self::$shipping_total = 0;
		self::$shipping_tax = 0;
		self::$shipping_label = null;
	}
	
}