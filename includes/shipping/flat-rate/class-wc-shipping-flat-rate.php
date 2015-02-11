<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Flat Rate Shipping Method
 *
 * A simple shipping method for a flat fee per item or per order
 *
 * @class 		WC_Shipping_Flat_Rate
 * @version		2.0.0
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Flat_Rate extends WC_Shipping_Method {

	/**
	 * Stores an array of rates
	 * @var array
	 */
	public $flat_rates = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->id 						= 'flat_rate';
		$this->method_title 			= __( 'Flat Rate', 'woocommerce' );
		$this->flat_rate_option 		= 'woocommerce_flat_rates';
		$this->method_description 		= __( 'Flat rates let you define a standard rate per item, or per order.', 'woocommerce' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_flat_rates' ) );
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'save_default_costs' ) );

		$this->init();
	}

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 		  = $this->get_option( 'title' );
		$this->availability   = $this->get_option( 'availability' );
		$this->countries 	  = $this->get_option( 'countries' );
		$this->type 		  = $this->get_option( 'type' );
		$this->tax_status	  = $this->get_option( 'tax_status' );
		$this->cost 		  = $this->get_option( 'cost' );
		$this->cost_per_order = $this->get_option( 'cost_per_order' );
		$this->fee 			  = $this->get_option( 'fee' );
		$this->minimum_fee 	  = $this->get_option( 'minimum_fee' );
		$this->options 		  = array_filter( (array) explode( "\n", $this->get_option( 'options' ) ) );

		// Load Flat rates
		$this->get_flat_rates();
	}


	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = include( 'includes/settings-flat-rate.php' );
	}

	/**
	 * calculate_shipping function.
	 *
	 * @param array $package (default: array())
	 */
	public function calculate_shipping( $package = array() ) {
		$this->rates    = array();
		$cost_per_order = ! empty( $this->cost_per_order ) ? $this->cost_per_order : 0;
		$rate           = array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => 0,
		);

		if ( method_exists( $this, $this->type . '_shipping' ) ) {
			$costs = call_user_func( array( $this, $this->type . '_shipping' ), $package );

			if ( ! is_null( $costs ) || $costs > 0 ) {
				if ( is_array( $costs ) ) {
					$costs['order'] = $cost_per_order;
				} else {
					$costs += $cost_per_order;
				}

				$rate['cost']     = $costs;
				$rate['calc_tax'] = 'per_' . $this->type;

				$this->add_rate( $rate );
			}
		}

		$this->calculate_extra_shipping( $rate, $package );
	}

	/**
	 * Get items in package
	 * @param  array $package
	 * @return int
	 */
	public function get_package_item_qty( $package ) {
		$total_quantity = 0;

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['quantity'] > 0 && $values['data']->needs_shipping() ) {
				$total_quantity += $values['quantity'];
			}
		}

		return $total_quantity;
	}

	/**
	 * Get extra cost
	 * @param  string $cost_string
	 * @param  string $type
	 * @param  array $package
	 * @return float
	 */
	public function get_extra_cost( $cost_string, $type, $package ) {
		$cost         = $cost_string;
		$cost_percent = false;
		$pattern      =
			'/' .           // start regex
			'(\d+\.?\d*)' . // capture digits, optionally capture a `.` and more digits
			'\s*' .         // match whitespace
			'(\+|-)' .      // capture the operand
			'\s*'.          // match whitespace
			'(\d+\.?\d*)'.  // capture digits, optionally capture a `.` and more digits
			'\%/';          // match the percent sign & end regex
		if ( preg_match( $pattern, $cost_string, $this_cost_matches ) ) {
			$cost_operator = $this_cost_matches[2];
			$cost_percent  = $this_cost_matches[3] / 100;
			$cost          = $this_cost_matches[1];
		}

		switch ( $type ) {
			case 'class' :
				$cost = $cost * sizeof( $this->find_shipping_classes( $package ) );
			break;
			case 'item' :
				$cost = $cost * $this->get_package_item_qty( $package );
			break;
		}

		if ( $cost_percent ) {
			switch ( $type ) {
				case 'class' :
					$shipping_classes = $this->find_shipping_classes( $package );
					foreach ( $shipping_classes as $shipping_class => $items ){
						foreach ( $items as $item_id => $values ) {
							$cost = $this->calc_percentage_adjustment( $cost, $cost_percent, $cost_operator, $values['line_total'] );
						}
					}
				break;
				case 'item' :
					foreach ( $package['contents'] as $item_id => $values ) {
						$cost = $this->calc_percentage_adjustment( $cost, $cost_percent, $cost_operator, $values['line_total'] );
					}
				break;
				case  'order' :
					$cost = $this->calc_percentage_adjustment( $cost, $cost_percent, $cost_operator, $package['contents_cost'] );
				break;
			}
		}

		return $cost;
	}

	/**
	 * Adds extra calculated flat rates
	 *
	 * Additonal rates defined like this:
	 * 	Option Name | Additional Cost [+- Percents%] | Per Cost Type (order, class, or item)
	 */
	public function calculate_extra_shipping( $rate, $package ) {
		foreach ( $this->options as $option ) {
			$this_option = array_map( 'trim', explode( WC_DELIMITER, $option ) );

			if ( sizeof( $this_option ) !== 3 ) {
				continue;
			}

			$extra_rate          = $rate;
			$extra_rate['id']    = $this->id . ':' . urldecode( sanitize_title( $this_option[0] ) );
			$extra_rate['label'] = $this_option[0];
			$extra_cost          = $this->get_extra_cost( $this_option[1], $this_option[2], $package );

			if ( is_array( $extra_rate['cost'] ) ) {
				$extra_rate['cost']['order'] = $extra_rate['cost']['order'] + $extra_cost;
			} else {
				$extra_rate['cost'] += $extra_cost;
			}

			$this->add_rate( $extra_rate );
		}
	}

	/**
	 * Calculate the percentage adjustment for each shipping rate.
	 *
	 * @access public
	 * @param  float  $cost
	 * @param  float  $percent_adjustment
	 * @param  string $percent_operator
	 * @param  float  $base_price
	 * @return float
	 */
	public function calc_percentage_adjustment( $cost, $percent_adjustment, $percent_operator, $base_price ) {
		if ( '+' == $percent_operator ) {
			$cost += $percent_adjustment * $base_price;
		} else {
			$cost -= $percent_adjustment * $base_price;
		}
		return $cost;
	}

	/**
	 * order_shipping function.
	 *
	 * @param array $package
	 * @return float
	 */
	public function order_shipping( $package ) {
		$cost 	= null;
		$fee 	= null;

		if ( sizeof( $this->flat_rates ) > 0 ) {

			$found_shipping_classes = $this->find_shipping_classes( $package );

			// Find most expensive class (if found)
			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
					if ( $this->flat_rates[ $shipping_class ]['cost'] > $cost ) {
						$cost 	= $this->flat_rates[ $shipping_class ]['cost'];
						$fee	= $this->flat_rates[ $shipping_class ]['fee'];
					}

				// No matching classes so use defaults
				} elseif ( $this->cost > $cost ) {
					$cost 	= $this->cost;
					$fee	= $this->fee;
				}
			}
		}

		// Default rates if set
		if ( is_null( $cost ) && $this->cost !== '' ) {
			$cost 	= $this->cost;
			$fee 	= $this->fee;
		} elseif ( is_null( $cost ) ) {
			// Set rates to 0 if nothing is set by the user
			$cost 	= 0;
			$fee 	= 0;
		}

		// Shipping for whole order
		return $cost + $this->get_fee( $fee, $package['contents_cost'] );
	}

	/**
	 * class_shipping function.
	 *
	 * @access public
	 * @param array $package
	 * @return float
	 */
	public function class_shipping( $package ) {
		$cost 	= null;
		$fee 	= null;
		$matched = false;

		if ( sizeof( $this->flat_rates ) > 0 || $this->cost !== '' ) {

			// Find shipping classes for products in the cart.
			$found_shipping_classes = $this->find_shipping_classes( $package );

			//  Store prices too, so we can calc a fee for the class.
			$found_shipping_classes_values = array();

			foreach ( $found_shipping_classes as $shipping_class => $products ) {
				if ( ! isset( $found_shipping_classes_values[ $shipping_class ] ) ) {
					$found_shipping_classes_values[ $shipping_class ] = 0;
				}

				foreach ( $products as $product ) {
					$found_shipping_classes_values[ $shipping_class ] += $product['data']->get_price() * $product['quantity'];
				}
			}

			// For each found class, add up the costs and fees
			foreach ( $found_shipping_classes_values as $shipping_class => $class_price ) {
				if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
					$cost 	+= $this->flat_rates[ $shipping_class ]['cost'];
					$fee	+= $this->get_fee( $this->flat_rates[ $shipping_class ]['fee'], $class_price );
					$matched = true;
				} elseif ( $this->cost !== '' ) {
					// Class not set so we use default rate if its set
					$cost 	+= $this->cost;
					$fee	+= $this->get_fee( $this->fee, $class_price );
					$matched = true;
				}
			}
		}

		// Total
		if ( $matched ) {
			return $cost + $fee;
		} else {
			return null;
		}
	}

	/**
	 * item_shipping function.
	 *
	 * @access public
	 * @param array $package
	 * @return array
	 */
	public function item_shipping( $package ) {
		// Per item shipping so we pass an array of costs (per item) instead of a single value
		$costs = array();

		$matched = false;

		// Shipping per item
		foreach ( $package['contents'] as $item_id => $values ) {
			$_product = $values['data'];

			if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
				$shipping_class = $_product->get_shipping_class();

				$fee = $cost = 0;

				if ( isset( $this->flat_rates[ $shipping_class ] ) ) {
					$cost 	= $this->flat_rates[ $shipping_class ]['cost'];
					$fee	= $this->get_fee( $this->flat_rates[ $shipping_class ]['fee'], $_product->get_price() );
					$matched = true;
				} elseif ( $this->cost !== '' ) {
					$cost 	= $this->cost;
					$fee	= $this->get_fee( $this->fee, $_product->get_price() );
					$matched = true;
				}

				$costs[ $item_id ] = ( ( $cost + $fee ) * $values['quantity'] );
			}
		}

		if ( $matched ) {
			return $costs;
		} else {
			return null;
		}
	}

	/**
	 * Finds and returns shipping classes and the products with said class.
	 *
	 * @access public
	 * @param mixed $package
	 * @return array
	 */
	public function find_shipping_classes( $package ) {
		$found_shipping_classes = array();

		foreach ( $package['contents'] as $item_id => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$found_class = $values['data']->get_shipping_class();

				if ( ! isset( $found_shipping_classes[ $found_class ] ) ) {
					$found_shipping_classes[ $found_class ] = array();
				}

				$found_shipping_classes[ $found_class ][ $item_id ] = $values;
			}
		}

		return $found_shipping_classes;
	}

	/**
	 * validate_additional_costs_field function.
	 *
	 * @access public
	 * @param mixed $key
	 * @return bool
	 */
	public function validate_additional_costs_table_field( $key ) {
		return false;
	}

	/**
	 * generate_additional_costs_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function generate_additional_costs_table_html() {
		ob_start();
		include( 'includes/html-extra-costs.php' );
		return ob_get_clean();
	}

	/**
	 * process_flat_rates function.
	 */
	public function process_flat_rates() {
		$flat_rates      = array();
		$flat_rate_class = isset( $_POST[ $this->id . '_class'] ) ? array_map( 'wc_clean', $_POST[ $this->id . '_class'] ) : array();

		for ( $i = 0; $i <= max( array_merge( array( 0 ), array_keys( $flat_rate_class ) ) ); $i ++ ) {
			if ( empty( $flat_rate_class[ $i ] ) ) {
				continue;
			}

			$cost = wc_format_decimal( $_POST[ $this->id . '_cost'][ $i ] );
			$fee  = wc_clean( $_POST[ $this->id . '_fee'][ $i ] );

			if ( ! strstr( $fee, '%' ) ) {
				$fee = wc_format_decimal( $fee );
			}

			$flat_rates[ urldecode( sanitize_title( $flat_rate_class[ $i ] ) ) ] = array(
				'cost' => $cost,
				'fee'  => $fee
			);
		}

		update_option( $this->flat_rate_option, $flat_rates );

		$this->get_flat_rates();
	}

	/**
	 * save_default_costs function.
	 *
	 * @param array $fields
	 * @return array
	 */
	public function save_default_costs( $fields ) {
	 	$fields['cost'] = wc_format_decimal( $_POST['default_cost'] );
		$fields['fee']  = wc_clean( $_POST['default_fee'] );

	 	if ( ! strstr( $fields['fee'], '%' ) ) {
			$fields['fee'] = wc_format_decimal( $fields['fee'] );
		}

	 	return $fields;
	}

	/**
	 * get_flat_rates function.
	 */
	public function get_flat_rates() {
		$this->flat_rates = array_filter( (array) get_option( $this->flat_rate_option ) );
	}
}
