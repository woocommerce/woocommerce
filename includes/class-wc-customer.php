<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Customer
 *
 * The WooCommerce customer class handles storage of the current customer's data, such as location.
 *
 * @class    WC_Customer
 * @version  2.3.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 *
 * @property string $country
 * @property string $state
 * @property string $postcode
 * @property string $city
 * @property string $address_1
 * @property string $address_2
 * @property string $shipping_country
 * @property string $shipping_state
 * @property string $shipping_postcode
 * @property string $shipping_city
 * @property string $shipping_address_1
 * @property string $shipping_address_2
 * @property string $is_vat_exempt
 * @property string $calculated_shipping
 */
class WC_Customer {

	/**
	 * Stores customer data
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Stores bool when data is changed
	 *
	 * @var bool
	 */
	private $_changed = false;

	/**
	 * Constructor for the customer class loads the customer data.
	 *
	 */
	public function __construct() {
		$this->_data = (array) WC()->session->get( 'customer' );

		// No data - set defaults
		if ( empty( $this->_data ) ) {
			$this->set_default_data();
		}

		// When leaving or ending page load, store data
		add_action( 'shutdown', array( $this, 'save_data' ), 10 );
	}

	/**
	 * Save data function.
	 */
	public function save_data() {
		if ( $this->_changed ) {
			WC()->session->set( 'customer', $this->_data );
		}
	}

	/**
	 * __set function.
	 *
	 * @param mixed $property
	 * @return bool
	 */
	public function __isset( $property ) {
		if ( 'address' === $property ) {
			$property = 'address_1';
		}
		if ( 'shipping_address' === $property ) {
			$property = 'shipping_address_1';
		}
		return isset( $this->_data[ $property ] );
	}

	/**
	 * __get function.
	 *
	 * @param string $property
	 * @return string
	 */
	public function __get( $property ) {
		if ( 'address' === $property ) {
			$property = 'address_1';
		}
		if ( 'shipping_address' === $property ) {
			$property = 'shipping_address_1';
		}
		return isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : '';
	}

	/**
	 * __set function.
	 *
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set( $property, $value ) {
		if ( 'address' === $property ) {
			$property = 'address_1';
		}
		if ( 'shipping_address' === $property ) {
			$property = 'shipping_address_1';
		}
		$this->_data[ $property ] = $value;
		$this->_changed = true;
	}

	/**
	 * Get default country for a customer
	 *
	 * @return string
	 */
	public function get_default_country() {
		$default = wc_get_customer_default_location();
		return $default['country'];
	}

	/**
	 * Get default state for a customer
	 *
	 * @return string
	 */
	public function get_default_state() {
		$default = wc_get_customer_default_location();
		return $default['state'];
	}

	/**
	 * has_calculated_shipping function.
	 *
	 * @return bool
	 */
	public function has_calculated_shipping() {
		return ! empty( $this->calculated_shipping );
	}

	/**
	 * Set customer address to match shop base address.
	 */
	public function set_to_base() {
		$this->country  = $this->get_default_country();
		$this->state    = $this->get_default_state();
		$this->postcode = '';
		$this->city     = '';
	}

	/**
	 * Set customer shipping address to base address.
	 */
	public function set_shipping_to_base() {
		$this->shipping_country  = $this->get_default_country();
		$this->shipping_state    = $this->get_default_state();
		$this->shipping_postcode = '';
		$this->shipping_city     = '';
	}

	/**
	 * Is customer outside base country (for tax purposes)?
	 *
	 * @return bool
	 */
	public function is_customer_outside_base() {
		list( $country, $state ) = $this->get_taxable_address();

		if ( $country ) {

			$default = wc_get_base_location();

			if ( $default['country'] !== $country ) {
				return true;
			}

			if ( $default['state'] && $default['state'] !== $state ) {
				return true;
			}

		}

		return false;
	}

	/**
	 * Is the user a paying customer?
	 *
	 * @return bool
	 */
	function is_paying_customer( $user_id ) {
		return '1' === get_user_meta( $user_id, 'paying_customer', true );
	}

	/**
	 * Is customer VAT exempt?
	 *
	 * @return bool
	 */
	public function is_vat_exempt() {
		return ( ! empty( $this->is_vat_exempt ) ) ? true : false;
	}

	/**
	 * Gets the state from the current session.
	 *
	 * @return string
	 */
	public function get_state() {
		return $this->state;
	}

	/**
	 * Gets the country from the current session
	 *
	 * @return string
	 */
	public function get_country() {
		return $this->country;
	}

	/**
	 * Gets the postcode from the current session.
	 *
	 * @return string
	 */
	public function get_postcode() {
		return empty( $this->postcode ) ? '' : wc_format_postcode( $this->postcode, $this->get_country() );
	}

	/**
	 * Get the city from the current session.
	 *
	 * @return string
	 */
	public function get_city() {
		return $this->city;
	}

	/**
	 * Gets the address from the current session.
	 *
	 * @return string
	 */
	public function get_address() {
		return $this->address_1;
	}

	/**
	 * Gets the address_2 from the current session.
	 *
	 * @return string
	 */
	public function get_address_2() {
		return $this->address_2;
	}

	/**
	 * Gets the state from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->shipping_state;
	}

	/**
	 * Gets the country from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->shipping_country;
	}

	/**
	 * Gets the postcode from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_postcode() {
		return empty( $this->shipping_postcode ) ? '' : wc_format_postcode( $this->shipping_postcode, $this->get_shipping_country() );
	}

	/**
	 * Gets the city from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->shipping_city;
	}

	/**
	 * Gets the address from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->shipping_address_1;
	}

	/**
	 * Gets the address_2 from the current session.
	 *
	 * @return string
	 */
	public function get_shipping_address_2() {
		return $this->shipping_address_2;
	}

	/**
	 * get_taxable_address function.
	 *
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( true == apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && WC()->cart->needs_shipping() && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'local_pickup' ) ) ) ) > 0 ) {
			$tax_based_on = 'base';
		}

		if ( 'base' === $tax_based_on ) {

			$country  = WC()->countries->get_base_country();
			$state    = WC()->countries->get_base_state();
			$postcode = WC()->countries->get_base_postcode();
			$city     = WC()->countries->get_base_city();

		} elseif ( 'billing' === $tax_based_on ) {
			$country  = $this->get_country();
			$state    = $this->get_state();
			$postcode = $this->get_postcode();
			$city     = $this->get_city();

		} else {
			$country  = $this->get_shipping_country();
			$state    = $this->get_shipping_state();
			$postcode = $this->get_shipping_postcode();
			$city     = $this->get_shipping_city();
		}

		return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city ) );
	}

	/**
	 * Set default data for a customer
	 */
	public function set_default_data( $get_user_profile_data = true ) {
		$this->_data = array(
			'postcode'            => '',
			'city'                => '',
			'address_1'           => '',
			'address_2'           => '',
			'state'               => '',
			'country'             => '',
			'shipping_postcode'   => '',
			'shipping_city'       => '',
			'shipping_address_1'  => '',
			'shipping_address_2'  => '',
			'shipping_state'      => '',
			'shipping_country'    => '',
			'is_vat_exempt'       => false,
			'calculated_shipping' => false
		);

		if ( is_user_logged_in() && $get_user_profile_data ) {
			foreach ( $this->_data as $key => $value ) {
				$meta_value          = get_user_meta( get_current_user_id(), ( false === strstr( $key, 'shipping_' ) ? 'billing_' : '' ) . $key, true );
				$this->_data[ $key ] = $meta_value ? $meta_value : $this->_data[ $key ];
			}
		}

		if ( empty( $this->_data['country'] ) ) {
			$this->_data['country'] = $this->get_default_country();
		}

		if ( empty( $this->_data['shipping_country'] ) ) {
			$this->_data['shipping_country'] = $this->_data['country'];
		}

		if ( empty( $this->_data['state'] ) ) {
			$this->_data['state'] = $this->get_default_state();
		}

		if ( empty( $this->_data['shipping_state'] ) ) {
			$this->_data['shipping_state'] = $this->_data['state'];
		}
	}

	/**
	 * Sets session data for the location.
	 *
	 * @param string $country
	 * @param string $state
	 * @param string $postcode (default: '')
	 * @param string $city (default: '')
	 */
	public function set_location( $country, $state, $postcode = '', $city = '' ) {
		$this->country  = $country;
		$this->state    = $state;
		$this->postcode = $postcode;
		$this->city     = $city;
	}

	/**
	 * Sets session data for the country.
	 *
	 * @param mixed $country
	 */
	public function set_country( $country ) {
		$this->country = $country;
	}

	/**
	 * Sets session data for the state.
	 *
	 * @param mixed $state
	 */
	public function set_state( $state ) {
		$this->state = $state;
	}

	/**
	 * Sets session data for the postcode.
	 *
	 * @param mixed $postcode
	 */
	public function set_postcode( $postcode ) {
		$this->postcode = $postcode;
	}

	/**
	 * Sets session data for the city.
	 *
	 * @param mixed $city
	 */
	public function set_city( $city ) {
		$this->city = $city;
	}

	/**
	 * Sets session data for the address.
	 *
	 * @param mixed $address
	 */
	public function set_address( $address ) {
		$this->address_1 = $address;
	}

	/**
	 * Sets session data for the $address.
	 *
	 * @param mixed $address
	 */
	public function set_address_2( $address ) {
		$this->address_2 = $address;
	}

	/**
	 * Sets session data for the location.
	 *
	 * @param string $country
	 * @param string $state (default: '')
	 * @param string $postcode (default: '')
	 * @param string $city (default: '')
	 */
	public function set_shipping_location( $country, $state = '', $postcode = '', $city = '' ) {
		$this->shipping_country  = $country;
		$this->shipping_state    = $state;
		$this->shipping_postcode = $postcode;
		$this->shipping_city     = $city;
	}

	/**
	 * Sets session data for the country.
	 *
	 * @param string $country
	 */
	public function set_shipping_country( $country ) {
		$this->shipping_country = $country;
	}

	/**
	 * Sets session data for the state.
	 *
	 * @param string $state
	 */
	public function set_shipping_state( $state ) {
		$this->shipping_state = $state;
	}

	/**
	 * Sets session data for the postcode.
	 *
	 * @param string $postcode
	 */
	public function set_shipping_postcode( $postcode ) {
		$this->shipping_postcode = $postcode;
	}

	/**
	 * Sets session data for the city.
	 *
	 * @param string $city
	 */
	public function set_shipping_city( $city ) {
		$this->shipping_city = $city;
	}

	/**
	 * Sets session data for the address.
	 *
	 * @param string $address
	 */
	public function set_shipping_address( $address ) {
		$this->shipping_address_1 = $address;
	}

	/**
	 * Sets session data for the address_2.
	 *
	 * @param string $address
	 */
	public function set_shipping_address_2( $address ) {
		$this->shipping_address_2 = $address;
	}

	/**
	 * Sets session data for the tax exemption.
	 *
	 * @param bool $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = $is_vat_exempt;
	}

	/**
	 * calculated_shipping function.
	 *
	 * @param boolean $calculated
	 */
	public function calculated_shipping( $calculated = true ) {
		$this->calculated_shipping = $calculated;
	}

	/**
	 * Gets a user's downloadable products if they are logged in.
	 *
	 * @return array Array of downloadable products
	 */
	public function get_downloadable_products() {
		$downloads = array();

		if ( is_user_logged_in() ) {
			$downloads = wc_get_customer_available_downloads( get_current_user_id() );
		}

		return apply_filters( 'woocommerce_customer_get_downloadable_products', $downloads );
	}
}
