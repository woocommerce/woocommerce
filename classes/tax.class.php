<?php
/**
 * Performs tax calculations and loads tax rates.
 *
 * @class 		woocommerce_tax
 * @package		WooCommerce
 * @category	Class
 * @author		WooThemes
 */
class woocommerce_tax {
	
	var $rates = '';
	
	/**
	 * Get the tax rates as an array
	 */
	function get_tax_rates() {
		if (!is_array($this->rates)) :
			global $woocommerce;
			
			$tax_rates = get_option('woocommerce_tax_rates');
			$tax_rates_array = array();
			$index = 0;
			if ($tax_rates && is_array($tax_rates) && sizeof($tax_rates)>0) foreach( $tax_rates as $rate ) :
				
				// Standard Rate?
				if (!$rate['class']) $rate['class'] = '*';
				
				// Add entry for each country/state - each will hold all matching rates
				if ($rate['countries']) foreach ($rate['countries'] as $country => $states) :
					
					if ($states) :
						
						foreach ($states as $state) :
							
							if (!$rate['label']) :
							
								$rate['label'] = $woocommerce->countries->tax_or_vat();
								
								// Add % to label
								if ( $rate['rate'] > 0 || $rate['rate'] === 0 ) :
									$rate['label'] .= ' ('. rtrim(rtrim($rate['rate'], '0'), '.') . '%)';
								endif;
							
							endif;
							
							$tax_rates_array[$country][$state][$rate['class']][$index] = array( 
								'label' => $rate['label'], 
								'rate' => $rate['rate'], 
								'postcode' => $rate['postcode'], 
								'shipping' => $rate['shipping'],
								'compound' => $rate['compound'] 
								);
							
							$index++;
							
						endforeach;
					endif;
				
				endforeach;
				
			endforeach;
			
			$this->rates = $tax_rates_array;
		endif;
		
		return $this->rates;
	}
	
	/**
	 * Searches for all matching country/state/postcode tax rates
	 *
	 * @since 	1.4
	 * @param   string	country
	 * @param	string	state
	 * @param   string	postcode
	 * @param	string	Tax Class
	 * @return  array
	 */
	function find_rates( $country = '', $state = '', $postcode = '', $tax_class = '' ) {
		$this->get_tax_rates();
		
		if (!$country) return array();
		
		if (!$state) $state = '*';
		
		$tax_class = sanitize_title($tax_class);
		if (!$tax_class) $tax_class = '*';

		$found_rates = array();

		// Look for a state specific rule
		if (isset($this->rates[ $country ][ $state ])) :
			
			// Look for tax class specific rule
			if (isset($this->rates[ $country ][ $state ][ $tax_class ])) :
				$found_rates = $this->rates[ $country ][ $state ][ $tax_class ];
			else :
				$found_rates = $this->rates[ $country ][ $state ][ '*' ];
			endif;
		
		// Default to * if not state specific rules are found
		elseif (isset($this->rates[ $country ][ '*' ])) :
		
			// Look for tax class specific rule
			if (isset($this->rates[ $country ][ '*' ][ $tax_class ])) :
				$found_rates = $this->rates[ $country ][ '*' ][ $tax_class ];
			else :
				$found_rates = $this->rates[ $country ][ '*' ][ '*' ];
			endif;
			
		endif;
		
		// Now we have an array of matching rates, lets filter this based on postcode
		$matched_tax_rates = array();
		
		if ($postcode) :
			foreach ($found_rates as $key => $rate) :
				if (in_array($postcode, $rate['postcode']) || sizeof($rate['postcode'])==0) $matched_tax_rates[$key] = $rate;
			endforeach;
		else :
			foreach ($found_rates as $key => $rate) :
				if (sizeof($rate['postcode'])==0) $matched_tax_rates[$key] = $rate;
			endforeach;
		endif;

		$matched_tax_rates = apply_filters('woocommerce_matched_tax_rates', $matched_tax_rates, $this->rates, $country, $state, $postcode, $tax_class );
		
		return $matched_tax_rates;
	}
	
	/**
	 * Get's an array of matching rates for a tax class
	 *
	 * @param   object	Tax Class
	 * @return  array
	 */
	function get_rates( $tax_class = '' ) {
		global $woocommerce;
		
		$this->get_tax_rates();
		
		$tax_class = sanitize_title($tax_class);
		
		/* Checkout uses customer location, otherwise use store base rate */
		if ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) :
			
			$country 	= $woocommerce->customer->get_shipping_country();
			$state 		= $woocommerce->customer->get_shipping_state();
			$postcode   = $woocommerce->customer->get_shipping_postcode();
			
			$matched_tax_rates = $this->find_rates( $country, $state, $postcode, $tax_class );
			
			return $matched_tax_rates;
		
		else :
			
			return $this->get_shop_base_rate( $tax_class );
			
		endif;

	}
	
	/**
	 * Get's an array of matching rates for the shop's base country
	 *
	 * @param   string	Tax Class
	 * @return  array
	 */
	function get_shop_base_rate( $tax_class = '' ) {
		global $woocommerce;
		
		$this->get_tax_rates();
		
		$country 	= $woocommerce->countries->get_base_country();
		$state 		= $woocommerce->countries->get_base_state();
		
		return $this->find_rates( $country, $state, '', $tax_class );
	}

	
	/**
	 * Gets an array of matching shipping tax rates for a given class
	 *
	 * @param   string	Tax Class
	 * @return  mixed		
	 */
	function get_shipping_tax_rates( $tax_class = null ) {
		global $woocommerce;
		
		$this->get_tax_rates();
		
		if (defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT) :
			$country 	= $woocommerce->customer->get_shipping_country();
			$state 		= $woocommerce->customer->get_shipping_state();
			$postcode   = $woocommerce->customer->get_shipping_postcode();
		else :
			$country 	= $woocommerce->countries->get_base_country();
			$state 		= $woocommerce->countries->get_base_state();
			$postcode   = '';
		endif;
		
		// If we are here then shipping is taxable - work it out
		if ( !is_null($tax_class) ) :
			
			$matched_tax_rates = array();
			
			// This will be per item shipping
			$rates = $this->find_rates( $country, $state, $postcode, $tax_class );
			
			if ($rates) foreach ($rates as $key => $rate) :
				if (isset($rate['shipping']) && $rate['shipping']=='yes') :
					$matched_tax_rates[$key] = $rate;
				endif;
			endforeach;
			
			if (sizeof($matched_tax_rates)==0) :
				// Get standard rate
				$rates = $this->find_rates( $country, $state, $postcode );
				
				if ($rates) foreach ($rates as $key => $rate) :
					if (isset($rate['shipping']) && $rate['shipping']=='yes') :
						$matched_tax_rates[$key] = $rate;
					endif;
				endforeach;
			endif;
			
			return $matched_tax_rates;
			
		else :
			
			// This will be per order shipping - loop through the order and find the highest tax class rate
			$found_rates = array();
			$found_shipping_rates = array();
			
			// Loop cart and find the highest tax band
			if (sizeof($woocommerce->cart->get_cart())>0) : foreach ($woocommerce->cart->get_cart() as $item) :
				
				if ($item['data']->get_tax_class()) :
					
					$found_rate = $this->find_rates( $country, $state, $item['data']->get_tax_class() );
					
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
					$rate = $this->find_rates( $country, $state );
					if (isset($rate['shipping']) && $rate['shipping']=='yes') return $rate['rate'];
				endif;
				
			else :
				// Use standard rate
				$rate = $this->find_rates( $country, $state );	
				if (isset($rate['shipping']) && $rate['shipping']=='yes') return $rate['rate'];
			endif;
			
		endif;

		return array(); // return false
	}
	
	/**
	 * Calculate the tax using a passed array of rates
	 *
	 * @param   float	price
	 * @param   array	rates
	 * @param	bool	passed price includes tax
	 * @return  array	array of rates/amounts
	 */
	function calc_tax( $price, $rates, $price_includes_tax = true ) {
		
		$price = $price * 100;	// To avoid float rounding errors, work with integers (pence)
		
		// Taxes array
		$taxes = array();
		
		// Multiple taxes
		foreach ($rates as $key => $rate) :
			if ($rate['compound']=='yes') continue;
			
			if ($price_includes_tax) :
			
				$the_rate = ($rate['rate'] / 100) + 1;
				$tax_amount = $price - ( $price / $the_rate);
				
			else :
				$tax_amount = $price * ($rate['rate']/100);
			endif;
			
			// Back to pounds
			$tax_amount = ($tax_amount / 100);
			
			// Rounding
			if ( get_option( 'woocommerce_tax_round_at_subtotal' ) == 'no' ) :
				$tax_amount = round( $tax_amount, 2 );
			endif;
			
			// Add rate
			if (!isset($taxes[$key])) $taxes[$key] = $tax_amount; else $taxes[$key] += $tax_amount;

		endforeach;
		
		$pre_compound_total = array_sum($taxes);
		
		// Compound taxes
		foreach ($rates as $key => $rate) :
			if ($rate['compound']=='no') continue;
			
			$the_price_inc_tax = $price + ($pre_compound_total * 100);
			
			if ($price_includes_tax) :
			
				$the_rate = ($rate['rate'] / 100) + 1;
				$tax_amount = $the_price_inc_tax - ( $the_price_inc_tax / $the_rate);
				
			else :
				$tax_amount = $the_price_inc_tax * ($rate['rate']/100);
			endif;

			// Back to pounds
			$tax_amount = ($tax_amount / 100);
			
			// Rounding
			if ( get_option( 'woocommerce_tax_round_at_subtotal' ) == 'no' ) :
				$tax_amount = round( $tax_amount, 2 );
			endif;
			
			// Add rate
			if (!isset($taxes[$key])) $taxes[$key] = $tax_amount; else $taxes[$key] += $tax_amount;

		endforeach;

		return $taxes;
	}
	
	/**
	 * Calculate the shipping tax using a passed array of rates
	 *
	 * @param   int		Price
	 * @param	int		Taxation Rate
	 * @return  int		
	 */
	function calc_shipping_tax( $price, $rates ) {

		// Taxes array
		$taxes = array();
		
		// Multiple taxes
		foreach ($rates as $key => $rate) :
			if ($rate['compound']=='yes') continue;
			
			$tax_amount = $price * ($rate['rate']/100);
			
			// Add rate
			if (!isset($taxes[$key])) $taxes[$key] = $tax_amount; else $taxes[$key] += $tax_amount;

		endforeach;

		$pre_compound_total = array_sum($taxes);
		
		// Compound taxes
		foreach ($rates as $key => $rate) :
			if ($rate['compound']=='no') continue;
			
			$the_price_inc_tax = $price + $pre_compound_total;
			
			$tax_amount = $the_price_inc_tax * ($rate['rate']/100);
			
			// Add rate
			if (!isset($taxes[$key])) $taxes[$key] = $tax_amount; else $taxes[$key] += $tax_amount;

		endforeach;
		
		return $taxes;
	}
		
}

/**
 * WooCommerce Taxes
 * 
 * A Simple class for storing multiple taxes, including compound taxes
 */
class woocommerce_taxes {
	
	var $taxes;
	
	function __construct( $item_meta = '' ) {
		$this->taxes = array();
	}
	
	function get_taxes() {
		return $this->taxes;
	}
	
	/**
	 * Add tax rate
	 */
	function add_tax_rate( $key, $label, $compound = false ) {
		if (!isset($this->taxes[$key])) :
			$this->taxes[$key] = array(
				'label' => $label,
				'total' => 0,
				'compound' => $compound
			);
		endif;
	}
	
	/**
	 * Add tax amount
	 */
	function add_tax_amount( $key, $amount ) {
		$this->taxes[$key]['total'] += $amount;
	}
	
	/**
	 * Merge + sum tax rows
	 */
	function merge( $taxes ) {
		if ($taxes) foreach ($taxes as $key => $amount) :
			$this->add_tax_amount( $key, $amount );
		endforeach;
	}
	
	/**
	 * Get the total
	 */
	function get_tax_total( $compound = true ) {
		$total = 0;
		foreach ($this->taxes as $tax) :
			if (!$compound && $tax['compound']) continue;
			$total += $tax['total'];
		endforeach;
		return $total;
	}
	
}