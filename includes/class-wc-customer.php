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
class WC_Customer extends WC_Legacy_Customer {

	/**
	 * Stores customer data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'       => '',
		'date_modified'      => '',
		'email'              => '',
		'first_name'         => '',
		'last_name'          => '',
		'role'               => 'customer',
		'username'           => '',
		'billing'            => array(
			'first_name'     => '',
			'last_name'      => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'city'           => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
			'email'          => '',
			'phone'          => '',
		),
		'shipping'           => array(
			'first_name'     => '',
			'last_name'      => '',
			'company'        => '',
			'address_1'      => '',
			'address_2'      => '',
			'city'           => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
		),
		'is_paying_customer' => false,
	);

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'billing_postcode',
		'billing_city',
		'billing_address_1',
		'billing_address_2',
		'billing_state',
		'billing_country',
		'shipping_postcode',
		'shipping_city',
		'shipping_address_1',
		'shipping_address_2',
		'shipping_state',
		'shipping_country',
		'paying_customer',
		'last_update',
		'first_name',
		'last_name',
		'show_admin_bar_front',
		'use_ssl',
		'admin_color',
		'rich_editing',
		'comment_shortcuts',
		'dismissed_wp_pointers',
		'show_welcome_panel',
		'_woocommerce_persistent_cart',
		'session_tokens',
		'nickname',
		'description',
		'billing_first_name',
		'billing_last_name',
		'billing_company',
		'billing_phone',
		'billing_email',
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'default_password_nag',
		'primary_blog',
		'source_domain',
	);

	/**
	 * Internal meta type used to store user data.
	 *
	 * @var string
	 */
	protected $meta_type = 'user';

	/**
	 * Stores a password if this needs to be changed. Write-only and hidden from _data.
	 *
	 * @var string
	 */
	protected $password = '';

	/**
	 * Stores if user is VAT exempt for this session.
	 *
	 * @var string
	 */
	protected $is_vat_exempt = false;

	/**
	 * Stores if user has calculated shipping in this session.
	 *
	 * @var string
	 */
	protected $calculated_shipping = false;

	/**
	 * Load customer data based on how WC_Customer is called.
	 *
	 * If $customer is 'new', you can build a new WC_Customer object. If it's empty, some
	 * data will be pulled from the session for the current user/customer.
	 *
	 * @param WC_Customer|int $data Customer ID or data.
	 * @param bool $is_session True if this is the customer session
	 * @throws Exception if customer cannot be read/found and $data is set.
	 */
	public function __construct( $data = 0, $is_session = false ) {
		parent::__construct( $data );

		if ( $data instanceof WC_Customer ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = WC_Data_Store::load( 'customer' );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			$this->data_store->read( $this );
		} else {
			$this->set_object_read( true );
		}

		// If this is a session, set or change the data store to sessions. Changes do not persist in the database.
		if ( $is_session ) {
			$this->data_store = WC_Data_Store::load( 'customer-session' );
			$this->data_store->read( $this );
		}
	}

	/**
	 * Callback to remove unwanted meta data.
	 *
	 * @param object $meta
	 * @return bool
	 */
	protected function exclude_internal_meta_keys( $meta ) {
		global $wpdb;
		return ! in_array( $meta->meta_key, $this->get_internal_meta_keys() )
			&& 0 !== strpos( $meta->meta_key, 'closedpostboxes_' )
			&& 0 !== strpos( $meta->meta_key, 'metaboxhidden_' )
			&& 0 !== strpos( $meta->meta_key, 'manageedit-' )
			&& ! strstr( $meta->meta_key, $wpdb->prefix );
	 }

	/**
	 * Delete a customer and reassign posts..
	 *
	 * @param int $reassign Reassign posts and links to new User ID.
	 * @since 2.7.0
	 * @return bool
	 */
	public function delete_and_reassign( $reassign = null ) {
		if ( $this->data_store ) {
			$this->data_store->delete( $this, array( 'force_delete' => true, 'reassign' => $reassign ) );
			$this->set_id( 0 );
			return true;
		}
		return false;
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
	 * Return this customer's avatar.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_avatar_url() {
		$avatar_html = get_avatar( $this->get_email() );

		// Get the URL of the avatar from the provided HTML
		preg_match( '/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches );

		if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
			return esc_url( $matches[1] );
		}

		return '';
	}

	/**
	 * Get taxable address.
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		// Check shipping method at this point to see if we need special handling
		if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
			$tax_based_on = 'base';
		}

		if ( 'base' === $tax_based_on ) {
			$country  = WC()->countries->get_base_country();
			$state    = WC()->countries->get_base_state();
			$postcode = WC()->countries->get_base_postcode();
			$city     = WC()->countries->get_base_city();
		} elseif ( 'billing' === $tax_based_on ) {
			$country  = $this->get_billing_country();
			$state    = $this->get_billing_state();
			$postcode = $this->get_billing_postcode();
			$city     = $this->get_billing_city();
		} else {
			$country  = $this->get_shipping_country();
			$state    = $this->get_shipping_state();
			$postcode = $this->get_shipping_postcode();
			$city     = $this->get_shipping_city();
		}

		return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city ) );
	}

	/**
	 * Gets a customer's downloadable products.
	 *
	 * @return array Array of downloadable products
	 */
	public function get_downloadable_products() {
		$downloads = array();
		if ( $this->get_id() ) {
			$downloads = wc_get_customer_available_downloads( $this->get_id() );
		}
		return apply_filters( 'woocommerce_customer_get_downloadable_products', $downloads );
	}

	/**
	 * Is customer VAT exempt?
	 *
	 * @return bool
	 */
	public function is_vat_exempt() {
		return $this->get_is_vat_exempt();
	}

	/**
	 * Has calculated shipping?
	 *
	 * @return bool
	 */
	public function has_calculated_shipping() {
		return $this->get_calculated_shipping();
	}

	/**
	 * Get if customer is VAT exempt?
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_is_vat_exempt() {
		return $this->is_vat_exempt;
	}

	/**
	 * Get password (only used when updating the user object).
	 *
	 * @return string
	 */
	public function get_password() {
		return $this->password;
	}

	/**
	 * Has customer calculated shipping?
	 *
	 * @param  string $context
	 * @return bool
	 */
	public function get_calculated_shipping() {
		return $this->calculated_shipping;
	}

	/**
	 * Set if customer has tax exemption.
	 *
	 * @param bool $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = (bool) $is_vat_exempt;
	}

	/**
	 * Calculated shipping?
	 *
	 * @param boolean $calculated
	 */
	public function set_calculated_shipping( $calculated = true ) {
		$this->calculated_shipping = (bool) $calculated;
	}

	/**
	 * Set customer's password.
	 *
	 * @since 2.7.0
	 * @param string $password
	 * @throws WC_Data_Exception
	 */
	public function set_password( $password ) {
		$this->password = wc_clean( $password );
	}

	/**
	 * Gets the customers last order.
	 *
	 * @param WC_Customer
	 * @return WC_Order|false
	 */
	public function get_last_order() {
		return $this->data_store->get_last_order( $this );
	}

	/**
	 * Return the number of orders this customer has.
	 *
	 * @param WC_Customer
	 * @return integer
	 */
	public function get_order_count() {
		return $this->data_store->get_order_count( $this );
	}

	/**
	 * Return how much money this customer has spent.
	 *
	 * @param WC_Customer
	 * @return float
	 */
	public function get_total_spent() {
		return $this->data_store->get_total_spent( $this );
	}

	/*
	 |--------------------------------------------------------------------------
	 | Getters
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Return the customer's username.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_username( $context = 'view' ) {
		return $this->get_prop( 'username', $context );
	}

	/**
	 * Return the customer's email.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		return $this->get_prop( 'email', $context );
	}

	/**
	 * Return customer's first name.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Return customer's last name.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Return customer's user role.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return string
	 */
	public function get_role( $context = 'view' ) {
		return $this->get_prop( 'role', $context );
	}

	/**
	 * Return the date this customer was created.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Return the date this customer was last updated.
	 *
	 * @since  2.7.0
	 * @param  string $context
	 * @return integer
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Gets customer billing first name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_first_name( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['first_name'];
	}

	/**
	 * Gets customer billing last name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_last_name( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['last_name'];
	}

	/**
	 * Gets customer billing company.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_company( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['company'];
	}

	/**
	 * Gets billing phone.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_phone( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['phone'];
	}

	/**
	 * Gets billing email.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_email( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['email'];
	}

	/**
	 * Gets customer postcode.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_postcode( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return 'view' === $context ? wc_format_postcode( $billing['postcode'], $this->get_billing_country() ) : $billing['postcode'];
	}

	/**
	 * Get customer city.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_city( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['city'];
	}

	/**
	 * Get customer address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_address( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['address_1'];
	}

	/**
	 * Get customer address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_address_1( $context = 'view' ) {
		return $this->get_billing_address( $context );
	}

	/**
	 * Get customer's second address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_address_2( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['address_2'];
	}

	/**
	 * Get customer state.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_state( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['state'];
	}

	/**
	 * Get customer country.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_billing_country( $context = 'view' ) {
		$billing = $this->get_prop( 'billing', $context );
		return $billing['country'];
	}

	/**
	 * Gets customer shipping first name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_first_name( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['first_name'];
	}

	/**
	 * Gets customer shipping last name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_last_name( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['last_name'];
	}

	/**
	 * Gets customer shipping company.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_company( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['company'];
	}

	/**
	 * Get customer's shipping state.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_state( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['state'];
	}

	/**
	 * Get customer's shipping country.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_country( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['country'];
	}

	/**
	 * Get customer's shipping postcode.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_postcode( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return 'view' === $context ? wc_format_postcode( $shipping['postcode'], $this->get_shipping_country() ) : $shipping['postcode'];
	}

	/**
	 * Get customer's shipping city.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_city( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['city'];
	}

	/**
	 * Get customer's shipping address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_address( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['address_1'];
	}

	/**
	 * Get customer address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_address_1( $context = 'view' ) {
		return $this->get_shipping_address( $context );
	}

	/**
	 * Get customer's second shipping address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_address_2( $context = 'view' ) {
		$shipping = $this->get_prop( 'shipping', $context );
		return $shipping['address_2'];
	}

	/**
	 * Is the user a paying customer?
	 *
	 * @since 2.7.0
	 * @param  string $context
	 * @return bool
	 */
	function get_is_paying_customer( $context = 'view' ) {
		return $this->get_prop( 'is_paying_customer', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set customer's username.
	 *
	 * @since 2.7.0
	 * @param string $username
	 * @throws WC_Data_Exception
	 */
	public function set_username( $username ) {
		$this->set_prop( 'username', $username );
	}

	/**
	 * Set customer's email.
	 *
	 * @since 2.7.0
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_email', __( 'Invalid email address', 'woocommerce' ) );
		}
		$this->set_prop( 'email', sanitize_email( $value ) );
	}

	/**
	 * Set customer's first name.
	 *
	 * @since 2.7.0
	 * @param string $first_name
	 * @throws WC_Data_Exception
	 */
	public function set_first_name( $first_name ) {
		$this->set_prop( 'first_name', $first_name );
	}

	/**
	 * Set customer's last name.
	 *
	 * @since 2.7.0
	 * @param string $last_name
	 * @throws WC_Data_Exception
	 */
	public function set_last_name( $last_name ) {
		$this->set_prop( 'last_name', $last_name );
	}

	/**
	 * Set customer's user role(s).
	 *
	 * @since 2.7.0
	 * @param mixed $role
	 * @throws WC_Data_Exception
	 */
	public function set_role( $role ) {
		global $wp_roles;

		if ( $role && ! empty( $wp_roles->roles ) && ! in_array( $role, array_keys( $wp_roles->roles ) ) ) {
			$this->error( 'customer_invalid_role', __( 'Invalid role', 'woocommerce' ) );
		}
		$this->set_prop( 'role', $role );
	}

	/**
	 * Set the date this customer was last updated.
	 *
	 * @since 2.7.0
	 * @param integer $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $timestamp ) {
		$this->set_prop( 'date_modified', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Set the date this customer was last updated.
	 *
	 * @since 2.7.0
	 * @param integer $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $timestamp ) {
		$this->set_prop( 'date_created', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Set customer address to match shop base address.
	 *
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->set_billing_location( $base['country'], $base['state'], '', '' );
	}

	/**
	 * Set customer shipping address to base address.
	 *
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_to_base() {
		$base = wc_get_customer_default_location();
		$this->set_shipping_location( $base['country'], $base['state'], '', '' );
	}

	/**
	 * Sets all address info at once.
	 *
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 * @throws WC_Data_Exception
	 */
	public function set_billing_location( $country, $state, $postcode = '', $city = '' ) {
		$billing             = $this->get_prop( 'billing', 'edit' );
		$billing['country']  = $country;
		$billing['state']    = $state;
		$billing['postcode'] = $postcode;
		$billing['city']     = $city;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Sets all shipping info at once.
	 *
	 * @param string $country
	 * @param string $state
	 * @param string $postcode
	 * @param string $city
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_location( $country, $state = '', $postcode = '', $city = '' ) {
		$shipping             = $this->get_prop( 'shipping', 'edit' );
		$shipping['country']  = $country;
		$shipping['state']    = $state;
		$shipping['postcode'] = $postcode;
		$shipping['city']     = $city;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set billing first name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_first_name( $value ) {
		$billing               = $this->get_prop( 'billing', 'edit' );
		$billing['first_name'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set billing last name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_last_name( $value ) {
		$billing              = $this->get_prop( 'billing', 'edit' );
		$billing['last_name'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set billing company.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_company( $value ) {
		$billing            = $this->get_prop( 'billing', 'edit' );
		$billing['company'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set billing phone.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_phone( $value ) {
		$billing          = $this->get_prop( 'billing', 'edit' );
		$billing['phone'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set billing email.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'customer_invalid_billing_email', __( 'Invalid billing email address', 'woocommerce' ) );
		}
		$billing          = $this->get_prop( 'billing', 'edit' );
		$billing['email'] = sanitize_email( $value );
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set customer country.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_country( $value ) {
		$billing            = $this->get_prop( 'billing', 'edit' );
		$billing['country'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set customer state.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_state( $value ) {
		$billing          = $this->get_prop( 'billing', 'edit' );
		$billing['state'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Sets customer postcode.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_postcode( $value ) {
		$billing             = $this->get_prop( 'billing', 'edit' );
		$billing['postcode'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Sets customer city.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_city( $value ) {
		$billing         = $this->get_prop( 'billing', 'edit' );
		$billing['city'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set customer address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address( $value ) {
		$billing              = $this->get_prop( 'billing', 'edit' );
		$billing['address_1'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Set customer address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_1( $value ) {
		$this->set_billing_address( $value );
	}

	/**
	 * Set customer's second address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_2( $value ) {
		$billing              = $this->get_prop( 'billing', 'edit' );
		$billing['address_2'] = $value;
		$this->set_prop( 'billing', $billing );
	}

	/**
	 * Sets customer shipping first name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_first_name( $value ) {
		$shipping               = $this->get_prop( 'shipping', 'edit' );
		$shipping['first_name'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Sets customer shipping last name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_last_name( $value ) {
		$shipping              = $this->get_prop( 'shipping', 'edit' );
		$shipping['last_name'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Sets customer shipping company.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_company( $value ) {
		$shipping            = $this->get_prop( 'shipping', 'edit' );
		$shipping['company'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set shipping country.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_country( $value ) {
		$shipping            = $this->get_prop( 'shipping', 'edit' );
		$shipping['country'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set shipping state.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_state( $value ) {
		$shipping          = $this->get_prop( 'shipping', 'edit' );
		$shipping['state'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set shipping postcode.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_postcode( $value ) {
		$shipping             = $this->get_prop( 'shipping', 'edit' );
		$shipping['postcode'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Sets shipping city.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_city( $value ) {
		$shipping         = $this->get_prop( 'shipping', 'edit' );
		$shipping['city'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set shipping address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address( $value ) {
		$shipping              = $this->get_prop( 'shipping', 'edit' );
		$shipping['address_1'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set customer shipping address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_1( $value ) {
		$this->set_shipping_address( $value );
	}

	/**
	 * Set second shipping address.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_2( $value ) {
		$shipping              = $this->get_prop( 'shipping', 'edit' );
		$shipping['address_2'] = $value;
		$this->set_prop( 'shipping', $shipping );
	}

	/**
	 * Set if the user a paying customer.
	 *
	 * @since 2.7.0
	 * @param bool $is_paying_customer
	 * @throws WC_Data_Exception
	 */
	function set_is_paying_customer( $is_paying_customer ) {
		$this->set_prop( 'is_paying_customer', (bool) $is_paying_customer );
	}
}
