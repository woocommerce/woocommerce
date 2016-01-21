<?php
/**
 * Abstract Order
 *
 * The WooCommerce order class handles order data.
 *
 * @class       WC_Abstract_Order
 * @version     2.6.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
abstract class WC_Abstract_Order {

	/**
	 * Get the order if ID is passed, otherwise the order is new and empty.
	 * This class should NOT be instantiated, but the get_order function or new WC_Order_Factory.
	 * should be used. It is possible, but the aforementioned are preferred and are the only.
	 * methods that will be maintained going forward.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		$this->prices_include_tax    = 'yes' === get_option( 'woocommerce_prices_include_tax' );
		$this->tax_display_cart      = get_option( 'woocommerce_tax_display_cart' );
		$this->display_totals_ex_tax = 'excl' === $this->tax_display_cart;
		$this->display_cart_ex_tax   = 'excl' === $this->tax_display_cart;
		$this->init( $order );
	}

	/**
	 * Load the order object. Called from the constructor.
	 *
	 * @param int|object|WC_Order $order Order to init.
	 */
	protected function init( $order ) {
		if ( is_numeric( $order ) ) {
			$this->read( $order );
		} elseif ( $order instanceof WC_Order ) {
			$this->read( absint( $order->get_order_id() ) );
		} elseif ( isset( $order->ID ) ) {
			$this->read( absint( $order->ID ) );
		}
	}

	/**
	 * Magic __isset method.
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		// @todo needs to check data
		return $this->id ? metadata_exists( 'post', $this->id, '_' . $key ) : false;
	}

	/**
	 * Magic __get method.
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		// @todo needs to get data
		//
		// Get values or default if not set.
		if ( 'completed_date' === $key ) {
			$value = ( $value = get_post_meta( $this->id, '_completed_date', true ) ) ? $value : $this->modified_date;
		} elseif ( 'user_id' === $key ) {
			$value = ( $value = get_post_meta( $this->id, '_customer_user', true ) ) ? absint( $value ) : '';
		} elseif ( 'status' === $key ) {
			$value = $this->get_status();
		} else {
			$value = get_post_meta( $this->id, '_' . $key, true );
		}

		// @todo bw compat for
		// customer_message and customer_note
		// post_status
		// post
		// order_date
		// modified_date
		// $order_type
		// id
		// customer_user
		//
		// cart_discount
		// order_shipping
		// order_tax
		// cart_discount_tax
		// order_shipping_tax
		// order_total

		return $value;
	}

	/**
	 * Data array, with defaults.
	 * @since 2.6.0
	 * @var array
	 */
    protected $data = array(
		// @todo when migrating to custom tables, these will be column names
		'order_id'             => 0,
		'order_status'         => '',
		'order_type'           => 'simple',
		'order_key'            => '',
		'order_currency'       => '',
		'order_date'           => '',
		'modified_date'        => '',
		'completed_date'       => '',
		'customer_id'          => 0,

		/*
		date_created
		date_modified
		date_shipped?

		 */

		// Billing address
		'billing_first_name'   => '',
		'billing_last_name'    => '',
		'billing_company'      => '',
		'billing_address_1'    => '',
		'billing_address_2'    => '',
		'billing_city'         => '',
		'billing_state'        => '',
		'billing_postcode'     => '',
		'billing_country'      => '',
		'billing_email'        => '',
		'billing_phone'        => '',

		// Shipping address
		'shipping_first_name'  => '',
		'shipping_last_name'   => '',
		'shipping_company'     => '',
		'shipping_address_1'   => '',
		'shipping_address_2'   => '',
		'shipping_city'        => '',
		'shipping_state'       => '',
		'shipping_postcode'    => '',
		'shipping_country'     => '',

		// Order totals
		'discount_total'       => 0,
		'discount_tax'         => 0,
		'shipping_total'       => 0,
		'shipping_tax'         => 0, // Total shipping taxes for the order
		'order_tax'            => 0, // Total tax for the order excluding shipping
		'order_total'          => 0,

		// @todo when migrating to custom tables, these will be custom meta
		'payment_method'       => '',
		'payment_method_title' => '',
		'transaction_id'       => '',
		'customer_ip_address'  => '',
		'customer_user_agent'  => '',
		'created_via'          => '',
		'order_version'        => '',
		'prices_include_tax'   => '',
		'customer_note'        => '',
	);

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the order object.
	|
	*/

	/**
	 * Get all class data in array format.
	 * @since 2.6.0
     * @access protected
	 * @return array
	 */
	protected function get_data() {
		return $this->data;
	}

	/**
	 * Get order ID.
	 * @since 2.6.0
	 * @return integer
	 */
	public function get_order_id() {
		return absint( $this->data['order_id'] );
	}

	/**
	 * Get order key.
	 * @since 2.6.0
	 * @return string
	 */
	public function get_order_key() {
		return $this->data['order_key'];
	}

	/**
	 * Gets order currency.
	 * @return string
	 */
	public function get_order_currency() {
		return apply_filters( 'woocommerce_get_order_currency', $this->data['order_currency'], $this );
	}

	/**
	 * Returns the requested address in raw, non-formatted way.
	 * @since  2.4.0
	 * @param  string $type Billing or shipping. Anything else besides 'billing' will return shipping address.
	 * @return array The stored address after filter.
	 */
	public function get_address( $type = 'billing' ) {
		if ( 'billing' === $type ) {
			$address = array(
				'first_name' => $this->data['billing_first_name'],
				'last_name'  => $this->data['billing_last_name'],
				'company'    => $this->data['billing_company'],
				'address_1'  => $this->data['billing_address_1'],
				'address_2'  => $this->data['billing_address_2'],
				'city'       => $this->data['billing_city'],
				'state'      => $this->data['billing_state'],
				'postcode'   => $this->data['billing_postcode'],
				'country'    => $this->data['billing_country'],
				'email'      => $this->data['billing_email'],
				'phone'      => $this->data['billing_phone']
			);
		} else {
			$address = array(
				'first_name' => $this->data['shipping_first_name'],
				'last_name'  => $this->data['shipping_last_name'],
				'company'    => $this->data['shipping_company'],
				'address_1'  => $this->data['shipping_address_1'],
				'address_2'  => $this->data['shipping_address_2'],
				'city'       => $this->data['shipping_city'],
				'state'      => $this->data['shipping_state'],
				'postcode'   => $this->data['shipping_postcode'],
				'country'    => $this->data['shipping_country'],
			);
		}

		return apply_filters( 'woocommerce_get_order_address', $address, $type, $this );
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * Queries get_post_status() directly to avoid having out of date statuses, if updated elsewhere.
	 *
	 * @return string
	 */
	public function get_status() {
		$this->post_status = get_post_status( $this->id );
		return apply_filters( 'woocommerce_order_get_status', 'wc-' === substr( $this->post_status, 0, 3 ) ? substr( $this->post_status, 3 ) : $this->post_status, $this );
	}

	/**
	 * Gets the user ID associated with the order. Guests are 0.
	 * @since  2.2
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->data['customer_id'] );
	}

	/**
	 * Get the user associated with the order. False for guests.
	 *
	 * @since  2.2
	 * @return WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get transaction id for the order.
	 *
	 * @return string
	 */
	public function get_transaction_id() {
		return get_post_meta( $this->id, '_transaction_id', true );
	}

	/**
	 * get_order_number function.
	 *
	 * Gets the order number for display (by default, order ID).
	 *
	 * @return string
	 */
	public function get_order_number() {
		return apply_filters( 'woocommerce_order_number', $this->id, $this );
	}

	/**
	 * Get a formatted billing address for the order.
	 * @return string
	 */
	public function get_formatted_billing_address() {
		return WC()->countries->get_formatted_address( apply_filters( 'woocommerce_order_formatted_billing_address', array(
			'first_name'    => $this->billing_first_name,
			'last_name'     => $this->billing_last_name,
			'company'       => $this->billing_company,
			'address_1'     => $this->billing_address_1,
			'address_2'     => $this->billing_address_2,
			'city'          => $this->billing_city,
			'state'         => $this->billing_state,
			'postcode'      => $this->billing_postcode,
			'country'       => $this->billing_country
		), $this ) );
	}

	/**
	 * Get a formatted shipping address for the order.
	 * @return string
	 */
	public function get_formatted_shipping_address() {
		if ( $this->shipping_address_1 || $this->shipping_address_2 ) {
			return WC()->countries->get_formatted_address( apply_filters( 'woocommerce_order_formatted_shipping_address', array(
				'first_name'    => $this->shipping_first_name,
				'last_name'     => $this->shipping_last_name,
				'company'       => $this->shipping_company,
				'address_1'     => $this->shipping_address_1,
				'address_2'     => $this->shipping_address_2,
				'city'          => $this->shipping_city,
				'state'         => $this->shipping_state,
				'postcode'      => $this->shipping_postcode,
				'country'       => $this->shipping_country
			), $this ) );
		} else {
			return '';
		}
	}

	/**
	 * Get a formatted shipping address for the order.
	 *
	 * @return string
	 */
	public function get_shipping_address_map_url() {
		$address = apply_filters( 'woocommerce_shipping_address_map_url_parts', array(
			'address_1'     => $this->shipping_address_1,
			'address_2'     => $this->shipping_address_2,
			'city'          => $this->shipping_city,
			'state'         => $this->shipping_state,
			'postcode'      => $this->shipping_postcode,
			'country'       => $this->shipping_country
		), $this );

		return apply_filters( 'woocommerce_shipping_address_map_url', 'http://maps.google.com/maps?&q=' . urlencode( implode( ', ', $address ) ) . '&z=16', $this );
	}

	/**
	 * Get a formatted billing full name.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_formatted_billing_full_name() {
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ),  $this->billing_first_name, $this->billing_last_name );
	}

	/**
	 * Get a formatted shipping full name.
	 *
	 * @since 2.4.0
	 *
	 * @return string
	 */
	public function get_formatted_shipping_full_name() {
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ),  $this->shipping_first_name, $this->shipping_last_name );
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function get_cart_tax() {
		return apply_filters( 'woocommerce_order_amount_cart_tax', (double) $this->data['order_tax'], $this );
	}

	/**
	 * Gets shipping tax amount.
	 *
	 * @return float
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_order_amount_shipping_tax', (double) $this->data['order_shipping_tax'], $this );
	}

	/**
	 * Gets shipping and product tax.
	 *
	 * @return float
	 */
	public function get_total_tax() {
		return apply_filters( 'woocommerce_order_amount_total_tax', wc_round_tax_total( $this->get_cart_tax() + $this->get_shipping_tax() ), $this );
	}

	/**
	 * Gets shipping total.
	 *
	 * @return float
	 */
	public function get_total_shipping() {
		return apply_filters( 'woocommerce_order_amount_total_shipping', (double) $this->data['order_shipping'], $this );
	}

	/**
	 * Gets order total.
	 *
	 * @return float
	 */
	public function get_total() {
		return apply_filters( 'woocommerce_order_amount_total', (double) $this->data['order_total'], $this );
	}

	/**
	 * Gets order subtotal.
	 * @return float
	 */
	public function get_subtotal() {
		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += isset( $item['line_subtotal'] ) ? $item['line_subtotal'] : 0;
		}

		return apply_filters( 'woocommerce_order_amount_subtotal', (double) $subtotal, $this );
	}

	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$taxes      = $this->get_items( 'tax' );
		$tax_totals = array();

		foreach ( $taxes as $key => $tax ) {
			$code = $tax[ 'name' ];

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals[ $code ] = new stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->id                = $key;
			$tax_totals[ $code ]->rate_id           = $tax['rate_id'];
			$tax_totals[ $code ]->is_compound       = $tax[ 'compound' ];
			$tax_totals[ $code ]->label             = isset( $tax[ 'label' ] ) ? $tax[ 'label' ] : $tax[ 'name' ];
			$tax_totals[ $code ]->amount           += $tax[ 'tax_amount' ] + $tax[ 'shipping_tax_amount' ];
			$tax_totals[ $code ]->formatted_amount  = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ), array('currency' => $this->get_order_currency()) );
		}

		return apply_filters( 'woocommerce_order_tax_totals', $tax_totals, $this );
	}

	/**
	 * Generates a URL so that a customer can pay for their (unpaid - pending) order. Pass 'true' for the checkout version which doesn't offer gateway choices.
	 *
	 * @param  bool $on_checkout
	 * @return string
	 */
	public function get_checkout_payment_url( $on_checkout = false ) {
		$pay_url = wc_get_endpoint_url( 'order-pay', $this->id, wc_get_page_permalink( 'checkout' ) );

		if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
			$pay_url = str_replace( 'http:', 'https:', $pay_url );
		}

		if ( $on_checkout ) {
			$pay_url = add_query_arg( 'key', $this->get_order_key(), $pay_url );
		} else {
			$pay_url = add_query_arg( array( 'pay_for_order' => 'true', 'key' => $this->get_order_key() ), $pay_url );
		}

		return apply_filters( 'woocommerce_get_checkout_payment_url', $pay_url, $this );
	}

	/**
	 * Generates a URL for the thanks page (order received).
	 *
	 * @return string
	 */
	public function get_checkout_order_received_url() {
		$order_received_url = wc_get_endpoint_url( 'order-received', $this->id, wc_get_page_permalink( 'checkout' ) );

		if ( 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
			$order_received_url = str_replace( 'http:', 'https:', $order_received_url );
		}

		$order_received_url = add_query_arg( 'key', $this->get_order_key(), $order_received_url );

		return apply_filters( 'woocommerce_get_checkout_order_received_url', $order_received_url, $this );
	}

	/**
	 * Generates a URL so that a customer can cancel their (unpaid - pending) order.
	 *
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function get_cancel_order_url( $redirect = '' ) {
		return apply_filters( 'woocommerce_get_cancel_order_url', wp_nonce_url( add_query_arg( array(
			'cancel_order' => 'true',
			'order'        => $this->get_order_key(),
			'order_id'     => $this->id,
			'redirect'     => $redirect
		), $this->get_cancel_endpoint() ), 'woocommerce-cancel_order' ) );
	}

	/**
	 * Generates a raw (unescaped) cancel-order URL for use by payment gateways.
	 *
	 * @param string $redirect
	 *
	 * @return string The unescaped cancel-order URL.
	 */
	public function get_cancel_order_url_raw( $redirect = '' ) {
		return apply_filters( 'woocommerce_get_cancel_order_url_raw', add_query_arg( array(
			'cancel_order' => 'true',
			'order'        => $this->get_order_key(),
			'order_id'     => $this->id,
			'redirect'     => $redirect,
			'_wpnonce'     => wp_create_nonce( 'woocommerce-cancel_order' )
		), $this->get_cancel_endpoint() ) );
	}


	/**
	 * Helper method to return the cancel endpoint.
	 *
	 * @return string the cancel endpoint; either the cart page or the home page.
	 */
	public function get_cancel_endpoint() {
		$cancel_endpoint = wc_get_page_permalink( 'cart' );
		if ( ! $cancel_endpoint ) {
			$cancel_endpoint = home_url();
		}

		if ( false === strpos( $cancel_endpoint, '?' ) ) {
			$cancel_endpoint = trailingslashit( $cancel_endpoint );
		}

		return $cancel_endpoint;
	}


	/**
	 * Generates a URL to view an order from the my account page.
	 *
	 * @return string
	 */
	public function get_view_order_url() {
		return apply_filters( 'woocommerce_get_view_order_url', wc_get_endpoint_url( 'view-order', $this->id, wc_get_page_permalink( 'myaccount' ) ), $this );
	}

	/**
	 * Get refunds.
	 * @return array
	 */
	public function get_refunds() { return array(); }

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting order data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object. However, for backwards compatibility pre 2.6.0 some of these
	| setters may handle both.
	|
	*/

	/**
	 * Set some order data.
	 * @since 2.6.0
	 * @return array
	 */
	public function set_data( $key, $value ) {
		if ( in_array( $key, array_keys( $this->data ) ) ) {
			if ( method_exists( $this, "set_{$key}" ) ) {
				$this->{"set_{$key}"}( $value );
			} else {
				$this->data[ $key ] = $value;
			}
		}
	}

	/**
	 * Set order ID.
	 * @since 2.6.0
	 * @param int $value
	 */
	public function set_order_id( $value ) {
		$this->data['order_id'] = absint( $value );
	}

	/**
	 * Set order status.
	 * @since 2.6.0
	 * @param string $value
	 */
	public function set_status( $value ) {
		if ( in_array( $value, array_keys( wc_get_order_statuses() ) ) ) {
			$this->data['order_status'] = $value;
		}
	}

	/**
	 * Set the payment method for the order.
	 * @since 2.2.0
	 * @param WC_Payment_Gateway $payment_method
	 */
	public function set_payment_method( $payment_method ) {
		if ( is_object( $payment_method ) ) {
			update_post_meta( $this->id, '_payment_method', $payment_method->id );
			update_post_meta( $this->id, '_payment_method_title', $payment_method->get_title() );
		}
	}

	/**
	 * Set the customer address.
	 * @since 2.2.0
	 * @param array $address Address data.
	 * @param string $type billing or shipping.
	 */
	public function set_address( $address, $type = 'billing' ) {
		foreach ( $address as $key => $value ) {
			update_post_meta( $this->id, "_{$type}_" . $key, $value );
		}
	}

	/**
	 * Set an order total.
	 * @since 2.2.0
	 * @param float $amount
	 * @param string $total_type
	 * @return bool
	 */
	public function set_total( $amount, $total_type = 'total' ) {
		if ( ! in_array( $total_type, array( 'shipping', 'tax', 'shipping_tax', 'total', 'cart_discount', 'cart_discount_tax' ) ) ) {
			return false;
		}

		switch ( $total_type ) {
			case 'total' :
				$key    = '_order_total';
				$amount = wc_format_decimal( $amount, wc_get_price_decimals() );
			break;
			case 'cart_discount' :
			case 'cart_discount_tax' :
				$key    = '_' . $total_type;
				$amount = wc_format_decimal( $amount );
			break;
			default :
				$key    = '_order_' . $total_type;
				$amount = wc_format_decimal( $amount );
			break;
		}

		update_post_meta( $this->id, $key, $amount );

		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete orders from the database.
	| Written in abstract fashion so that the way orders are stored can be
	| changed more easily in the future.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the order exists yet).
	|
	*/

	/**
     * Insert data into the database.
	 * @since 2.6.0
     * @access protected
     * @todo Convert to custom tables.
     * @param array $data data to save
     */
    protected function create( $data ) {
		$order_data                  = array();
		$order_data['post_type']     = 'shop_order';
		$order_data['post_status']   = 'wc-' . apply_filters( 'woocommerce_default_order_status', 'pending' );
		$order_data['ping_status']   = 'closed';
		$order_data['post_author']   = 1;
		$order_data['post_password'] = uniqid( 'order_' );
		$order_data['post_title']    = sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		$order_data['post_parent']   = absint( $args['parent'] );
		$order_id                    = wp_insert_post( apply_filters( 'woocommerce_new_order_data', $order_data ), true );
		$this->set_order_id( $order_id );
	}

	/**
     * Read from the database.
	 * @since 2.6.0
     * @access protected
     * @todo Convert to custom tables.
     * @param int ID of object to read.
     */
    protected function read( $zone_id ) {

		// Standard post data
		$this->id                  = $result->ID;
		$this->order_date          = $result->post_date;
		$this->modified_date       = $result->post_modified;
		$this->customer_message    = $result->post_excerpt;
		$this->customer_note       = $result->post_excerpt;
		$this->post_status         = $result->post_status;

		// Billing email can default to user if set.
		if ( empty( $this->billing_email ) && ! empty( $this->customer_user ) && ( $user = get_user_by( 'id', $this->customer_user ) ) ) {
			$this->billing_email = $user->user_email;
		}

		// Orders store the state of prices including tax when created.
		$this->prices_include_tax = metadata_exists( 'post', $this->id, '_prices_include_tax' ) ? get_post_meta( $this->id, '_prices_include_tax', true ) === 'yes' : $this->prices_include_tax;






		// $this->set_order_id( $order );
	}

    /**
     * Update data in the database.
	 * @since 2.6.0
	 * @access protected
	 * @todo Convert to custom tables.
     * @param array $data data to save
     */
    protected function update( $zone_data ) {
		$order_data                  = array();
		$order_data['post_type']     = 'shop_order';
		$order_data['post_status']   = 'wc-' . apply_filters( 'woocommerce_default_order_status', 'pending' );
		$order_data['ping_status']   = 'closed';
		$order_data['post_author']   = 1;
		$order_data['post_password'] = uniqid( 'order_' );
		$order_data['post_title']    = sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
		$order_data['post_parent']   = absint( $args['parent'] );
		wp_update_post( $order_data );
    }

	/**
     * Delete data from the database.
	 * @since 2.6.0
     * @todo Convert to custom tables.
	 * @access protected
     */
    protected function delete() {
        wp_delete_post( $this->get_order_id() );
    }

	/**
     * Save data to the database.
	 * @since 2.6.0
     * @access protected
     */
    protected function save() {
        if ( ! $this->get_order_id() ) {
			$this->create();
        } else {
            $this->update();
        }
	}

	/*
	|--------------------------------------------------------------------------
	| Order Item Handling
	|--------------------------------------------------------------------------
	|
	| Order items are used for products, taxes, shipping, and fees within
	| each order.
	|
	*/

	/**
	 * Return an array of items/products within this order.
	 *
	 * @param string|array $type Types of line items to get (array or string).
	 * @return array
	 */
	public function get_items( $type = '' ) {
		global $wpdb;

		if ( empty( $type ) ) {
			$type = array( 'line_item' );
		}

		if ( ! is_array( $type ) ) {
			$type = array( $type );
		}

		$items          = array();
		$get_items_sql  = $wpdb->prepare( "SELECT order_item_id, order_item_name, order_item_type FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d ", $this->id );
		$get_items_sql .= "AND order_item_type IN ( '" . implode( "','", array_map( 'esc_sql', $type ) ) . "' ) ORDER BY order_item_id;";
		$line_items     = $wpdb->get_results( $get_items_sql );

		// Loop items
		foreach ( $line_items as $item ) {
			$items[ $item->order_item_id ]['name']            = $item->order_item_name;
			$items[ $item->order_item_id ]['type']            = $item->order_item_type;
			$items[ $item->order_item_id ]['item_meta']       = $this->get_item_meta( $item->order_item_id );
			$items[ $item->order_item_id ]['item_meta_array'] = $this->get_item_meta_array( $item->order_item_id );
			$items[ $item->order_item_id ]                    = $this->expand_item_meta( $items[ $item->order_item_id ] );
		}

		return apply_filters( 'woocommerce_order_get_items', $items, $this );
	}

	/**
	 * Expand item meta into the $item array.
	 * @since 2.4.0
	 * @param array $item before expansion.
	 * @return array
	 */
	public function expand_item_meta( $item ) {
		// Reserved meta keys
		$reserved_item_meta_keys = array(
			'name',
			'type',
			'item_meta',
			'item_meta_array',
			'qty',
			'tax_class',
			'product_id',
			'variation_id',
			'line_subtotal',
			'line_total',
			'line_tax',
			'line_subtotal_tax'
		);

		// Expand item meta if set.
		if ( ! empty( $item['item_meta'] ) ) {
			foreach ( $item['item_meta'] as $name => $value ) {
				if ( in_array( $name, $reserved_item_meta_keys ) ) {
					continue;
				}
				if ( '_' === substr( $name, 0, 1 ) ) {
					$item[ substr( $name, 1 ) ] = $value[0];
				} elseif ( ! in_array( $name, $reserved_item_meta_keys ) ) {
					$item[ $name ] = make_clickable( $value[0] );
				}
			}
		}
		return $item;
	}

	/**
	 * Return an array of fees within this order.
	 *
	 * @return array
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Return an array of taxes within this order.
	 *
	 * @return array
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Return an array of shipping costs within this order.
	 *
	 * @return array
	 */
	public function get_shipping_methods() {
		return $this->get_items( 'shipping' );
	}

	/**
	 * Get coupon codes only.
	 *
	 * @return array
	 */
	public function get_used_coupons() {
		return array_map( 'trim', wp_list_pluck( $this->get_items( 'coupon' ), 'name' ) );
	}

	/**
	 * Gets the count of order items of a certain type.
	 *
	 * @param string $item_type
	 * @return string
	 */
	public function get_item_count( $item_type = '' ) {
		if ( empty( $item_type ) ) {
			$item_type = array( 'line_item' );
		}
		if ( ! is_array( $item_type ) ) {
			$item_type = array( $item_type );
		}

		$items = $this->get_items( $item_type );
		$count = 0;

		foreach ( $items as $item ) {
			$count += empty( $item['qty'] ) ? 1 : $item['qty'];
		}

		return apply_filters( 'woocommerce_get_item_count', $count, $item_type, $this );
	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the order.
	 *
	 * @param string $type Order item type. Default null.
	 */
	public function remove_order_items( $type = null ) {
		global $wpdb;

		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d AND items.order_item_type = %s", $this->id, $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $this->id, $type ) );
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id and items.order_id = %d", $this->id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $this->id ) );
		}
	}

	/**
	 * Add a product line item to the order.
	 * Order must be saved prior to adding items.
	 *
	 * @since 2.2
	 * @param \WC_Product $product
	 * @param int $qty Line item quantity.
	 * @param array $args
	 * @return int updated order item ID
	 */
	public function add_product( $product, $qty = 1, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'name'         => $product->get_title(),
			'qty'          => absint( $qty ),
			'tax_class'    => $product->get_tax_class(),
			'product_id'   => $product->id,
			'variation_id' => isset( $product->variation_id ) ? $product->variation_id : 0,
			'variation'    => array(),
			'subtotal'     => $product->get_price_excluding_tax( $qty ),
			'subtotal_tax' => 0,
			'total'        => $product->get_price_excluding_tax( $qty ),
			'total_tax'    => 0,
			'taxes'        => array(
				'subtotal' => array(),
				'total'    => array()
			)
		) );
		$item = new WC_Order_Item_Product();
		$item->set_order_id( $this->get_order_id() );
		return $this->update_product( $item, $product, $args );
	}

	/**
	 * Update a line item for the order.
	 *
	 * Note this does not update order totals.
	 *
	 * @since 2.2
	 * @param object|int $item order item ID or item object.
	 * @param WC_Product $product
	 * @param array $args data to update.
	 * @return int updated order item ID
	 */
	public function update_product( $item, $product, $args ) {
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		if ( ! is_object( $product ) || ! $item->is_type( 'line_item' ) ) {
			return false;
		}

		if ( ! $this->get_order_id() ) {
			$this->save();
		}

		if ( ! $item->get_order_item_id() ) {
			$inserting = true;
		} else {
			$inserting = false;
		}

		if ( isset( $args['name'] ) ) {
			$item->set_name( $args['name'] );
		}

		if ( isset( $args['qty'] ) ) {
			$item->set_qty( $args['qty'] );

			if ( $product->backorders_require_notification() && $product->is_on_backorder( $args['qty'] ) ) {
				$item->add_meta_data( apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ) ), $args['qty'] - max( 0, $product->get_total_stock() ) );
			}

			$item->set_line_subtotal( $product->get_price_excluding_tax( $args['qty'] ) );
			$item->set_line_total( $product->get_price_excluding_tax( $args['qty'] ) );
		}

		if ( isset( $args['tax_class'] ) ) {
			$item->set_tax_class( $args['tax_class'] );
		}

		if ( isset( $args['product_id'] ) ) {
			$item->set_product_id( $args['product_id'] );
		}

		if ( isset( $args['variation_id'] ) ) {
			$item->set_variation_id( $args['variation_id'] );
		}

		if ( isset( $args['variation'] ) && is_array( $args['variation'] ) ) {
			foreach ( $args['variation'] as $key => $value ) {
				$item->add_meta_data( str_replace( 'attribute_', '', $key ), $value );
			}
		}

		if ( isset( $args['totals'] ) ) {
			// BW compatibility with old args
			if ( isset( $args['totals']['subtotal'] ) ) {
				$args['subtotal'] = $args['totals']['subtotal'];
			}
			if ( isset( $args['totals']['total'] ) ) {
				$args['total'] = $args['totals']['total'];
			}
			if ( isset( $args['totals']['subtotal_tax'] ) ) {
				$args['subtotal_tax'] = $args['totals']['subtotal_tax'];
			}
			if ( isset( $args['totals']['tax'] ) ) {
				$args['total_tax'] = $args['totals']['tax'];
			}
			if ( isset( $args['totals']['tax_data'] ) ) {
				$args['taxes'] = $args['totals']['tax_data'];
			}
		}

		if ( isset( $args['subtotal'] ) ) {
			$item->set_subtotal( $args['subtotal'] );
		}
		if ( isset( $args['total'] ) ) {
			$item->set_total( $args['total'] );
		}
		if ( isset( $args['subtotal_tax'] ) ) {
			$item->set_line_subtotal_tax( $args['subtotal_tax'] );
		}
		if ( isset( $args['total_tax'] ) ) {
			$item->set_total_tax( $args['total_tax'] );
		}
		if ( isset( $args['taxes'] ) ) {
			$item->set_taxes( $args['taxes'] );
		}

		$item->save();

		if ( $inserting ) {
			do_action( 'woocommerce_order_add_product', $this->get_order_id(), $item->get_order_item_id(), $args, $product );
		} else {
			do_action( 'woocommerce_order_edit_product', $this->get_order_id(), $item->get_order_item_id(), $args, $product );
		}

		return $item->get_order_item_id();
	}

	/**
	 * Add coupon code to the order.
	 * Order must be saved prior to adding items.
	 *
	 * @param string $code
	 * @param int $discount_amount
	 * @param int $discount_amount_tax "Discounted" tax - used for tax inclusive prices.
	 * @return int updated order item ID
	 */
	public function add_coupon( $code, $discount_amount = 0, $discount_amount_tax = 0 ) {
		$args = wp_parse_args( $args, array(
			'code'                => $code,
			'discount_amount'     => $discount_amount,
			'discount_amount_tax' => $discount_amount_tax
		) );
		$item = new WC_Order_Item_Coupon();
		$item->set_order_id( $this->get_order_id() );
		return $this->update_coupon( $item, $args );
	}

	/**
	 * Update coupon for order. Note this does not update order totals.
	 * @since 2.2
	 * @param object|int $item
	 * @param array $args
	 * @return int updated order item ID
	 */
	public function update_coupon( $item, $args ) {
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		if ( ! is_object( $product ) || ! $item->is_type( 'coupon' ) ) {
			return false;
		}

		if ( ! $this->get_order_id() ) {
			$this->save();
		}

		if ( ! $item->get_order_item_id() ) {
			$inserting = true;
		} else {
			$inserting = false;
		}

		if ( isset( $args['code'] ) ) {
			$item->set_coupon_code( $args['code'] );
		}
		if ( isset( $args['discount_amount'] ) ) {
			$item->set_discount_amount( $args['discount_amount'] );
		}
		if ( isset( $args['discount_amount_tax'] ) ) {
			$item->set_discount_amount_tax( $args['discount_amount_tax'] );
		}

		$item->save();

		if ( $inserting ) {
			do_action( 'woocommerce_order_add_coupon', $this->get_order_id(), $item->get_order_item_id(), $args );
		} else {
			do_action( 'woocommerce_order_update_coupon', $this->get_order_id(), $item->get_order_item_id(), $args );
		}

		return $item->get_order_item_id();
	}

	/**
	 * Add a shipping row to the order.
	 * Order must be saved prior to adding items.
	 *
	 * @param WC_Shipping_Rate shipping_rate
	 * @return int updated order item ID
	 */
	public function add_shipping( $shipping_rate ) {
		$args = wp_parse_args( $args, array(
			'method_title' => $shipping_rate->label,
			'method_id'    => $shipping_rate->id,
			'cost'         => wc_format_decimal( $shipping_rate->cost ),
			'taxes'        => $shipping_rate->taxes
		) );
		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $this->get_order_id() );
		return $this->update_shipping( $item, $args );
	}

	/**
	 * Update shipping method for order.
	 *
	 * Note this does not update the order total.
	 *
	 * @since 2.2
	 * @param object|int $item
	 * @param array $args
	 * @return int updated order item ID
	 */
	public function update_shipping( $item, $args ) {
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		if ( ! is_object( $product ) || ! $item->is_type( 'shipping' ) ) {
			return false;
		}

		if ( ! $this->get_order_id() ) {
			$this->save();
		}

		if ( ! $item->get_order_item_id() ) {
			$inserting = true;
		} else {
			$inserting = false;
		}

		if ( isset( $args['method_title'] ) ) {
			$item->set_method_title( $args['method_title'] );
		}

		if ( isset( $args['method_id'] ) ) {
			$item->set_method_id( $args['method_id'] );
		}

		if ( isset( $args['cost'] ) ) {
			// Get old cost before updating
			$old_cost = $item->get_cost();

			// Update
			$item->set_cost( $args['cost'] );

			// Update total
			$this->set_total( $this->get_total_shipping() - wc_format_decimal( $old_cost ) + $item->get_cost(), 'shipping' );
		}

		if ( isset( $args['taxes'] ) && is_array( $args['taxes'] ) ) {
			$item->set_taxes( $args['taxes'] );
		}

		$item->save();

		if ( $inserting ) {
			do_action( 'woocommerce_order_add_shipping', $this->get_order_id(), $item->get_order_item_id(), $args );
		} else {
			do_action( 'woocommerce_order_update_shipping', $this->get_order_id(), $item->get_order_item_id(), $args );
		}

		return $item->get_order_item_id();
	}

	/**
	 * Add a fee to the order.
	 * Order must be saved prior to adding items.
	 * @param object $fee
	 * @return int updated order item ID
	 */
	public function add_fee( $fee ) {
		$args = wp_parse_args( $args, array(
			'name'      => $fee->name,
			'tax_class' => $fee->taxable ? $fee->tax_class : 0,
			'total'     => $fee->amount,
			'total_tax' => $fee->tax,
			'taxes'     => array(
				'total' => $fee->tax_data
			)
		) );
		$item = new WC_Order_Item_Fee();
		$item->set_order_id( $this->get_order_id() );
		$item_id = $this->update_fee( $item, $args );

		do_action( 'woocommerce_order_add_fee', $this->get_order_id(), $item->get_order_item_id(), $fee );

		return $item_id;
	}

	/**
	 * Update fee for order.
	 *
	 * Note this does not update order totals.
	 *
	 * @since 2.2
	 * @param object|int $item
	 * @param array $args
	 * @return int updated order item ID
	 */
	public function update_fee( $item, $args ) {
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		if ( ! is_object( $product ) || ! $item->is_type( 'fee' ) ) {
			return false;
		}

		if ( ! $this->get_order_id() ) {
			$this->save();
		}

		if ( ! $item->get_order_item_id() ) {
			$inserting = true;
		} else {
			$inserting = false;
		}

		if ( isset( $args['name'] ) ) {
			$item->set_name( $args['name'] );
		}

		if ( isset( $args['tax_class'] ) ) {
			$item->set_tax_class( $args['tax_class'] );
		}

		if ( isset( $args['total'] ) ) {
			$item->set_total( $args['total'] );
		}

		if ( isset( $args['total_tax'] ) ) {
			$item->set_total_tax( $args['total_tax'] );
		}

		if ( isset( $args['taxes'] ) ) {
			$item->set_taxes( $args['taxes'] );
		}

		$item->save();

		if ( ! $inserting ) {
			do_action( 'woocommerce_order_update_fee', $this->get_order_id(), $item->get_order_item_id(), $args );
		}

		return $item->get_order_item_id();
	}

	/**
	 * Add a tax row to the order.
	 * Order must be saved prior to adding items.
	 * @since 2.2
	 * @param int tax_rate_id
	 * @return int updated order item ID
	 */
	public function add_tax( $tax_rate_id, $tax_amount = 0, $shipping_tax_amount = 0 ) {
		if ( ! $code = WC_Tax::get_rate_code( $tax_rate_id ) ) {
			return false;
		}

		$args = wp_parse_args( $args, array(
	        'rate_code'          => $code,
	        'rate_id'            => $tax_rate_id,
	        'label'              => WC_Tax::get_rate_label( $tax_rate_id ),
	        'compound'           => WC_Tax::is_compound( $tax_rate_id ),
	        'tax_total'          => $tax_amount,
	        'shipping_tax_total' => $shipping_tax_amount
		) );
		$item = new WC_Order_Item_Tax();
		$item->set_order_id( $this->get_order_id() );
		$item_id = $this->update_tax( $item, $args );

		do_action( 'woocommerce_order_add_tax', $this->get_order_id(), $item->get_order_item_id(), $tax_rate_id, $tax_amount, $shipping_tax_amount );

		return $item_id;
	}

	/**
	 * Update tax line on order.
	 * Note this does not update order totals.
	 *
	 * @since 2.6
	 * @param object|int $item
	 * @param array $args
	 * @return int updated order item ID
	 */
	public function update_tax( $item, $args ) {
		if ( is_numeric( $item ) ) {
			$item = $this->get_item( $item );
		}

		if ( ! is_object( $product ) || ! $item->is_type( 'tax' ) ) {
			return false;
		}

		if ( ! $this->get_order_id() ) {
			$this->save();
		}

		if ( ! $item->get_order_item_id() ) {
			$inserting = true;
		} else {
			$inserting = false;
		}

		if ( isset( $args['rate_code'] ) ) {
			$item->set_rate_code( $args['rate_code'] );
		}

		if ( isset( $args['rate_id'] ) ) {
			$item->set_rate_id( $args['rate_id'] );
		}

		if ( isset( $args['label'] ) ) {
			$item->set_label( $args['label'] );
		}

		if ( isset( $args['compound'] ) ) {
			$item->set_compound( $args['compound'] );
		}

		if ( isset( $args['tax_total'] ) ) {
			$item->set_tax_total( $args['tax_total'] );
		}

		if ( isset( $args['shipping_tax_total'] ) ) {
			$item->set_shipping_tax_total( $args['shipping_tax_total'] );
		}

		$item->save();

		if ( ! $inserting ) {
			do_action( 'woocommerce_order_update_tax', $this->get_order_id(), $item->get_order_item_id(), $args );
		}

		return $item->get_order_item_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Calculations.
	|--------------------------------------------------------------------------
	|
	| These methods calculate order totals and taxes based on the current data.
	|
	*/

	/**
	 * Calculate shipping total.
	 *
	 * @since 2.2
	 * @return float
	 */
	public function calculate_shipping() {
		$shipping_total = 0;

		foreach ( $this->get_shipping_methods() as $shipping ) {
			$shipping_total += $shipping['cost'];
		}

		$this->set_total( $shipping_total, 'shipping' );

		return $this->get_total_shipping();
	}

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows.
	 *
	 * Will use the base country unless customer addresses are set.
	 *
	 * @return bool success or fail.
	 */
	public function calculate_taxes() {
		$tax_total    = 0;
		$taxes        = array();
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		if ( 'billing' === $tax_based_on ) {
			$country  = $this->billing_country;
			$state    = $this->billing_state;
			$postcode = $this->billing_postcode;
			$city     = $this->billing_city;
		} elseif ( 'shipping' === $tax_based_on ) {
			$country  = $this->shipping_country;
			$state    = $this->shipping_state;
			$postcode = $this->shipping_postcode;
			$city     = $this->shipping_city;
		}

		// Default to base
		if ( 'base' === $tax_based_on || empty( $country ) ) {
			$default  = wc_get_base_location();
			$country  = $default['country'];
			$state    = $default['state'];
			$postcode = '';
			$city     = '';
		}

		// Get items
		foreach ( $this->get_items( array( 'line_item', 'fee' ) ) as $item_id => $item ) {

			$product           = $this->get_product_from_item( $item );
			$line_total        = isset( $item['line_total'] ) ? $item['line_total'] : 0;
			$line_subtotal     = isset( $item['line_subtotal'] ) ? $item['line_subtotal'] : 0;
			$tax_class         = $item['tax_class'];
			$item_tax_status   = $product ? $product->get_tax_status() : 'taxable';

			if ( '0' !== $tax_class && 'taxable' === $item_tax_status ) {

				$tax_rates = WC_Tax::find_rates( array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
					'tax_class' => $tax_class
				) );

				$line_subtotal_taxes = WC_Tax::calc_tax( $line_subtotal, $tax_rates, false );
				$line_taxes          = WC_Tax::calc_tax( $line_total, $tax_rates, false );
				$line_subtotal_tax   = max( 0, array_sum( $line_subtotal_taxes ) );
				$line_tax            = max( 0, array_sum( $line_taxes ) );
				$tax_total           += $line_tax;

				wc_update_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( $line_subtotal_tax ) );
				wc_update_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $line_tax ) );
				wc_update_order_item_meta( $item_id, '_line_tax_data', array( 'total' => $line_taxes, 'subtotal' => $line_subtotal_taxes ) );

				// Sum the item taxes
				foreach ( array_keys( $taxes + $line_taxes ) as $key ) {
					$taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
				}
			}
		}

		// Now calculate shipping tax
		$shipping_methods = $this->get_shipping_methods();

		if ( ! empty( $shipping_methods ) ) {
			$matched_tax_rates = array();
			$tax_rates         = WC_Tax::find_rates( array(
				'country'   => $country,
				'state'     => $state,
				'postcode'  => $postcode,
				'city'      => $city,
				'tax_class' => ''
			) );

			if ( ! empty( $tax_rates ) ) {
				foreach ( $tax_rates as $key => $rate ) {
					if ( isset( $rate['shipping'] ) && 'yes' === $rate['shipping'] ) {
						$matched_tax_rates[ $key ] = $rate;
					}
				}
			}

			$shipping_taxes     = WC_Tax::calc_shipping_tax( $this->order_shipping, $matched_tax_rates );
			$shipping_tax_total = WC_Tax::round( array_sum( $shipping_taxes ) );
		} else {
			$shipping_taxes     = array();
			$shipping_tax_total = 0;
		}

		// Save tax totals
		$this->set_total( $shipping_tax_total, 'shipping_tax' );
		$this->set_total( $tax_total, 'tax' );

		// Tax rows
		$this->remove_order_items( 'tax' );

		// Now merge to keep tax rows
		foreach ( array_keys( $taxes + $shipping_taxes ) as $tax_rate_id ) {
			$this->add_tax( $tax_rate_id, isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ] : 0, isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] : 0 );
		}

		return true;
	}

	/**
	 * Calculate totals by looking at the contents of the order. Stores the totals and returns the orders final total.
	 *
	 * @since 2.2
	 * @param  bool $and_taxes Calc taxes if true.
	 * @return float calculated grand total.
	 */
	public function calculate_totals( $and_taxes = true ) {
		$cart_subtotal     = 0;
		$cart_total        = 0;
		$fee_total         = 0;
		$cart_subtotal_tax = 0;
		$cart_total_tax    = 0;

		if ( $and_taxes && wc_tax_enabled() ) {
			$this->calculate_taxes();
		}

		// line items
		foreach ( $this->get_items() as $item ) {
			$cart_subtotal     += wc_format_decimal( isset( $item['line_subtotal'] ) ? $item['line_subtotal'] : 0 );
			$cart_total        += wc_format_decimal( isset( $item['line_total'] ) ? $item['line_total'] : 0 );
			$cart_subtotal_tax += wc_format_decimal( isset( $item['line_subtotal_tax'] ) ? $item['line_subtotal_tax'] : 0 );
			$cart_total_tax    += wc_format_decimal( isset( $item['line_tax'] ) ? $item['line_tax'] : 0 );
		}

		$this->calculate_shipping();

		foreach ( $this->get_fees() as $item ) {
			$fee_total += $item['line_total'];
		}

		$this->set_total( $cart_subtotal - $cart_total, 'cart_discount' );
		$this->set_total( $cart_subtotal_tax - $cart_total_tax, 'cart_discount_tax' );

		$grand_total = round( $cart_total + $fee_total + $this->get_total_shipping() + $this->get_cart_tax() + $this->get_shipping_tax(), wc_get_price_decimals() );

		$this->set_total( $grand_total, 'total' );

		return $grand_total;
	}











































	/**
	 * Update tax lines at order level by looking at the line item taxes themselves.
	 *
	 * @return bool success or fail.
	 */
	public function update_taxes() {
		$order_taxes          = array();
		$order_shipping_taxes = array();

		foreach ( $this->get_items( array( 'line_item', 'fee' ) ) as $item_id => $item ) {

			$line_tax_data = maybe_unserialize( $item['line_tax_data'] );

			if ( isset( $line_tax_data['total'] ) ) {

				foreach ( $line_tax_data['total'] as $tax_rate_id => $tax ) {

					if ( ! isset( $order_taxes[ $tax_rate_id ] ) ) {
						$order_taxes[ $tax_rate_id ] = 0;
					}

					$order_taxes[ $tax_rate_id ] += $tax;
				}
			}
		}

		foreach ( $this->get_items( array( 'shipping' ) ) as $item_id => $item ) {

			$line_tax_data = maybe_unserialize( $item['taxes'] );

			if ( isset( $line_tax_data ) ) {
				foreach ( $line_tax_data as $tax_rate_id => $tax ) {
					if ( ! isset( $order_shipping_taxes[ $tax_rate_id ] ) ) {
						$order_shipping_taxes[ $tax_rate_id ] = 0;
					}

					$order_shipping_taxes[ $tax_rate_id ] += $tax;
				}
			}
		}

		// Remove old existing tax rows.
		$this->remove_order_items( 'tax' );

		// Now merge to keep tax rows.
		foreach ( array_keys( $order_taxes + $order_shipping_taxes ) as $tax_rate_id ) {
			$this->add_tax( $tax_rate_id, isset( $order_taxes[ $tax_rate_id ] ) ? $order_taxes[ $tax_rate_id ] : 0, isset( $order_shipping_taxes[ $tax_rate_id ] ) ? $order_shipping_taxes[ $tax_rate_id ] : 0 );
		}

		// Save tax totals
		$this->set_total( WC_Tax::round( array_sum( $order_shipping_taxes ) ), 'shipping_tax' );
		$this->set_total( WC_Tax::round( array_sum( $order_taxes ) ), 'tax' );

		return true;
	}













































	/**
	 * Get all item meta data in array format in the order it was saved. Does not group meta by key like get_item_meta().
	 *
	 * @param mixed $order_item_id
	 * @return array of objects
	 */
	public function get_item_meta_array( $order_item_id ) {
		global $wpdb;

		// Get cache key - uses get_cache_prefix to invalidate when needed
		$cache_key       = WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'item_meta_array_' . $order_item_id;
		$item_meta_array = wp_cache_get( $cache_key, 'orders' );

		if ( false === $item_meta_array ) {
			$item_meta_array = array();
			$metadata        = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d ORDER BY meta_id", absint( $order_item_id ) ) );
			foreach ( $metadata as $metadata_row ) {
				$item_meta_array[ $metadata_row->meta_id ] = (object) array( 'key' => $metadata_row->meta_key, 'value' => $metadata_row->meta_value );
			}
			wp_cache_set( $cache_key, $item_meta_array, 'orders' );
		}

		return $item_meta_array ;
	}

	/**
	 * Display meta data belonging to an item.
	 * @param  array $item
	 */
	public function display_item_meta( $item ) {
		$product   = $this->get_product_from_item( $item );
		$item_meta = new WC_Order_Item_Meta( $item, $product );
		$item_meta->display();
	}

	/**
	 * Get order item meta.
	 *
	 * @param mixed $order_item_id
	 * @param string $key (default: '')
	 * @param bool $single (default: false)
	 * @return array|string
	 */
	public function get_item_meta( $order_item_id, $key = '', $single = false ) {
		return get_metadata( 'order_item', $order_item_id, $key, $single );
	}

	/** Total Getters *******************************************************/

	/**
	 * Gets the total discount amount.
	 * @param  bool $ex_tax Show discount excl any tax.
	 * @return float
	 */
	public function get_total_discount( $ex_tax = true ) {
		if ( ! $this->order_version || version_compare( $this->order_version, '2.3.7', '<' ) ) {
			// Backwards compatible total calculation - totals were not stored consistently in old versions.
			if ( $ex_tax ) {
				if ( $this->prices_include_tax ) {
					$total_discount = (double) $this->cart_discount - (double) $this->cart_discount_tax;
				} else {
					$total_discount = (double) $this->cart_discount;
				}
			} else {
				if ( $this->prices_include_tax ) {
					$total_discount = (double) $this->cart_discount;
				} else {
					$total_discount = (double) $this->cart_discount + (double) $this->cart_discount_tax;
				}
			}
		// New logic - totals are always stored exclusive of tax, tax total is stored in cart_discount_tax
		} else {
			if ( $ex_tax ) {
				$total_discount = (double) $this->cart_discount;
			} else {
				$total_discount = (double) $this->cart_discount + (double) $this->cart_discount_tax;
			}
		}
		return apply_filters( 'woocommerce_order_amount_total_discount', round( $total_discount, WC_ROUNDING_PRECISION ), $this );
	}

	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax ) {
			$price = ( $item['line_subtotal'] + $item['line_subtotal_tax'] ) / max( 1, $item['qty'] );
		} else {
			$price = ( $item['line_subtotal'] / max( 1, $item['qty'] ) );
		}

		$price = $round ? number_format( (float) $price, wc_get_price_decimals(), '.', '' ) : $price;

		return apply_filters( 'woocommerce_order_amount_item_subtotal', $price, $this, $item, $inc_tax, $round );
	}

	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_line_subtotal( $item, $inc_tax = false, $round = true ) {
		if ( $inc_tax ) {
			$price = $item['line_subtotal'] + $item['line_subtotal_tax'];
		} else {
			$price = $item['line_subtotal'];
		}

		$price = $round ? round( $price, wc_get_price_decimals() ) : $price;

		return apply_filters( 'woocommerce_order_amount_line_subtotal', $price, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {

		$qty = ( ! empty( $item['qty'] ) ) ? $item['qty'] : 1;

		if ( $inc_tax ) {
			$price = ( $item['line_total'] + $item['line_tax'] ) / max( 1, $qty );
		} else {
			$price = $item['line_total'] / max( 1, $qty );
		}

		$price = $round ? round( $price, wc_get_price_decimals() ) : $price;

		return apply_filters( 'woocommerce_order_amount_item_total', $price, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate line total - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_line_total( $item, $inc_tax = false, $round = true ) {

		// Check if we need to add line tax to the line total.
		$line_total = $inc_tax ? $item['line_total'] + $item['line_tax'] : $item['line_total'];

		// Check if we need to round.
		$line_total = $round ? round( $line_total, wc_get_price_decimals() ) : $line_total;

		return apply_filters( 'woocommerce_order_amount_line_total', $line_total, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate item tax - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_tax( $item, $round = true ) {
		$price = $item['line_tax'] / max( 1, $item['qty'] );
		$price = $round ? wc_round_tax_total( $price ) : $price;

		return apply_filters( 'woocommerce_order_amount_item_tax', $price, $item, $round, $this );
	}

	/**
	 * Calculate line tax - useful for gateways.
	 *
	 * @param mixed $item
	 * @return float
	 */
	public function get_line_tax( $item ) {
		return apply_filters( 'woocommerce_order_amount_line_tax', wc_round_tax_total( $item['line_tax'] ), $item, $this );
	}

	/** End Total Getters *******************************************************/

	/**
	 * Gets formatted shipping method title.
	 *
	 * @return string
	 */
	public function get_shipping_method() {
		$labels           = array();
		$shipping_methods = $this->get_shipping_methods();

		foreach ( $shipping_methods as $shipping ) {
			$labels[] = $shipping['name'];
		}

		return apply_filters( 'woocommerce_order_shipping_method', implode( ', ', $labels ), $this );
	}

	/**
	 * Gets line subtotal - formatted for display.
	 *
	 * @param array  $item
	 * @param string $tax_display
	 * @return string
	 */
	public function get_formatted_line_subtotal( $item, $tax_display = '' ) {

		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) {
			return '';
		}

		if ( 'excl' == $tax_display ) {
			$ex_tax_label = $this->prices_include_tax ? 1 : 0;

			$subtotal = wc_price( $this->get_line_subtotal( $item ), array( 'ex_tax_label' => $ex_tax_label, 'currency' => $this->get_order_currency() ) );
		} else {
			$subtotal = wc_price( $this->get_line_subtotal( $item, true ), array('currency' => $this->get_order_currency()) );
		}

		return apply_filters( 'woocommerce_order_formatted_line_subtotal', $subtotal, $item, $this );
	}



	/**
	 * Gets order total - formatted for display.
	 *
	 * @return string
	 */
	public function get_formatted_order_total() {
		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_order_currency() ) );
		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this );
	}

	/**
	 * Gets subtotal - subtotal is shown before discounts, but with localised taxes.
	 *
	 * @param bool $compound (default: false).
	 * @param string $tax_display (default: the tax_display_cart value).
	 * @return string
	 */
	public function get_subtotal_to_display( $compound = false, $tax_display = '' ) {

		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		$subtotal = 0;

		if ( ! $compound ) {
			foreach ( $this->get_items() as $item ) {

				if ( ! isset( $item['line_subtotal'] ) || ! isset( $item['line_subtotal_tax'] ) ) {
					return '';
				}

				$subtotal += $item['line_subtotal'];

				if ( 'incl' == $tax_display ) {
					$subtotal += $item['line_subtotal_tax'];
				}
			}

			$subtotal = wc_price( $subtotal, array('currency' => $this->get_order_currency()) );

			if ( $tax_display == 'excl' && $this->prices_include_tax ) {
				$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}

		} else {

			if ( 'incl' == $tax_display ) {
				return '';
			}

			foreach ( $this->get_items() as $item ) {

				$subtotal += $item['line_subtotal'];

			}

			// Add Shipping Costs.
			$subtotal += $this->get_total_shipping();

			// Remove non-compound taxes.
			foreach ( $this->get_taxes() as $tax ) {

				if ( ! empty( $tax['compound'] ) ) {
					continue;
				}

				$subtotal = $subtotal + $tax['tax_amount'] + $tax['shipping_tax_amount'];

			}

			// Remove discounts.
			$subtotal = $subtotal - $this->get_total_discount();

			$subtotal = wc_price( $subtotal, array('currency' => $this->get_order_currency()) );
		}

		return apply_filters( 'woocommerce_order_subtotal_to_display', $subtotal, $compound, $this );
	}


	/**
	 * Gets shipping (formatted).
	 *
	 * @return string
	 */
	public function get_shipping_to_display( $tax_display = '' ) {
		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		if ( $this->order_shipping != 0 ) {

			if ( $tax_display == 'excl' ) {

				// Show shipping excluding tax.
				$shipping = wc_price( $this->order_shipping, array('currency' => $this->get_order_currency()) );

				if ( $this->order_shipping_tax != 0 && $this->prices_include_tax ) {
					$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>', $this, $tax_display );
				}

			} else {

				// Show shipping including tax.
				$shipping = wc_price( $this->order_shipping + $this->order_shipping_tax, array('currency' => $this->get_order_currency()) );

				if ( $this->order_shipping_tax != 0 && ! $this->prices_include_tax ) {
					$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>', $this, $tax_display );
				}

			}

			$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_shipped_via', '&nbsp;<small class="shipped_via">' . sprintf( __( 'via %s', 'woocommerce' ), $this->get_shipping_method() ) . '</small>', $this );

		} elseif ( $this->get_shipping_method() ) {
			$shipping = $this->get_shipping_method();
		} else {
			$shipping = __( 'Free!', 'woocommerce' );
		}

		return apply_filters( 'woocommerce_order_shipping_to_display', $shipping, $this );
	}

	/**
	 * Get the discount amount (formatted).
	 * @since  2.3.0
	 * @return string
	 */
	public function get_discount_to_display( $tax_display = '' ) {
		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}
		return apply_filters( 'woocommerce_order_discount_to_display', wc_price( $this->get_total_discount( $tax_display === 'excl' && $this->display_totals_ex_tax ), array( 'currency' => $this->get_order_currency() ) ), $this );
	}


	/**
	 * Get a product (either product or variation).
	 *
	 * @param mixed $item
	 * @return WC_Product
	 */
	public function get_product_from_item( $item ) {
		if ( ! empty( $item['variation_id'] ) && 'product_variation' === get_post_type( $item['variation_id'] ) ) {
			$_product = wc_get_product( $item['variation_id'] );
		} elseif ( ! empty( $item['product_id']  ) ) {
			$_product = wc_get_product( $item['product_id'] );
		} else {
			$_product = false;
		}
		return apply_filters( 'woocommerce_get_product_from_item', $_product, $item, $this );
	}


	/**
	 * Get totals for display on pages and in emails.
	 *
	 * @param mixed $tax_display
	 * @return array
	 */
	public function get_order_item_totals( $tax_display = '' ) {

		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		$total_rows = array();

		if ( $subtotal = $this->get_subtotal_to_display( false, $tax_display ) ) {
			$total_rows['cart_subtotal'] = array(
				'label' => __( 'Subtotal:', 'woocommerce' ),
				'value'	=> $subtotal
			);
		}

		if ( $this->get_total_discount() > 0 ) {
			$total_rows['discount'] = array(
				'label' => __( 'Discount:', 'woocommerce' ),
				'value'	=> '-' . $this->get_discount_to_display( $tax_display )
			);
		}

		if ( $this->get_shipping_method() ) {
			$total_rows['shipping'] = array(
				'label' => __( 'Shipping:', 'woocommerce' ),
				'value'	=> $this->get_shipping_to_display( $tax_display )
			);
		}

		if ( $fees = $this->get_fees() ) {
			foreach ( $fees as $id => $fee ) {

				if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', $fee['line_total'] + $fee['line_tax'] == 0, $id ) ) {
					continue;
				}

				if ( 'excl' == $tax_display ) {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => ( $fee['name'] ? $fee['name'] : __( 'Fee', 'woocommerce' ) ) . ':',
						'value'	=> wc_price( $fee['line_total'], array('currency' => $this->get_order_currency()) )
					);

				} else {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => $fee['name'] . ':',
						'value'	=> wc_price( $fee['line_total'] + $fee['line_tax'], array('currency' => $this->get_order_currency()) )
					);
				}
			}
		}

		// Tax for tax exclusive prices.
		if ( 'excl' === $tax_display ) {

			if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {

					$total_rows[ sanitize_title( $code ) ] = array(
						'label' => $tax->label . ':',
						'value'	=> $tax->formatted_amount
					);
				}

			} else {

				$total_rows['tax'] = array(
					'label' => WC()->countries->tax_or_vat() . ':',
					'value'	=> wc_price( $this->get_total_tax(), array( 'currency' => $this->get_order_currency() ) )
				);
			}
		}

		if ( $this->get_total() > 0 && $this->payment_method_title ) {
			$total_rows['payment_method'] = array(
				'label' => __( 'Payment Method:', 'woocommerce' ),
				'value' => $this->payment_method_title
			);
		}

		if ( $refunds = $this->get_refunds() ) {
			foreach ( $refunds as $id => $refund ) {
				$total_rows[ 'refund_' . $id ] = array(
					'label' => $refund->get_refund_reason() ? $refund->get_refund_reason() : __( 'Refund', 'woocommerce' ) . ':',
					'value'	=> wc_price( '-' . $refund->get_refund_amount(), array( 'currency' => $this->get_order_currency() ) )
				);
			}
		}

		$total_rows['order_total'] = array(
			'label' => __( 'Total:', 'woocommerce' ),
			'value'	=> $this->get_formatted_order_total( $tax_display )
		);

		return apply_filters( 'woocommerce_get_order_item_totals', $total_rows, $this );
	}







	/**
	 * Get the downloadable files for an item in this order.
	 *
	 * @param  array $item
	 * @return array
	 */
	public function get_item_downloads( $item ) {
		global $wpdb;

		$product_id   = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
		$product      = wc_get_product( $product_id );
		if ( ! $product ) {
			/**
			 * $product can be `false`. Example: checking an old order, when a product or variation has been deleted.
			 * @see \WC_Product_Factory::get_product
			 */
			return array();
		}
		$download_ids = $wpdb->get_col( $wpdb->prepare("
			SELECT download_id
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
			ORDER BY permission_id
		", $this->billing_email, $this->get_order_key(), $product_id ) );

		$files = array();

		foreach ( $download_ids as $download_id ) {

			if ( $product->has_file( $download_id ) ) {
				$files[ $download_id ]                 = $product->get_file( $download_id );
				$files[ $download_id ]['download_url'] = $this->get_download_url( $product_id, $download_id );
			}
		}

		return apply_filters( 'woocommerce_get_item_downloads', $files, $item, $this );
	}

	/**
	 * Display download links for an order item.
	 * @param  array $item
	 */
	public function display_item_downloads( $item ) {
		$product = $this->get_product_from_item( $item );

		if ( $product && $product->exists() && $product->is_downloadable() && $this->is_download_permitted() ) {
			$download_files = $this->get_item_downloads( $item );
			$i              = 0;
			$links          = array();

			foreach ( $download_files as $download_id => $file ) {
				$i++;
				$prefix  = count( $download_files ) > 1 ? sprintf( __( 'Download %d', 'woocommerce' ), $i ) : __( 'Download', 'woocommerce' );
				$links[] = '<small class="download-url">' . $prefix . ': <a href="' . esc_url( $file['download_url'] ) . '" target="_blank">' . esc_html( $file['name'] ) . '</a></small>' . "\n";
			}

			echo '<br/>' . implode( '<br/>', $links );
		}
	}

	/**
	 * Get the Download URL.
	 *
	 * @param  int $product_id
	 * @param  int $download_id
	 * @return string
	 */
	public function get_download_url( $product_id, $download_id ) {
		return add_query_arg( array(
			'download_file' => $product_id,
			'order'         => $this->get_order_key(),
			'email'         => urlencode( $this->billing_email ),
			'key'           => $download_id
		), trailingslashit( home_url() ) );
	}

	/**
	 * List order notes (public) for the customer.
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {
		$notes = array();
		$args  = array(
			'post_id' => $this->id,
			'approve' => 'approve',
			'type'    => ''
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		$comments = get_comments( $args );

		foreach ( $comments as $comment ) {
			if ( ! get_comment_meta( $comment->comment_ID, 'is_customer_note', true ) ) {
				continue;
			}
			$comment->comment_content = make_clickable( $comment->comment_content );
			$notes[] = $comment;
		}

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		return $notes;
	}

	/**
	 * Adds a note (comment) to the order.
	 *
	 * @param string $note Note to add.
	 * @param int $is_customer_note (default: 0) Is this a note for the customer?
	 * @param  bool added_by_user Was the note added by a user?
	 * @return int Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->id ) && $added_by_user ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
		} else {
			$comment_author       = __( 'WooCommerce', 'woocommerce' );
			$comment_author_email = strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
			$comment_author_email = sanitize_email( $comment_author_email );
		}

		$comment_post_ID        = $this->id;
		$comment_author_url     = '';
		$comment_content        = $note;
		$comment_agent          = 'WooCommerce';
		$comment_type           = 'order_note';
		$comment_parent         = 0;
		$comment_approved       = 1;
		$commentdata            = apply_filters( 'woocommerce_new_order_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'order_id' => $this->id, 'is_customer_note' => $is_customer_note ) );

		$comment_id = wp_insert_comment( $commentdata );

		if ( $is_customer_note ) {
			add_comment_meta( $comment_id, 'is_customer_note', 1 );

			do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->id, 'customer_note' => $commentdata['comment_content'] ) );
		}

		return $comment_id;
	}

	/*
	|--------------------------------------------------------------------------
	| Status Updates and actions
	|--------------------------------------------------------------------------
	|
	| Methods which update the order immediately. Order must exist prior to use.
	|
	*/

	/**
	 * Updates status of order.
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @param string $note (default: '') Optional note to add.
	 * @param bool $manual is this a manual order status change?
	 * @return bool Successful change or not
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		if ( ! $this->id ) {
			return false;
		}

		// Standardise status names.
		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		$old_status = $this->get_status();

		// If the statuses are the same there is no need to update, unless the post status is not a valid 'wc' status.
		if ( $new_status === $old_status && in_array( $this->post_status, array_keys( wc_get_order_statuses() ) ) ) {
			return false;
		}

		// Update the order.
		wp_update_post( array( 'ID' => $this->id, 'post_status' => 'wc-' . $new_status ) );
		$this->post_status = 'wc-' . $new_status;

		$this->add_order_note( trim( $note . ' ' . sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), wc_get_order_status_name( $old_status ), wc_get_order_status_name( $new_status ) ) ), 0, $manual );

		// Status was changed.
		do_action( 'woocommerce_order_status_' . $new_status, $this->id );
		do_action( 'woocommerce_order_status_' . $old_status . '_to_' . $new_status, $this->id );
		do_action( 'woocommerce_order_status_changed', $this->id, $old_status, $new_status );

		switch ( $new_status ) {

			case 'completed' :
				// Record the sales.
				$this->record_product_sales();

				// Increase coupon usage counts.
				$this->increase_coupon_usage_counts();

				// Record the completed date of the order.
				update_post_meta( $this->id, '_completed_date', current_time('mysql') );

				// Update reports.
				wc_delete_shop_order_transients( $this->id );
				break;

			case 'processing' :
			case 'on-hold' :
				// Record the sales.
				$this->record_product_sales();

				// Increase coupon usage counts.
				$this->increase_coupon_usage_counts();

				// Update reports.
				wc_delete_shop_order_transients( $this->id );
				break;

			case 'cancelled' :
				// If the order is cancelled, restore used coupons.
				$this->decrease_coupon_usage_counts();

				// Update reports.
				wc_delete_shop_order_transients( $this->id );
				break;
		}

		return true;
	}

	/**
	 * Cancel the order and restore the cart (before payment).
	 *
	 * @param string $note (default: '') Optional note to add.
	 */
	public function cancel_order( $note = '' ) {
		WC()->session->set( 'order_awaiting_payment', false );
		$this->update_status( 'cancelled', $note );
	}

	/**
	 * When a payment is complete this function is called.
	 *
	 * Most of the time this should mark an order as 'processing' so that admin can process/post the items.
	 * If the cart contains only downloadable items then the order is 'completed' since the admin needs to take no action.
	 * Stock levels are reduced at this point.
	 * Sales are also recorded for products.
	 * Finally, record the date of payment.
	 *
	 * @param string $transaction_id Optional transaction id to store in post meta.
	 */
	public function payment_complete( $transaction_id = '' ) {
		do_action( 'woocommerce_pre_payment_complete', $this->id );

		if ( null !== WC()->session ) {
			WC()->session->set( 'order_awaiting_payment', false );
		}

		$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ), $this );

		if ( $this->id && $this->has_status( $valid_order_statuses ) ) {
			$order_needs_processing = false;

			if ( sizeof( $this->get_items() ) > 0 ) {
				foreach ( $this->get_items() as $item ) {
					if ( $_product = $this->get_product_from_item( $item ) ) {
						$virtual_downloadable_item = $_product->is_downloadable() && $_product->is_virtual();

						if ( apply_filters( 'woocommerce_order_item_needs_processing', ! $virtual_downloadable_item, $_product, $this->id ) ) {
							$order_needs_processing = true;
							break;
						}
					}
				}
			}

			$this->update_status( apply_filters( 'woocommerce_payment_complete_order_status', $order_needs_processing ? 'processing' : 'completed', $this->id ) );

			add_post_meta( $this->id, '_paid_date', current_time( 'mysql' ), true );

			if ( ! empty( $transaction_id ) ) {
				update_post_meta( $this->id, '_transaction_id', $transaction_id );
			}

			wp_update_post( array(
				'ID'            => $this->id,
				'post_date'     => current_time( 'mysql', 0 ),
				'post_date_gmt' => current_time( 'mysql', 1 )
			) );

			// Payment is complete so reduce stock levels
			if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! get_post_meta( $this->id, '_order_stock_reduced', true ), $this->id ) ) {
				$this->reduce_order_stock();
			}

			do_action( 'woocommerce_payment_complete', $this->id );
		} else {
			do_action( 'woocommerce_payment_complete_order_status_' . $this->get_status(), $this->id );
		}
	}

	/**
	 * Record sales.
	 */
	public function record_product_sales() {
		if ( 'yes' === get_post_meta( $this->id, '_recorded_sales', true ) ) {
			return;
		}

		if ( sizeof( $this->get_items() ) > 0 ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					update_post_meta( $item['product_id'], 'total_sales', absint( get_post_meta( $item['product_id'], 'total_sales', true ) ) + absint( $item['qty'] ) );
				}
			}
		}

		update_post_meta( $this->id, '_recorded_sales', 'yes' );

		/**
		 * Called when sales for an order are recorded
		 *
		 * @param int $order_id order id
		 */
		do_action( 'woocommerce_recorded_sales', $this->id );
	}

	/**
	 * Increase applied coupon counts.
	 */
	public function increase_coupon_usage_counts() {
		if ( 'yes' === get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) ) {
			return;
		}

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {

			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code ) {
					continue;
				}

				$coupon = new WC_Coupon( $code );

				$used_by = $this->get_user_id();

				if ( ! $used_by ) {
					$used_by = $this->billing_email;
				}

				$coupon->inc_usage_count( $used_by );
			}

			update_post_meta( $this->id, '_recorded_coupon_usage_counts', 'yes' );
		}
	}

	/**
	 * Decrease applied coupon counts.
	 */
	public function decrease_coupon_usage_counts() {
		if ( 'yes' !== get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) ) {
			return;
		}

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {

			foreach ( $this->get_used_coupons() as $code ) {

				if ( ! $code ) {
					continue;
				}

				$coupon = new WC_Coupon( $code );

				$used_by = $this->get_user_id();
				if ( ! $used_by ) {
					$used_by = $this->billing_email;
				}

				$coupon->dcr_usage_count( $used_by );
			}

			delete_post_meta( $this->id, '_recorded_coupon_usage_counts' );
		}
	}

	/**
	 * Reduce stock levels for all line items in the order.
	 * Runs if stock management is enabled, but can be disabled on per-order basis by extensions @since 2.4.0 via woocommerce_can_reduce_order_stock hook.
	 */
	public function reduce_order_stock() {
		if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && apply_filters( 'woocommerce_can_reduce_order_stock', true, $this ) && sizeof( $this->get_items() ) > 0 ) {
			foreach ( $this->get_items() as $item ) {
				if ( $item['product_id'] > 0 ) {
					$_product = $this->get_product_from_item( $item );

					if ( $_product && $_product->exists() && $_product->managing_stock() ) {
						$qty       = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $this, $item );
						$new_stock = $_product->reduce_stock( $qty );
						$item_name = $_product->get_sku() ? $_product->get_sku(): $item['product_id'];

						if ( isset( $item['variation_id'] ) && $item['variation_id'] ) {
							$this->add_order_note( sprintf( __( 'Item %s variation #%s stock reduced from %s to %s.', 'woocommerce' ), $item_name, $item['variation_id'], $new_stock + $qty, $new_stock) );
						} else {
							$this->add_order_note( sprintf( __( 'Item %s stock reduced from %s to %s.', 'woocommerce' ), $item_name, $new_stock + $qty, $new_stock) );
						}
						$this->send_stock_notifications( $_product, $new_stock, $item['qty'] );
					}
				}
			}

			add_post_meta( $this->id, '_order_stock_reduced', '1', true );

			do_action( 'woocommerce_reduce_order_stock', $this );
		}
	}

	/**
	 * Send the stock notifications.
	 *
	 * @param WC_Product $product
	 * @param int $new_stock
	 * @param int $qty_ordered
	 */
	public function send_stock_notifications( $product, $new_stock, $qty_ordered ) {
		// Backorders
		if ( $new_stock < 0 ) {
			do_action( 'woocommerce_product_on_backorder', array( 'product' => $product, 'order_id' => $this->id, 'quantity' => $qty_ordered ) );
		}

		// stock status notifications
		$notification_sent = false;

		if ( 'yes' == get_option( 'woocommerce_notify_no_stock' ) && get_option( 'woocommerce_notify_no_stock_amount' ) >= $new_stock ) {
			do_action( 'woocommerce_no_stock', $product );
			$notification_sent = true;
		}

		if ( ! $notification_sent && 'yes' == get_option( 'woocommerce_notify_low_stock' ) && get_option( 'woocommerce_notify_low_stock_amount' ) >= $new_stock ) {
			do_action( 'woocommerce_low_stock', $product );
		}

		do_action( 'woocommerce_after_send_stock_notifications', $product, $new_stock, $qty_ordered );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	|
	| Checks if a condition is true or false.
	|
	*/

	/**
	 * Checks the order status against a passed in status.
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_order_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * has_meta function for order items.
	 *
	 * @param string $order_item_id
	 * @return array of meta data.
	 */
	public function has_meta( $order_item_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id, order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d
			ORDER BY meta_id", absint( $order_item_id ) ), ARRAY_A );
	}

	/**
	 * Check whether this order has a specific shipping method or not.
	 *
	 * @param string $method_id
	 * @return bool
	 */
	public function has_shipping_method( $method_id ) {
		$shipping_methods = $this->get_shipping_methods();
		$has_method       = false;

		if ( empty( $shipping_methods ) ) {
			return false;
		}

		foreach ( $shipping_methods as $shipping_method ) {
			if ( $shipping_method['method_id'] === $method_id ) {
				$has_method = true;
				break;
			}
		}

		return $has_method;
	}

	/**
	 * Check if an order key is valid.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function key_is_valid( $key ) {
		return $key === $this->get_order_key();
	}

	/**
	 * Returns if an order has been paid for based on the order status.
	 * @since 2.5.0
	 * @return bool
	 */
	public function is_paid() {
		return apply_filters( 'woocommerce_order_is_paid', $this->has_status( apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) ) ), $this );
	}

	/**
	 * Checks if product download is permitted.
	 *
	 * @return bool
	 */
	public function is_download_permitted() {
		return apply_filters( 'woocommerce_order_is_download_permitted', $this->has_status( 'completed' ) || ( get_option( 'woocommerce_downloads_grant_access_after_payment' ) == 'yes' && $this->has_status( 'processing' ) ), $this );
	}

	/**
	 * Returns true if the order contains a downloadable product.
	 * @return bool
	 */
	public function has_downloadable_item() {
		foreach ( $this->get_items() as $item ) {
			$_product = $this->get_product_from_item( $item );

			if ( $_product && $_product->exists() && $_product->is_downloadable() && $_product->has_file() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if the order contains a free product.
	 * @since 2.5.0
	 * @return bool
	 */
	public function has_free_item() {
		foreach ( $this->get_items() as $item ) {
			if ( ! $item['line_total'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if an order needs payment, based on status and order total.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment', array( 'pending', 'failed' ), $this );

		if ( $this->has_status( $valid_order_statuses ) && $this->get_total() > 0 ) {
			$needs_payment = true;
		} else {
			$needs_payment = false;
		}

		return apply_filters( 'woocommerce_order_needs_payment', $needs_payment, $this, $valid_order_statuses );
	}

	/**
	 * Checks if an order needs display the shipping address, based on shipping method.
	 *
	 * @return boolean
	 */
	public function needs_shipping_address() {
		if ( 'no' === get_option( 'woocommerce_calc_shipping' ) ) {
			return false;
		}

		$hide  = apply_filters( 'woocommerce_order_hide_shipping_address', array( 'local_pickup' ), $this );
		$needs_address = false;

		foreach ( $this->get_shipping_methods() as $shipping_method ) {
			if ( ! in_array( $shipping_method['method_id'], $hide ) ) {
				$needs_address = true;
				break;
			}
		}

		return apply_filters( 'woocommerce_order_needs_shipping_address', $needs_address, $hide, $this );
	}

	/**
	 * Checks if an order can be edited, specifically for use on the Edit Order screen.
	 *
	 * @return bool
	 */
	public function is_editable() {
		return apply_filters( 'wc_order_is_editable', in_array( $this->get_status(), array( 'pending', 'on-hold', 'auto-draft' ) ), $this );
	}


	public function get_item( $item_id ) {
		return WC_Order_Factory::get_order_item( $item_id );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods
	|--------------------------------------------------------------------------
	|
	| Will be removed after 2 major releases, or 1 year.
	|
	*/

	/**
	 * Gets an order from the database.
	 * @deprecated 2.6
	 * @param int $id (default: 0).
	 * @return bool
	 */
	public function get_order( $id = 0 ) {
		_deprecated_function( 'get_order', '2.6', 'read' );
		if ( ! $id ) {
			return false;
		}
		if ( $result = get_post( $id ) ) {
			$this->populate( $result );
			return true;
		}
		return false;
	}

	/**
	 * Populates an order from the loaded post data.
	 * @deprecated 2.6
	 * @param mixed $result
	 */
	public function populate( $result ) {
		_deprecated_function( 'populate', '2.6', 'read' );
		$this->read( $result->ID );
	}

	/**
	 * Get cart discount (formatted).
	 * @deprecated
	 * @return string
	 */
	public function get_cart_discount_to_display( $tax_display = '' ) {
		_deprecated_function( 'get_cart_discount_to_display', '2.3', 'get_discount_to_display' );
		return apply_filters( 'woocommerce_order_cart_discount_to_display', $this->get_discount_to_display( $tax_display ), $this );
	}

	/**
	 * Gets the discount amount.
	 * @deprecated in favour of get_total_discount() since we now only have one discount type.
	 * @return float
	 */
	public function get_cart_discount() {
		_deprecated_function( 'get_cart_discount', '2.3', 'get_total_discount' );
		return apply_filters( 'woocommerce_order_amount_cart_discount', $this->get_total_discount(), $this );
	}

	/**
	 * Get cart discount (formatted).
	 *
	 * @deprecated order (after tax) discounts removed in 2.3.0.
	 * @return string
	 */
	public function get_order_discount_to_display() {
		_deprecated_function( 'get_order_discount_to_display', '2.3' );
	}

	/**
	 * Gets the total (order) discount amount - these are applied after tax.
	 *
	 * @deprecated order (after tax) discounts removed in 2.3.0.
	 * @return float
	 */
	public function get_order_discount() {
		_deprecated_function( 'get_order_discount', '2.3' );
		return apply_filters( 'woocommerce_order_amount_order_discount', (double) $this->order_discount, $this );
	}

	/**
	 * Get the billing address in an array.
	 * @deprecated 2.3
	 * @return string
	 */
	public function get_billing_address() {
		_deprecated_function( 'get_billing_address', '2.3', 'get_formatted_billing_address' );
		return $this->get_formatted_billing_address();
	}

	/**
	 * Get the shipping address in an array.
	 * @deprecated 2.3
	 * @return string
	 */
	public function get_shipping_address() {
		_deprecated_function( 'get_shipping_address', '2.3', 'get_formatted_shipping_address' );
		return $this->get_formatted_shipping_address();
	}
}
