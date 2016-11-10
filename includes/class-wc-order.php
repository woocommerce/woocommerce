<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Class.
 *
 * These are regular WooCommerce orders, which extend the abstract order class.
 *
 * @class    WC_Order
 * @version  2.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order extends WC_Abstract_Order {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_customer_user',
		'_order_key',
		'_order_currency',
		'_billing_first_name',
		'_billing_last_name',
		'_billing_company',
		'_billing_address_1',
		'_billing_address_2',
		'_billing_city',
		'_billing_state',
		'_billing_postcode',
		'_billing_country',
		'_billing_email',
		'_billing_phone',
		'_shipping_first_name',
		'_shipping_last_name',
		'_shipping_company',
		'_shipping_address_1',
		'_shipping_address_2',
		'_shipping_city',
		'_shipping_state',
		'_shipping_postcode',
		'_shipping_country',
		'_completed_date',
		'_paid_date',
		'_edit_lock',
		'_edit_last',
		'_cart_discount',
		'_cart_discount_tax',
		'_order_shipping',
		'_order_shipping_tax',
		'_order_tax',
		'_order_total',
		'_payment_method',
		'_payment_method_title',
		'_transaction_id',
		'_customer_ip_address',
		'_customer_user_agent',
		'_created_via',
		'_order_version',
		'_prices_include_tax',
		'_customer_note',
		'_date_completed',
		'_date_paid',
		'_payment_tokens',
	);

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $data = array(
		// Abstract order props
		'parent_id'            => 0,
		'status'               => '',
		'currency'             => '',
		'version'              => '',
		'prices_include_tax'   => false,
		'date_created'         => '',
		'date_modified'        => '',
		'discount_total'       => 0,
		'discount_tax'         => 0,
		'shipping_total'       => 0,
		'shipping_tax'         => 0,
		'cart_tax'             => 0,
		'total'                => 0,
		'total_tax'            => 0,

		// Order props
		'customer_id'          => 0,
		'order_key'            => '',
		'billing'              => array(
			'first_name'       => '',
			'last_name'        => '',
			'company'          => '',
			'address_1'        => '',
			'address_2'        => '',
			'city'             => '',
			'state'            => '',
			'postcode'         => '',
			'country'          => '',
			'email'            => '',
			'phone'            => '',
		),
		'shipping'             => array(
			'first_name'       => '',
			'last_name'        => '',
			'company'          => '',
			'address_1'        => '',
			'address_2'        => '',
			'city'             => '',
			'state'            => '',
			'postcode'         => '',
			'country'          => '',
		),
		'payment_method'       => '',
		'payment_method_title' => '',
		'transaction_id'       => '',
		'customer_ip_address'  => '',
		'customer_user_agent'  => '',
		'created_via'          => '',
		'customer_note'        => '',
		'date_completed'       => '',
		'date_paid'            => '',
		'cart_hash'            => '',
	);

	/**
	 * When a payment is complete this function is called.
	 *
	 * Most of the time this should mark an order as 'processing' so that admin can process/post the items.
	 * If the cart contains only downloadable items then the order is 'completed' since the admin needs to take no action.
	 * Stock levels are reduced at this point.
	 * Sales are also recorded for products.
	 * Finally, record the date of payment.
	 *
	 * Order must exist.
	 *
	 * @param string $transaction_id Optional transaction id to store in post meta.
	 * @return bool success
	 */
	public function payment_complete( $transaction_id = '' ) {
		try {
			if ( ! $this->get_id() ) {
				return false;
			}
			do_action( 'woocommerce_pre_payment_complete', $this->get_id() );

			if ( ! empty( WC()->session ) ) {
				WC()->session->set( 'order_awaiting_payment', false );
			}

			if ( $this->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ), $this ) ) ) {
				$order_needs_processing = false;

				if ( sizeof( $this->get_items() ) > 0 ) {
					foreach ( $this->get_items() as $item ) {
						if ( $item->is_type( 'line_item' ) && ( $product = $item->get_product() ) ) {
							$virtual_downloadable_item = $product->is_downloadable() && $product->is_virtual();

							if ( apply_filters( 'woocommerce_order_item_needs_processing', ! $virtual_downloadable_item, $product, $this->get_id() ) ) {
								$order_needs_processing = true;
								break;
							}
						}
					}
				}

				if ( ! empty( $transaction_id ) ) {
					$this->set_transaction_id( $transaction_id );
				}

				$this->set_status( apply_filters( 'woocommerce_payment_complete_order_status', $order_needs_processing ? 'processing' : 'completed', $this->get_id() ) );
				$this->set_date_paid( current_time( 'timestamp' ) );
				$this->save();

				do_action( 'woocommerce_payment_complete', $this->get_id() );
			} else {
				do_action( 'woocommerce_payment_complete_order_status_' . $this->get_status(), $this->get_id() );
			}
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Gets order total - formatted for display.
	 *
	 * @param  string $tax_display     Type of tax display.
	 * @param  bool  $display_refunded If should include refunded value.
	 *
	 * @return string
	 */
	public function get_formatted_order_total( $tax_display = '', $display_refunded = true ) {
		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_currency() ) );
		$order_total    = $this->get_total();
		$total_refunded = $this->get_total_refunded();
		$tax_string     = '';

		// Tax for inclusive prices.
		if ( wc_tax_enabled() && 'incl' == $tax_display ) {
			$tax_string_array = array();

			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = ( $total_refunded && $display_refunded ) ? wc_price( WC_Tax::round( $tax->amount - $this->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), array( 'currency' => $this->get_currency() ) ) : $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}
			} else {
				$tax_amount         = ( $total_refunded && $display_refunded ) ? $this->get_total_tax() - $this->get_total_tax_refunded() : $this->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, array( 'currency' => $this->get_currency() ) ), WC()->countries->tax_or_vat() );
			}
			if ( ! empty( $tax_string_array ) ) {
				$tax_string = ' ' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
			}
		}

		if ( $total_refunded && $display_refunded ) {
			$formatted_total = '<del>' . strip_tags( $formatted_total ) . '</del> <ins>' . wc_price( $order_total - $total_refunded, array( 'currency' => $this->get_currency() ) ) . $tax_string . '</ins>';
		} else {
			$formatted_total .= $tax_string;
		}

		/**
		 * Filter WooCommerce formatted order total.
		 *
		 * @param string   $formatted_total  Total to display.
		 * @param WC_Order $order            Order data.
		 * @param string   $tax_display      Type of tax display.
		 * @param bool     $display_refunded If should include refunded value.
		 */
		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this, $tax_display, $display_refunded );
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
	 * @since 2.7.0
	 */
	public function create() {
		parent::create();

		// Store additonal order data
		if ( $this->get_id() ) {
			$this->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );
			$this->update_post_meta( '_customer_user', $this->get_customer_id() );
			$this->update_post_meta( '_order_key', $this->get_order_key() );
			$this->update_post_meta( '_billing_first_name', $this->get_billing_first_name() );
			$this->update_post_meta( '_billing_last_name', $this->get_billing_last_name() );
			$this->update_post_meta( '_billing_company', $this->get_billing_company() );
			$this->update_post_meta( '_billing_address_1', $this->get_billing_address_1() );
			$this->update_post_meta( '_billing_address_2', $this->get_billing_address_2() );
			$this->update_post_meta( '_billing_city', $this->get_billing_city() );
			$this->update_post_meta( '_billing_state', $this->get_billing_state() );
			$this->update_post_meta( '_billing_postcode', $this->get_billing_postcode() );
			$this->update_post_meta( '_billing_country', $this->get_billing_country() );
			$this->update_post_meta( '_billing_email', $this->get_billing_email() );
			$this->update_post_meta( '_billing_phone', $this->get_billing_phone() );
			$this->update_post_meta( '_shipping_first_name', $this->get_shipping_first_name() );
			$this->update_post_meta( '_shipping_last_name', $this->get_shipping_last_name() );
			$this->update_post_meta( '_shipping_company', $this->get_shipping_company() );
			$this->update_post_meta( '_shipping_address_1', $this->get_shipping_address_1() );
			$this->update_post_meta( '_shipping_address_2', $this->get_shipping_address_2() );
			$this->update_post_meta( '_shipping_city', $this->get_shipping_city() );
			$this->update_post_meta( '_shipping_state', $this->get_shipping_state() );
			$this->update_post_meta( '_shipping_postcode', $this->get_shipping_postcode() );
			$this->update_post_meta( '_shipping_country', $this->get_shipping_country() );
			$this->update_post_meta( '_payment_method', $this->get_payment_method() );
			$this->update_post_meta( '_payment_method_title', $this->get_payment_method_title() );
			$this->update_post_meta( '_transaction_id', $this->get_transaction_id() );
			$this->update_post_meta( '_customer_ip_address', $this->get_customer_ip_address() );
			$this->update_post_meta( '_customer_user_agent', $this->get_customer_user_agent() );
			$this->update_post_meta( '_created_via', $this->get_created_via() );
			$this->update_post_meta( '_customer_note', $this->get_customer_note() );
			$this->update_post_meta( '_date_completed', $this->get_date_completed() );
			$this->update_post_meta( '_date_paid', $this->get_date_paid() );
			$this->update_post_meta( '_cart_hash', $this->get_cart_hash() );
			do_action( 'woocommerce_new_order', $this->get_id() );
		}
	}

	/**
	 * Read from the database.
	 * @since 2.7.0
	 * @param int $id ID of object to read.
	 */
	public function read( $id ) {
		parent::read( $id );

		if ( ! $this->get_id() ) {
			return;
		}

		$post_object = get_post( $this->get_id() );

		$this->set_props( array(
			'order_key'            => get_post_meta( $this->get_id(), '_order_key', true ),
			'customer_id'          => get_post_meta( $this->get_id(), '_customer_user', true ),
			'billing_first_name'   => get_post_meta( $this->get_id(), '_billing_first_name', true ),
			'billing_last_name'    => get_post_meta( $this->get_id(), '_billing_last_name', true ),
			'billing_company'      => get_post_meta( $this->get_id(), '_billing_company', true ),
			'billing_address_1'    => get_post_meta( $this->get_id(), '_billing_address_1', true ),
			'billing_address_2'    => get_post_meta( $this->get_id(), '_billing_address_2', true ),
			'billing_city'         => get_post_meta( $this->get_id(), '_billing_city', true ),
			'billing_state'        => get_post_meta( $this->get_id(), '_billing_state', true ),
			'billing_postcode'     => get_post_meta( $this->get_id(), '_billing_postcode', true ),
			'billing_country'      => get_post_meta( $this->get_id(), '_billing_country', true ),
			'billing_email'        => get_post_meta( $this->get_id(), '_billing_email', true ),
			'billing_phone'        => get_post_meta( $this->get_id(), '_billing_phone', true ),
			'shipping_first_name'  => get_post_meta( $this->get_id(), '_shipping_first_name', true ),
			'shipping_last_name'   => get_post_meta( $this->get_id(), '_shipping_last_name', true ),
			'shipping_company'     => get_post_meta( $this->get_id(), '_shipping_company', true ),
			'shipping_address_1'   => get_post_meta( $this->get_id(), '_shipping_address_1', true ),
			'shipping_address_2'   => get_post_meta( $this->get_id(), '_shipping_address_2', true ),
			'shipping_city'        => get_post_meta( $this->get_id(), '_shipping_city', true ),
			'shipping_state'       => get_post_meta( $this->get_id(), '_shipping_state', true ),
			'shipping_postcode'    => get_post_meta( $this->get_id(), '_shipping_postcode', true ),
			'shipping_country'     => get_post_meta( $this->get_id(), '_shipping_country', true ),
			'payment_method'       => get_post_meta( $this->get_id(), '_payment_method', true ),
			'payment_method_title' => get_post_meta( $this->get_id(), '_payment_method_title', true ),
			'transaction_id'       => get_post_meta( $this->get_id(), '_transaction_id', true ),
			'customer_ip_address'  => get_post_meta( $this->get_id(), '_customer_ip_address', true ),
			'customer_user_agent'  => get_post_meta( $this->get_id(), '_customer_user_agent', true ),
			'created_via'          => get_post_meta( $this->get_id(), '_created_via', true ),
			'customer_note'        => get_post_meta( $this->get_id(), '_customer_note', true ),
			'date_completed'       => get_post_meta( $this->get_id(), '_completed_date', true ),
			'date_paid'            => get_post_meta( $this->get_id(), '_paid_date', true ),
			'cart_hash'            => get_post_meta( $this->get_id(), '_cart_hash', true ),
			'customer_note'        => $post_object->post_excerpt,
		) );

		$this->maybe_set_user_billing_email();
	}

	/**
	 * Update data in the database.
	 * @since 2.7.0
	 */
	public function update() {
		global $wpdb;

		// Store additonal order data.
		$this->update_post_meta( '_order_key', $this->get_order_key() );
		$customer_changed = $this->update_post_meta( '_customer_user', $this->get_customer_id() );
		$this->update_post_meta( '_billing_first_name', $this->get_billing_first_name() );
		$this->update_post_meta( '_billing_last_name', $this->get_billing_last_name() );
		$this->update_post_meta( '_billing_company', $this->get_billing_company() );
		$this->update_post_meta( '_billing_address_1', $this->get_billing_address_1() );
		$this->update_post_meta( '_billing_address_2', $this->get_billing_address_2() );
		$this->update_post_meta( '_billing_city', $this->get_billing_city() );
		$this->update_post_meta( '_billing_state', $this->get_billing_state() );
		$this->update_post_meta( '_billing_postcode', $this->get_billing_postcode() );
		$this->update_post_meta( '_billing_country', $this->get_billing_country() );
		$email_changed = $this->update_post_meta( '_billing_email', $this->get_billing_email() );
		$this->update_post_meta( '_billing_phone', $this->get_billing_phone() );
		$this->update_post_meta( '_shipping_first_name', $this->get_shipping_first_name() );
		$this->update_post_meta( '_shipping_last_name', $this->get_shipping_last_name() );
		$this->update_post_meta( '_shipping_company', $this->get_shipping_company() );
		$this->update_post_meta( '_shipping_address_1', $this->get_shipping_address_1() );
		$this->update_post_meta( '_shipping_address_2', $this->get_shipping_address_2() );
		$this->update_post_meta( '_shipping_city', $this->get_shipping_city() );
		$this->update_post_meta( '_shipping_state', $this->get_shipping_state() );
		$this->update_post_meta( '_shipping_postcode', $this->get_shipping_postcode() );
		$this->update_post_meta( '_shipping_country', $this->get_shipping_country() );
		$this->update_post_meta( '_payment_method', $this->get_payment_method() );
		$this->update_post_meta( '_payment_method_title', $this->get_payment_method_title() );
		$this->update_post_meta( '_transaction_id', $this->get_transaction_id() );
		$this->update_post_meta( '_customer_ip_address', $this->get_customer_ip_address() );
		$this->update_post_meta( '_customer_user_agent', $this->get_customer_user_agent() );
		$this->update_post_meta( '_created_via', $this->get_created_via() );
		$this->update_post_meta( '_customer_note', $this->get_customer_note() );
		$this->update_post_meta( '_date_completed', $this->get_date_completed() );
		$this->update_post_meta( '_date_paid', $this->get_date_paid() );
		$this->update_post_meta( '_cart_hash', $this->get_cart_hash() );

		// Update parent.
		parent::update();

		// If customer changed, update any downloadable permissions.
		if ( $customer_changed || $email_changed ) {
			$wpdb->update( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
				array(
					'user_id'    => $this->get_customer_id(),
					'user_email' => $this->get_billing_email(),
				),
				array(
					'order_id'   => $this->get_id(),
				),
				array(
					'%d',
					'%s',
				),
				array(
					'%d',
				)
			);
		}

		// Clear order cache.
		clean_post_cache( $this->get_id() );
		wc_delete_shop_order_transients( $this->get_id() );

		// Handle status change.
		$this->status_transition();

	}

	/**
	 * Set order status.
	 * @since 2.7.0
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @param string $note (default: '') Optional note to add.
	 * @param bool $manual_update is this a manual order status change?
	 * @param array details of change
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$result = parent::set_status( $new_status );

		if ( ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'note'   => $note,
				'manual' => (bool) $manual_update,
			);

			if ( 'pending' === $result['from'] && ! $manual_update ) {
				$this->set_date_paid( current_time( 'timestamp' ) );
			}

			if ( 'completed' === $result['to'] ) {
				$this->set_date_completed( current_time( 'timestamp' ) );
			}
		}

		return $result;
	}

	/**
	 * Updates status of order immediately. Order must exist.
	 * @uses WC_Order::set_status()
	 * @return bool success
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		try {
			if ( ! $this->get_id() ) {
				return false;
			}
			$this->set_status( $new_status, $note, $manual );
			$this->save();
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		if ( $this->status_transition ) {
			if ( ! empty( $this->status_transition['from'] ) ) {
				/* translators: 1: old order status 2: new order status */
				$transition_note = sprintf( __( 'Order status changed from %1$s to %2$s.', 'woocommerce' ), wc_get_order_status_name( $this->status_transition['from'] ), wc_get_order_status_name( $this->status_transition['to'] ) );

				do_action( 'woocommerce_order_status_' . $this->status_transition['from'] . '_to_' . $this->status_transition['to'], $this->get_id() );
				do_action( 'woocommerce_order_status_changed', $this->get_id(), $this->status_transition['from'], $this->status_transition['to'] );
			} else {
				/* translators: %s: new order status */
				$transition_note = sprintf( __( 'Order status set to %s.', 'woocommerce' ), wc_get_order_status_name( $this->status_transition['to'] ) );
			}

			do_action( 'woocommerce_order_status_' . $this->status_transition['to'], $this->get_id() );

			// Note the transition occured
			$this->add_order_note( trim( $this->status_transition['note'] . ' ' . $transition_note ), 0, $this->status_transition['manual'] );

			// This has ran, so reset status transition variable
			$this->status_transition = false;
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
			$this->data,
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
		return $this->data['order_key'];
	}

	/**
	 * Get customer_id
	 * @return int
	 */
	public function get_customer_id() {
		return $this->data['customer_id'];
	}

	/**
	 * Alias for get_customer_id().
	 * @return int
	 */
	public function get_user_id() {
		return $this->get_customer_id();
	}

	/**
	 * Get the user associated with the order. False for guests.
	 * @return WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get billing_first_name
	 * @return string
	 */
	public function get_billing_first_name() {
		return $this->data['billing']['first_name'];
	}

	/**
	 * Get billing_last_name
	 * @return string
	 */
	public function get_billing_last_name() {
		return $this->data['billing']['last_name'];
	}

	/**
	 * Get billing_company
	 * @return string
	 */
	public function get_billing_company() {
		return $this->data['billing']['company'];
	}

	/**
	 * Get billing_address_1
	 * @return string
	 */
	public function get_billing_address_1() {
		return $this->data['billing']['address_1'];
	}

	/**
	 * Get billing_address_2
	 * @return string $value
	 */
	public function get_billing_address_2() {
		return $this->data['billing']['address_2'];
	}

	/**
	 * Get billing_city
	 * @return string $value
	 */
	public function get_billing_city() {
		return $this->data['billing']['city'];
	}

	/**
	 * Get billing_state
	 * @return string
	 */
	public function get_billing_state() {
		return $this->data['billing']['state'];
	}

	/**
	 * Get billing_postcode
	 * @return string
	 */
	public function get_billing_postcode() {
		return $this->data['billing']['postcode'];
	}

	/**
	 * Get billing_country
	 * @return string
	 */
	public function get_billing_country() {
		return $this->data['billing']['country'];
	}

	/**
	 * Get billing_email
	 * @return string
	 */
	public function get_billing_email() {
		return $this->data['billing']['email'];
	}

	/**
	 * Get billing_phone
	 * @return string
	 */
	public function get_billing_phone() {
		return $this->data['billing']['phone'];
	}

	/**
	 * Get shipping_first_name
	 * @return string
	 */
	public function get_shipping_first_name() {
		return $this->data['shipping']['first_name'];
	}

	/**
	 * Get shipping_last_name
	 * @return string
	 */
	public function get_shipping_last_name() {
		 return $this->data['shipping']['last_name'];
	}

	/**
	 * Get shipping_company
	 * @return string
	 */
	public function get_shipping_company() {
		return $this->data['shipping']['company'];
	}

	/**
	 * Get shipping_address_1
	 * @return string
	 */
	public function get_shipping_address_1() {
		return $this->data['shipping']['address_1'];
	}

	/**
	 * Get shipping_address_2
	 * @return string
	 */
	public function get_shipping_address_2() {
		return $this->data['shipping']['address_2'];
	}

	/**
	 * Get shipping_city
	 * @return string
	 */
	public function get_shipping_city() {
		return $this->data['shipping']['city'];
	}

	/**
	 * Get shipping_state
	 * @return string
	 */
	public function get_shipping_state() {
		return $this->data['shipping']['state'];
	}

	/**
	 * Get shipping_postcode
	 * @return string
	 */
	public function get_shipping_postcode() {
		return $this->data['shipping']['postcode'];
	}

	/**
	 * Get shipping_country
	 * @return string
	 */
	public function get_shipping_country() {
		return $this->data['shipping']['country'];
	}

	/**
	 * Get the payment method.
	 * @return string
	 */
	public function get_payment_method() {
		return $this->data['payment_method'];
	}

	/**
	 * Get payment_method_title
	 * @return string
	 */
	public function get_payment_method_title() {
		return $this->data['payment_method_title'];
	}

	/**
	 * Get transaction_id
	 * @return string
	 */
	public function get_transaction_id() {
		return $this->data['transaction_id'];
	}

	/**
	 * Get customer_ip_address
	 * @return string
	 */
	public function get_customer_ip_address() {
		return $this->data['customer_ip_address'];
	}

	/**
	 * Get customer_user_agent
	 * @return string
	 */
	public function get_customer_user_agent() {
		return $this->data['customer_user_agent'];
	}

	/**
	 * Get created_via
	 * @return string
	 */
	public function get_created_via() {
		return $this->data['created_via'];
	}

	/**
	 * Get customer_note
	 * @return string
	 */
	public function get_customer_note() {
		return $this->data['customer_note'];
	}

	/**
	 * Get date_completed
	 * @return int
	 */
	public function get_date_completed() {
		return absint( $this->data['date_completed'] );
	}

	/**
	 * Get date_paid
	 * @return int
	 */
	public function get_date_paid() {
		return absint( $this->data['date_paid'] );
	}

	/**
	 * Returns the requested address in raw, non-formatted way.
	 * @since  2.4.0
	 * @param  string $type Billing or shipping. Anything else besides 'billing' will return shipping address.
	 * @return array The stored address after filter.
	 */
	public function get_address( $type = 'billing' ) {
		return apply_filters( 'woocommerce_get_order_address', isset( $this->data[ $type ] ) ? $this->data[ $type ] : array(), $type, $this );
	}

	/**
	 * Get a formatted shipping address for the order.
	 *
	 * @return string
	 */
	public function get_shipping_address_map_url() {
		$address = apply_filters( 'woocommerce_shipping_address_map_url_parts', $this->get_address( 'shipping' ), $this );
		return apply_filters( 'woocommerce_shipping_address_map_url', 'https://maps.google.com/maps?&q=' . urlencode( implode( ', ', $address ) ) . '&z=16', $this );
	}

	/**
	 * Get a formatted billing full name.
	 * @return string
	 */
	public function get_formatted_billing_full_name() {
		/* translators: 1: first name 2: last name */
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $this->get_billing_first_name(), $this->get_billing_last_name() );
	}

	/**
	 * Get a formatted shipping full name.
	 * @return string
	 */
	public function get_formatted_shipping_full_name() {
		/* translators: 1: first name 2: last name */
		return sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $this->get_shipping_first_name(), $this->get_shipping_last_name() );
	}

	/**
	 * Get a formatted billing address for the order.
	 * @return string
	 */
	public function get_formatted_billing_address() {
		return WC()->countries->get_formatted_address( apply_filters( 'woocommerce_order_formatted_billing_address', $this->get_address( 'billing' ), $this ) );
	}

	/**
	 * Get a formatted shipping address for the order.
	 * @return string
	 */
	public function get_formatted_shipping_address() {
		if ( $this->get_shipping_address_1() || $this->get_shipping_address_2() ) {
			return WC()->countries->get_formatted_address( apply_filters( 'woocommerce_order_formatted_shipping_address', $this->get_address( 'shipping' ), $this ) );
		} else {
			return '';
		}
	}

	/**
	 * Get cart hash
	 * @return string
	 */
	public function get_cart_hash() {
		return $this->data['cart_hash'];
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
	 * Set order_key.
	 * @param string $value Max length 20 chars.
	 * @throws WC_Data_Exception
	 */
	public function set_order_key( $value ) {
		$this->data['order_key'] = substr( $value, 0, 20 );
	}

	/**
	 * Set customer_id
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_customer_id( $value ) {
		$this->data['customer_id'] = absint( $value );
	}

	/**
	 * Set billing_first_name
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_first_name( $value ) {
		$this->data['billing']['first_name'] = $value;
	}

	/**
	 * Set billing_last_name
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_last_name( $value ) {
		$this->data['billing']['last_name'] = $value;
	}

	/**
	 * Set billing_company
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_company( $value ) {
		$this->data['billing']['company'] = $value;
	}

	/**
	 * Set billing_address_1
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_1( $value ) {
		$this->data['billing']['address_1'] = $value;
	}

	/**
	 * Set billing_address_2
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_address_2( $value ) {
		$this->data['billing']['address_2'] = $value;
	}

	/**
	 * Set billing_city
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_city( $value ) {
		$this->data['billing']['city'] = $value;
	}

	/**
	 * Set billing_state
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_state( $value ) {
		$this->data['billing']['state'] = $value;
	}

	/**
	 * Set billing_postcode
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_postcode( $value ) {
		$this->data['billing']['postcode'] = $value;
	}

	/**
	 * Set billing_country
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_country( $value ) {
		$this->data['billing']['country'] = $value;
	}

	/**
	 * Maybe set empty billing email to that of the user who owns the order.
	 */
	protected function maybe_set_user_billing_email() {
		if ( ! $this->get_billing_email() && ( $user = $this->get_user() ) ) {
			try {
				$this->set_billing_email( $user->user_email );
			} catch( WC_Data_Exception $e ) {
				unset( $e );
			}
		}
	}

	/**
	 * Set billing_email
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'order_invalid_billing_email', __( 'Invalid order billing email address', 'woocommerce' ) );
		}
		$this->data['billing']['email'] = sanitize_email( $value );
	}

	/**
	 * Set billing_phone
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_billing_phone( $value ) {
		$this->data['billing']['phone'] = $value;
	}

	/**
	 * Set shipping_first_name
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_first_name( $value ) {
		$this->data['shipping']['first_name'] = $value;
	}

	/**
	 * Set shipping_last_name
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_last_name( $value ) {
		$this->data['shipping']['last_name'] = $value;
	}

	/**
	 * Set shipping_company
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_company( $value ) {
		$this->data['shipping']['company'] = $value;
	}

	/**
	 * Set shipping_address_1
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_1( $value ) {
		$this->data['shipping']['address_1'] = $value;
	}

	/**
	 * Set shipping_address_2
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_address_2( $value ) {
		$this->data['shipping']['address_2'] = $value;
	}

	/**
	 * Set shipping_city
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_city( $value ) {
		$this->data['shipping']['city'] = $value;
	}

	/**
	 * Set shipping_state
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_state( $value ) {
		$this->data['shipping']['state'] = $value;
	}

	/**
	 * Set shipping_postcode
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_postcode( $value ) {
		$this->data['shipping']['postcode'] = $value;
	}

	/**
	 * Set shipping_country
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_country( $value ) {
		$this->data['shipping']['country'] = $value;
	}

	/**
	 * Set the payment method.
	 * @param string $payment_method Supports WC_Payment_Gateway for bw compatibility with < 2.7
	 * @throws WC_Data_Exception
	 */
	public function set_payment_method( $payment_method = '' ) {
		if ( is_object( $payment_method ) ) {
			$this->set_payment_method( $payment_method->id );
			$this->set_payment_method_title( $payment_method->get_title() );
		} elseif ( '' === $payment_method ) {
			$this->data['payment_method'] = '';
			$this->data['payment_method_title'] = '';
		} else {
			$this->data['payment_method'] = $payment_method;
		}
	}

	/**
	 * Set payment_method_title
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_payment_method_title( $value ) {
		$this->data['payment_method_title'] = $value;
	}

	/**
	 * Set transaction_id
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_transaction_id( $value ) {
		$this->data['transaction_id'] = $value;
	}

	/**
	 * Set customer_ip_address
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_customer_ip_address( $value ) {
		$this->data['customer_ip_address'] = $value;
	}

	/**
	 * Set customer_user_agent
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_customer_user_agent( $value ) {
		$this->data['customer_user_agent'] = $value;
	}

	/**
	 * Set created_via
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_created_via( $value ) {
		$this->data['created_via'] = $value;
	}

	/**
	 * Set customer_note
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_customer_note( $value ) {
		$this->data['customer_note'] = $value;
	}

	/**
	 * Set date_completed
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_completed( $timestamp ) {
		$this->data['date_completed'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set date_paid
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_paid( $timestamp ) {
		$this->data['date_paid'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set cart hash
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_cart_hash( $value ) {
		$this->data['cart_hash'] = $value;
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
	 * Check if an order key is valid.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function key_is_valid( $key ) {
		return $key === $this->get_order_key();
	}

	/**
	 * See if order matches cart_hash.
	 * @return bool
	 */
	public function has_cart_hash( $cart_hash = '' ) {
		return hash_equals( $this->get_cart_hash(), $cart_hash );
	}

	/**
	 * Checks if an order can be edited, specifically for use on the Edit Order screen.
	 * @return bool
	 */
	public function is_editable() {
		return apply_filters( 'wc_order_is_editable', in_array( $this->get_status(), array( 'pending', 'on-hold', 'auto-draft' ) ), $this );
	}

	/**
	 * Returns if an order has been paid for based on the order status.
	 * @since 2.5.0
	 * @return bool
	 */
	public function is_paid() {
		return apply_filters( 'woocommerce_order_is_paid', $this->has_status( wc_get_is_paid_statuses() ), $this );
	}

	/**
	 * Checks if product download is permitted.
	 *
	 * @return bool
	 */
	public function is_download_permitted() {
		return apply_filters( 'woocommerce_order_is_download_permitted', $this->has_status( 'completed' ) || ( 'yes' === get_option( 'woocommerce_downloads_grant_access_after_payment' ) && $this->has_status( 'processing' ) ), $this );
	}

	/**
	 * Checks if an order needs display the shipping address, based on shipping method.
	 * @return bool
	 */
	public function needs_shipping_address() {
		if ( 'no' === get_option( 'woocommerce_calc_shipping' ) ) {
			return false;
		}

		$hide  = apply_filters( 'woocommerce_order_hide_shipping_address', array( 'local_pickup' ), $this );
		$needs_address = false;

		foreach ( $this->get_shipping_methods() as $shipping_method ) {
			// Remove any instance IDs after :
			$shipping_method_id = current( explode( ':', $shipping_method['method_id'] ) );

			if ( ! in_array( $shipping_method_id, $hide ) ) {
				$needs_address = true;
				break;
			}
		}

		return apply_filters( 'woocommerce_order_needs_shipping_address', $needs_address, $hide, $this );
	}

	/**
	 * Returns true if the order contains a downloadable product.
	 * @return bool
	 */
	public function has_downloadable_item() {
		foreach ( $this->get_items() as $item ) {
			if ( $item->is_type( 'line_item' ) && ( $product = $item->get_product() ) && $product->is_downloadable() && $product->has_file() ) {
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
		return apply_filters( 'woocommerce_order_needs_payment', ( $this->has_status( $valid_order_statuses ) && $this->get_total() > 0 ), $this, $valid_order_statuses );
	}

	/*
	|--------------------------------------------------------------------------
	| URLs and Endpoints
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generates a URL so that a customer can pay for their (unpaid - pending) order. Pass 'true' for the checkout version which doesn't offer gateway choices.
	 *
	 * @param  bool $on_checkout
	 * @return string
	 */
	public function get_checkout_payment_url( $on_checkout = false ) {
		$pay_url = wc_get_endpoint_url( 'order-pay', $this->get_id(), wc_get_page_permalink( 'checkout' ) );

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
		$order_received_url = wc_get_endpoint_url( 'order-received', $this->get_id(), wc_get_page_permalink( 'checkout' ) );

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
			'order_id'     => $this->get_id(),
			'redirect'     => $redirect,
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
			'order_id'     => $this->get_id(),
			'redirect'     => $redirect,
			'_wpnonce'     => wp_create_nonce( 'woocommerce-cancel_order' ),
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
		return apply_filters( 'woocommerce_get_view_order_url', wc_get_endpoint_url( 'view-order', $this->get_id(), wc_get_page_permalink( 'myaccount' ) ), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Order notes.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Adds a note (comment) to the order. Order must exist.
	 *
	 * @param string $note Note to add.
	 * @param int $is_customer_note (default: 0) Is this a note for the customer?
	 * @param  bool added_by_user Was the note added by a user?
	 * @return int Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		if ( ! $this->get_id() ) {
			return 0;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->get_id() ) && $added_by_user ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
		} else {
			$comment_author       = __( 'WooCommerce', 'woocommerce' );
			$comment_author_email = strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
			$comment_author_email = sanitize_email( $comment_author_email );
		}
		$commentdata = apply_filters( 'woocommerce_new_order_note_data', array(
			'comment_post_ID'      => $this->get_id(),
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => '',
			'comment_content'      => $note,
			'comment_agent'        => 'WooCommerce',
			'comment_type'         => 'order_note',
			'comment_parent'       => 0,
			'comment_approved'     => 1,
		), array( 'order_id' => $this->get_id(), 'is_customer_note' => $is_customer_note ) );

		$comment_id = wp_insert_comment( apply_filters( 'woocommerce_new_comment_data', $commentdata, array( 'order_id' => $this->id, 'customer_note' => $note, 'is_customer_note' => $is_customer_note ) ) );

		if ( $is_customer_note ) {
			add_comment_meta( $comment_id, 'is_customer_note', 1 );

			do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->get_id(), 'customer_note' => $commentdata['comment_content'] ) );
		}

		return $comment_id;
	}

	/**
	 * List order notes (public) for the customer.
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {
		$notes = array();
		$args  = array(
			'post_id' => $this->get_id(),
			'approve' => 'approve',
			'type'    => '',
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

	/*
	|--------------------------------------------------------------------------
	| Refunds
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order refunds.
	 * @since 2.2
	 * @return array of WC_Order_Refund objects
	 */
	public function get_refunds() {
		$this->refunds = wc_get_orders( array(
			'type'   => 'shop_order_refund',
			'parent' => $this->get_id(),
			'limit'  => -1,
		) );
		return $this->refunds;
	}

	/**
	 * Get amount already refunded.
	 *
	 * @since 2.2
	 * @return string
	 */
	public function get_total_refunded() {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			WHERE postmeta.meta_key = '_refund_amount'
			AND postmeta.post_id = posts.ID
		", $this->get_id() ) );

		return $total;
	}

	/**
	 * Get the total tax refunded.
	 *
	 * @since  2.3
	 * @return float
	 */
	public function get_total_tax_refunded() {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'tax' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('tax_amount', 'shipping_tax_amount')
		", $this->get_id() ) );

		return abs( $total );
	}

	/**
	 * Get the total shipping refunded.
	 *
	 * @since  2.4
	 * @return float
	 */
	public function get_total_shipping_refunded() {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( order_itemmeta.meta_value )
			FROM {$wpdb->prefix}woocommerce_order_itemmeta AS order_itemmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON ( order_items.order_id = posts.ID AND order_items.order_item_type = 'shipping' )
			WHERE order_itemmeta.order_item_id = order_items.order_item_id
			AND order_itemmeta.meta_key IN ('cost')
		", $this->get_id() ) );

		return abs( $total );
	}

	/**
	 * Gets the count of order items of a certain type that have been refunded.
	 * @since  2.4.0
	 * @param string $item_type
	 * @return string
	 */
	public function get_item_count_refunded( $item_type = '' ) {
		if ( empty( $item_type ) ) {
			$item_type = array( 'line_item' );
		}
		if ( ! is_array( $item_type ) ) {
			$item_type = array( $item_type );
		}
		$count = 0;

		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$count += $refunded_item->get_quantity();
			}
		}

		return apply_filters( 'woocommerce_get_item_count_refunded', $count, $item_type, $this );
	}

	/**
	 * Get the total number of items refunded.
	 *
	 * @since  2.4.0
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_total_qty_refunded( $item_type = 'line_item' ) {
		$qty = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$qty += $refunded_item->get_quantity();
			}
		}
		return $qty;
	}

	/**
	 * Get the refunded amount for a line item.
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_qty_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		$qty = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $item_id ) {
					$qty += $refunded_item->get_quantity();
				}
			}
		}
		return $qty;
	}

	/**
	 * Get the refunded amount for a line item.
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_total_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $item_id ) {
					$total += $refunded_item->get_total();
				}
			}
		}
		return $total * -1;
	}

	/**
	 * Get the refunded amount for a line item.
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  int $tax_id ID of the tax we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return double
	 */
	public function get_tax_refunded_for_item( $item_id, $tax_id, $item_type = 'line_item' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $item_id ) {
					$total += $refunded_item->get_total_tax();
				}
			}
		}
		return wc_round_tax_total( $total ) * -1;
	}

	/**
	 * Get total tax refunded by rate ID.
	 *
	 * @param  int $rate_id
	 *
	 * @return float
	 */
	public function get_total_tax_refunded_by_rate_id( $rate_id ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( 'tax' ) as $refunded_item ) {
				if ( absint( $refunded_item->get_rate_id() ) === $rate_id ) {
					$total += abs( $refunded_item->get_tax_total() ) + abs( $refunded_item->get_shipping_tax_total() );
				}
			}
		}

		return $total;
	}

	/**
	 * How much money is left to refund?
	 * @return string
	 */
	public function get_remaining_refund_amount() {
		return wc_format_decimal( $this->get_total() - $this->get_total_refunded(), wc_get_price_decimals() );
	}

	/**
	 * How many items are left to refund?
	 * @return int
	 */
	public function get_remaining_refund_items() {
		return absint( $this->get_item_count() - $this->get_item_count_refunded() );
	}
}
