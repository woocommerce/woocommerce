<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Shipping Method Class.
 *
 * Extended by shipping methods to handle shipping calculations etc.
 *
 * @class       WC_Shipping_Method
 * @version     2.6.0
 * @package     WooCommerce/Abstracts
 * @category    Abstract Class
 * @author      WooThemes
 */
abstract class WC_Shipping_Method extends WC_Settings_API {

	/**
	 * Features this method supports. Possible features used by core:
	 *   - shipping-zones Shipping zone functionality + instances
	 *   - global-settings Non-instance settings screens. Enabled by default for BW compatibility with methods before instances existed.
	 * @var array
	 */
	public $supports     = array( 'global-settings' );

	/**
	 * Unique ID for the shipping method - must be set.
	 * @var string
	 */
	public $id = '';

	/**
	 * Instance ID if used.
	 * @var int
	 */
	protected $instance_id = 0;

	/**
	 * Method title.
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Method description.
	 * @var string
	 */
	public $method_description = '';

	/**
	 * yes or no based on whether the method is enabled.
	 * @var string
	 */
	public $enabled = 'yes';
	
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

	/** @var array This is an array of rates - methods must populate this array to register shipping costs */
	public $rates        = array();

	/**
	 * Constructor.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->instance_id = absint( $instance_id );
	}

	/**
	 * Check if a shipping method supports a given feature.
	 *
	 * Methods should override this to declare support (or lack of support) for a feature.
	 *
	 * @param $feature string The name of a feature to test support for.
	 * @return bool True if the shipping method supports the feature, false otherwise.
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_shipping_method_supports', in_array( $feature, $this->supports ), $feature, $this );
	}

	/**
	 * Does this method have a global settings page?
	 * @return bool
	 */
	public function has_settings() {
		return $this->supports( 'global-settings' );
	}

	/**
	 * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
	 */
	public function calculate_shipping() {}

	/**
	 * Whether or not we need to calculate tax on top of the shipping rate.
	 *
	 * @return boolean
	 */
	public function is_taxable() {
		return wc_tax_enabled() && 'taxable' === $this->tax_status && ! WC()->customer->is_vat_exempt();
	}

	/**
	 * Return the shipping method title.
	 * @since 2.6.0
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'woocommerce_shipping_method_title', $this->method_title, $this );
	}

	/**
	 * Return the shipping method description.
	 * @since 2.6.0
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'woocommerce_shipping_method_description', $this->method_description, $this );
	}

	/**
	 * Return the name of the option in the WP DB.
	 * @return string
	 */
	public function get_option_key() {
		if ( $this->instance_id ) {
			return $this->plugin_id . $this->id . $this->instance_id . '_settings';
		} else {
			return $this->plugin_id . $this->id . '_settings';
		}
	}

	/**
	 * Output the shipping settings screen.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		parent::admin_options();
	}

	/**
	 * Return the shipping title which is user set.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_shipping_method_title', $this->title, $this->id );
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
	 * is_available function.
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
	 * get_fee function.
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
	 * Get setting form fields for instances of this shipping method within zones.
	 * @return array
	 */
	public function get_instance_form_fields() {
		return array();
	}

	/**
	 * Output settings for this instance.
	 * @uses self::get_instance_form_fields()
	 * @since 2.6.0
	 */
	public function instance_options() {
		echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>
		<table class="form-table">
			<?php $this->generate_settings_html( $this->get_instance_form_fields() ); ?>
		</table>
		<?php
	}

	/**
	 * Save the settings for this instance.
	 * @uses self::get_instance_form_fields()
	 * @since 2.6.0
	 * @return bool was anything saved?
	 */
	public function process_instance_options() {
		$this->validate_settings_fields( $this->get_instance_form_fields()  );

		if ( count( $this->errors ) > 0 ) {
			$this->display_errors();
		} else {
			return update_option( $this->plugin_id . $this->id . $this->instance_id . '_settings', $this->sanitized_fields );
		}

		return false;
	}
}
