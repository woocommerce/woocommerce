<?php
/**
 * Customer
 *
 * The WooCommerce customer class handles storage of the current customer's data, such as location.
 *
 * @class 		WC_Customer
 * @version		2.2.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Customer {

	/** Stores customer data as an array */
	protected $_data;

	/** Stores bool when data is changed */
	private $_changed = false;

	/**
	 * Constructor for the customer class loads the customer data.
	 *
	 * @access public
	 */
	public function __construct() {
		$this->_data = WC()->session->get( 'customer' );

		if ( empty( $this->_data ) ) {
			$this->_data = array(
				'country' 				=> esc_html( $this->get_default_country() ),
				'state' 				=> '',
				'postcode' 				=> '',
				'city'					=> '',
				'address' 				=> '',
				'address_2' 			=> '',
				'shipping_country' 		=> esc_html( $this->get_default_country() ),
				'shipping_state' 		=> '',
				'shipping_postcode' 	=> '',
				'shipping_city'			=> '',
				'shipping_address'		=> '',
				'shipping_address_2'	=> '',
				'is_vat_exempt' 		=> false,
				'calculated_shipping'	=> false
			);
		}

		// When leaving or ending page load, store data
		add_action( 'shutdown', array( $this, 'save_data' ), 10 );
	}

	/**
	 * save_data function.
	 *
	 * @access public
	 */
	public function save_data() {
		if ( $this->_changed ) {
			WC()->session->set( 'customer', $this->_data );
		}
	}

	/**
	 * __set function.
	 * @access   public
	 * @param mixed $property
	 * @return bool
	 */
	public function __isset( $property ) {
		return isset( $this->_data[ $property ] );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param string $property
	 * @return string
	 */
	public function __get( $property ) {
		return isset( $this->_data[ $property ] ) ? $this->_data[ $property ] : '';
	}

	/**
	 * __set function.
	 *
	 * @access public
	 * @param mixed $property
	 * @param mixed $value
	 */
	public function __set( $property, $value ) {
		$this->_data[ $property ] = $value;
		$this->_changed = true;
	}

	/**
	 * Get default country for a customer
	 * @return string
	 */
	public function get_default_country() {
		$default = apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) );

		if ( strstr( $default, ':' ) ) {
			list( $country, $state ) = explode( ':', $default );
		} else {
			$country = $default;
			$state   = '';
		}

		return $country;
	}

	/**
	 * Get default state for a customer
	 * @return string
	 */
	public function get_default_state() {
		$default = apply_filters( 'woocommerce_customer_default_location', get_option( 'woocommerce_default_country' ) );

		if ( strstr( $default, ':' ) ) {
			list( $country, $state ) = explode( ':', $default );
		} else {
			$country = $default;
			$state   = '';
		}

		return $state;
	}

	/**
	 * has_calculated_shipping function.
	 *
	 * @access public
	 * @return bool
	 */
	public function has_calculated_shipping() {
		return ( ! empty( $this->calculated_shipping ) ) ? true : false;
	}

	/**
	 * Set customer address to match shop base address.
	 *
	 * @access public
	 */
	public function set_to_base() {
		$this->country  = $this->get_default_country();
		$this->state    = $this->get_default_state();
		$this->postcode = '';
		$this->city     = '';
	}

	/**
	 * Set customer shipping address to base address.
	 *
	 * @access public
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
	 * @access public
	 * @return bool
	 */
	public function is_customer_outside_base() {
		list( $country, $state, $postcode, $city ) = $this->get_taxable_address();

		if ( $country ) {

			$default = get_option('woocommerce_default_country');

			if ( strstr( $default, ':' ) ) {
				list( $default_country, $default_state ) = explode( ':', $default );
			} else {
				$default_country = $default;
				$default_state = '';
			}

			if ( $default_country !== $country ) return true;
			if ( $default_state && $default_state !== $state ) return true;

		}
		return false;
	}

	/**
	 * Is the user a paying customer?
	 *
	 * @access public
	 * @return bool
	 */
	function is_paying_customer( $user_id ) {
		return '1' === get_user_meta( $user_id, 'paying_customer', true );
	}

	/**
	 * Is customer VAT exempt?
	 *
	 * @access public
	 * @return bool
	 */
	public function is_vat_exempt() {
		return ( ! empty( $this->is_vat_exempt ) ) ? true : false;
	}

	/**
	 * Gets the state from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_state() {
		return $this->state;
	}

	/**
	 * Gets the country from the current session
	 *
	 * @access public
	 * @return string
	 */
	public function get_country() {
		return $this->country;
	}

	/**
	 * Gets the postcode from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_postcode() {
		return empty( $this->postcode ) ? '' : wc_format_postcode( $this->postcode, $this->get_country() );
	}

	/**
	 * Get the city from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_city() {
		return $this->city;
	}

	/**
	 * Gets the address from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_address() {
		return $this->address;
	}

	/**
	 * Gets the address_2 from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_address_2() {
		return $this->address_2;
	}

	/**
	 * Gets the state from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->shipping_state;
	}


	/**
	 * Gets the country from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->shipping_country;
	}


	/**
	 * Gets the postcode from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_postcode() {
		return empty( $this->shipping_postcode ) ? '' : wc_format_postcode( $this->shipping_postcode, $this->get_shipping_country() );
	}


	/**
	 * Gets the city from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->shipping_city;
	}

	/**
	 * Gets the address from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->shipping_address;
	}

	/**
	 * Gets the address_2 from the current session.
	 *
	 * @access public
	 * @return string
	 */
	public function get_shipping_address_2() {
		return $this->shipping_address_2;
	}

	/**
	 * get_taxable_address function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) == true && WC()->cart->needs_shipping() && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array( get_option( 'woocommerce_default_shipping_method' ) ) ), apply_filters( 'woocommerce_local_pickup_methods', array( 'local_pickup' ) ) ) ) > 0 ) {
			$tax_based_on = 'base';
		}

		if ( $tax_based_on == 'base' ) {

			$default = get_option( 'woocommerce_default_country' );
			if ( strstr( $default, ':' ) ) {
				list( $country, $state ) = explode( ':', $default );
			} else {
				$country = $default;
				$state = '';
			}

			$postcode   = '';
			$city   	= '';

		} elseif ( $tax_based_on == 'billing' ) {

			$country 	= $this->get_country();
			$state 		= $this->get_state();
			$postcode   = $this->get_postcode();
			$city   	= $this->get_city();

		} else {

			$country 	= $this->get_shipping_country();
			$state 		= $this->get_shipping_state();
			$postcode   = $this->get_shipping_postcode();
			$city   	= $this->get_shipping_city();

		}

		return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city ) );
	}


	/**
	 * Sets session data for the location.
	 *
	 * @access public
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
	 * @access public
	 * @param mixed $country
	 */
	public function set_country( $country ) {
		$this->country = $country;
	}

	/**
	 * Sets session data for the state.
	 *
	 * @access public
	 * @param mixed $state
	 */
	public function set_state( $state ) {
		$this->state = $state;
	}

	/**
	 * Sets session data for the postcode.
	 *
	 * @access public
	 * @param mixed $postcode
	 */
	public function set_postcode( $postcode ) {
		$this->postcode = $postcode;
	}

	/**
	 * Sets session data for the city.
	 *
	 * @access public
	 * @param mixed $city
	 */
	public function set_city( $city ) {
		$this->city = $city;
	}

	/**
	 * Sets session data for the address.
	 *
	 * @access public
	 * @param mixed $address
	 */
	public function set_address( $address ) {
		$this->address = $address;
	}

	/**
	 * Sets session data for the address_2.
	 *
	 * @access public
	 * @param mixed $address_2
	 */
	public function set_address_2( $address_2 ) {
		$this->address_2 = $address_2;
	}

	/**
	 * Sets session data for the location.
	 *
	 * @access public
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
	 * @access public
	 * @param string $country
	 */
	public function set_shipping_country( $country ) {
		$this->shipping_country = $country;
	}

	/**
	 * Sets session data for the state.
	 *
	 * @access public
	 * @param string $state
	 */
	public function set_shipping_state( $state ) {
		$this->shipping_state = $state;
	}

	/**
	 * Sets session data for the postcode.
	 *
	 * @access public
	 * @param string $postcode
	 */
	public function set_shipping_postcode( $postcode ) {
		$this->shipping_postcode = $postcode;
	}

	/**
	 * Sets session data for the city.
	 *
	 * @access public
	 * @param string $city
	 */
	public function set_shipping_city( $city ) {
		$this->shipping_city = $city;
	}

	/**
	 * Sets session data for the address.
	 *
	 * @access public
	 * @param string $address
	 */
	public function set_shipping_address( $address ) {
		$this->shipping_address = $address;
	}

	/**
	 * Sets session data for the address_2.
	 *
	 * @access public
	 * @param string $address_2
	 */
	public function set_shipping_address_2( $address_2 ) {
		$this->shipping_address_2 = $address_2;
	}

	/**
	 * Sets session data for the tax exemption.
	 *
	 * @access public
	 * @param bool $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = $is_vat_exempt;
	}

	/**
	 * calculated_shipping function.
	 *
	 * @access public
	 * @param boolean $calculated
	 */
	public function calculated_shipping( $calculated = true ) {
		$this->calculated_shipping = $calculated;
	}

	/**
	 * Gets a user's downloadable products if they are logged in.
	 *
	 * @access public
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
