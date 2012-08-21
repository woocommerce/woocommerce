<?php
/**
 * WooCommerce Shipping Method Class
 *
 * Extended by shipping methods to handle shipping calculations etc.
 *
 * @class 		WC_Shipping_Method
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Method extends WC_Settings_API {

	/** @var string Unique ID for the shipping method - must be set. */
	var $id;

	/** @var int Optional instance ID. */
	var $number;

	/** @var string Method title */
	var $method_title;

	/** @var string User set title */
	var $title;

	/**  @var bool True if the method is available. */
	var $availability;

	/** @var array Array of countries this method is enabled for. */
	var $countries;

	/** @var string If 'taxable' tax will be charged for this method (if applicable) */
	var $tax_status			= 'taxable';

	/** @var mixed Fees for the method */
	var $fee				= 0;

	/** @var float Minimum fee for the method */
	var $minimum_fee		= null;

	/** @var float Min amount (if set) for the cart to use this method */
	var $min_amount			= null;

	/** @var bool Enabled for disabled */
	var $enabled			= false;

	/** @var bool Whether the method has settings or not (In WooCommerce > Settings > Shipping) */
	var $has_settings		= true;

	/** @var array Features this method supports. */
	var $supports			= array();		// Features this method supports.

	/** @var array This is an array of rates - methods must populate this array to register shipping costs */
	var $rates 				= array();

	/**
	 * Add a rate
	 *
	 * Add a shipping rate. If taxes are not set they will be calculated based on cost.
	 *
	 * @access public
	 * @param array $args (default: array())
	 * @return void
	 */
	function add_rate( $args = array() ) {
		global $woocommerce;

		$defaults = array(
			'id' 		=> '',			// ID for the rate
			'label' 	=> '',			// Label for the rate
			'cost' 		=> '0',			// Amount or array of costs (per item shipping)
			'taxes' 	=> '',			// Pass taxes, nothing to have it calculated for you, or 'false' to calc no tax
			'calc_tax'	=> 'per_order'	// Calc tax per_order or per_item. Per item needs an array of costs
		);

		$args = wp_parse_args( $args, $defaults );

		extract( $args );

		// Id and label are required
		if ( ! $id || ! $label ) return;

		// Handle cost
		$total_cost = ( is_array( $cost ) ) ? array_sum( $cost ) : $cost;

		// Taxes - if not an array and not set to false, calc tax based on cost and passed calc_tax variable
		// This saves shipping methods having to do compelex tax calculations
		if ( ! is_array( $taxes ) && $taxes !== false && $total_cost > 0 && get_option( 'woocommerce_calc_taxes' ) == 'yes' && $this->tax_status == 'taxable' ) {

			$_tax 	= new WC_Tax();
			$taxes 	= array();

			switch ( $calc_tax ) {

				case "per_item" :

					// If we have an array of costs we can look up each items tax class and add tax accordingly
					if ( is_array( $cost ) ) {

						$cart = $woocommerce->cart->get_cart();

						foreach ( $cost as $cost_key => $amount ) {

							if ( ! isset( $cart[ $cost_key ] ) )
								continue;

							$_product = $cart[	$cost_key ]['data'];

							$rates = $_tax->get_shipping_tax_rates( $_product->get_tax_class() );
							$item_taxes = $_tax->calc_shipping_tax( $amount, $rates );

							// Sum the item taxes
							foreach ( array_keys( $taxes + $item_taxes ) as $key )
								$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );

						}

						// Add any cost for the order - order costs are in the key 'order'
						if ( isset( $cost['order'] ) ) {

							$rates = $_tax->get_shipping_tax_rates();
							$item_taxes = $_tax->calc_shipping_tax( $cost['order'], $rates );

							// Sum the item taxes
							foreach ( array_keys( $taxes + $item_taxes ) as $key )
								$taxes[ $key ] = ( isset( $item_taxes[ $key ] ) ? $item_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
						}

					}

				break;

				default :

					$rates = $_tax->get_shipping_tax_rates();
					$taxes = $_tax->calc_shipping_tax( $total_cost, $rates );

				break;

			}

		}

		$this->rates[] = new WC_Shipping_Rate( $id, $label, $total_cost, $taxes );
	}

	/**
	 * has_settings function.
	 *
	 * @access public
	 * @return void
	 */
	function has_settings() {
		if ( $this->has_settings )
			return true;
	}

    /**
     * is_available function.
     *
     * @access public
     * @param array $package
     * @return bool
     */
    function is_available( $package ) {
    	global $woocommerce;

    	if ( $this->enabled == "no" )
    		return false;

		if ( isset( $woocommerce->cart->cart_contents_total ) && isset( $this->min_amount ) && $this->min_amount && $this->min_amount > $woocommerce->cart->cart_contents_total )
			return false;

		$ship_to_countries = '';

		if ( $this->availability == 'specific' ) :
			$ship_to_countries = $this->countries;
		else :
			if ( get_option( 'woocommerce_allowed_countries' ) == 'specific' ) :
				$ship_to_countries = get_option( 'woocommerce_specific_allowed_countries' );
			endif;
		endif;

		if ( is_array( $ship_to_countries ) ) :
			if ( ! in_array( $package['destination']['country'], $ship_to_countries ) ) return false;
		endif;

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', true, $package );
    }

	/**
	 * Return the gateways title
	 *
	 * @access public
	 * @return void
	 */
	function get_title() {
		return apply_filters( 'woocommerce_shipping_method_title', $this->title, $this->id );
	}


    /**
     * get_fee function.
     *
     * @access public
     * @param mixed $fee
     * @param mixed $total
     * @return void
     */
    function get_fee( $fee, $total ) {
		if ( strstr( $fee, '%' ) ) :
			$fee = ( $total / 100 ) * str_replace( '%', '', $fee );
		endif;
		if ( ! empty( $this->minimum_fee ) && $this->minimum_fee > $fee ) $fee = $this->minimum_fee;
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
	function supports( $feature ) {
		return apply_filters( 'woocommerce_shipping_method_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}
}

/**
 * WooCommerce Shipping Rate Class
 *
 * Simple Class for storing rates.
 *
 * @class 		WC_Shipping_Rate
 * @version		1.6.4
 * @package		WooCommerce/Classes/Shipping
 * @author 		WooThemes
 */
class WC_Shipping_Rate {

	var $id 	= '';
	var $label 	= '';
	var $cost 	= 0;
	var $taxes 	= array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $id
	 * @param mixed $label
	 * @param mixed $cost
	 * @param mixed $taxes
	 * @return void
	 */
	public function __construct( $id, $label, $cost, $taxes ) {
		$this->id 		= $id;
		$this->label 	= $label;
		$this->cost 	= $cost;
		$this->taxes 	= $taxes ? $taxes : array();
	}

	/**
	 * get_shipping_tax function.
	 *
	 * @access public
	 * @return array
	 */
	function get_shipping_tax() {
		$taxes = 0;
		if ( $this->taxes && sizeof( $this->taxes ) > 0 )
			$taxes = array_sum( $this->taxes );
		return $taxes;
	}
}

/**
 * woocommerce_shipping_method class.
 *
 * @package		WooCommerce/Classes/Shipping
 * @extends 	WC_Shipping_Method
 * @deprecated 	1.4
 */
class woocommerce_shipping_method extends WC_Shipping_Method {
	public function __construct() {
		_deprecated_function( 'woocommerce_shipping_method', '1.4', 'WC_Shipping_Method()' );
	}
}