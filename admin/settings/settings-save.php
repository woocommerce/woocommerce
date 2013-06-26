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

    // Options to update will be stored here
    $update_options = array();

    // Loop options and get values to save
    foreach ( $options as $value ) {

    	if ( ! isset( $value['id'] ) )
    		continue;

    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

    	// Get the option name
    	$option_value = null;

    	switch ( $type ) {

	    	// Standard types
	    	case "checkbox" :

	    		if ( isset( $_POST[ $value['id'] ] ) ) {
	    			$option_value = 'yes';
	            } else {
	            	$option_value = 'no';
	            }

	    	break;

	    	case "textarea" :

		    	if ( isset( $_POST[$value['id']] ) ) {
		    		$option_value = wp_kses_post( trim( stripslashes( $_POST[ $value['id'] ] ) ) );
	            } else {
	                $option_value = '';
	            }

	    	break;

	    	case "text" :
	    	case 'email':
            case 'number':
	    	case "select" :
	    	case "color" :
            case 'password' :
	    	case "single_select_page" :
	    	case "single_select_country" :
	    	case 'radio' :

	    		if ( $value['id'] == 'woocommerce_price_thousand_sep' || $value['id'] == 'woocommerce_price_decimal_sep' ) {

					// price separators get a special treatment as they should allow a spaces (don't trim)
					if ( isset( $_POST[ $value['id'] ] )  ) {
						$option_value = wp_kses_post( stripslashes( $_POST[ $value['id'] ] ) );
					} else {
		            	$option_value = '';
		            }

	    		} elseif ( $value['id'] == 'woocommerce_price_num_decimals' ) {

					// price separators get a special treatment as they should allow a spaces (don't trim)
					if ( isset( $_POST[ $value['id'] ] )  ) {
						$option_value = absint( $_POST[ $value['id'] ] );
					} else {
		               $option_value = 2;
		            }

	    		} elseif ( $value['id'] == 'woocommerce_hold_stock_minutes' ) {

	    			// Allow > 0 or set to ''
		            if ( ! empty( $_POST[ $value['id'] ] )  ) {
						$option_value = absint( $_POST[ $value['id'] ] );
					} else {
		            	$option_value = '';
		            }

		            wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );

		            if ( $option_value != '' )
		            	wp_schedule_single_event( time() + ( absint( $option_value ) * 60 ), 'woocommerce_cancel_unpaid_orders' );

		        } else {

			       if ( isset( $_POST[$value['id']] ) ) {
		            	$option_value = woocommerce_clean( stripslashes( $_POST[ $value['id'] ] ) );
		            } else {
		                $option_value = '';
		            }

		        }

	    	break;

	    	// Special types
	    	case "multiselect" :
	    	case "multi_select_countries" :

	    		// Get countries array
				if ( isset( $_POST[ $value['id'] ] ) )
					$selected_countries = array_map( 'woocommerce_clean', array_map( 'stripslashes', (array) $_POST[ $value['id'] ] ) );
				else
					$selected_countries = array();

				$option_value = $selected_countries;

	    	break;

	    	case "image_width" :

		    	if ( isset( $_POST[$value['id'] ]['width'] ) ) {

	              	$update_options[ $value['id'] ]['width']  = woocommerce_clean( stripslashes( $_POST[ $value['id'] ]['width'] ) );
	              	$update_options[ $value['id'] ]['height'] = woocommerce_clean( stripslashes( $_POST[ $value['id'] ]['height'] ) );

					if ( isset( $_POST[ $value['id'] ]['crop'] ) )
						$update_options[ $value['id'] ]['crop'] = 1;
					else
						$update_options[ $value['id'] ]['crop'] = 0;

	            } else {
	            	$update_options[ $value['id'] ]['width'] 	= $value['default']['width'];
	            	$update_options[ $value['id'] ]['height'] 	= $value['default']['height'];
	            	$update_options[ $value['id'] ]['crop'] 	= $value['default']['crop'];
	            }

	    	break;

	    	// Custom handling
	    	default :

	    		do_action( 'woocommerce_update_option_' . $type, $value );

	    	break;

    	}

    	if ( ! is_null( $option_value ) ) {
	    	// Check if option is an array
			if ( strstr( $value['id'], '[' ) ) {

				parse_str( $value['id'], $option_array );

	    		// Option name is first key
	    		$option_name = current( array_keys( $option_array ) );

	    		// Get old option value
	    		if ( ! isset( $update_options[ $option_name ] ) )
	    			 $update_options[ $option_name ] = get_option( $option_name, array() );

	    		if ( ! is_array( $update_options[ $option_name ] ) )
	    			$update_options[ $option_name ] = array();

	    		// Set keys and value
	    		$key = key( $option_array[ $option_name ] );

	    		$update_options[ $option_name ][ $key ] = $option_value;

			// Single value
			} else {
				$update_options[ $value['id'] ] = $option_value;
			}
		}

    	// Custom handling
    	do_action( 'woocommerce_update_option', $value );
    }

    // Now save the options
    foreach( $update_options as $name => $value )
    	update_option( $name, $value );

    return true;
}