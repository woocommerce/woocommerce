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
	 * - shipping-zones Shipping zone functionality + instances
	 * - instance-settings Instance settings screens.
	 * - settings Non-instance settings screens. Enabled by default for BW compatibility with methods before instances existed.
	 * - instance-settings-modal Allows the instance settings to be loaded within a modal in the zones UI.
	 * @var array
	 */
	public $supports = array( 'settings' );

	/**
	 * Unique ID for the shipping method - must be set.
	 * @var string
	 */
	public $id = '';

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

	/**
	 * Shipping method title for the frontend.
	 * @var string
	 */
	public $title;

	/**
	 * This is an array of rates - methods must populate this array to register shipping costs.
	 * @var array
	 */
	public $rates = array();

	/**
	 * If 'taxable' tax will be charged for this method (if applicable).
	 * @var string
	 */
	public $tax_status = 'taxable';

	/**
	 * Fee for the method (if applicable).
	 * @var string
	 */
	public $fee = null;

	/**
	 * Minimum fee for the method (if applicable).
	 * @var string
	 */
	public $minimum_fee = null;

	/**
	 * Instance ID if used.
	 * @var int
	 */
	public $instance_id = 0;

	/**
	 * Instance form fields.
	 * @var array
	 */
	public $instance_form_fields = array();

	/**
	 * Instance settings.
	 * @var array
	 */
	public $instance_settings = array();

	/**
	 * Availability - legacy. Used for method Availability.
	 * No longer useful for instance based shipping methods.
	 * @deprecated 2.6.0
	 * @var string
	 */
	public $availability;

	/**
	 * Availability countries - legacy. Used for method Availability.
	 * No longer useful for instance based shipping methods.
	 * @deprecated 2.6.0
	 * @var array
	 */
	public $countries = array();

	/**
	 * Constructor.
	 * @param int $instance_id
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
	 * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
	 */
	public function calculate_shipping( $package = array() ) {}

	/**
	 * Whether or not we need to calculate tax on top of the shipping rate.
	 * @return boolean
	 */
	public function is_taxable() {
		return wc_tax_enabled() && 'taxable' === $this->tax_status && ! WC()->customer->is_vat_exempt();
	}

	/**
	 * Whether or not this method is enabled in settings.
	 * @since 2.6.0
	 * @return boolean
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Return the shipping method instance ID.
	 * @since 2.6.0
	 * @return int
	 */
	public function get_instance_id() {
		return $this->instance_id;
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
	 * Return the shipping title which is user set.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_shipping_method_title', $this->title, $this->id );
	}

	/**
	 * Return calculated rates for a package.
	 * @since 2.6.0
	 * @param object $package
	 * @return array
	 */
	public function get_rates_for_package( $package ) {
		$this->rates = array();
		if ( $this->is_available( $package ) && ( empty( $package['ship_via'] ) || in_array( $this->id, $package['ship_via'] ) ) ) {
			$this->calculate_shipping( $package );
		}
		return $this->rates;
	}

	/**
	 * Returns a rate ID based on this methods ID and instance, with an optional
	 * suffix if distinguishing between multiple rates.
	 * @since 2.6.0
	 * @param string $suffix
	 * @return string
	 */
	public function get_rate_id( $suffix = '' ) {
		$rate_id = array( $this->id );

		if ( $this->instance_id ) {
			$rate_id[] = $this->instance_id;
		}

		if ( $suffix ) {
			$rate_id[] = $suffix;
		}

		return implode( ':', $rate_id );
	}

	/**
	 * Add a shipping rate. If taxes are not set they will be calculated based on cost.
	 * @param array $args (default: array())
	 */
	public function add_rate( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id'        => $this->get_rate_id(), // ID for the rate. If not passed, this id:instance default will be used.
			'label'     => '', // Label for the rate
			'cost'      => '0', // Amount or array of costs (per item shipping)
			'taxes'     => '', // Pass taxes, or leave empty to have it calculated for you, or 'false' to disable calculations
			'calc_tax'  => 'per_order', // Calc tax per_order or per_item. Per item needs an array of costs
			'meta_data' => array(), // Array of misc meta data to store along with this rate - key value pairs.
			'package'   => false, // Package array this rate was generated for @since 2.6.0
		) );

		// ID and label are required
		if ( ! $args['id'] || ! $args['label'] ) {
			return;
		}

		// Total up the cost
		$total_cost = is_array( $args['cost'] ) ? array_sum( $args['cost'] ) : $args['cost'];
		$taxes      = $args['taxes'];

		// Taxes - if not an array and not set to false, calc tax based on cost and passed calc_tax variable. This saves shipping methods having to do complex tax calculations.
		if ( ! is_array( $taxes ) && $taxes !== false && $total_cost > 0 && $this->is_taxable() ) {
			$taxes = 'per_item' === $args['calc_tax'] ? $this->get_taxes_per_item( $args['cost'] ) : WC_Tax::calc_shipping_tax( $total_cost, WC_Tax::get_shipping_tax_rates() );
		}

		// Round the total cost after taxes have been calculated.
		$total_cost = wc_format_decimal( $total_cost, wc_get_price_decimals() );

		// Create rate object
		$rate = new WC_Shipping_Rate( $args['id'], $args['label'], $total_cost, $taxes, $this->id );

		if ( ! empty( $args['meta_data'] ) ) {
			foreach ( $args['meta_data'] as $key => $value ) {
				$rate->add_meta_data( $key, $value );
			}
		}

		// Store package data
		if ( $args['package'] ) {
			$items_in_package = array();
			foreach ( $args['package']['contents'] as $item ) {
				$product = $item['data'];
				$items_in_package[] = $product->get_title() . ' &times; ' . $item['quantity'];
			}
			$rate->add_meta_data( __( 'Items', 'woocommerce' ), implode( ', ', $items_in_package ) );
		}

		$this->rates[ $args['id'] ] = $rate;
	}

	/**
	 * Calc taxes per item being shipping in costs array.
	 * @since 2.6.0
	 * @access protected
	 * @param  array $costs
	 * @return array of taxes
	 */
	protected function get_taxes_per_item( $costs ) {
		$taxes = array();

		// If we have an array of costs we can look up each items tax class and add tax accordingly
		if ( is_array( $costs ) ) {

			$cart = WC()->cart->get_cart();

			foreach ( $costs as $cost_key => $amount ) {
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
			if ( isset( $costs['order'] ) ) {
				$item_taxes = WC_Tax::calc_shipping_tax( $costs['order'], WC_Tax::get_shipping_tax_rates() );

				// Sum the item taxes
				foreach ( array_keys( $taxes + $item_taxes ) as $key ) {
					$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
				}
			}
		}

		return $taxes;
	}

	/**
	 * Is this method available?
	 * @param array $package
	 * @return bool
	 */
	public function is_available( $package ) {
		$available = $this->is_enabled();

		// Country availability (legacy, for non-zone based methods)
		if ( ! $this->instance_id && $available ) {
			$countries = is_array( $this->countries ) ? $this->countries : array();

			switch ( $this->availability ) {
				case 'specific' :
				case 'including' :
					$available = in_array( $package['destination']['country'], array_intersect( $countries, array_keys( WC()->countries->get_shipping_countries() ) ) );
				break;
				case 'excluding' :
					$available = in_array( $package['destination']['country'], array_diff( array_keys( WC()->countries->get_shipping_countries() ), $countries ) );
				break;
				default :
					$available = in_array( $package['destination']['country'], array_keys( WC()->countries->get_shipping_countries() ) );
				break;
			}
		}

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $available, $package );
	}

	/**
	 * Get fee to add to shipping cost.
	 * @param string|float $fee
	 * @param float $total
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
	 * Does this method have a settings page?
	 * @return bool
	 */
	public function has_settings() {
		return $this->instance_id ? $this->supports( 'instance-settings' ) : $this->supports( 'settings' );
	}

	/**
	 * Return admin options as a html string.
	 * @return string
	 */
	public function get_admin_options_html() {
		if ( $this->instance_id ) {
			$settings_html = $this->generate_settings_html( $this->get_instance_form_fields(), false );
		} else {
			$settings_html = $this->generate_settings_html( $this->get_form_fields(), false );
		}

		return '<table class="form-table">' . $settings_html . '</table>';
	}

	/**
	 * Output the shipping settings screen.
	 */
	public function admin_options() {
		if ( ! $this->instance_id ) {
			echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		}
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		echo $this->get_admin_options_html();
	}

	/**
	 * get_option function.
	 *
	 * Gets and option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key
	 * @param  mixed  $empty_value
	 * @return mixed  The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) {
		// Instance options take priority over global options
		if ( array_key_exists( $key, $this->get_instance_form_fields() ) ) {
			return $this->get_instance_option( $key, $empty_value );
		}

		// Return global option
		return parent::get_option( $key, $empty_value );
	}

	/**
	 * Gets an option from the settings API, using defaults if necessary to prevent undefined notices.
	 *
	 * @param  string $key
	 * @param  mixed  $empty_value
	 * @return mixed  The value specified for the option or a default value for the option.
	 */
	public function get_instance_option( $key, $empty_value = null ) {
		if ( empty( $this->instance_settings ) ) {
			$this->init_instance_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->instance_settings[ $key ] ) ) {
			$form_fields                     = $this->get_instance_form_fields();
			$this->instance_settings[ $key ] = $this->get_field_default( $form_fields[ $key ] );
		}

		if ( ! is_null( $empty_value ) && '' === $this->instance_settings[ $key ] ) {
			$this->instance_settings[ $key ] = $empty_value;
		}

		return $this->instance_settings[ $key ];
	}

	/**
	 * Get settings fields for instances of this shipping method (within zones).
	 * Should be overridden by shipping methods to add options.
	 * @since 2.6.0
	 * @return array
	 */
	public function get_instance_form_fields() {
		return apply_filters( 'woocommerce_shipping_instance_form_fields_' . $this->id, array_map( array( $this, 'set_defaults' ), $this->instance_form_fields ) );
	}

	/**
	 * Return the name of the option in the WP DB.
	 * @since 2.6.0
	 * @return string
	 */
	public function get_instance_option_key() {
		return $this->instance_id ? $this->plugin_id . $this->id . '_' . $this->instance_id . '_settings' : '';
	}

	/**
	 * Initialise Settings for instances.
	 * @since 2.6.0
	 */
	public function init_instance_settings() {
		$this->instance_settings = get_option( $this->get_instance_option_key(), null );

		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->instance_settings ) ) {
			$form_fields             = $this->get_instance_form_fields();
			$this->instance_settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
		}
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 * @since 2.6.0
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		if ( $this->instance_id ) {
			$this->init_instance_settings();

			$post_data = $this->get_post_data();

			foreach ( $this->get_instance_form_fields() as $key => $field ) {
				if ( 'title' !== $this->get_field_type( $field ) ) {
					try {
						$this->instance_settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
					} catch ( Exception $e ) {
						$this->add_error( $e->getMessage() );
					}
				}
			}

			return update_option( $this->get_instance_option_key(), apply_filters( 'woocommerce_shipping_' . $this->id . '_instance_settings_values', $this->instance_settings, $this ) );
		} else {
			return parent::process_admin_options();
		}
	}
}
