<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Shipping Method Class.
 *
 * Extended by shipping methods to handle shipping calculations etc.
 *
 * @class       WC_Shipping_Method
 * @version     2.3.0
 * @package     WooCommerce/Abstracts
 * @category    Abstract Class
 * @author      WooThemes
 */
abstract class WC_Shipping_Method extends WC_Settings_API {

	/** @var string Unique ID for the shipping method - must be set. */
	public $id;

	/** @var int Optional instance ID. */
	public $number;

	/** @var string Method title */
	public $method_title;

	/** @var string User set title */
	public $title;

	/**  @var bool True if the method is available. */
	public $availability;

	/** @var array Array of countries this method is enabled for. */
	public $countries    = array();

	/** @var string If 'taxable' tax will be charged for this method (if applicable) */
	public $tax_status   = 'taxable';

	/** @var mixed Fees for the method */
	public $fee          = 0;

	/** @var float Minimum fee for the method */
	public $minimum_fee  = null;

	/** @var bool Enabled for disabled */
	public $enabled      = false;

	/** @var bool Whether the method has settings or not (In WooCommerce > Settings > Shipping) */
	public $has_settings = true;

	/** @var array Features this method supports. */
	public $supports     = array();

	/** @var array This is an array of rates - methods must populate this array to register shipping costs */
	public $rates        = array();

	/**
	 * Whether or not we need to calculate tax on top of the shipping rate.
	 *
	 * @return boolean
	 */
	public function is_taxable() {
		return ( wc_tax_enabled() && $this->tax_status == 'taxable' && ! WC()->customer->is_vat_exempt() );
	}

	/**
	 * Add a rate.
	 *
	 * Add a shipping rate. If taxes are not set they will be calculated based on cost.
	 *
	 * @param array $args (default: array())
	 */
	public function add_rate( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id'        => '',          // ID for the rate
			'label'     => '',          // Label for the rate
			'cost'      => '0',         // Amount or array of costs (per item shipping)
			'taxes'     => '',          // Pass taxes, nothing to have it calculated for you, or 'false' to calc no tax
			'calc_tax'  => 'per_order'  // Calc tax per_order or per_item. Per item needs an array of costs
		) );

		// Id and label are required
		if ( ! $args['id'] || ! $args['label'] ) {
			return;
		}

		// Handle cost
		$total_cost = is_array( $args['cost'] ) ? array_sum( $args['cost'] ) : $args['cost'];
		$taxes      = $args['taxes'];

		// Taxes - if not an array and not set to false, calc tax based on cost and passed calc_tax variable
		// This saves shipping methods having to do complex tax calculations
		if ( ! is_array( $taxes ) && $taxes !== false && $total_cost > 0 && $this->is_taxable() ) {
			$taxes = array();

			switch ( $args['calc_tax'] ) {
				case "per_item" :
					// If we have an array of costs we can look up each items tax class and add tax accordingly
					if ( is_array( $args['cost'] ) ) {

						$cart = WC()->cart->get_cart();

						foreach ( $args['cost'] as $cost_key => $amount ) {
							if ( ! isset( $cart[ $cost_key ] ) ) {
								continue;
							}

							$item_taxes = WC_Tax::calc_shipping_tax( $amount, WC_Tax::get_shipping_tax_rates( $cart[ $cost_key ]['data']->get_tax_class() ) );

							// Sum the item taxes
							foreach ( array_keys( $taxes + $item_taxes ) as $key ) {
								$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
							}
						}

						// Add any cost for the order - order costs are in the key 'order'
						if ( isset( $args['cost']['order'] ) ) {
							$item_taxes = WC_Tax::calc_shipping_tax( $args['cost']['order'], WC_Tax::get_shipping_tax_rates() );

							// Sum the item taxes
							foreach ( array_keys( $taxes + $item_taxes ) as $key ) {
								$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
							}
						}
					}
				break;
				default :
					$taxes = WC_Tax::calc_shipping_tax( $total_cost, WC_Tax::get_shipping_tax_rates() );
				break;
			}
		}

		$this->rates[] = new WC_Shipping_Rate( $args['id'], $args['label'], $total_cost, $taxes, $this->id );
	}

	/**
	 * Check if the shipping method has settings or not.
	 *
	 * @return bool
	 */
	public function has_settings() {
		return $this->has_settings;
	}

	/**
	 * Check if shipping method is available or not.
	 *
	 * @param array $package
	 * @return bool
	 */
	public function is_available( $package ) {
		if ( 'no' == $this->enabled ) {
			return false;
		}

		// Country availability
		$countries = is_array( $this->countries ) ? $this->countries : array();

		switch ( $this->availability ) {
			case 'specific' :
			case 'including' :
				$ship_to_countries = array_intersect( $countries, array_keys( WC()->countries->get_shipping_countries() ) );
			break;

			case 'excluding' :
				$ship_to_countries = array_diff( array_keys( WC()->countries->get_shipping_countries() ), $countries );
			break;

			default :
				$ship_to_countries = array_keys( WC()->countries->get_shipping_countries() );
			break;
		}

		if ( ! in_array( $package['destination']['country'], $ship_to_countries ) ) {
			return false;
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true, $package );
	}

	/**
	 * Return the shipping method title.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_shipping_method_title', $this->title, $this->id );
	}

	/**
	 * Get fee for the shipping method.
	 *
	 * @param mixed $fee
	 * @param mixed $total
	 * @return float
	 */
	public function get_fee( $fee, $total ) {
		if ( strstr( $fee, '%' ) ) {
			$fee = ( $total / 100 ) * str_replace( '%', '', $fee );
		}
		if ( ! empty( $this->minimum_fee ) && $this->minimum_fee > $fee ) {
			$fee = $this->minimum_fee;
		}
		return $fee;
	}

	/**
	 * Check if a shipping method supports a given feature.
	 *
	 * Methods should override this to declare support (or lack of support) for a feature.
	 *
	 * @param $feature string The name of a feature to test support for.
	 * @return bool True if the gateway supports the feature, false otherwise.
	 * @since 1.5.7
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_shipping_method_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}
}
