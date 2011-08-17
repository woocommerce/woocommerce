<?php
/**
 * Performs tax calculations
 *
 * @class woocommerce_tax
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */

class woocommerce_tax {
	
	var $total;
	var $rates;
	
	/**
	 * Get the current tax class
	 *
	 * @return  array
	 */
	function woocommerce_tax() {
		$this->rates = $this->get_tax_rates();
	}
	
	/**
	 * Get an array of tax classes
	 *
	 * @return  array
	 */
	function get_tax_classes() {
		$classes = get_option('woocommerce_tax_classes');
		$classes = explode("\n", $classes);
		$classes = array_map('trim', $classes);
		$classes_array = array();
		if (sizeof($classes)>0) foreach ($classes as $class) :
			if ($class) $classes_array[] = $class;
		endforeach;
		return $classes_array;
	}
	
	/**
	 * Get the tax rates as an array
	 *
	 * @return  array
	 */
	function get_tax_rates() {
		$tax_rates = get_option('woocommerce_tax_rates');
		$tax_rates_array = array();
		if ($tax_rates && is_array($tax_rates) && sizeof($tax_rates)>0) foreach( $tax_rates as $rate ) :
			if ($rate['class']) :
				$tax_rates_array[$rate['country']][$rate['state']][$rate['class']] = array( 'rate' => $rate['rate'], 'shipping' => $rate['shipping'] );
			else :
				// Standard Rate
				$tax_rates_array[$rate['country']][$rate['state']]['*'] = $rate['rate'] = array( 'rate' => $rate['rate'], 'shipping' => $rate['shipping'] );
			endif;
		endforeach;
		return $tax_rates_array;
	}
	
	/**
	 * Searches for a country / state tax rate
	 *
	 * @param   string	country
	 * @param	string	state
	 * @param	object	Tax Class
	 * @return  int
	 */
	function find_rate( $country, $state = '*', $tax_class = '' ) {
		
		$rate['rate'] = 0;

		if (isset($this->rates[ $country ][ $state ])) :
			if ($tax_class) :
				if (isset($this->rates[ $country ][ $state ][$tax_class])) :
					$rate = $this->rates[ $country ][ $state ][$tax_class];
				endif;
			else :
				$rate = $this->rates[ $country ][ $state ][ '*' ];
			endif;
		elseif (isset($this->rates[ $country ][ '*' ])) :
			if ($tax_class) :
				if (isset($this->rates[ $country ][ '*' ][$tax_class])) :
					$rate = $this->rates[ $country ][ '*' ][$tax_class];
				endif;
			else :
				$rate = $this->rates[ $country ][ '*' ][ '*' ];
			endif;
		endif;
		
		return $rate;
		
	}
	
	/**
	 * Get the current taxation rate using find_rate()
	 *
	 * @param   object	Tax Class
	 * @return  int
	 */
	function get_rate( $tax_class = '' ) {
		
		/* Checkout uses customer location, otherwise use store base rate */
		if ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) :
			
			$country 	= woocommerce_customer::get_country();
			$state 		= woocommerce_customer::get_state();
			
			$rate = $this->find_rate( $country, $state, $tax_class );
			
			return $rate['rate'];
		
		else :
			
			return $this->get_shop_base_rate( $tax_class );
			
		endif;

	}
	
	/**
	 * Get the shop's taxation rate using find_rate()
	 *
	 * @param   object	Tax Class
	 * @return  int
	 */
	function get_shop_base_rate( $tax_class = '' ) {
		
		$country 	= woocommerce_countries::get_base_country();
		$state 		= woocommerce_countries::get_base_state();
		
		$rate = $this->find_rate( $country, $state, $tax_class );
		
		return $rate['rate'];
	}

	
	/**
	 * Get the tax rate based on the country and state
	 *
	 * @param   object	Tax Class
	 * @return  mixed		
	 */
	function get_shipping_tax_rate( $tax_class = '' ) {
		
		if (defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT) :
			$country 	= woocommerce_customer::get_country();
			$state 		= woocommerce_customer::get_state();
		else :
			$country 	= woocommerce_countries::get_base_country();
			$state 		= woocommerce_countries::get_base_state();
		endif;
		
		// If we are here then shipping is taxable - work it out
		
		if ($tax_class) :
			
			// This will be per item shipping
			$rate = $this->find_rate( $country, $state, $tax_class );
			
			if (isset($rate['shipping']) && $rate['shipping']=='yes') :
				return $rate['rate'];
			else :
				// Get standard rate
				$rate = $this->find_rate( $country, $state );
				if (isset($rate['shipping']) && $rate['shipping']=='yes') return $rate['rate'];
			endif;
			
		else :
			
			// This will be per order shipping - loop through the order and find the highest tax class rate
			
			$found_rates = array();
			$found_shipping_rates = array();
			
			// Loop cart and find the highest tax band
			if (sizeof(woocommerce_cart::$cart_contents)>0) : foreach (woocommerce_cart::$cart_contents as $item) :
				
				if ($item['data']->tax_class) :
					
					$found_rate = $this->find_rate( $country, $state, $item['data']->tax_class );
					
					$found_rates[] = $found_rate['rate'];
					
					if (isset($found_rate['shipping']) && $found_rate['shipping']=='yes') $found_shipping_rates[] = $found_rate['rate'];
					
				endif;
				
			endforeach; endif;
			
			if (sizeof($found_rates) > 0 && sizeof($found_shipping_rates) > 0) :
				
				rsort($found_rates);
				rsort($found_shipping_rates);
				
				if ($found_rates[0] == $found_shipping_rates[0]) :
					return $found_shipping_rates[0];
				else :
					// Use standard rate
					$rate = $this->find_rate( $country, $state );
					if (isset($rate['shipping']) && $rate['shipping']=='yes') return $rate['rate'];
				endif;
				
			else :
				// Use standard rate
				$rate = $this->find_rate( $country, $state );	
				if (isset($rate['shipping']) && $rate['shipping']=='yes') return $rate['rate'];
			endif;
			
		endif;

		return 0; // return false
	}
	
	/**
	 * Calculate the tax using the final value
	 *
	 * @param   int		Price
	 * @param	int		Taxation Rate
	 * @return  int		
	 */
	function calc_tax( $price, $rate, $price_includes_tax = true ) {
	
		// To avoid float roudning errors, work with integers (pence)
		$price = round($price * 100, 0);
		$math_rate = $rate;

		if ($price_includes_tax) :

			$math_rate = ($math_rate / 100) + 1;

			//$price_ex = round($price / $math_rate);
			//$tax_amount = round($price - $price_ex);
			$tax_amount = $price - ( $price / $math_rate);
			
		else :
			$tax_amount = $price * ($rate/100);
		endif;
		
		// Round to the nearest pence
		$tax_amount = round($tax_amount);

		$tax_amount = $tax_amount / 100; // Back to pounds
		
		//return number_format($tax_amount, 4, '.', '');
		return number_format($tax_amount, 2, '.', '');
	}
	
	/**
	 * Calculate the shipping tax using the final value
	 *
	 * @param   int		Price
	 * @param	int		Taxation Rate
	 * @return  int		
	 */
	function calc_shipping_tax( $price, $rate ) {
	
		$rate = round($rate, 4);
			
		$tax_amount = $price * ($rate/100);

		return round($tax_amount, 2);
	}
		
}