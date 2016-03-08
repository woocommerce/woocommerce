<?php
include_once( 'legacy/class-wc-legacy-customer.php' );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WooCommerce customer class handles storage of the current customer's data, such as location.
 *
 * @class    WC_Customer
 * @version  2.7.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Customer extends WC_Legacy_Customer implements WC_Data {

	/**
	 * Stores customer data.
	 * @var array
	 */
	protected $_data = array(
		'id'				  => 0,
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
		'calculated_shipping' => false,
		'is_user'             => false,
	);

	/**
	 * Keys which are also stored in a session (so we can make sure they get updated...)
	 * @var array
	 */
	protected $_session_keys = array(
		'postcode', 'city', 'address_1', 'address_2', 'state', 'country',
		'shipping_postcode', 'shipping_city', 'shipping_address_1', 'shipping_address_2',
		'shipping_state', 'shipping_country', 'is_vat_exempt', 'calculated_shipping',
	);

	/**
	 * Was data changed in the database for this class?
	 * @var boolean
	 */
	private $_changed = false;

	/**
	 * If some of the customer information is loaded by session (instead of just from the DB).
	 * @var boolean
	 */
	private $_from_session = false;

	/**
	 * Load customer data based on how WC_Customer is called.
	 */
	public function __construct( $customer = '' ) {
		if ( $customer instanceof WC_Customer ) {
			$this->_data['is_user'] = true;
			$this->read( absint( $customer->get_id() ) );
		} else if ( is_numeric( $customer ) ) {
			$this->_data['is_user'] = true;
			$this->read( $customer );
		} else if ( empty( $customer ) ) {
			$this->_from_session = true;
			if ( is_user_logged_in() ) {
				$this->_data['is_user'] = true;
				$this->read( get_current_user_id() );
			} else {
				$this->read( WC()->session->get_customer_id() );
			}
		}

		if ( $this->_from_session ) {
			add_action( 'shutdown', array( $this, 'save_session' ), 10 );
		}
	}

	/**
	 * Saves customer information to the current session if any data changed.
	 * @since 2.7.0
	 */
	public function save_session() {
		if ( $this->_changed ) {
			$data = array();
			foreach ( $this->_session_keys as $session_key ) {
				$data[ $session_key ] = $this->_data[ $session_key ];
			}
			WC()->session->set( 'customer', $data );
		}
	}

	/**
	 * Return a customer's user ID.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_id() {

	}

	/**
	 * Get all class data in array format.
	 * @since 2.7.0
	 * @return array
	 */
	public function get_data() {

	}

	/**
	 * Get default country for a customer.
	 *
	 * @return string
	 */
	public function get_default_country() {
		$default = wc_get_customer_default_location();
		return $default['country'];
	}

	/**
	 * Get default state for a customer.
	 *
	 * @return string
	 */
	public function get_default_state() {
		$default = wc_get_customer_default_location();
		return $default['state'];
	}

	/**
	 * Has calculated shipping?
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
	 * Gets the country from the current session.
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
	 * Get taxable address.
	 *
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( true == apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && WC()->cart->needs_shipping() && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
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
	 * Set default data for a customer.
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
	 * Calculated shipping.
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

	/*
	 |--------------------------------------------------------------------------
	 | CRUD methods
	 |--------------------------------------------------------------------------
	 | Methods which create, read, update and delete from the database.
	 |
	 | A save method is included for convenience (chooses update or create based
	 | on if the order exists yet).
	 */

	 /**
	  * Create a customer.
	  * @since 2.7.0.
	  */
	public function create() {

	}

	/**
	 * Read a customer from the database.
	 * @since 2.7.0
	 * @param integer $id
	 */
	public function read( $id ) {
		$pull_from_db = true;
		if ( $this->_from_session ) {
			$data = (array) WC()->session->get( 'customer' );
			if ( ! empty( $data ) ) {
				$pull_from_db  = false;
				foreach ( $this->_session_keys as $session_key ) {
					$this->_data[ $session_key ] = $data[ $session_key ];
				}
			}
		}

		if ( $pull_from_db ) {
			foreach ( array_keys( $this->_data ) as $key ) {
				$meta_value = get_user_meta( $id, ( false === strstr( $key, 'shipping_' ) ? 'billing_' : '' ) . $key, true );
				$this->_data[ $key ] = $meta_value ? $meta_value : $this->_data[ $key ];
			}
		}

		$this->_data['id'] = $id;

		// Set some defaults if some of our values are still not set.
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
	 * Update a customer.
	 * @since 2.7.0
	 */
	public function update() {

	}

	/**
	 * Delete a customer.
	 * @since 2.7.0
	 */
	public function delete() {

	}

	/**
	 * Save data (either create or update depending on if we are working on an existing coupon).
	 * @since 2.7.0
	 */
	public function save() {

	}

}
