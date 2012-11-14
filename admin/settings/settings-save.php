<?php
/**
 * Update options
 *
 * Updates the options on the woocommerce settings pages. Returns true if saved.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Settings
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Update all settings which are passed.
 *
 * @access public
 * @param array $options
 * @return void
 */
function woocommerce_update_options( $options ) {

    if ( empty( $_POST ) ) 
    	return false;

    foreach ( $options as $value ) {
    	
    	if ( ! isset( $value['id'] ) )
    		continue;
    	
    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';
    
    	switch ( $type ) {
    	
	    	// Standard types
	    	case "checkbox" :
	    		
	    		if ( isset( $_POST[$value['id']] ) ) {
	            	update_option( $value['id'], 'yes' );
	            } else {
	                update_option( $value['id'], 'no' );
	            }
	            
	    	break;
	    	
	    	case "textarea" :
	    	
		    	if ( isset( $_POST[$value['id']] ) ) {
	            	update_option( $value['id'], wp_kses_post( $_POST[ $value['id'] ] ) );
	            } else {
	                delete_option( $value['id'] );
	            }
            
	    	break;
	    	
	    	case "text" :
	    	case "select" :
	    	case "color" :
	    	case "single_select_page" :
	    	case "single_select_country" :
	    	
	    		if ( $value['id'] == 'woocommerce_price_thousand_sep' || $value['id'] == 'woocommerce_price_decimal_sep' ) {

					// price separators get a special treatment as they should allow a spaces (don't trim)
					if ( isset( $_POST[ $value['id'] ] )  ) {
						update_option( $value['id'], esc_attr( $_POST[ $value['id'] ] ) );
					} else {
		                delete_option( $value['id'] );
		            }
		            
		        } else {
			        
			       if ( isset( $_POST[$value['id']] ) ) {
		            	update_option( $value['id'], woocommerce_clean( $_POST[ $value['id'] ] ) );
		            } else {
		                delete_option( $value['id'] );
		            } 
			        
		        }
	    	
	    	break;
	    	
	    	// Special types
	    	case "tax_rates" :

	    		// Tax rates saving
	    		$tax_rates 			= array();
	    		$tax_classes 		= isset( $_POST['tax_class'] ) ? $_POST['tax_class'] : array();
	    		$tax_countries 		= isset( $_POST['tax_country'] ) ? $_POST['tax_country'] : array();
	    		$tax_rate 			= isset( $_POST['tax_rate'] ) ? $_POST['tax_rate'] : array();
	    		$tax_shipping 		= isset( $_POST['tax_shipping'] ) ? $_POST['tax_shipping'] : array();
	    		$tax_postcode 		= isset( $_POST['tax_postcode'] ) ? $_POST['tax_postcode'] : array();
	    		$tax_compound 		= isset( $_POST['tax_compound'] ) ? $_POST['tax_compound'] : array();
	    		$tax_label 			= isset( $_POST['tax_label'] ) ? $_POST['tax_label'] : array();
				$tax_classes_count	= sizeof( $tax_classes );
				
				for ( $i = 0; $i < $tax_classes_count; $i ++ ) {
	
					if ( isset( $tax_classes[ $i ] ) && isset( $tax_countries[ $i ] ) && isset( $tax_rate[ $i ] ) && is_numeric( $tax_rate[ $i ] ) ) {
	
						$rate = woocommerce_clean( $tax_rate[ $i ] );
						$rate = number_format( $rate, 4, '.', '' );
	
						$class = woocommerce_clean( $tax_classes[ $i ] );
						
						$shipping = empty( $tax_shipping[ $i ] ) ? 'no' : 'yes';
						$compound = empty( $tax_compound[ $i ] ) ? 'no' : 'yes';
	
						// Handle countries
						$counties_array = array();
						$countries = $tax_countries[ $i ];
						if ( $countries ) foreach ( $countries as $country ) {
	
							$country = woocommerce_clean( $country );
							$state = '*';
	
							if ( strstr( $country, ':' ) ) {
								$cr = explode( ':', $country );
								$country = current( $cr );
								$state = end( $cr );
							}
	
							$counties_array[ woocommerce_clean( $country ) ][] = woocommerce_clean( $state );
						}
	
						$tax_rates[] = array(
							'countries' => $counties_array,
							'rate' 		=> $rate,
							'shipping' 	=> $shipping,
							'compound' 	=> $compound,
							'class' 	=> $class,
							'label' 	=> woocommerce_clean( $tax_label[ $i ] )
						);
					}
				}
	
				update_option( 'woocommerce_tax_rates', $tax_rates );
	
	    		// Local tax rates saving
	    		$local_tax_rates 	= array();
	    		$tax_classes 		= isset( $_POST['local_tax_class'] ) ? $_POST['local_tax_class'] : array();
	    		$tax_countries 		= isset( $_POST['local_tax_country'] ) ? $_POST['local_tax_country'] : array();
	    		$tax_location_type 	= isset( $_POST['local_tax_location_type'] ) ? $_POST['local_tax_location_type'] : 'postcode';
	    		$tax_location	 	= isset( $_POST['local_tax_location'] ) ? $_POST['local_tax_location'] : array();
	    		$tax_rate 			= isset( $_POST['local_tax_rate'] ) ? $_POST['local_tax_rate'] : array();
	    		$tax_shipping 		= isset( $_POST['local_tax_shipping'] ) ? $_POST['local_tax_shipping'] : array();
	    		$tax_postcode 		= isset( $_POST['local_tax_postcode'] ) ? $_POST['local_tax_postcode'] : array();
	    		$tax_compound 		= isset( $_POST['local_tax_compound'] ) ? $_POST['local_tax_compound'] : array();
	    		$tax_label 			= isset( $_POST['local_tax_label'] ) ? $_POST['local_tax_label'] : array();
				$tax_classes_count	= sizeof( $tax_classes );
				for ( $i = 0; $i < $tax_classes_count; $i ++ ) {
	
					if ( isset( $tax_classes[ $i ] ) && isset( $tax_countries[ $i ] ) && isset( $tax_rate[ $i ] ) && is_numeric( $tax_rate[ $i ] ) ) {
	
						$rate = woocommerce_clean( $tax_rate[ $i ] );
						$rate = number_format( $rate, 4, '.', '' );
	
						$class = woocommerce_clean( $tax_classes[ $i ] );
	
						if ( ! empty( $tax_shipping[ $i ] ) ) $shipping = 'yes'; else $shipping = 'no';
						if ( ! empty( $tax_compound[ $i ] ) ) $compound = 'yes'; else $compound = 'no';
	
						// Handle country
						$country = woocommerce_clean( $tax_countries[ $i ] );
						$state = '*';
	
						if ( strstr( $country, ':' ) ) {
							$cr = explode( ':', $country );
							$country = current( $cr );
							$state = end( $cr );
						}
	
						// Handle postcodes/cities
						$location_type = $tax_location_type[ $i ] == 'city' ? 'city' : 'postcode';
						$locations = explode( "\n", $tax_location[ $i ] );
						$locations = array_filter( array_map( 'woocommerce_clean', $locations ) );
						
						if ( $location_type == 'city' ) {
							$locations = array_map( 'sanitize_title', $locations );
						}
	
						$local_tax_rates[] = array(
							'country' => $country,
							'state' => $state,
							'location_type' => $location_type,
							'locations' => $locations,
							'rate' => $rate,
							'shipping' => $shipping,
							'compound' => $compound,
							'class' => $class,
							'label' => woocommerce_clean( $tax_label[ $i ] )
						);
					}
				}
	
				update_option( 'woocommerce_local_tax_rates', $local_tax_rates );
			
	    	break;
	    	
	    	case "multi_select_countries" :
	    		
	    		// Get countries array
				if ( isset( $_POST[ $value['id'] ] ) ) 
					$selected_countries = array_map( 'woocommerce_clean', (array) $_POST[ $value['id'] ] ); 
				else 
					$selected_countries = array();
					
				update_option( $value['id'], $selected_countries );
			
	    	break;
	    	
	    	case "image_width" :

		    	if ( isset( $_POST[$value['id'] . '_width'] ) ) {
	              	
	              	update_option( $value['id'] . '_width', woocommerce_clean( $_POST[ $value['id'] . '_width'] ) );
	            	update_option( $value['id'] . '_height', woocommerce_clean( $_POST[ $value['id'] . '_height'] ) );
					
					if ( isset( $_POST[ $value['id'] . '_crop'] ) )
						update_option( $value['id'] . '_crop', 1 );
					else
						update_option( $value['id'].'_crop', 0 );
					
	            } else {
	                update_option( $value['id'] . '_width', $value['std'] );
	            	update_option( $value['id'] . '_height', $value['std'] );
	            	update_option( $value['id'] . '_crop', 1 );
	            }
            
	    	break;
	    	
	    	// Custom handling
	    	default :
	    	
	    		do_action( 'woocommerce_update_option_' . $type, $value );
	    	
	    	break;

    	}
    	
    	// Custom handling
    	do_action( 'woocommerce_update_option', $value );
    	
    }
    return true;
}