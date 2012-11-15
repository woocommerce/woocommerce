<?php
/**
 * Performs tax calculations and loads tax rates.
 *
 * @class 		WC_Tax
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
class WC_Tax {

	/** @var array Contains an array of tax rates. */
	var $rates;

	/** @var array Contains an array of parsed tax rates with counties/states as keys. */
	var $parsed_rates;

	/**
	 * Get the tax rates as an array.
	 *
	 * @access public
	 * @return array
	 */
	function get_tax_rates() {
		if ( ! is_array( $this->parsed_rates ) ) {

			global $woocommerce;

			$tax_rates 			= array_filter( (array) get_option('woocommerce_tax_rates') );
			$local_tax_rates 	= array_filter( (array) get_option('woocommerce_local_tax_rates') );

			$parsed_rates 		= array();
			$flat_rates			= array();
			$index 				= 0;

			if ( ! empty( $tax_rates ) ) {
				foreach( $tax_rates as $rate ) {
	
					// Standard Rate?
					if ( empty( $rate['class'] ) ) 
						$rate['class'] = '*';
	
					// Add entry for each country/state - each will hold all matching rates
					if ( ! empty( $rate['countries'] ) ) {
						foreach( $rate['countries'] as $country => $states ) {
							if ( $states ) {
								foreach ( $states as $state ) {
		
									if ( empty( $rate['label'] ) ) {
										$rate['label'] = $woocommerce->countries->tax_or_vat();
					
										// Add % to label
										if ( $rate['rate'] > 0 || $rate['rate'] === 0 )
											$rate['label'] .= ' (' . rtrim( rtrim( $rate['rate'], '0' ), '.' ) . '%)';
									}
		
									$flat_rates[ $index ] = $rate;
		
									$parsed_rates[ $country ][ $state ][ $rate['class'] ][ $index ] = array(
										'label' 	=> $rate['label'],
										'rate' 		=> $rate['rate'],
										'shipping' 	=> $rate['shipping'],
										'compound' 	=> $rate['compound']
									);
									
									$index++;		
								}
							}
						}
					}
				}
			}

			if ( ! empty( $local_tax_rates ) ) {
				foreach( $local_tax_rates as $rate ) {
	
					// Standard Rate?
					if ( empty( $rate['class'] ) ) 
						$rate['class'] = '*';
	
					if ( empty( $rate['label'] ) ) {
						$rate['label'] = $woocommerce->countries->tax_or_vat();
	
						// Add % to label
						if ( $rate['rate'] > 0 || $rate['rate'] === 0 )
							$rate['label'] .= ' (' . rtrim( rtrim( $rate['rate'], '0' ), '.' ) . '%)';
					}
					
					// Backwards compat
					if ( empty( $rate['locations'] ) && isset( $rate['postcode'] ) )
						$rate['locations'] = $rate['postcode'];
						
					if ( empty( $rate['location_type'] ) )
						$rate['location_type'] = 'postcode';
						
					// Postcodes -> Uppercase
					if ( $rate['location_type'] == 'postcode' )
				    	$rate['locations'] = array_map( 'strtoupper', (array) $rate['locations'] );
	
					$flat_rates[ $index ] = $rate;
	
					$parsed_rates[ $rate['country'] ][ $rate['state'] ][ $rate['class'] ][ $index ] = array(
						'label' 		=> $rate['label'],
						'rate' 			=> $rate['rate'],
						'locations' 	=> $rate['locations'],
						'location_type' => $rate['location_type'],
						'shipping' 		=> $rate['shipping'],
						'compound' 		=> $rate['compound']
					);

					$index++;
				}
			}

			$this->rates 		= $flat_rates;
			$this->parsed_rates = $parsed_rates;

			do_action( 'woocommerce_get_tax_rates', $this );
		}
	}
	
	/**
	 * Searches for all matching country/state/postcode tax rates.
	 * 
	 * @access public
	 * @param string $args (default: '')
	 * @return array
	 */
	function find_rates( $args = '' ) {
		$this->get_tax_rates();
		
		$defaults = array(
			'country' 	=> '',
			'state' 	=> '',
			'city' 		=> '',
			'postcode' 	=> '',
			'tax_class'	=> ''
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args, EXTR_SKIP );

		if ( ! $country ) 
			return array();

		$state = $state ? $state : '*';
		$tax_class = $tax_class ? $tax_class : '*';
		$city = sanitize_title( $city );
		$tax_class = sanitize_title( $tax_class );
		
		$found_rates = array();

		// Look for a state specific rule
		if ( isset( $this->parsed_rates[ $country ][ $state ][ $tax_class ] ) || isset( $this->parsed_rates[ $country ][ $state ][ '*' ] ) ) {

			// Look for tax class specific rule
			if ( isset( $this->parsed_rates[ $country ][ $state ][ $tax_class ] ) )
				$found_rates = $this->parsed_rates[ $country ][ $state ][ $tax_class ];
			else
				$found_rates = $this->parsed_rates[ $country ][ $state ][ '*' ];

		// Default to * if not state specific rules are found
		} elseif ( isset( $this->parsed_rates[ $country ][ '*' ] ) ) {

			// Look for tax class specific rule
			if ( isset( $this->parsed_rates[ $country ][ '*' ][ $tax_class ] ) )
				$found_rates = $this->parsed_rates[ $country ][ '*' ][ $tax_class ];
			elseif ( isset( $this->parsed_rates[ $country ][ '*' ][ '*' ] ) )
				$found_rates = $this->parsed_rates[ $country ][ '*' ][ '*' ];

		}

		// Now we have an array of matching rates, lets filter this based on postcode
		$matched_tax_rates = array();

		if ( $found_rates ) {
			foreach ( $found_rates as $key => $rate ) {

				if ( ! empty( $rate['locations'] ) && $rate['location_type'] == 'postcode' ) {
	
					// Local rate
					if ( $postcode ) {
					
						// Check if postcode matches up
						if ( in_array( $postcode, $rate['locations'] ) || sizeof( $rate['locations'] ) == 0 ) 
							$matched_tax_rates[ $key ] = $rate;
	
						// Check postcode ranges
						if ( is_numeric( $postcode ) ) {
							foreach ( $rate['locations'] as $rate_postcode ) {
								if ( strstr( $rate_postcode, '-' ) ) {
									$parts = array_map( 'trim', explode( '-', $rate_postcode ) );
									if ( sizeof( $parts ) == 2 && is_numeric( $parts[0] ) && is_numeric( $parts[1] ) )
										if ( $postcode >= $parts[0] && $postcode <= $parts[1] ) 
											$matched_tax_rates[ $key ] = $rate;
								}
							}
						}
					}
					
				} elseif ( ! empty( $rate['locations'] ) && $rate['location_type'] == 'city' ) {
										
					// Local rate
					if ( $city ) {
						// Check if city matches up
						if ( in_array( $city, $rate['locations'] ) || sizeof( $rate['locations'] ) == 0 ) 
							$matched_tax_rates[ $key ] = $rate;
					}
					
				} else {
					// Standard rate
					$matched_tax_rates[ $key ] = $rate;
				}
			}
		}

		$matched_tax_rates = apply_filters('woocommerce_matched_tax_rates', $matched_tax_rates, $this->parsed_rates, $country, $state, $postcode, $city, $tax_class );

		return $matched_tax_rates;
	}

	/**
	 * Get's an array of matching rates for a tax class.
	 *
	 * @param   object	Tax Class
	 * @return  array
	 */
	function get_rates( $tax_class = '' ) {
		global $woocommerce;
		
		$this->get_tax_rates();

		$tax_class = sanitize_title( $tax_class );

		/* Checkout uses customer location for the tax rates. Also, if shipping has been calculated, use the customers address. */
		if ( ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) || $woocommerce->customer->has_calculated_shipping() ) {
			
			list( $country, $state, $postcode, $city ) = $woocommerce->customer->get_taxable_address();

			$matched_tax_rates = $this->find_rates( array(
				'country' 	=> $country, 
				'state' 	=> $state, 
				'postcode' 	=> $postcode, 
				'city' 		=> $city,
				'tax_class' => $tax_class
			) );

			return $matched_tax_rates;

		} else {
			
			// Prices which include tax should always use the base rate if we don't know where the user is located
			// Prices exlcuding tax however should just not add any taxes, as they will be added during checkout
			if ( $woocommerce->cart->prices_include_tax )
				return $this->get_shop_base_rate( $tax_class );
			else
				return array();
		}

	}

	/**
	 * Get's an array of matching rates for the shop's base country.
	 *
	 * @param   string	Tax Class
	 * @return  array
	 */
	function get_shop_base_rate( $tax_class = '' ) {
		global $woocommerce;

		$this->get_tax_rates();

		$country 	= $woocommerce->countries->get_base_country();
		$state 		= $woocommerce->countries->get_base_state();

		return $this->find_rates( array(
			'country' 	=> $country, 
			'state' 	=> $state, 
			'tax_class' => $tax_class 
		) );
	}

	/**
	 * Gets an array of matching shipping tax rates for a given class.
	 *
	 * @param   string	Tax Class
	 * @return  mixed
	 */
	function get_shipping_tax_rates( $tax_class = null ) {
		global $woocommerce;
		
		// See if we have an explicitly set shipping tax class
		if ( $shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' ) ) {
			$tax_class = $shipping_tax_class == 'standard' ? '' : $shipping_tax_class;
		}

		$this->get_tax_rates();

		if ( ( defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT ) || $woocommerce->customer->has_calculated_shipping() ) {
		
			list( $country, $state, $postcode, $city ) = $woocommerce->customer->get_taxable_address();

		} else {
			
			// Prices which include tax should always use the base rate if we don't know where the user is located
			// Prices exlcuding tax however should just not add any taxes, as they will be added during checkout
			if ( $woocommerce->cart->prices_include_tax ) {
				$country 	= $woocommerce->countries->get_base_country();
				$state 		= $woocommerce->countries->get_base_state();
				$postcode   = '';
				$city		= '';
			} else {
				return array();
			}

		}

		// If we are here then shipping is taxable - work it out
		if ( ! is_null( $tax_class ) ) {

			$matched_tax_rates = array();

			// This will be per item shipping
			$rates = $this->find_rates( array(
				'country' 	=> $country, 
				'state' 	=> $state, 
				'postcode' 	=> $postcode, 
				'city' 		=> $city,
				'tax_class' => $tax_class 
			) );

			if ( $rates ) 
				foreach ( $rates as $key => $rate )
					if ( isset( $rate['shipping'] ) && $rate['shipping'] == 'yes' )
						$matched_tax_rates[ $key ] = $rate;

			if ( sizeof( $matched_tax_rates ) == 0 ) {
				// Get standard rate
				$rates = $this->find_rates( array(
					'country' 	=> $country, 
					'state' 	=> $state, 
					'city' 		=> $city,
					'postcode' 	=> $postcode, 
				) );

				if ( $rates ) 
					foreach ( $rates as $key => $rate )
						if ( isset( $rate['shipping'] ) && $rate['shipping'] == 'yes' )
							$matched_tax_rates[ $key ] = $rate;
			}

			return $matched_tax_rates;

		} else {

			// This will be per order shipping - loop through the order and find the highest tax class rate
			$found_tax_classes = array();
			$matched_tax_rates = array();
			$rates = false;

			// Loop cart and find the highest tax band
			if ( sizeof( $woocommerce->cart->get_cart() ) > 0 ) 
				foreach ( $woocommerce->cart->get_cart() as $item )
					$found_tax_classes[] = $item['data']->get_tax_class();

			$found_tax_classes = array_unique( $found_tax_classes );

			// If multiple classes are found, use highest
			if ( sizeof( $found_tax_classes ) > 1 ) {

				if ( in_array( '', $found_tax_classes ) ) {
					$rates = $this->find_rates( array(
						'country' 	=> $country, 
						'state' 	=> $state, 
						'city' 		=> $city,
						'postcode' 	=> $postcode, 
					) );
				} else {
					$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );

					foreach ( $tax_classes as $tax_class ) {
						if ( in_array( $tax_class, $found_tax_classes ) ) {
							$rates = $this->find_rates( array(
								'country' 	=> $country, 
								'state' 	=> $state, 
								'postcode' 	=> $postcode, 
								'city' 		=> $city,
								'tax_class' => $tax_class 
							) );
							break;
						}
					}
				}

			// If a single tax class is found, use it
			} elseif ( sizeof( $found_tax_classes ) == 1 ) {

				$rates = $this->find_rates( array(
					'country' 	=> $country, 
					'state' 	=> $state, 
					'postcode' 	=> $postcode, 
					'city' 		=> $city,
					'tax_class' => $found_tax_classes[0] 
				) );

			}

			// If no class rate are found, use standard rates
			if ( ! $rates )
				$rates = $this->find_rates( array(
					'country' 	=> $country, 
					'state' 	=> $state, 
					'postcode' 	=> $postcode, 
					'city' 		=> $city,
				) );

			if ( $rates ) 
				foreach ( $rates as $key => $rate )
					if ( isset( $rate['shipping'] ) && $rate['shipping'] == 'yes' )
						$matched_tax_rates[ $key ] = $rate;

			return $matched_tax_rates;
		}

		return array(); // return false
	}

	/**
	 * Calculate the tax using a passed array of rates.
	 *
	 * @param   float	price
	 * @param   array	rates
	 * @param	bool	passed price includes tax
	 * @return  array	array of rates/amounts
	 */
	function calc_tax( $price, $rates, $price_includes_tax = true, $supress_rounding = false ) {

		$price = $price * 100;	// To avoid float rounding errors, work with integers (pence)

		// Taxes array
		$taxes = array();

		// Price inc tax
		if ( $price_includes_tax ) {

			$regular_tax_rates = $compound_tax_rates = 0;

			foreach ( $rates as $key => $rate )
				if ($rate['compound']=='yes')
					$compound_tax_rates = $compound_tax_rates + $rate['rate'];
				else
					$regular_tax_rates = $regular_tax_rates + $rate['rate'];

			$regular_tax_rate 	= 1 + ( $regular_tax_rates / 100 );
			$compound_tax_rate 	= 1 + ( $compound_tax_rates / 100 );
			$tax_rate 			= $regular_tax_rate * $compound_tax_rate;
			$non_compound_price = ( $price / $compound_tax_rate );

			foreach ( $rates as $key => $rate ) {

				$the_rate = $rate['rate'] / 100;

				if ( $rate['compound'] == 'yes' )
					$tax_amount = ( $the_rate / $compound_tax_rate ) * $price;
				else
					$tax_amount = ( $the_rate / $regular_tax_rate ) * $non_compound_price;

				// ADVANCED: Allow third parties to modifiy this rate
				$tax_amount = apply_filters( 'woocommerce_price_inc_tax_amount', $tax_amount, $key, $rate, $price, $non_compound_price );

				// Back to pounds
				$tax_amount = ( $tax_amount / 100 );

				// Rounding
				if ( get_option( 'woocommerce_tax_round_at_subtotal' ) == 'no' && ! $supress_rounding ) {
					$decimals = ( '' != get_option( 'woocommerce_price_num_decimals' ) ) ? get_option( 'woocommerce_price_num_decimals' ) : 0;
					$tax_amount = round( $tax_amount, $decimals );
				}

				// Add rate
				if ( ! isset( $taxes[$key] ) )
					$taxes[ $key ] = $tax_amount;
				else
					$taxes[ $key ] += $tax_amount;

			}

		// Prices ex tax
		} else {

			// Multiple taxes
			foreach ( $rates as $key => $rate ) {

				if ( $rate['compound'] == 'yes' )
					continue;

				$tax_amount = $price * ( $rate['rate'] / 100 );

				// ADVANCED: Allow third parties to modifiy this rate
				$tax_amount = apply_filters( 'woocommerce_price_ex_tax_amount', $tax_amount, $key, $rate, $price );

				// Back to pounds
				$tax_amount = ( $tax_amount / 100 );

				// Rounding
				if ( get_option( 'woocommerce_tax_round_at_subtotal' ) == 'no' && ! $supress_rounding ) {
					$decimals = ( '' != get_option( 'woocommerce_price_num_decimals' ) ) ? get_option( 'woocommerce_price_num_decimals' ) : 0;
					$tax_amount = round( $tax_amount, $decimals );
				}

				// Add rate
				if ( ! isset( $taxes[ $key ] ) )
					$taxes[ $key ] = $tax_amount;
				else
					$taxes[ $key ] += $tax_amount;

			}

			$pre_compound_total = array_sum( $taxes );

			// Compound taxes
			foreach ( $rates as $key => $rate ) {

				if ( $rate['compound'] == 'no' )
					continue;

				$the_price_inc_tax = $price + ( $pre_compound_total * 100 );

				$tax_amount = $the_price_inc_tax * ( $rate['rate'] / 100 );

				// ADVANCED: Allow third parties to modifiy this rate
				$tax_amount = apply_filters( 'woocommerce_price_ex_tax_amount', $tax_amount, $key, $rate, $price, $the_price_inc_tax, $pre_compound_total );

				// Back to pounds
				$tax_amount = ( $tax_amount / 100 );

				// Rounding
				if ( get_option( 'woocommerce_tax_round_at_subtotal' ) == 'no' && ! $supress_rounding ) {
					$decimals = ( '' != get_option( 'woocommerce_price_num_decimals' ) ) ? get_option( 'woocommerce_price_num_decimals' ) : 0;
					$tax_amount = round( $tax_amount, $decimals );
				}

				// Add rate
				if ( ! isset( $taxes[ $key ] ) )
					$taxes[ $key ] = $tax_amount;
				else
					$taxes[ $key ] += $tax_amount;

			}

		}

		return apply_filters( 'woocommerce_calc_tax', $taxes, $price, $rates, $price_includes_tax, $supress_rounding );
	}

	/**
	 * Calculate the shipping tax using a passed array of rates.
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

	/**
	 * Return true/false depending on if a rate is a compound rate.
	 *
	 * @param   int		key
	 * @return  bool
	 */
	function is_compound( $key ) {
		return ( isset( $this->rates[$key] ) && $this->rates[$key]['compound'] == 'yes' ) ? true : false;
	}

	/**
	 * Return a given rates label.
	 *
	 * @param   int		key
	 * @return  string
	 */
	function get_rate_label( $key ) {
		$label = empty( $this->rates[ $key ]['label'] ) ? '' : $this->rates[ $key ]['label'];
		
		return apply_filters( 'woocommerce_rate_label', $label, $key, $this );
	}

	/**
	 * Round tax lines and return the sum.
	 *
	 * @param   array
	 * @return  float
	 */
	function get_tax_total( $taxes ) {
		return array_sum( array_map( array(&$this, 'round'), $taxes ) );
	}

	/**
	 * Round to currency DP.
	 */
	function round( $in ) {
		$decimals = ( '' != get_option( 'woocommerce_price_num_decimals' ) ) ? get_option( 'woocommerce_price_num_decimals' ) : 0;
		return round( $in, $decimals );
	}

}