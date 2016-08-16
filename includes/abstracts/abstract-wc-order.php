<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'abstract-wc-legacy-order.php' );

/**
 * Abstract Order
 *
 * Handles generic order data and database interaction which is extended by both
 * WC_Order (regular orders) and WC_Order_Refund (refunds are negative orders).
 *
 * @class       WC_Abstract_Order
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
abstract class WC_Abstract_Order extends WC_Abstract_Legacy_Order {

	/**
	 * Order Data array, with defaults. This is the core order data exposed
	 * in APIs since 2.7.0.
	 *
	 * Notes:
	 * order_tax = Sum of all taxes.
	 * cart_tax = cart_tax is the new name for the legacy 'order_tax' which is the tax for items only, not shipping.
	 *
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'id'                 => 0,
		'parent_id'          => 0,
		'status'             => '',
		'type'               => 'shop_order',
		'order_key'          => '',
		'currency'           => '',
		'version'            => '',
		'prices_include_tax' => false,
		'date_created'       => '',
		'date_modified'      => '',
		'customer_id'        => 0,
		'discount_total'     => 0,
		'discount_tax'       => 0,
		'shipping_total'     => 0,
		'shipping_tax'       => 0,
		'cart_tax'           => 0,
		'total'              => 0,
		'total_tax'          => 0,
	);

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_internal_meta_keys = array(
		'_customer_user', '_order_key', '_order_currency', '_cart_discount',
		'_cart_discount_tax', '_order_shipping', '_order_shipping_tax',
		'_order_tax', '_order_total', '_order_version', '_prices_include_tax',
		'_payment_tokens',
	);

	/**
	 * Order items will be stored here, sometimes before they persist in the DB.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_items = array(
		'line_items'     => null,
		'coupon_lines'   => null,
		'shipping_lines' => null,
		'fee_lines'      => null,
		'tax_lines'      => null,
	);

	/**
	 *  Internal meta type used to store order data.
	 * @var string
	 */
	protected $_meta_type = 'post';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $_cache_group = 'order';

	/**
	 * Get the order if ID is passed, otherwise the order is new and empty.
	 * This class should NOT be instantiated, but the get_order function or new WC_Order_Factory.
	 * should be used. It is possible, but the aforementioned are preferred and are the only.
	 * methods that will be maintained going forward.
	 *
	 * @param  int|object|WC_Order $order Order to init.
	 */
	public function __construct( $order = 0 ) {
		if ( is_numeric( $order ) && $order > 0 ) {
			$this->read( $order );
		} elseif ( $order instanceof self ) {
			$this->read( absint( $order->get_id() ) );
		} elseif ( ! empty( $order->ID ) ) {
			$this->read( absint( $order->ID ) );
		}
		// Set default status if none were read.
		if ( ! $this->get_status() ) {
			$this->set_status( apply_filters( 'woocommerce_default_order_status', 'pending' ) );
		}
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
	 * Get internal type (post type.)
	 * @return string
	 */
	public function get_type() {
		return 'shop_order';
	}

	/**
	 * Get a title for the new post type.
	 */
	protected function get_post_title() {
		return sprintf( __( 'Order &ndash; %s', 'woocommerce' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce' ) ) );
	}

	/**
	 * Insert data into the database.
	 * @since 2.7.0
	 */
	public function create() {
		$this->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
		$this->set_date_created( current_time( 'timestamp' ) );

		$order_id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', array(
			'post_date'     => date( 'Y-m-d H:i:s', $this->get_date_created() ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
			'post_type'     => $this->get_type(),
			'post_status'   => 'wc-' . ( $this->get_status() ? $this->get_status() : apply_filters( 'woocommerce_default_order_status', 'pending' ) ),
			'ping_status'   => 'closed',
			'post_author'   => 1,
			'post_title'    => $this->get_post_title(),
			'post_password' => uniqid( 'order_' ),
			'post_parent'   => $this->get_parent_id(),
		) ), true );

		if ( $order_id ) {
			$this->set_id( $order_id );

			// Set meta data
			$this->update_post_meta( '_customer_user', $this->get_customer_id() );
			$this->update_post_meta( '_order_currency', $this->get_currency() );
			$this->update_post_meta( '_order_key', $this->get_order_key() );
			$this->update_post_meta( '_cart_discount', $this->get_discount_total( true ) );
			$this->update_post_meta( '_cart_discount_tax', $this->get_discount_tax( true ) );
			$this->update_post_meta( '_order_shipping', $this->get_shipping_total( true ) );
			$this->update_post_meta( '_order_shipping_tax', $this->get_shipping_tax( true ) );
			$this->update_post_meta( '_order_tax', $this->get_cart_tax( true ) );
			$this->update_post_meta( '_order_total', $this->get_total( true ) );
			$this->update_post_meta( '_order_version', $this->get_version() );
			$this->update_post_meta( '_prices_include_tax', $this->get_prices_include_tax() );
			$this->save_meta_data();
		}
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int $id ID of object to read.
	 */
	public function read( $id ) {
		if ( empty( $id ) || ! ( $post_object = get_post( $id ) ) ) {
			return;
		}

		// Map standard post data
		$this->set_id( $post_object->ID );
		$this->set_parent_id( $post_object->post_parent );
		$this->set_date_created( $post_object->post_date );
		$this->set_date_modified( $post_object->post_modified );
		$this->set_status( $post_object->post_status );
		$this->set_order_type( $post_object->post_type );
		$this->set_customer_id( get_post_meta( $this->get_id(), '_customer_user', true ) );
		$this->set_order_key( get_post_meta( $this->get_id(), '_order_key', true ) );
		$this->set_currency( get_post_meta( $this->get_id(), '_order_currency', true ) );
		$this->set_discount_total( get_post_meta( $this->get_id(), '_cart_discount', true ) );
		$this->set_discount_tax( get_post_meta( $this->get_id(), '_cart_discount_tax', true ) );
		$this->set_shipping_total( get_post_meta( $this->get_id(), '_order_shipping', true ) );
		$this->set_shipping_tax( get_post_meta( $this->get_id(), '_order_shipping_tax', true ) );
		$this->set_cart_tax( get_post_meta( $this->get_id(), '_order_tax', true ) );
		$this->set_total( get_post_meta( $this->get_id(), '_order_total', true ) );

		// Orders store the state of prices including tax when created.
		$this->set_prices_include_tax( metadata_exists( 'post', $this->get_id(), '_prices_include_tax' ) ? 'yes' === get_post_meta( $this->get_id(), '_prices_include_tax', true ) : 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

		// Load meta data
		$this->read_meta_data();
	}

	/**
	 * Post meta update wrapper. Sets or deletes based on value.
	 * @since 2.7.0
	 * @return bool Was it changed?
	 */
	protected function update_post_meta( $key, $value ) {
		if ( '' !== $value ) {
			return update_post_meta( $this->get_id(), $key, $value );
		} else {
			return delete_post_meta( $this->get_id(), $key );
		}
	}

	/**
	 * Update data in the database.
	 * @since 2.7.0
	 */
	public function update() {
		global $wpdb;

		$order_id = $this->get_id();

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => date( 'Y-m-d H:i:s', $this->get_date_created() ),
				'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
				'post_status'   => 'wc-' . ( $this->get_status() ? $this->get_status() : apply_filters( 'woocommerce_default_order_status', 'pending' ) ),
				'post_parent'   => $this->get_parent_id(),
			),
			array(
				'ID' => $order_id
			)
		);

		// Update meta data
		$this->update_post_meta( '_customer_user', $this->get_customer_id() );
		$this->update_post_meta( '_order_currency', $this->get_currency() );
		$this->update_post_meta( '_order_key', $this->get_order_key() );
		$this->update_post_meta( '_cart_discount', $this->get_discount_total( true ) );
		$this->update_post_meta( '_cart_discount_tax', $this->get_discount_tax( true ) );
		$this->update_post_meta( '_order_shipping', $this->get_shipping_total( true ) );
		$this->update_post_meta( '_order_shipping_tax', $this->get_shipping_tax( true ) );
		$this->update_post_meta( '_order_tax', $this->get_cart_tax( true ) );
		$this->update_post_meta( '_order_total', $this->get_total( true ) );
		$this->update_post_meta( '_prices_include_tax', $this->get_prices_include_tax() );
		$this->save_meta_data();
	}

	/**
	 * Delete data from the database.
	 * @since 2.7.0
	 */
	public function delete() {
		wp_delete_post( $this->get_id() );
	}

	/**
	 * Save data to the database.
	 * @since 2.7.0
	 * @return int order ID
	 */
	public function save() {
		$this->set_version( WC_VERSION );

		if ( ! $this->get_id() ) {
			$this->create();
		} else {
			$this->update();
		}

		$this->save_items();
		clean_post_cache( $this->get_id() );
		wc_delete_shop_order_transients( $this->get_id() );

		return $this->get_id();
	}

	/**
	 * Save all order items which are part of this order.
	 */
	protected function save_items() {
		foreach ( $this->_items as $item_group => $items ) {
			if ( is_array( $items ) ) {
				foreach ( $items as $item_key => $item ) {
					$item->set_order_id( $this->get_id() );
					$item_id = $item->save();

					// If ID changed (new item saved to DB)...
					if ( $item_id !== $item_key ) {
						$this->_items[ $item_group ][ $item_id ] = $item;
						unset( $this->_items[ $item_group ][ $item_key ] );

						// Legacy action handler
						switch ( $item_group ) {
							case 'fee_lines' :
								if ( isset( $item->legacy_fee, $item->legacy_fee_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_order_fee_meta', array( $this->get_id(), $item_id, $item->legacy_fee, $item->legacy_fee_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
							case 'shipping_lines' :
								if ( isset( $item->legacy_package_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_shipping_order_item', array( $item_id, $item->legacy_package_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
							case 'line_items' :
								if ( isset( $item->legacy_values, $item->legacy_cart_item_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_order_item_meta', array( $item_id, $item->legacy_values, $item->legacy_cart_item_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
						}
					}
				}
			}
		}
	}

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
	 * @since 2.7.0
	 * @return array
	 */
	public function get_data() {
		return array_merge(
			$this->_data,
			array(
				'number'         => $this->get_order_number(),
				'meta_data'      => $this->get_meta_data(),
				'line_items'     => $this->get_items( 'line_item' ),
				'tax_lines'      => $this->get_items( 'tax' ),
				'shipping_lines' => $this->get_items( 'shipping' ),
				'fee_lines'      => $this->get_items( 'fee' ),
				'coupon_lines'   => $this->get_items( 'coupon' ),
			)
		);
	}

	/**
	 * Get order ID.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_id() {
		return $this->_data['id'];
	}

	/**
	 * Get parent order ID.
	 * @since 2.7.0
	 * @return integer
	 */
	public function get_parent_id() {
		return $this->_data['parent_id'];
	}

	/**
	 * get_order_number function.
	 *
	 * Gets the order number for display (by default, order ID).
	 *
	 * @return string
	 */
	public function get_order_number() {
		return apply_filters( 'woocommerce_order_number', $this->get_id(), $this );
	}

	/**
	 * Get order key.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_order_key() {
		return $this->_data['order_key'];
	}

	/**
	 * Gets order currency.
	 * @return string
	 */
	public function get_currency() {
		return apply_filters( 'woocommerce_get_currency', $this->_data['currency'], $this );
	}

	/**
	 * Get order_version
	 * @return string
	 */
	public function get_version() {
		return $this->_data['version'];
	}

	/**
	 * Get prices_include_tax
	 * @return bool
	 */
	public function get_prices_include_tax() {
		return $this->_data['prices_include_tax'];
	}

	/**
	 * Get Order Type
	 * @return string
	 */
	public function get_order_type() {
		return $this->_data['type'];
	}

	/**
	 * Get date_created
	 * @return int
	 */
	public function get_date_created() {
		return $this->_data['date_created'];
	}

	/**
	 * Get date_modified
	 * @return int
	 */
	public function get_date_modified() {
		return $this->_data['date_modified'];
	}

	/**
	 * Get customer_id
	 * @return int
	 */
	public function get_customer_id() {
		return $this->_data['customer_id'];
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 * @return string
	 */
	public function get_status() {
		return apply_filters( 'woocommerce_order_get_status', 'wc-' === substr( $this->_data['status'], 0, 3 ) ? substr( $this->_data['status'], 3 ) : $this->_data['status'], $this );
	}

	/**
	 * Alias for get_customer_id().
	 * @since  2.2
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_customer_id();
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
	 * Get discount_total
	 * @param bool $raw Gets raw unfiltered value.
	 * @return string
	 */
	public function get_discount_total( $raw = false ) {
		$value = wc_format_decimal( $this->_data['discount_total'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_discount_total', $value, $this );
	}

	/**
	 * Get discount_tax
	 * @param bool $raw Gets raw unfiltered value.
	 * @return string
	 */
	public function get_discount_tax( $raw = false ) {
		$value = wc_format_decimal( $this->_data['discount_tax'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_discount_tax', $value, $this );
	}

	/**
	 * Get shipping_total
	 * @param bool $raw Gets raw unfiltered value.
	 * @return string
	 */
	public function get_shipping_total( $raw = false ) {
		$value = wc_format_decimal( $this->_data['shipping_total'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_shipping_total', $value, $this );
	}

	/**
	 * Get shipping_tax.
	 * @param bool $raw Gets raw unfiltered value.
	 * @return string
	 */
	public function get_shipping_tax( $raw = false ) {
		$value = wc_format_decimal( $this->_data['shipping_tax'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_shipping_tax', $value, $this );
	}

	/**
	 * Gets cart tax amount.
	 * @param bool $raw Gets raw unfiltered value.
	 * @return float
	 */
	public function get_cart_tax( $raw = false ) {
		$value = wc_format_decimal( $this->_data['cart_tax'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_cart_tax', $value, $this );
	}

	/**
	 * Gets order grand total. incl. taxes. Used in gateways. Filtered.
	 * @param bool $raw Gets raw unfiltered value.
	 * @return float
	 */
	public function get_total( $raw = false ) {
		$value = wc_format_decimal( $this->_data['total'], wc_get_price_decimals() );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_total', $value, $this );
	}

	/**
	 * Get total tax amount. Alias for get_order_tax().
	 *
	 * @since 2.7.0 woocommerce_order_amount_total_tax filter has been removed to avoid
	 * these values being modified and then saved back to the DB. There are
	 * other, later hooks available to change totals on display. e.g.
	 * woocommerce_get_order_item_totals.
	 * @param bool $raw Gets raw unfiltered value.
	 * @return float
	 */
	public function get_total_tax( $raw = false ) {
		$value = wc_format_decimal( $this->_data['total_tax'] );
		return $raw ? $value : apply_filters( 'woocommerce_order_amount_total_tax', $value, $this );
	}

	/**
	 * Gets the total discount amount.
	 * @param  bool $ex_tax Show discount excl any tax.
	 * @return float
	 */
	public function get_total_discount( $ex_tax = true ) {
		if ( $ex_tax ) {
			$total_discount = $this->get_discount_total();
		} else {
			$total_discount = $this->get_discount_total() + $this->get_discount_tax();
		}
		return apply_filters( 'woocommerce_order_amount_total_discount', round( $total_discount, WC_ROUNDING_PRECISION ), $this );
	}

	/**
	 * Gets order subtotal.
	 * @return float
	 */
	public function get_subtotal() {
		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += $item->get_subtotal();
		}

		return apply_filters( 'woocommerce_order_amount_subtotal', (double) $subtotal, $this );
	}

	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$tax_totals = array();

		foreach ( $this->get_items( 'tax' ) as $key => $tax ) {
			$code = $tax->get_rate_code();

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals[ $code ] = new stdClass();
				$tax_totals[ $code ]->amount = 0;
			}

			$tax_totals[ $code ]->id                = $key;
			$tax_totals[ $code ]->rate_id           = $tax->get_rate_id();
			$tax_totals[ $code ]->is_compound       = $tax->is_compound();
			$tax_totals[ $code ]->label             = $tax->get_label();
			$tax_totals[ $code ]->amount           += $tax->get_tax_total() + $tax->get_shipping_tax_total();
			$tax_totals[ $code ]->formatted_amount  = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ), array( 'currency' => $this->get_currency() ) );
		}

		if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'woocommerce_order_tax_totals', $tax_totals, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting order data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object. However, for backwards compatibility pre 2.7.0 some of these
	| setters may handle both.
	|
	*/

	/**
	 * Set order ID.
	 * @since 2.7.0
	 * @param int $value
	 */
	public function set_id( $value ) {
		$this->_data['id'] = absint( $value );
	}

	/**
	 * Set parent order ID.
	 * @since 2.7.0
	 * @param int $value
	 */
	public function set_parent_id( $value ) {
		$this->_data['parent_id'] = absint( $value );
	}

	/**
	 * Set order status.
	 * @since 2.7.0
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @param array details of change
	 */
	 public function set_status( $new_status ) {
		$old_status = $this->get_status();
		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;

		// Only allow valid new status
		if ( ! in_array( 'wc-' . $new_status, array_keys( wc_get_order_statuses() ) ) ) {
			$new_status = 'pending';
		}

		$this->_data['status'] = 'wc-' . $new_status;

		// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
		if ( $old_status && ! in_array( 'wc-' . $old_status, array_keys( wc_get_order_statuses() ) ) ) {
			$old_status = 'pending';
		}

		return array(
			'from' => $old_status,
			'to'   => $new_status
		);
	 }

	/**
	 * Set Order Type
	 * @param string $value
	 */
	public function set_order_type( $value ) {
		$this->_data['type'] = $value;
	}

	/**
	 * Set order_key.
	 * @param string $value Max length 20 chars.
	 */
	public function set_order_key( $value ) {
		$this->_data['order_key'] = substr( $value, 0, 20 );
	}

	/**
	 * Set order_version
	 * @param string $value
	 */
	public function set_version( $value ) {
		$this->_data['version'] = $value;
	}

	/**
	 * Set order_currency
	 * @param string $value
	 */
	public function set_currency( $value ) {
		$this->_data['currency'] = $value;
	}

	/**
	 * Set prices_include_tax
	 * @param bool $value
	 */
	public function set_prices_include_tax( $value ) {
		$this->_data['prices_include_tax'] = (bool) $value;
	}

	/**
	 * Set date_created
	 * @param string $timestamp Timestamp
	 */
	public function set_date_created( $timestamp ) {
		$this->_data['date_created'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set date_modified
	 * @param string $timestamp
	 */
	public function set_date_modified( $timestamp ) {
		$this->_data['date_modified'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set customer_id
	 * @param int $value
	 */
	public function set_customer_id( $value ) {
		$this->_data['customer_id'] = absint( $value );
	}

	/**
	 * Set discount_total
	 * @param string $value
	 */
	public function set_discount_total( $value ) {
		$this->_data['discount_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set discount_tax
	 * @param string $value
	 */
	public function set_discount_tax( $value ) {
		$this->_data['discount_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_total
	 * @param string $value
	 */
	public function set_shipping_total( $value ) {
		$this->_data['shipping_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_tax
	 * @param string $value
	 */
	public function set_shipping_tax( $value ) {
		$this->_data['shipping_tax'] = wc_format_decimal( $value );
		$this->set_total_tax( $this->get_cart_tax() + $this->get_shipping_tax() );
	}

	/**
	 * Set cart tax
	 * @param string $value
	 */
	public function set_cart_tax( $value ) {
		$this->_data['cart_tax'] = wc_format_decimal( $value );
		$this->set_total_tax( $this->get_cart_tax() + $this->get_shipping_tax() );
	}

	/**
	 * Sets order tax (sum of cart and shipping tax). Used internaly only.
	 * @param string $value
	 */
	protected function set_total_tax( $value ) {
		$this->_data['total_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Set total
	 * @param string $value
	 * @param string $deprecated Function used to set different totals based on this.
	 */
	public function set_total( $value, $deprecated = '' ) {
		if ( $deprecated ) {
			_deprecated_argument( 'total_type', '2.7', 'Use dedicated total setter methods instead.' );
			return $this->legacy_set_total( $value, $deprecated );
		}
		$this->_data['total'] = wc_format_decimal( $value, wc_get_price_decimals() );
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
	 * Prefixes an item key with a string so arrays are associative rather than numeric.
	 * @param  int $id
	 * @return string
	 */
	protected function prefix_item_id( $id ) {
		return 'item-' . $id;
	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the order.
	 * @param string $type Order item type. Default null.
	 */
	public function remove_order_items( $type = null ) {
		global $wpdb;
		if ( ! empty( $type ) ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id AND items.order_id = %d AND items.order_item_type = %s", $this->get_id(), $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $this->get_id(), $type ) );
			if ( $group = $this->type_to_group( $type ) ) {
				$this->_items[ $group ] = null;
			}
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM itemmeta USING {$wpdb->prefix}woocommerce_order_itemmeta itemmeta INNER JOIN {$wpdb->prefix}woocommerce_order_items items WHERE itemmeta.order_item_id = items.order_item_id and items.order_id = %d", $this->get_id() ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $this->get_id() ) );
			$this->_items = array(
				'line_items'     => null,
				'coupon_lines'   => null,
				'shipping_lines' => null,
				'fee_lines'      => null,
				'tax_lines'      => null,
			);
		}
	}

	/**
	 * Convert a type to a types group.
	 * @param string $type
	 * @return string group
	 */
	protected function type_to_group( $type ) {
		$type_to_group = array(
			'line_item' => 'line_items',
			'tax'       => 'tax_lines',
			'shipping'  => 'shipping_lines',
			'fee'       => 'fee_lines',
			'coupon'    => 'coupon_lines',
		);
		return isset( $type_to_group[ $type ] ) ? $type_to_group[ $type ] : '';
	}

	/**
	 * Return an array of items/products within this order.
	 * @param string|array $types Types of line items to get (array or string).
	 * @return Array of WC_Order_item
	 */
	public function get_items( $types = 'line_item' ) {
		$items = array();
		$types = array_filter( (array) $types );

		foreach ( $types as $type ) {
			if ( $group = $this->type_to_group( $type ) ) {
				if ( is_null( $this->_items[ $group ] ) ) {
					$this->_items[ $group ] = $this->get_items_from_db( $type );
				}
				$items = array_merge( $items, $this->_items[ $group ] );
			}
		}

		return apply_filters( 'woocommerce_order_get_items', $items, $this );
	}

	/**
	 * Gets items from the database by type.
	 * @param  string $type
	 * @return array
	 */
	protected function get_items_from_db( $type ) {
		global $wpdb;

		$get_items_sql = $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s ORDER BY order_item_id;", $this->get_id(), $type ) ;
		$items         = $wpdb->get_results( $get_items_sql );

		if ( ! empty( $items ) ) {
			$items = array_map( array( $this, 'get_item' ), array_combine( array_map( array( $this, 'prefix_item_id' ), wp_list_pluck( $items, 'order_item_id' ) ), $items ) );
		} else {
			$items = array();
		}

		return $items;
	}

	/**
	 * Return an array of fees within this order.
	 * @return array
	 */
	public function get_fees() {
		return $this->get_items( 'fee' );
	}

	/**
	 * Return an array of taxes within this order.
	 * @return array
	 */
	public function get_taxes() {
		return $this->get_items( 'tax' );
	}

	/**
	 * Return an array of shipping costs within this order.
	 * @return array
	 */
	public function get_shipping_methods() {
		return $this->get_items( 'shipping' );
	}

	/**
	 * Gets formatted shipping method title.
	 * @return string
	 */
	public function get_shipping_method() {
		$names = array();
		foreach ( $this->get_shipping_methods() as $shipping_method ) {
			$names[] = $shipping_method->get_name();
		}
		return apply_filters( 'woocommerce_order_shipping_method', implode( ', ', $names ), $this );
	}

	/**
	 * Get coupon codes only.
	 * @return array
	 */
	public function get_used_coupons() {
		$coupon_codes = array();
		if ( $coupons = $this->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				$coupon_codes[] = $coupon->get_code();
			}
		}
		return $coupon_codes;
	}

	/**
	 * Gets the count of order items of a certain type.
	 *
	 * @param string $item_type
	 * @return string
	 */
	public function get_item_count( $item_type = '' ) {
		$items = $this->get_items( empty( $item_type ) ? 'line_item' : $item_type );
		$count = 0;

		foreach ( $items as $item ) {
			$count += $item->get_quantity();
		}

		return apply_filters( 'woocommerce_get_item_count', $count, $item_type, $this );
	}

	/**
	 * Get an order item object, based on it's type.
	 * @since  2.7.0
	 * @param  int $item_id
	 * @return WC_Order_Item
	 */
	public function get_item( $item_id ) {
		return WC_Order_Factory::get_order_item( $item_id );
	}

	/**
	 * Adds an order item to this order. The order item will not persist until save.
	 * @param object Order item (product, shipping, fee, coupon, tax)
	 */
	public function add_item( $item ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$item_type = 'line_item';
			$items_key = 'line_items';
		} elseif ( is_a( $item, 'WC_Order_Item_Fee' ) ) {
			$item_type = 'fee';
			$items_key = 'fee_lines';
		} elseif ( is_a( $item, 'WC_Order_Item_Shipping' ) ) {
			$item_type = 'shipping';
			$items_key = 'shipping_lines';
		} elseif ( is_a( $item, 'WC_Order_Item_Tax' ) ) {
			$item_type = 'tax';
			$items_key = 'tax_lines';
		} elseif ( is_a( $item, 'WC_Order_Item_Coupon' ) ) {
			$item_type = 'coupon';
			$items_key = 'coupon_lines';
		} else {
			return false;
		}

		// Make sure existing items are loaded so we can append this new one.
		if ( is_null( $this->_items[ $items_key ] ) ) {
			$this->_items[ $items_key ] = $this->get_items( $item_type );
		}

		// Append new row with generated temporary ID
		if ( $item->get_id() ) {
			$this->_items[ $items_key ][ $this->prefix_item_id( $item->get_id() ) ] = $item;
		} else {
			$this->_items[ $items_key ][ 'new:' . md5( json_encode( $item ) ) ] = $item;
		}
	}

	/**
	 * Add a product line item to the order.
	 * @param  \WC_Product $product
	 * @param  int $qty
	 * @param  array $args
	 * @return int order item ID
	 */
	public function add_product( $product, $qty = 1, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'qty'          => $qty,
			'name'         => $product ? $product->get_title() : '',
			'tax_class'    => $product ? $product->get_tax_class() : '',
			'product_id'   => $product ? $product->get_id() : '',
			'variation_id' => $product && isset( $product->variation_id ) ? $product->variation_id : 0,
			'variation'    => $product && isset( $product->variation_id ) ? $product->get_variation_attributes() : array(),
			'subtotal'     => $product ? $product->get_price_excluding_tax( $qty ) : '',
			'total'        => $product ? $product->get_price_excluding_tax( $qty ) : '',
			'subtotal_tax' => 0,
			'total_tax'    => 0,
			'taxes'        => array(
				'subtotal' => array(),
				'total'    => array(),
			),
		) );

		// BW compatibility with old args
		if ( isset( $args['totals'] ) ) {
			foreach ( $args['totals'] as $key => $value ) {
				if ( 'tax' === $key ) {
					$args['total_tax'] = $value;
				} elseif ( 'tax_data' === $key ) {
					$args['taxes'] = $value;
				} else {
					$args[ $key ] = $value;
				}
			}
		}

		$item = new WC_Order_Item_Product( $args );
		$item->set_backorder_meta();
		$item->set_order_id( $this->get_id() );
		$item->save();
		$this->add_item( $item );
		wc_do_deprecated_action( 'woocommerce_order_add_product', array( $this->get_id(), $item->get_id(), $product, $qty, $args ), '2.7', 'Use woocommerce_new_order_item action instead.' );
		return $item->get_id();
	}

	/**
	 * Add coupon code to the order.
	 * @param string $code
	 * @param int $discount tax amount.
	 * @param int $discount_tax amount.
	 * @return int order item ID
	 */
	public function add_coupon( $code = array(), $discount = 0, $discount_tax = 0 ) {
		$item = new WC_Order_Item_Coupon( array(
			'code'         => $code,
			'discount'     => $discount,
			'discount_tax' => $discount_tax,
		) );
		$item->set_order_id( $this->get_id() );
		$item->save();
		$this->add_item( $item );
		wc_do_deprecated_action( 'woocommerce_order_add_coupon', array( $this->get_id(), $item->get_id(), $code, $discount, $discount_tax ), '2.7', 'Use woocommerce_new_order_item action instead.' );
		return $item->get_id();
	}

	/**
	 * Add a tax row to the order.
	 * @param array $args
	 * @param int $deprecated1 2.7.0 tax_rate_id, amount, shipping amount.
	 * @param int $deprecated2 2.7.0 tax_rate_id, amount, shipping amount.
	 * @return int order item ID
	 */
	public function add_tax( $args = array(), $deprecated1 = 0, $deprecated2 = 0 ) {
		if ( ! is_array( $args ) ) {
			_deprecated_argument( 'tax_rate_id', '2.7', 'Pass only an array of args' );
			$args = array(
				'rate_id'            => $args,
				'tax_total'          => $deprecated1,
				'shipping_tax_total' => $deprecated2,
			);
		}
		$args = wp_parse_args( $args, array(
			'rate_id'            => '',
			'tax_total'          => 0,
			'shipping_tax_total' => 0,
			'rate_code'          => isset( $args['rate_id'] ) ? WC_Tax::get_rate_code( $args['rate_id'] ) : '',
			'label'              => isset( $args['rate_id'] ) ? WC_Tax::get_rate_label( $args['rate_id'] ) : '',
			'compound'           => isset( $args['rate_id'] ) ? WC_Tax::is_compound( $args['rate_id'] ) : '',
		) );
		$item = new WC_Order_Item_Tax( $args );
		$item->set_order_id( $this->get_id() );
		$item->save();
		$this->add_item( $item );
		wc_do_deprecated_action( 'woocommerce_order_add_tax', array( $this->get_id(), $item->get_id(), $args['rate_id'], $args['tax_total'], $args['shipping_tax_total'] ), '2.7', 'Use woocommerce_new_order_item action instead.' );
		return $item->get_id();
	}

	/**
	 * Add a shipping row to the order.
	 * @param WC_Shipping_Rate shipping_rate
	 * @return int order item ID
	 */
	public function add_shipping( $shipping_rate ) {
		$item = new WC_Order_Item_Shipping( array(
			'method_title' => $shipping_rate->label,
			'method_id'    => $shipping_rate->id,
			'total'        => wc_format_decimal( $shipping_rate->cost ),
			'taxes'        => $shipping_rate->taxes,
			'meta_data'    => $shipping_rate->get_meta_data(),
		) );
		$item->set_order_id( $this->get_id() );
		$item->save();
		$this->add_item( $item );
		wc_do_deprecated_action( 'woocommerce_order_add_shipping', array( $this->get_id(), $item->get_id(), $shipping_rate ), '2.7', 'Use woocommerce_new_order_item action instead.' );
		return $item->get_id();
	}

	/**
	 * Add a fee to the order.
	 * Order must be saved prior to adding items.
	 * @param object $fee
	 * @return int updated order item ID
	 */
	public function add_fee( $fee ) {
		$item = new WC_Order_Item_Fee( array(
			'name'      => $fee->name,
			'tax_class' => $fee->taxable ? $fee->tax_class : 0,
			'total'     => $fee->amount,
			'total_tax' => $fee->tax,
			'taxes'     => array(
				'total' => $fee->tax_data,
			),
		) );
		$item->set_order_id( $this->get_id() );
		$item->save();
		$this->add_item( $item );
		wc_do_deprecated_action( 'woocommerce_order_add_fee', array( $this->get_id(), $item->get_id(), $fee ), '2.7', 'Use woocommerce_new_order_item action instead.' );
		return $item->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Payment Token Handling
	|--------------------------------------------------------------------------
	|
	| Payment tokens are hashes used to take payments by certain gateways.
	|
	*/

	/**
	 * Add a payment token to an order
	 *
	 * @since 2.6
	 * @param  WC_Payment_Token   $token     Payment token object
	 * @return boolean|int The new token ID or false if it failed.
	 */
	public function add_payment_token( $token ) {
		if ( empty( $token ) || ! ( $token instanceof WC_Payment_Token ) ) {
			return false;
		}

		$token_ids = get_post_meta( $this->get_id(), '_payment_tokens', true );

		if ( empty ( $token_ids ) ) {
			$token_ids = array();
		}

		$token_ids[] = $token->get_id();

		update_post_meta( $this->get_id(), '_payment_tokens', $token_ids );
		do_action( 'woocommerce_payment_token_added_to_order', $this->get_id(), $token->get_id(), $token, $token_ids );
		return $token->get_id();
	}

	/**
	 * Returns a list of all payment tokens associated with the current order
	 *
	 * @since 2.6
	 * @return array An array of payment token objects
	 */
	public function get_payment_tokens() {
		return WC_Payment_Tokens::get_order_tokens( $this->get_id() );
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
			$shipping_total += $shipping->get_total();
		}

		$this->set_shipping_total( $shipping_total );
		$this->save();

		return $this->get_shipping_total();
	}

	/**
	 * Get all tax classes for items in the order.
	 *
	 * @since 2.6.3
	 * @return array
	 */
	public function get_items_tax_classes() {
		$found_tax_classes = array();

		foreach ( $this->get_items() as $item ) {
			if ( $_product = $this->get_product_from_item( $item ) ) {
				$found_tax_classes[] = $_product->get_tax_class();
			}
		}

		return array_unique( $found_tax_classes );
	}

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows.
	 *
	 * Will use the base country unless customer addresses are set.
	 * @param $args array Added in 2.7.0 to pass things like location.
	 */
	public function calculate_taxes( $args = array() ) {
		$tax_based_on      = get_option( 'woocommerce_tax_based_on' );
		$args              = wp_parse_args( $args, array(
			'country'  => 'billing' === $tax_based_on ? $this->get_billing_country()  : $this->get_shipping_country(),
			'state'    => 'billing' === $tax_based_on ? $this->get_billing_state()    : $this->get_shipping_state(),
			'postcode' => 'billing' === $tax_based_on ? $this->get_billing_postcode() : $this->get_shipping_postcode(),
			'city'     => 'billing' === $tax_based_on ? $this->get_billing_city()     : $this->get_shipping_city(),
		) );

		// Default to base
		if ( 'base' === $tax_based_on || empty( $args['country'] ) ) {
			$default          = wc_get_base_location();
			$args['country']  = $default['country'];
			$args['state']    = $default['state'];
			$args['postcode'] = '';
			$args['city']     = '';
		}

		// Calc taxes for line items
		foreach ( $this->get_items( array( 'line_item', 'fee' ) ) as $item_id => $item ) {
			$tax_class           = $item->get_tax_class();
			$tax_status          = $item->get_tax_status();

			if ( '0' !== $tax_class && 'taxable' === $tax_status ) {
				$tax_rates = WC_Tax::find_rates( array(
					'country'   => $args['country'],
					'state'     => $args['state'],
					'postcode'  => $args['postcode'],
					'city'      => $args['city'],
					'tax_class' => $tax_class,
				) );

				$total = $item->get_total();
				$taxes = WC_Tax::calc_tax( $total, $tax_rates, false );

				if ( $item->is_type( 'line_item' ) ) {
					$subtotal       = $item->get_subtotal();
					$subtotal_taxes = WC_Tax::calc_tax( $subtotal, $tax_rates, false );
					$subtotal_tax   = max( 0, array_sum( $subtotal_taxes ) );
					$item->set_subtotal_tax( $subtotal_tax );
					$item->set_taxes( array( 'total' => $taxes, 'subtotal' => $subtotal_taxes ) );
				} else {
					$item->set_taxes( array( 'total' => $taxes ) );
				}
				$item->save();
			}
		}

		// Calc taxes for shipping
		foreach ( $this->get_shipping_methods() as $item_id => $item ) {
			$shipping_tax_class = get_option( 'woocommerce_shipping_tax_class' );

			// Inherit tax class from items
			if ( '' === $shipping_tax_class ) {
				$tax_rates         = array();
				$tax_classes       = array_merge( array( '' ), WC_Tax::get_tax_classes() );
				$found_tax_classes = $this->get_items_tax_classes();

				foreach ( $tax_classes as $tax_class ) {
					$tax_class = sanitize_title( $tax_class );
					if ( in_array( $tax_class, $found_tax_classes ) ) {
						$tax_rates = WC_Tax::find_shipping_rates( array(
							'country'   => $args['country'],
							'state'     => $args['state'],
							'postcode'  => $args['postcode'],
							'city'      => $args['city'],
							'tax_class' => $tax_class,
						) );
						break;
					}
				}
			} else {
				$tax_rates = WC_Tax::find_shipping_rates( array(
					'country'   => $args['country'],
					'state'     => $args['state'],
					'postcode'  => $args['postcode'],
					'city'      => $args['city'],
					'tax_class' => 'standard' === $shipping_tax_class ? '' : $shipping_tax_class,
				) );
			}
			$item->set_taxes( array( 'total' => WC_Tax::calc_tax( $item->get_total(), $tax_rates, false ) ) );
			$item->save();
		}
		$this->update_taxes();
	}

	/**
	 * Update tax lines for the order based on the line item taxes themselves.
	 */
	public function update_taxes() {
		$cart_taxes     = array();
		$shipping_taxes = array();

		foreach ( $this->get_items( array( 'line_item', 'fee' ) ) as $item_id => $item ) {
			$taxes = $item->get_taxes();
			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$cart_taxes[ $tax_rate_id ] = isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] + $tax : $tax;
			}
		}

		foreach ( $this->get_shipping_methods() as $item_id => $item ) {
			$taxes = $item->get_taxes();
			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$shipping_taxes[ $tax_rate_id ] = isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] + $tax : $tax;
			}
		}

		// Remove old existing tax rows.
		$this->remove_order_items( 'tax' );

		// Now merge to keep tax rows.
		foreach ( array_keys( $cart_taxes + $shipping_taxes ) as $tax_rate_id ) {
			$this->add_tax( array(
				'rate_id'            => $tax_rate_id,
				'tax_total'          => isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] : 0,
				'shipping_tax_total' => isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] : 0,
			) );
		}

		// Save tax totals
		$this->set_shipping_tax( WC_Tax::round( array_sum( $shipping_taxes ) ) );
		$this->set_cart_tax( WC_Tax::round( array_sum( $cart_taxes ) ) );
		$this->save();
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
			$cart_subtotal     += wc_format_decimal( $item->get_subtotal() );
			$cart_total        += wc_format_decimal( $item->get_total() );
			$cart_subtotal_tax += wc_format_decimal( $item->get_subtotal_tax() );
			$cart_total_tax    += wc_format_decimal( $item->get_total_tax() );
		}

		$this->calculate_shipping();

		foreach ( $this->get_fees() as $item ) {
			$fee_total += $item->get_total();
		}

		$grand_total = round( $cart_total + $fee_total + $this->get_shipping_total() + $this->get_cart_tax() + $this->get_shipping_tax(), wc_get_price_decimals() );

		$this->set_discount_total( $cart_subtotal - $cart_total );
		$this->set_discount_tax( $cart_subtotal_tax - $cart_total_tax );
		$this->set_total( $grand_total );
		$this->save();

		return $grand_total;
	}

	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * @param object $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		$subtotal = 0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
			if ( $inc_tax ) {
				$subtotal = ( $item->get_subtotal() + $item->get_subtotal_tax() ) / max( 1, $item->get_quantity() );
			} else {
				$subtotal = ( $item->get_subtotal() / max( 1, $item->get_quantity() ) );
			}

			$subtotal = $round ? number_format( (float) $subtotal, wc_get_price_decimals(), '.', '' ) : $subtotal;
		}

		return apply_filters( 'woocommerce_order_amount_item_subtotal', $subtotal, $this, $item, $inc_tax, $round );
	}

	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * @param object $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_line_subtotal( $item, $inc_tax = false, $round = true ) {
		$subtotal = 0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
			if ( $inc_tax ) {
				$subtotal = $item->get_subtotal() + $item->get_subtotal_tax();
			} else {
				$subtotal = $item->get_subtotal();
			}

			$subtotal = $round ? round( $subtotal, wc_get_price_decimals() ) : $subtotal;
		}

		return apply_filters( 'woocommerce_order_amount_line_subtotal', $subtotal, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * @param object $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {
		$total = 0;

		if ( is_callable( array( $item, 'get_total' ) ) ) {
			if ( $inc_tax ) {
				$total = ( $item->get_total() + $item->get_total_tax() ) / max( 1, $item->get_quantity() );
			} else {
				$total = $item->get_total() / max( 1, $item->get_quantity() );
			}

			$total = $round ? round( $total, wc_get_price_decimals() ) : $total;
		}

		return apply_filters( 'woocommerce_order_amount_item_total', $total, $this, $item, $inc_tax, $round );
	}

	/**
	 * Calculate line total - useful for gateways.
	 *
	 * @param object $item
	 * @param bool $inc_tax (default: false).
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_line_total( $item, $inc_tax = false, $round = true ) {
		$total = 0;

		if ( is_callable( array( $item, 'get_total' ) ) ) {
			// Check if we need to add line tax to the line total.
			$total = $inc_tax ? $item->get_total() + $item->get_total_tax() : $item->get_total();

			// Check if we need to round.
			$total = $round ? round( $total, wc_get_price_decimals() ) : $total;
		}

		return apply_filters( 'woocommerce_order_amount_line_total', $total, $this, $item, $inc_tax, $round );
	}

	/**
	 * Get item tax - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $round (default: true).
	 * @return float
	 */
	public function get_item_tax( $item, $round = true ) {
		$tax = 0;

		if ( is_callable( array( $item, 'get_total_tax' ) ) ) {
			$tax = $item->get_total_tax() / max( 1, $item->get_quantity() );
			$tax = $round ? wc_round_tax_total( $tax ) : $tax;
		}

		return apply_filters( 'woocommerce_order_amount_item_tax', $tax, $item, $round, $this );
	}

	/**
	 * Get line tax - useful for gateways.
	 *
	 * @param mixed $item
	 * @return float
	 */
	public function get_line_tax( $item ) {
		return apply_filters( 'woocommerce_order_amount_line_tax', is_callable( array( $item, 'get_total_tax' ) ) ? wc_round_tax_total( $item->get_total_tax() ) : 0, $item, $this );
	}

	/**
	 * Gets line subtotal - formatted for display.
	 *
	 * @param array  $item
	 * @param string $tax_display
	 * @return string
	 */
	public function get_formatted_line_subtotal( $item, $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );

		if ( 'excl' == $tax_display ) {
			$ex_tax_label = $this->get_prices_include_tax() ? 1 : 0;

			$subtotal = wc_price( $this->get_line_subtotal( $item ), array( 'ex_tax_label' => $ex_tax_label, 'currency' => $this->get_currency() ) );
		} else {
			$subtotal = wc_price( $this->get_line_subtotal( $item, true ), array('currency' => $this->get_currency()) );
		}

		return apply_filters( 'woocommerce_order_formatted_line_subtotal', $subtotal, $item, $this );
	}

	/**
	 * Gets order total - formatted for display.
	 * @return string
	 */
	public function get_formatted_order_total() {
		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_currency() ) );
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
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );
		$subtotal    = 0;

		if ( ! $compound ) {
			foreach ( $this->get_items() as $item ) {
				$subtotal += $item->get_subtotal();

				if ( 'incl' === $tax_display ) {
					$subtotal += $item->get_subtotal_tax();
				}
			}

			$subtotal = wc_price( $subtotal, array( 'currency' => $this->get_currency() ) );

			if ( 'excl' === $tax_display && $this->get_prices_include_tax() ) {
				$subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}

		} else {
			if ( 'incl' === $tax_display ) {
				return '';
			}

			foreach ( $this->get_items() as $item ) {
				$subtotal += $item->get_subtotal();
			}

			// Add Shipping Costs.
			$subtotal += $this->get_shipping_total();

			// Remove non-compound taxes.
			foreach ( $this->get_taxes() as $tax ) {
				if ( $this->is_compound() ) {
					continue;
				}
				$subtotal = $subtotal + $tax->get_tax_total() + $tax->get_shipping_tax_total();
			}

			// Remove discounts.
			$subtotal = $subtotal - $this->get_total_discount();
			$subtotal = wc_price( $subtotal, array( 'currency' => $this->get_currency() ) );
		}

		return apply_filters( 'woocommerce_order_subtotal_to_display', $subtotal, $compound, $this );
	}

	/**
	 * Gets shipping (formatted).
	 *
	 * @return string
	 */
	public function get_shipping_to_display( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );

		if ( $this->get_shipping_total() != 0 ) {

			if ( $tax_display == 'excl' ) {

				// Show shipping excluding tax.
				$shipping = wc_price( $this->get_shipping_total(), array('currency' => $this->get_currency()) );

				if ( $this->get_shipping_tax() != 0 && $this->get_prices_include_tax() ) {
					$shipping .= apply_filters( 'woocommerce_order_shipping_to_display_tax_label', '&nbsp;<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>', $this, $tax_display );
				}

			} else {

				// Show shipping including tax.
				$shipping = wc_price( $this->get_shipping_total() + $this->get_shipping_tax(), array('currency' => $this->get_currency()) );

				if ( $this->get_shipping_tax() != 0 && ! $this->get_prices_include_tax() ) {
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
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );
		return apply_filters( 'woocommerce_order_discount_to_display', wc_price( $this->get_total_discount( 'excl' === $tax_display && 'excl' === get_option( 'woocommerce_tax_display_cart' ) ), array( 'currency' => $this->get_currency() ) ), $this );
	}

	/**
	 * Get totals for display on pages and in emails.
	 *
	 * @param mixed $tax_display
	 * @return array
	 */
	public function get_order_item_totals( $tax_display = '' ) {
		$tax_display = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_cart' );
		$total_rows  = array();

		if ( $subtotal = $this->get_subtotal_to_display( false, $tax_display ) ) {
			$total_rows['cart_subtotal'] = array(
				'label' => __( 'Subtotal:', 'woocommerce' ),
				'value'    => $subtotal,
			);
		}

		if ( $this->get_total_discount() > 0 ) {
			$total_rows['discount'] = array(
				'label' => __( 'Discount:', 'woocommerce' ),
				'value'    => '-' . $this->get_discount_to_display( $tax_display ),
			);
		}

		if ( $this->get_shipping_method() ) {
			$total_rows['shipping'] = array(
				'label' => __( 'Shipping:', 'woocommerce' ),
				'value'    => $this->get_shipping_to_display( $tax_display ),
			);
		}

		if ( $fees = $this->get_fees() ) {
			foreach ( $fees as $id => $fee ) {
				if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', empty( $fee['line_total'] ) && empty( $fee['line_tax'] ), $id ) ) {
					continue;
				}
				$total_rows[ 'fee_' . $fee->get_id() ] = array(
					'label' => $fee->get_name() . ':',
					'value' => wc_price( 'excl' === $tax_display ? $fee->get_total() : $fee->get_total() + $fee->get_total_tax(), array('currency' => $this->get_currency()) )
				);
			}
		}

		// Tax for tax exclusive prices.
		if ( 'excl' === $tax_display ) {

			if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {

					$total_rows[ sanitize_title( $code ) ] = array(
						'label' => $tax->label . ':',
						'value'    => $tax->formatted_amount,
					);
				}

			} else {

				$total_rows['tax'] = array(
					'label' => WC()->countries->tax_or_vat() . ':',
					'value'    => wc_price( $this->get_total_tax(), array( 'currency' => $this->get_currency() ) ),
				);
			}
		}

		if ( $this->get_total() > 0 && $this->get_payment_method_title() ) {
			$total_rows['payment_method'] = array(
				'label' => __( 'Payment Method:', 'woocommerce' ),
				'value' => $this->get_payment_method_title(),
			);
		}

		if ( $refunds = $this->get_refunds() ) {
			foreach ( $refunds as $id => $refund ) {
				$total_rows[ 'refund_' . $id ] = array(
					'label' => $refund->get_refund_reason() ? $refund->get_refund_reason() : __( 'Refund', 'woocommerce' ) . ':',
					'value'    => wc_price( '-' . $refund->get_refund_amount(), array( 'currency' => $this->get_currency() ) ),
				);
			}
		}

		$total_rows['order_total'] = array(
			'label' => __( 'Total:', 'woocommerce' ),
			'value'    => $this->get_formatted_order_total( $tax_display ),
		);

		return apply_filters( 'woocommerce_get_order_item_totals', $total_rows, $this );
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
	 * Check whether this order has a specific shipping method or not.
	 *
	 * @param string $method_id
	 * @return bool
	 */
	public function has_shipping_method( $method_id ) {
		foreach ( $this->get_shipping_methods() as $shipping_method ) {
			if ( $shipping_method->get_method_id() === $method_id ) {
				return true;
			}
		}
		return false;
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
	 * Returns true if the order contains a free product.
	 * @since 2.5.0
	 * @return bool
	 */
	public function has_free_item() {
		foreach ( $this->get_items() as $item ) {
			if ( ! $item->get_total() ) {
				return true;
			}
		}
		return false;
	}
}
