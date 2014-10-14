<?php
/**
 * Abstract Order
 *
 * The WooCommerce order class handles order data.
 *
 * @class       WC_Order
 * @version     2.2.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */

abstract class WC_Abstract_Order {

	/** @public int Order (post) ID */
	public $id;

	/** @public string Order type */
	public $order_type = null;

	/**
	 * Get the order if ID is passed, otherwise the order is new and empty.
	 * This class should NOT be instantiated, but the get_order function or new WC_Order_Factory
	 * should be used. It is possible, but the aforementioned are preferred and are the only
	 * methods that will be maintained going forward.
	 *
	 */
	public function __construct( $order = '' ) {

		$this->prices_include_tax = get_option('woocommerce_prices_include_tax') == 'yes' ? true : false;
		$this->tax_display_cart   = get_option( 'woocommerce_tax_display_cart' );

		$this->display_totals_ex_tax = $this->tax_display_cart == 'excl' ? true : false;
		$this->display_cart_ex_tax   = $this->tax_display_cart == 'excl' ? true : false;

		$this->order_type = 'simple';

		if ( is_numeric( $order ) ) {

			$this->id = absint( $order );
			$this->post = get_post( $order );
			$this->get_order( $this->id );

		} elseif ( $order instanceof WC_Order ) {

			$this->id = absint( $order->id );
			$this->post = $order->post;
			$this->get_order( $this->id );

		} elseif ( $order instanceof WP_Post || isset( $order->ID ) ) {

			$this->id = absint( $order->ID );
			$this->post = $order;
			$this->get_order( $this->id );
		}
	}

	/**
	 * Remove all line items (products, coupons, shipping, taxes) from the order.
	 *
	 * @param string $type Order item type. Default null.
	 */
	public function remove_order_items( $type = null ) {
		global $wpdb;

		if ( $type ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s )", $this->id, $type ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d AND order_item_type = %s", $this->id, $type ) );
		} else {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN ( SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d )", $this->id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = %d", $this->id ) );
		}
	}

	/**
	 * Set the payment method for the order
	 *
	 * @param WC_Payment_Gateway
	 * @param WC_Payment_Gateway $payment_method
	 */
	public function set_payment_method( $payment_method ) {

		if ( is_object( $payment_method ) ) {
			update_post_meta( $this->id, '_payment_method', $payment_method->id );
			update_post_meta( $this->id, '_payment_method_title', $payment_method->get_title() );
		}
	}

	/**
	 * Set the customer address
	 *
	 * @param array $address Address data
	 * @param string $type billing or shipping
	 */
	public function set_address( $address, $type = 'billing' ) {

		foreach ( $address as $key => $value ) {
			update_post_meta( $this->id, "_{$type}_" . $key, $value );
		}
	}

	/**
	 * Add a product line item to the order
	 *
	 * @since 2.2
	 * @param \WC_Product $product
	 * @param int $qty Line item quantity
	 * @param array $args
	 * @return int|bool Item ID or false
	 */
	public function add_product( $product, $qty = 1, $args = array() ) {

		$default_args = array(
			'variation' => array(),
			'totals'    => array()
		);

		$args    = wp_parse_args( $args, $default_args );
		$item_id = wc_add_order_item( $this->id, array(
			'order_item_name' => $product->get_title(),
			'order_item_type' => 'line_item'
		) );

		if ( ! $item_id ) {
			return false;
		}

		wc_add_order_item_meta( $item_id, '_qty',          wc_stock_amount( $qty ) );
		wc_add_order_item_meta( $item_id, '_tax_class',    $product->get_tax_class() );
		wc_add_order_item_meta( $item_id, '_product_id',   $product->id );
		wc_add_order_item_meta( $item_id, '_variation_id', isset( $product->variation_id ) ? $product->variation_id : 0 );

		// Set line item totals, either passed in or from the product
		wc_add_order_item_meta( $item_id, '_line_subtotal',     wc_format_decimal( isset( $args['totals']['subtotal'] ) ? $args['totals']['subtotal'] : $product->get_price_excluding_tax( $qty ) ) );
		wc_add_order_item_meta( $item_id, '_line_total',        wc_format_decimal( isset( $args['totals']['total'] ) ? $args['totals']['total'] : $product->get_price_excluding_tax( $qty ) ) );
		wc_add_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( isset( $args['totals']['subtotal_tax'] ) ? $args['totals']['subtotal_tax'] : 0 ) );
		wc_add_order_item_meta( $item_id, '_line_tax',          wc_format_decimal( isset( $args['totals']['tax'] ) ? $args['totals']['tax'] : 0 ) );

		// Save tax data - Since 2.2
		if ( isset( $args['totals']['tax_data'] ) ) {

			$tax_data             = array();
			$tax_data['total']    = array_map( 'wc_format_decimal', $args['totals']['tax_data']['total'] );
			$tax_data['subtotal'] = array_map( 'wc_format_decimal', $args['totals']['tax_data']['subtotal'] );

			wc_add_order_item_meta( $item_id, '_line_tax_data', $tax_data );
		} else {
			wc_add_order_item_meta( $item_id, '_line_tax_data', array( 'total' => array(), 'subtotal' => array() ) );
		}

		// Add variation meta
		if ( ! empty( $args['variation'] ) ) {

			foreach ( $args['variation'] as $key => $value ) {
				wc_add_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value );
			}
		}

		// Backorders
		if ( $product->backorders_require_notification() && $product->is_on_backorder( $qty ) ) {
			wc_add_order_item_meta( $item_id, apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ) ), $qty - max( 0, $product->get_total_stock() ) );
		}

		do_action( 'woocommerce_order_add_product', $this->id, $item_id, $product, $qty, $args );

		return $item_id;
	}


	/**
	 * Update a line item for the order
	 *
	 * Note this does not update order totals
	 *
	 * @since 2.2
	 * @param int $item_id order item ID
	 * @param array $args data to update
	 * @return bool
	 */
	public function update_product( $item_id, $product, $args ) {

		if ( ! $item_id || ! is_object( $product ) ) {
			return false;
		}

		// quantity
		if ( isset( $args['qty'] ) ) {
			wc_update_order_item_meta( $item_id, '_qty', wc_stock_amount( $args['qty'] ) );
		}

		// tax class
		if ( isset( $args['tax_class'] ) ) {
			wc_update_order_item_meta( $item_id, '_tax_class', $args['tax_class'] );
		}

		// set item totals, either provided or from product
		if ( isset( $args['qty'] ) ) {
			wc_update_order_item_meta( $item_id, '_line_subtotal', wc_format_decimal( isset( $args['totals']['subtotal'] ) ? $args['totals']['subtotal'] : $product->get_price_excluding_tax( $args['qty'] ) ) );
			wc_update_order_item_meta( $item_id, '_line_total', wc_format_decimal( isset( $args['totals']['total'] ) ? $args['totals']['total'] : $product->get_price_excluding_tax( $args['qty'] ) ) );
		}

		// set item tax totals
		wc_update_order_item_meta( $item_id, '_line_subtotal_tax', wc_format_decimal( isset( $args['totals']['subtotal_tax'] ) ? $args['totals']['subtotal_tax'] : 0 ) );
		wc_update_order_item_meta( $item_id, '_line_tax', wc_format_decimal( isset( $args['totals']['tax'] ) ? $args['totals']['tax'] : 0 ) );

		// variation meta
		if ( isset( $args['variation'] ) && is_array( $args['variation'] ) ) {

			foreach ( $args['variation'] as $key => $value ) {
				wc_update_order_item_meta( $item_id, str_replace( 'attribute_', '', $key ), $value );
			}
		}

		// backorders
		if ( isset( $args['qty'] ) && $product->backorders_require_notification() && $product->is_on_backorder( $args['qty'] ) ) {
			wc_update_order_item_meta( $item_id, apply_filters( 'woocommerce_backordered_item_meta_name', __( 'Backordered', 'woocommerce' ) ), $args['qty'] - max( 0, $product->get_total_stock() ) );
		}

		do_action( 'woocommerce_order_edit_product', $this->id, $item_id, $args, $product );

		return true;
	}


	/**
	 * Add coupon code to the order
	 *
	 * @param string $code
	 * @param integer $discount_amount
	 * @return int|bool Item ID or false
	 */
	public function add_coupon( $code, $discount_amount = 0 ) {

		$item_id = wc_add_order_item( $this->id, array(
			'order_item_name' => $code,
			'order_item_type' => 'coupon'
		) );

		if ( ! $item_id ) {
			return false;
		}

		wc_add_order_item_meta( $item_id, 'discount_amount', $discount_amount );

		do_action( 'woocommerce_order_add_coupon', $this->id, $item_id, $code, $discount_amount );

		return $item_id;
	}

	/**
	 * Update coupon for order
	 *
	 * Note this does not update order totals
	 *
	 * @since 2.2
	 * @param int $item_id
	 * @param array $args
	 * @return bool
	 */
	public function update_coupon( $item_id, $args ) {

		if ( ! $item_id ) {
			return false;
		}

		// code
		if ( isset( $args['code'] ) ) {
			wc_update_order_item( $item_id, array( 'order_item_name' => $args['code'] ) );
		}

		// amount
		if ( isset( $args['discount_amount'] ) ) {
			wc_update_order_item_meta( $item_id, 'discount_amount', wc_format_decimal( $args['discount_amount'] ) );
		}

		do_action( 'woocommerce_order_update_coupon', $this->id, $item_id, $args );

		return true;
	}

	/**
	 * Add a tax row to the order
	 *
	 * @since 2.2
	 * @param int tax_rate_id
	 * @return int|bool Item ID or false
	 */
	public function add_tax( $tax_rate_id, $tax_amount = 0, $shipping_tax_amount = 0 ) {

		$code = WC_Tax::get_rate_code( $tax_rate_id );

		if ( ! $code ) {
			return false;
		}

		$item_id = wc_add_order_item( $this->id, array(
			'order_item_name' => $code,
			'order_item_type' => 'tax'
		) );

		if ( ! $item_id ) {
			return false;
		}

		wc_add_order_item_meta( $item_id, 'rate_id', $tax_rate_id );
		wc_add_order_item_meta( $item_id, 'label', WC_Tax::get_rate_label( $tax_rate_id ) );
		wc_add_order_item_meta( $item_id, 'compound', WC_Tax::is_compound( $tax_rate_id ) ? 1 : 0 );
		wc_add_order_item_meta( $item_id, 'tax_amount', wc_format_decimal( $tax_amount ) );
		wc_add_order_item_meta( $item_id, 'shipping_tax_amount', wc_format_decimal( $shipping_tax_amount ) );

		do_action( 'woocommerce_order_add_tax', $this->id, $item_id, $tax_rate_id, $tax_amount, $shipping_tax_amount );

		return $item_id;
	}

	/**
	 * Add a shipping row to the order
	 *
	 * @param WC_Shipping_Rate shipping_rate
	 * @return int|bool Item ID or false
	 */
	public function add_shipping( $shipping_rate ) {

		$item_id = wc_add_order_item( $this->id, array(
			'order_item_name' 		=> $shipping_rate->label,
			'order_item_type' 		=> 'shipping'
		) );

		if ( ! $item_id ) {
			return false;
		}

		wc_add_order_item_meta( $item_id, 'method_id', $shipping_rate->id );
		wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $shipping_rate->cost ) );

		// Save shipping taxes - Since 2.2
		$taxes = array_map( 'wc_format_decimal', $shipping_rate->taxes );
		wc_add_order_item_meta( $item_id, 'taxes', $taxes );

		do_action( 'woocommerce_order_add_shipping', $this->id, $item_id, $shipping_rate );

		// Update total
		$this->set_total( $this->order_shipping + wc_format_decimal( $shipping_rate->cost ), 'shipping' );

		return $item_id;
	}

	/**
	 * Update shipping method for order
	 *
	 * Note this does not update the order total
	 *
	 * @since 2.2
	 * @param int $item_id
	 * @param array $args
	 * @return bool
	 */
	public function update_shipping( $item_id, $args ) {

		if ( ! $item_id ) {
			return false;
		}

		// method title
		if ( isset( $args['method_title'] ) ) {
			wc_update_order_item( $item_id, array( 'order_item_name' => $args['method_title'] ) );
		}

		// method ID
		if ( isset( $args['method_id'] ) ) {
			wc_update_order_item_meta( $item_id, 'method_id', $args['method_id'] );
		}

		// method cost
		if ( isset( $args['cost'] ) ) {
			wc_update_order_item_meta( $item_id, 'cost', wc_format_decimal( $args['cost'] ) );
		}

		do_action( 'woocommerce_order_update_shipping', $this->id, $item_id, $args );

		return true;
	}

	/**
	 * Add a fee to the order
	 *
	 * @param object $fee
	 * @return int|bool Item ID or false
	 */
	public function add_fee( $fee ) {

		$item_id = wc_add_order_item( $this->id, array(
			'order_item_name' => $fee->name,
			'order_item_type' => 'fee'
		) );

		if ( ! $item_id ) {
			return false;
		}

		if ( $fee->taxable ) {
			wc_add_order_item_meta( $item_id, '_tax_class', $fee->tax_class );
		} else {
			wc_add_order_item_meta( $item_id, '_tax_class', '0' );
		}

		wc_add_order_item_meta( $item_id, '_line_total', wc_format_decimal( $fee->amount ) );
		wc_add_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $fee->tax ) );

		// Save tax data - Since 2.2
		$tax_data = array_map( 'wc_format_decimal', $fee->tax_data );
		wc_add_order_item_meta( $item_id, '_line_tax_data', array( 'total' => $tax_data ) );

		do_action( 'woocommerce_order_add_fee', $this->id, $item_id, $fee );

		return $item_id;
	}

	/**
	 * Update fee for order
	 *
	 * Note this does not update order totals
	 *
	 * @since 2.2
	 * @param int $item_id
	 * @param array $args
	 * @return bool
	 */
	public function update_fee( $item_id, $args ) {

		if ( ! $item_id ) {
			return false;
		}

		// name
		if ( isset( $args['name'] ) ) {
			wc_update_order_item( $item_id, array( 'order_item_name' => $args['name'] ) );
		}

		// tax class
		if ( isset( $args['tax_class'] ) ) {
			wc_update_order_item_meta( $item_id, '_tax_class', $args['tax_class'] );
		}

		// total
		if ( isset( $args['line_total'] ) ) {
			wc_update_order_item_meta( $item_id, '_line_total', wc_format_decimal( $args['line_total'] ) );
		}

		// total tax
		if ( isset( $args['line_tax'] ) ) {
			wc_update_order_item_meta( $item_id, '_line_tax', wc_format_decimal( $args['line_tax'] ) );
		}

		do_action( 'woocommerce_order_update_fee', $this->id, $item_id, $args );

		return true;
	}

	/**
	 * Set an order total
	 *
	 * @param float $amount
	 * @param string $total_type
	 */
	public function set_total( $amount, $total_type = 'total' ) {

		if ( ! in_array( $total_type, array( 'shipping', 'order_discount', 'tax', 'shipping_tax', 'total', 'cart_discount' ) ) ) {
			return false;
		}

		switch ( $total_type ) {
			case 'total' :
				$key    = '_order_total';
				$amount = wc_format_decimal( $amount, get_option( 'woocommerce_price_num_decimals' ) );
			break;
			case 'order_discount' :
			case 'cart_discount' :
				$key    = '_' . $total_type;
				$amount = wc_format_decimal( $amount );
			break;
			default :
				$key    = '_order_' . $total_type;
				$amount = wc_format_decimal( $amount );
			break;
		}

		update_post_meta( $this->id, $key, $amount );
	}

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows.
	 *
	 * Will use the base country unless customer addresses are set.
	 *
	 * @return bool success or fail
	 */
	public function calculate_taxes() {

		$shipping_tax_total = 0;
		$tax_total          = 0;
		$taxes              = array();
		$tax_based_on       = get_option( 'woocommerce_tax_based_on' );

		if ( 'base' === $tax_based_on ) {

			$default  = get_option( 'woocommerce_default_country' );
			$postcode = '';
			$city     = '';

			if ( strstr( $default, ':' ) ) {
				list( $country, $state ) = explode( ':', $default );
			} else {
				$country = $default;
				$state   = '';
			}

		} elseif ( 'billing' === $tax_based_on ) {

			$country 	= $this->billing_country;
			$state 		= $this->billing_state;
			$postcode   = $this->billing_postcode;
			$city   	= $this->billing_city;

		} else {

			$country 	= $this->shipping_country;
			$state 		= $this->shipping_state;
			$postcode   = $this->shipping_postcode;
			$city   	= $this->shipping_city;

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

				// Sum the item taxes
				foreach ( array_keys( $taxes + $line_taxes ) as $key ) {
					$taxes[ $key ] = ( isset( $line_taxes[ $key ] ) ? $line_taxes[ $key ] : 0 ) + ( isset( $taxes[ $key ] ) ? $taxes[ $key ] : 0 );
				}
			}
		}

		// Now calculate shipping tax
		$matched_tax_rates = array();
		$tax_rates         = WC_Tax::find_rates( array(
			'country'   => $country,
			'state'     => $state,
			'postcode'  => $postcode,
			'city'      => $city,
			'tax_class' => ''
		) );

		if ( $tax_rates ) {
			foreach ( $tax_rates as $key => $rate ) {
				if ( isset( $rate['shipping'] ) && 'yes' === $rate['shipping'] ) {
					$matched_tax_rates[ $key ] = $rate;
				}
			}
		}

		$shipping_taxes     = WC_Tax::calc_shipping_tax( $this->order_shipping, $matched_tax_rates );
		$shipping_tax_total = WC_Tax::round( array_sum( $shipping_taxes ) );

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
	 * Calculate shipping total
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
	 * Update tax lines at order level by looking at the line item taxes themselves.
	 *
	 * @return bool success or fail
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

			if ( isset( $line_tax_data['total'] ) ) {

				foreach ( $line_tax_data['total'] as $tax_rate_id => $tax ) {

					if ( ! isset( $order_shipping_taxes[ $tax_rate_id ] ) ) {
						$order_shipping_taxes[ $tax_rate_id ] = 0;
					}

					$order_shipping_taxes[ $tax_rate_id ] += $tax;
				}
			}
		}

		// Remove old existing tax rows
		$this->remove_order_items( 'tax' );

		// Now merge to keep tax rows
		foreach ( array_keys( $order_taxes + $order_shipping_taxes ) as $tax_rate_id ) {
			$this->add_tax( $tax_rate_id, isset( $order_taxes[ $tax_rate_id ] ) ? $order_taxes[ $tax_rate_id ] : 0, isset( $order_shipping_taxes[ $tax_rate_id ] ) ? $order_shipping_taxes[ $tax_rate_id ] : 0 );
		}

		// Save tax totals
		$this->set_total( WC_Tax::round( array_sum( $order_shipping_taxes ) ), 'shipping_tax' );
		$this->set_total( WC_Tax::round( array_sum( $order_taxes ) ), 'tax' );

		return true;
	}

	/**
	 * Calculate totals by looking at the contents of the order. Stores the totals and returns the orders final total.
	 *
	 * @since 2.2
	 * @param  $and_taxes bool Calc taxes if true
	 * @return float calculated grand total
	 */
	public function calculate_totals( $and_taxes = true ) {

		$cart_subtotal  = 0;
		$cart_total     = 0;
		$fee_total      = 0;

		if ( $and_taxes ) {
			$this->calculate_taxes();
		}

		// line items
		foreach ( $this->get_items() as $item ) {
			$cart_subtotal += wc_format_decimal( isset( $item['line_subtotal'] ) ? $item['line_subtotal'] : 0 );
			$cart_total    += wc_format_decimal( isset( $item['line_total'] ) ? $item['line_total'] : 0 );
		}

		$this->calculate_shipping();

		foreach ( $this->get_fees() as $item ) {
			$fee_total += $item['line_total'];
		}

		$this->set_total( $cart_subtotal - $cart_total, 'cart_discount' );

		$grand_total = round( $cart_total + $fee_total + $this->get_total_shipping() - $this->get_order_discount() + $this->get_cart_tax() + $this->get_shipping_tax(), absint( get_option( 'woocommerce_price_num_decimals' ) ) );

		$this->set_total( $grand_total, 'total' );

		return $grand_total;
	}

	/**
	 * Gets an order from the database.
	 *
	 * @param int $id (default: 0)
	 * @return bool
	 */
	public function get_order( $id = 0 ) {

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
	 *
	 * @param mixed $result
	 */
	public function populate( $result ) {

		// Standard post data
		$this->id                  = $result->ID;
		$this->order_date          = $result->post_date;
		$this->modified_date       = $result->post_modified;
		$this->customer_message    = $result->post_excerpt;
		$this->customer_note       = $result->post_excerpt;
		$this->post_status         = $result->post_status;

		// Billing email cam default to user if set
		if ( empty( $this->billing_email ) && ! empty( $this->customer_user ) ) {
			$user                = get_user_by( 'id', $this->customer_user );
			$this->billing_email = $user->user_email;
		}
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {

		if ( ! $this->id ) {
			return false;
		}

		return metadata_exists( 'post', $this->id, '_' . $key );
	}

	/**
	 * __get function.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {

		// Get values or default if not set
		if ( 'completed_date' === $key ) {
			$value = ( $value = get_post_meta( $this->id, '_completed_date', true ) ) ? $value : $this->modified_date;
		} elseif ( 'user_id' === $key ) {
			$value = ( $value = get_post_meta( $this->id, '_customer_user', true ) ) ? absint( $value ) : '';
		} elseif ( 'status' === $key ) {
			$value = $this->get_status();
		} else {
			$value = get_post_meta( $this->id, '_' . $key, true );
		}

		return $value;
	}

	/**
	 * Return the order statuses without wc- internal prefix
	 *
	 * @return string
	 */
	public function get_status() {
		return apply_filters( 'woocommerce_order_get_status', 'wc-' === substr( $this->post_status, 0, 3 ) ? substr( $this->post_status, 3 ) : $this->post_status, $this );
	}

	/**
	 * Checks the order status against a passed in status.
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_order_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * Gets the user ID associated with the order. Guests are 0.
	 *
	 * @since  2.2
	 * @return int|false
	 */
	public function get_user_id() {
		return $this->customer_user ? $this->customer_user : 0;
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
	 * Get transaction id for the order
	 *
	 * @return string
	 */
	public function get_transaction_id() {
		return get_post_meta( $this->id, '_transaction_id', true );
	}

	/**
	 * Check if an order key is valid.
	 *
	 * @param mixed $key
	 * @return bool
	 */
	public function key_is_valid( $key ) {

		if ( $key == $this->order_key ) {
			return true;
		}

		return false;
	}

	/**
	 * get_order_number function.
	 *
	 * Gets the order number for display (by default, order ID)
	 *
	 * @return string
	 */
	public function get_order_number() {
		return apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce' ) . $this->id, $this );
	}

	/**
	 * Get a formatted billing address for the order.
	 *
	 * @return string
	 */
	public function get_formatted_billing_address() {

		if ( ! $this->formatted_billing_address ) {

			// Formatted Addresses
			$address = apply_filters( 'woocommerce_order_formatted_billing_address', array(
				'first_name'    => $this->billing_first_name,
				'last_name'     => $this->billing_last_name,
				'company'       => $this->billing_company,
				'address_1'     => $this->billing_address_1,
				'address_2'     => $this->billing_address_2,
				'city'          => $this->billing_city,
				'state'         => $this->billing_state,
				'postcode'      => $this->billing_postcode,
				'country'       => $this->billing_country
			), $this );

			$this->formatted_billing_address = WC()->countries->get_formatted_address( $address );
		}

		return $this->formatted_billing_address;
	}

	/**
	 * Get the billing address in an array.
	 *
	 * @return string
	 */
	public function get_billing_address() {

		if ( ! $this->billing_address ) {

			// Formatted Addresses
			$address = array(
				'address_1'     => $this->billing_address_1,
				'address_2'     => $this->billing_address_2,
				'city'          => $this->billing_city,
				'state'         => $this->billing_state,
				'postcode'      => $this->billing_postcode,
				'country'       => $this->billing_country
			);

			$joined_address = array();

			foreach ( $address as $part ) {

				if ( ! empty( $part ) ) {
					$joined_address[] = $part;
				}
			}

			$this->billing_address = implode( ', ', $joined_address );
		}

		return $this->billing_address;
	}

	/**
	 * Get a formatted shipping address for the order.
	 *
	 * @return string
	 */
	public function get_formatted_shipping_address() {

		if ( ! $this->formatted_shipping_address ) {

			if ( $this->shipping_address_1 ) {

				// Formatted Addresses
				$address = apply_filters( 'woocommerce_order_formatted_shipping_address', array(
					'first_name'    => $this->shipping_first_name,
					'last_name'     => $this->shipping_last_name,
					'company'       => $this->shipping_company,
					'address_1'     => $this->shipping_address_1,
					'address_2'     => $this->shipping_address_2,
					'city'          => $this->shipping_city,
					'state'         => $this->shipping_state,
					'postcode'      => $this->shipping_postcode,
					'country'       => $this->shipping_country
				), $this );

				$this->formatted_shipping_address = WC()->countries->get_formatted_address( $address );
			}
		}

		return $this->formatted_shipping_address;
	}

	/**
	 * Get the shipping address in an array.
	 *
	 * @return array
	 */
	public function get_shipping_address() {

		if ( ! $this->shipping_address ) {

			if ( $this->shipping_address_1 ) {

				// Formatted Addresses
				$address = array(
					'address_1'     => $this->shipping_address_1,
					'address_2'     => $this->shipping_address_2,
					'city'          => $this->shipping_city,
					'state'         => $this->shipping_state,
					'postcode'      => $this->shipping_postcode,
					'country'       => $this->shipping_country
				);

				$joined_address = array();

				foreach ( $address as $part ) {

					if ( ! empty( $part ) ) {
						$joined_address[] = $part;
					}
				}

				$this->shipping_address = implode( ', ', $joined_address );
			}
		}

		return $this->shipping_address;
	}

	/**
	 * Return an array of items/products within this order.
	 *
	 * @param string|array $type Types of line items to get (array or string)
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

		$type = array_map( 'esc_attr', $type );

		$line_items = $wpdb->get_results( $wpdb->prepare( "
			SELECT      order_item_id, order_item_name, order_item_type
			FROM        {$wpdb->prefix}woocommerce_order_items
			WHERE       order_id = %d
			AND         order_item_type IN ( '" . implode( "','", $type ) . "' )
			ORDER BY    order_item_id
		", $this->id ) );

		$items = array();

		// Reserved meta keys
		$reserved_item_meta_keys = array(
			'name',
			'type',
			'item_meta',
			'qty',
			'tax_class',
			'product_id',
			'variation_id',
			'line_subtotal',
			'line_total',
			'line_tax',
			'line_subtotal_tax'
		);

		// Loop items
		foreach ( $line_items as $item ) {

			// Place line item into array to return
			$items[ $item->order_item_id ]['name']      = $item->order_item_name;
			$items[ $item->order_item_id ]['type']      = $item->order_item_type;
			$items[ $item->order_item_id ]['item_meta'] = $this->get_item_meta( $item->order_item_id );

			// Expand meta data into the array
			if ( $items[ $item->order_item_id ]['item_meta'] ) {
				foreach ( $items[ $item->order_item_id ]['item_meta'] as $name => $value ) {

					if ( in_array( $name, $reserved_item_meta_keys ) ) {
						continue;
					}

					if ( '_' === substr( $name, 0, 1 ) ) {
						$items[ $item->order_item_id ][ substr( $name, 1 ) ] = $value[0];
					} elseif ( ! in_array( $name, $reserved_item_meta_keys ) ) {
						$items[ $item->order_item_id ][ $name ] = $value[0];
					}
				}
			}
		}

		return apply_filters( 'woocommerce_order_get_items', $items, $this );
	}

	/**
	 * Gets order total - formatted for display.
	 *
	 * @return string
	 */
	public function get_item_count( $type = '' ) {

		if ( empty( $type ) ) {
			$type = array( 'line_item' );
		}

		if ( ! is_array( $type ) ) {
			$type = array( $type );
		}

		$items = $this->get_items( $type );

		$count = 0;

		foreach ( $items as $item ) {

			if ( ! empty( $item['qty'] ) ) {
				$count += $item['qty'];
			} else {
				$count ++;
			}
		}

		return apply_filters( 'woocommerce_get_item_count', $count, $type, $this );
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
	 * Check whether this order has a specific shipping method or not
	 *
	 * @param string $method_id
	 */
	public function has_shipping_method( $method_id ) {

		$shipping_methods = $this->get_shipping_methods();
		$has_method = false;

		if ( ! $shipping_methods ) {
			return false;
		}

		foreach ( $shipping_methods as $shipping_method ) {

			if ( $shipping_method['method_id'] == $method_id ) {
				$has_method = true;
			}
		}

		return $has_method;
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
	 * has_meta function for order items.
	 *
	 * @param string $order_item_id
	 * @return array of meta data
	 */
	public function has_meta( $order_item_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value, meta_id, order_item_id
			FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d
			ORDER BY meta_id", absint( $order_item_id ) ), ARRAY_A );
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
	 * Gets the total (product) discount amount - these are applied before tax.
	 *
	 * @return float
	 */
	public function get_cart_discount() {
		return apply_filters( 'woocommerce_order_amount_cart_discount', (double) $this->cart_discount, $this );
	}

	/**
	 * Gets the total (product) discount amount - these are applied before tax.
	 *
	 * @return float
	 */
	public function get_order_discount() {
		return apply_filters( 'woocommerce_order_amount_order_discount', (double) $this->order_discount, $this );
	}

	/**
	 * Gets the total discount amount - both kinds
	 *
	 * @return float
	 */
	public function get_total_discount() {
		return apply_filters( 'woocommerce_order_amount_total_discount', $this->get_cart_discount() + $this->get_order_discount(), $this );
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @return float
	 */
	public function get_cart_tax() {
		return apply_filters( 'woocommerce_order_amount_cart_tax', (double) $this->order_tax, $this );
	}

	/**
	 * Gets shipping tax amount.
	 *
	 * @return float
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_order_amount_shipping_tax', (double) $this->order_shipping_tax, $this );
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
		return apply_filters( 'woocommerce_order_amount_total_shipping', (double) $this->order_shipping, $this );
	}

	/**
	 * Gets order total.
	 *
	 * @return float
	 */
	public function get_total() {
		return apply_filters( 'woocommerce_order_amount_total', (double) $this->order_total, $this );
	}

	/**
	 * Gets order subtotal.
	 *
	 * @return mixed|void
	 */
	public function get_subtotal() {

		$subtotal = 0;

		foreach ( $this->get_items() as $item ) {
			$subtotal += ( isset( $item['line_subtotal'] ) ) ? $item['line_subtotal'] : 0;
		}

		return apply_filters( 'woocommerce_order_amount_subtotal', (double) $subtotal, $this );
	}

	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {

		if ( $inc_tax ) {
			$price = ( $item['line_subtotal'] + $item['line_subtotal_tax'] ) / max( 1, $item['qty'] );
		} else {
			$price = ( $item['line_subtotal'] / $item['qty'] );
		}

		$price = $round ? round( $price, 2 ) : $price;

		return apply_filters( 'woocommerce_order_amount_item_subtotal', $price, $this, $item );
	}

	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_line_subtotal( $item, $inc_tax = false, $round = true ) {

		if ( $inc_tax ) {
			$price = $item['line_subtotal'] + $item['line_subtotal_tax'];
		} else {
			$price = $item['line_subtotal'];
		}

		$price = $round ? round( $price, 2 ) : $price;

		return apply_filters( 'woocommerce_order_amount_line_subtotal', $price, $this, $item );
	}

	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @param bool $round (default: true)
	 * @return float
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {

		$qty = ( ! empty( $item['qty'] ) ) ? $item['qty'] : 1;

		if ( $inc_tax ) {
			$price = ( $item['line_total'] + $item['line_tax'] ) / max( 1, $qty );
		} else {
			$price = $item['line_total'] / $qty;
		}

		$price = $round ? round( $price, 2 ) : $price;

		return apply_filters( 'woocommerce_order_amount_item_total', $price, $this );
	}

	/**
	 * Calculate line total - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $inc_tax (default: false)
	 * @return float
	 */
	public function get_line_total( $item, $inc_tax = false ) {

		$line_total = $inc_tax ? round( $item['line_total'] + $item['line_tax'], 2 ) : round( $item['line_total'], 2 );

		return apply_filters( 'woocommerce_order_amount_line_total', $line_total, $this );
	}

	/**
	 * Calculate item tax - useful for gateways.
	 *
	 * @param mixed $item
	 * @param bool $round (default: true)
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

	/**
	 * Gets shipping total.
	 *
	 * @deprecated As of 2.1, use of get_total_shipping() is preferred
	 * @return float
	 */
	public function get_shipping() {
		_deprecated_function( 'get_shipping', '2.1', 'get_total_shipping' );
		return $this->get_total_shipping();
	}

	/**
	 * get_order_total function. Alias for get_total()
	 *
	 * @deprecated As of 2.1, use of get_total() is preferred
	 * @return float
	 */
	public function get_order_total() {
		_deprecated_function( 'get_order_total', '2.1', 'get_total' );
		return $this->get_total();
	}

	/** End Total Getters *******************************************************/

	/**
	 * Gets formatted shipping method title.
	 *
	 * @return string
	 */
	public function get_shipping_method() {

		$labels = array();

		// Backwards compat < 2.1 - get shipping title stored in meta
		if ( $this->shipping_method_title ) {

			$labels[] = $this->shipping_method_title;
		} else {

			// 2.1+ get line items for shipping
			$shipping_methods = $this->get_shipping_methods();

			foreach ( $shipping_methods as $shipping ) {
				$labels[] = $shipping['name'];
			}
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
	 * Gets order currency
	 *
	 * @return string
	 */
	public function get_order_currency() {
		return apply_filters( 'woocommerce_get_order_currency', $this->order_currency, $this );
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
	 * @param bool $compound (default: false)
	 * @param string $tax_display (default: the tax_display_cart value)
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
				$subtotal .= ' <small>' . WC()->countries->ex_tax_or_vat() . '</small>';
			}

		} else {

			if ( 'incl' == $tax_display ) {
				return '';
			}

			foreach ( $this->get_items() as $item ) {

				$subtotal += $item['line_subtotal'];

			}

			// Add Shipping Costs
			$subtotal += $this->get_total_shipping();

			// Remove non-compound taxes
			foreach ( $this->get_taxes() as $tax ) {

				if ( ! empty( $tax['compound'] ) ) {
					continue;
				}

				$subtotal = $subtotal + $tax['tax_amount'] + $tax['shipping_tax_amount'];

			}

			// Remove discounts
			$subtotal = $subtotal - $this->get_cart_discount();

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

		if ( $this->order_shipping > 0 ) {

			$tax_text = '';

			if ( $tax_display == 'excl' ) {

				// Show shipping excluding tax
				$shipping = wc_price( $this->order_shipping, array('currency' => $this->get_order_currency()) );

				if ( $this->order_shipping_tax > 0 && $this->prices_include_tax ) {
					$tax_text = WC()->countries->ex_tax_or_vat() . ' ';
				}

			} else {

				// Show shipping including tax
				$shipping = wc_price( $this->order_shipping + $this->order_shipping_tax, array('currency' => $this->get_order_currency()) );

				if ( $this->order_shipping_tax > 0 && ! $this->prices_include_tax ) {
					$tax_text = WC()->countries->inc_tax_or_vat() . ' ';
				}

			}

			$shipping .= sprintf( __( '&nbsp;<small>%svia %s</small>', 'woocommerce' ), $tax_text, $this->get_shipping_method() );

		} elseif ( $this->get_shipping_method() ) {
			$shipping = $this->get_shipping_method();
		} else {
			$shipping = __( 'Free!', 'woocommerce' );
		}

		return apply_filters( 'woocommerce_order_shipping_to_display', $shipping, $this );
	}


	/**
	 * Get cart discount (formatted).
	 *
	 * @return string.
	 */
	public function get_cart_discount_to_display() {
		return apply_filters( 'woocommerce_order_cart_discount_to_display', wc_price( $this->get_cart_discount(), array( 'currency' => $this->get_order_currency() ) ), $this );
	}


	/**
	 * Get cart discount (formatted).
	 *
	 * @return string
	 */
	public function get_order_discount_to_display() {
		return apply_filters( 'woocommerce_order_discount_to_display', wc_price( $this->get_order_discount(), array( 'currency' => $this->get_order_currency() ) ), $this );
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
	 * @return array
	 */
	public function get_order_item_totals( $tax_display = '' ) {

		if ( ! $tax_display ) {
			$tax_display = $this->tax_display_cart;
		}

		$total_rows = array();

		if ( $subtotal = $this->get_subtotal_to_display( false, $tax_display ) ) {
			$total_rows['cart_subtotal'] = array(
				'label' => __( 'Cart Subtotal:', 'woocommerce' ),
				'value'	=> $subtotal
			);
		}

		if ( $this->get_cart_discount() > 0 ) {
			$total_rows['cart_discount'] = array(
				'label' => __( 'Cart Discount:', 'woocommerce' ),
				'value'	=> '-' . $this->get_cart_discount_to_display()
			);
		}

		if ( $this->get_shipping_method() ) {
			$total_rows['shipping'] = array(
				'label' => __( 'Shipping:', 'woocommerce' ),
				'value'	=> $this->get_shipping_to_display()
			);
		}

		if ( $fees = $this->get_fees() )

			foreach( $fees as $id => $fee ) {

				if ( apply_filters( 'woocommerce_get_order_item_totals_excl_free_fees', $fee['line_total'] + $fee['line_tax'] == 0, $id ) ) {
					continue;
				}

				if ( 'excl' == $tax_display ) {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => $fee['name'] . ':',
						'value'	=> wc_price( $fee['line_total'], array('currency' => $this->get_order_currency()) )
					);

				} else {

					$total_rows[ 'fee_' . $id ] = array(
						'label' => $fee['name'] . ':',
						'value'	=> wc_price( $fee['line_total'] + $fee['line_tax'], array('currency' => $this->get_order_currency()) )
					);
				}
			}

		// Tax for tax exclusive prices
		if ( 'excl' == $tax_display ) {

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
					'value'	=> wc_price( $this->get_total_tax(), array('currency' => $this->get_order_currency()) )
				);
			}
		}

		if ( $this->get_order_discount() > 0 ) {
			$total_rows['order_discount'] = array(
				'label' => __( 'Order Discount:', 'woocommerce' ),
				'value'	=> '-' . $this->get_order_discount_to_display()
			);
		}

		if ( $this->get_total() > 0 ) {
			$total_rows['payment_method'] = array(
				'label' => __( 'Payment Method:', 'woocommerce' ),
				'value' => $this->payment_method_title
			);
		}

		$total_rows['order_total'] = array(
			'label' => __( 'Order Total:', 'woocommerce' ),
			'value'	=> $this->get_formatted_order_total()
		);

		// Tax for inclusive prices
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) && 'incl' == $tax_display ) {

			$tax_string_array = array();

			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
				}

			} else {
				$tax_string_array[] = sprintf( '%s %s', wc_price( $this->get_total_tax(), array('currency' => $this->get_order_currency()) ), WC()->countries->tax_or_vat() );
			}

			if ( ! empty( $tax_string_array ) ) {
				$total_rows['order_total']['value'] .= ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
			}
		}

		return apply_filters( 'woocommerce_get_order_item_totals', $total_rows, $this );
	}


	/**
	 * Output items for display in html emails.
	 *
	 * @param bool $show_download_links (default: false)
	 * @param bool $show_sku (default: false)
	 * @param bool $show_purchase_note (default: false)
	 * @param bool $show_image (default: false)
	 * @param array $image_size (default: array( 32, 32 )
	 * @param bool plain text
	 * @return string
	 */
	public function email_order_items_table( $show_download_links = false, $show_sku = false, $show_purchase_note = false, $show_image = false, $image_size = array( 32, 32 ), $plain_text = false ) {

		ob_start();

		$template = $plain_text ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

		wc_get_template( $template, array(
			'order'                 => $this,
			'items'                 => $this->get_items(),
			'show_download_links'   => $show_download_links,
			'show_sku'              => $show_sku,
			'show_purchase_note'    => $show_purchase_note,
			'show_image'            => $show_image,
			'image_size'            => $image_size
		) );

		$return = apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $this );

		return $return;
	}

	/**
	 * Checks if product download is permitted
	 *
	 * @return bool
	 */
	public function is_download_permitted() {
		return apply_filters( 'woocommerce_order_is_download_permitted', $this->has_status( 'completed' ) || ( get_option( 'woocommerce_downloads_grant_access_after_payment' ) == 'yes' && $this->has_status( 'processing' ) ), $this );
	}

	/**
	 * Returns true if the order contains a downloadable product.
	 *
	 * @return bool
	 */
	public function has_downloadable_item() {

		$has_downloadable_item = false;

		foreach ( $this->get_items() as $item ) {

			$_product = $this->get_product_from_item( $item );

			if ( $_product && $_product->exists() && $_product->is_downloadable() && $_product->has_file() ) {
				$has_downloadable_item = true;
			}

		}

		return $has_downloadable_item;
	}


	/**
	 * Generates a URL so that a customer can pay for their (unpaid - pending) order. Pass 'true' for the checkout version which doesn't offer gateway choices.
	 *
	 * @param  boolean $on_checkout
	 * @return string
	 */
	public function get_checkout_payment_url( $on_checkout = false ) {

		$pay_url = wc_get_endpoint_url( 'order-pay', $this->id, get_permalink( wc_get_page_id( 'checkout' ) ) );

		if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
			$pay_url = str_replace( 'http:', 'https:', $pay_url );
		}

		if ( $on_checkout ) {
			$pay_url = add_query_arg( 'key', $this->order_key, $pay_url );
		} else {
			$pay_url = add_query_arg( array( 'pay_for_order' => 'true', 'key' => $this->order_key ), $pay_url );
		}

		return apply_filters( 'woocommerce_get_checkout_payment_url', $pay_url, $this );
	}


	/**
	 * Generates a URL for the thanks page (order received)
	 *
	 * @return string
	 */
	public function get_checkout_order_received_url() {

		$order_received_url = wc_get_endpoint_url( 'order-received', $this->id, get_permalink( wc_get_page_id( 'checkout' ) ) );

		if ( 'yes' == get_option( 'woocommerce_force_ssl_checkout' ) || is_ssl() ) {
			$order_received_url = str_replace( 'http:', 'https:', $order_received_url );
		}

		$order_received_url = add_query_arg( 'key', $this->order_key, $order_received_url );

		return apply_filters( 'woocommerce_get_checkout_order_received_url', $order_received_url, $this );
	}


	/**
	 * Generates a URL so that a customer can cancel their (unpaid - pending) order.
	 *
	 * @return string
	 */
	public function get_cancel_order_url( $redirect = '' ) {
		$cancel_endpoint = get_permalink( wc_get_page_id( 'cart' ) );
		if ( ! $cancel_endpoint ) {
			$cancel_endpoint = home_url();
		}

		if ( false === strpos( $cancel_endpoint, '?' ) ) {
			$cancel_endpoint = trailingslashit( $cancel_endpoint );
		}

		return apply_filters('woocommerce_get_cancel_order_url', wp_nonce_url( add_query_arg( array( 'cancel_order' => 'true', 'order' => $this->order_key, 'order_id' => $this->id, 'redirect' => $redirect ), $cancel_endpoint ), 'woocommerce-cancel_order' ) );
	}

	/**
	 * Generates a URL to view an order from the my account page
	 *
	 * @return string
	 */
	public function get_view_order_url() {

		$view_order_url = wc_get_endpoint_url( 'view-order', $this->id, get_permalink( wc_get_page_id( 'myaccount' ) ) );

		return apply_filters( 'woocommerce_get_view_order_url', $view_order_url, $this );
	}

	/**
	 * Gets any downloadable product file urls.
	 *
	 * @deprecated as of 2.1 get_item_downloads is preferred as downloads are more than just file urls
	 * @param int $product_id product identifier
	 * @param int $variation_id variation identifier, or null
	 * @param array $item the item
	 * @return array available downloadable file urls
	 */
	public function get_downloadable_file_urls( $product_id, $variation_id, $item ) {
		global $wpdb;

		_deprecated_function( 'get_downloadable_file_urls', '2.1', 'get_item_downloads' );

		$download_file = $variation_id > 0 ? $variation_id : $product_id;
		$_product = wc_get_product( $download_file );

		$user_email = $this->billing_email;

		$results = $wpdb->get_results( $wpdb->prepare("
			SELECT download_id
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
		", $user_email, $this->order_key, $download_file ) );

		$file_urls = array();

		foreach ( $results as $result ) {

			if ( $_product->has_file( $result->download_id ) ) {
				$file_urls[ $_product->get_file_download_path( $result->download_id ) ] = $this->get_download_url( $download_file, $result->download_id );
			}
		}

		return apply_filters( 'woocommerce_get_downloadable_file_urls', $file_urls, $product_id, $variation_id, $item );
	}

	/**
	 * Get the downloadable files for an item in this order
	 *
	 * @param  array $item
	 * @return array
	 */
	public function get_item_downloads( $item ) {
		global $wpdb;

		$product_id   = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
		$product      = wc_get_product( $product_id );
		$download_ids = $wpdb->get_col( $wpdb->prepare("
			SELECT download_id
			FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
			WHERE user_email = %s
			AND order_key = %s
			AND product_id = %s
			ORDER BY permission_id
		", $this->billing_email, $this->order_key, $product_id ) );

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
	 * Get the Download URL
	 *
	 * @param  int $product_id
	 * @param  int $download_id
	 * @return string
	 */
	public function get_download_url( $product_id, $download_id ) {
		return add_query_arg( array(
			'download_file' => $product_id,
			'order'         => $this->order_key,
			'email'         => urlencode( $this->billing_email ),
			'key'           => $download_id
		), trailingslashit( home_url() ) );
	}

	/**
	 * Adds a note (comment) to the order
	 *
	 * @param string $note Note to add
	 * @param int $is_customer_note (default: 0) Is this a note for the customer?
	 * @return id Comment ID
	 */
	public function add_order_note( $note, $is_customer_note = 0 ) {

		$is_customer_note = intval( $is_customer_note );

		if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->id ) ) {
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

		add_comment_meta( $comment_id, 'is_customer_note', $is_customer_note );

		if ( $is_customer_note ) {
			do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->id, 'customer_note' => $note ) );
		}

		return $comment_id;
	}

	/**
	 * Updates status of order
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @param string $note (default: '') Optional note to add
	 */
	public function update_status( $new_status, $note = '' ) {

		if ( ! $this->id ) {
			return;
		}

		// Standardise status names.
		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		$old_status = $this->get_status();

		// Only update if they differ - and ensure post_status is a 'wc' status.
		if ( $new_status !== $old_status || ! in_array( $this->post_status, array_keys( wc_get_order_statuses() ) ) ) {

			// Update the order
			wp_update_post( array( 'ID' => $this->id, 'post_status' => 'wc-' . $new_status ) );
			$this->post_status = 'wc-' . $new_status;

			$this->add_order_note( trim( $note . ' ' . sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), wc_get_order_status_name( $old_status ), wc_get_order_status_name( $new_status ) ) ) );

			// Status was changed
			do_action( 'woocommerce_order_status_' . $new_status, $this->id );
			do_action( 'woocommerce_order_status_' . $old_status . '_to_' . $new_status, $this->id );
			do_action( 'woocommerce_order_status_changed', $this->id, $old_status, $new_status );

			switch ( $new_status ) {

				case 'completed' :
					// Record the sales
					$this->record_product_sales();

					// Increase coupon usage counts
					$this->increase_coupon_usage_counts();

					// Record the completed date of the order
					update_post_meta( $this->id, '_completed_date', current_time('mysql') );

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;

				case 'processing' :
				case 'on-hold' :
					// Record the sales
					$this->record_product_sales();

					// Increase coupon usage counts
					$this->increase_coupon_usage_counts();

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;

				case 'cancelled' :
					// If the order is cancelled, restore used coupons
					$this->decrease_coupon_usage_counts();

					// Update reports
					wc_delete_shop_order_transients( $this->id );
				break;
			}
		}
	}


	/**
	 * Cancel the order and restore the cart (before payment)
	 *
	 * @param string $note (default: '') Optional note to add
	 */
	public function cancel_order( $note = '' ) {

		unset( WC()->session->order_awaiting_payment );

		$this->update_status( 'cancelled', $note );
	}

	/**
	 * When a payment is complete this function is called
	 *
	 * Most of the time this should mark an order as 'processing' so that admin can process/post the items
	 * If the cart contains only downloadable items then the order is 'completed' since the admin needs to take no action
	 * Stock levels are reduced at this point
	 * Sales are also recorded for products
	 * Finally, record the date of payment
	 *
	 * @param $transaction_id string Optional transaction id to store in post meta
	 */
	public function payment_complete( $transaction_id = '' ) {

		do_action( 'woocommerce_pre_payment_complete', $this->id );

		if ( ! empty( WC()->session->order_awaiting_payment ) ) {
			unset( WC()->session->order_awaiting_payment );
		}

		$valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed' ), $this );

		if ( $this->id && $this->has_status( $valid_order_statuses ) ) {

			$order_needs_processing = true;

			if ( sizeof( $this->get_items() ) > 0 ) {

				foreach ( $this->get_items() as $item ) {

					if ( $item['product_id'] > 0 ) {

						$_product = $this->get_product_from_item( $item );

							if ( false !== $_product && ! apply_filters( 'woocommerce_order_item_needs_processing', ! ( $_product->is_downloadable() && $_product->is_virtual() ), $_product, $this->id ) ) {
							$order_needs_processing = false;
							continue;
						}
					}

					$order_needs_processing = true;
					break;
				}
			}

			$new_order_status = $order_needs_processing ? 'processing' : 'completed';

			$new_order_status = apply_filters( 'woocommerce_payment_complete_order_status', $new_order_status, $this->id );

			$this->update_status( $new_order_status );

			add_post_meta( $this->id, '_paid_date', current_time('mysql'), true );

			if ( ! empty( $transaction_id ) ) {
				add_post_meta( $this->id, '_transaction_id', $transaction_id, true );
			}

			$this_order = array(
				'ID' => $this->id,
				'post_date' => current_time( 'mysql', 0 ),
				'post_date_gmt' => current_time( 'mysql', 1 )
			);
			wp_update_post( $this_order );

			if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', true, $this->id ) ) {
				$this->reduce_order_stock(); // Payment is complete so reduce stock levels
			}

			do_action( 'woocommerce_payment_complete', $this->id );

		} else {

			do_action( 'woocommerce_payment_complete_order_status_' . $this->get_status(), $this->id );

		}
	}


	/**
	 * Record sales
	 */
	public function record_product_sales() {

		if ( 'yes' == get_post_meta( $this->id, '_recorded_sales', true ) ) {
			return;
		}

		if ( sizeof( $this->get_items() ) > 0 ) {

			foreach ( $this->get_items() as $item ) {

				if ( $item['product_id'] > 0 ) {
					$sales = (int) get_post_meta( $item['product_id'], 'total_sales', true );
					$sales += (int) $item['qty'];

					if ( $sales ) {
						update_post_meta( $item['product_id'], 'total_sales', $sales );
					}
				}
			}
		}

		update_post_meta( $this->id, '_recorded_sales', 'yes' );
	}


	/**
	 * Get coupon codes only.
	 *
	 * @return array
	 */
	public function get_used_coupons() {

		$codes   = array();
		$coupons = $this->get_items( 'coupon' );

		foreach ( $coupons as $item_id => $item ) {
			$codes[] = trim( $item['name'] );
		}

		return $codes;
	}


	/**
	 * Increase applied coupon counts
	 */
	public function increase_coupon_usage_counts() {

		if ( 'yes' == get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) ) {
			return;
		}

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {

			foreach ( $this->get_used_coupons() as $code ) {
				if ( ! $code ) {
					continue;
				}

				$coupon = new WC_Coupon( $code );

				$used_by = $this->user_id;

				if ( ! $used_by ) {
					$used_by = $this->billing_email;
				}

				$coupon->inc_usage_count( $used_by );
			}
		}

		update_post_meta( $this->id, '_recorded_coupon_usage_counts', 'yes' );
	}


	/**
	 * Decrease applied coupon counts
	 */
	public function decrease_coupon_usage_counts() {

		if ( 'yes' != get_post_meta( $this->id, '_recorded_coupon_usage_counts', true ) ) {
			return;
		}

		if ( sizeof( $this->get_used_coupons() ) > 0 ) {

			foreach ( $this->get_used_coupons() as $code ) {

				if ( ! $code ) {
					continue;
				}

				$coupon = new WC_Coupon( $code );

				$used_by = $this->user_id;
				if ( ! $used_by ) {
					$used_by = $this->billing_email;
				}

				$coupon->dcr_usage_count( $used_by );
			}
		}

		delete_post_meta( $this->id, '_recorded_coupon_usage_counts' );
	}


	/**
	 * Reduce stock levels
	 */
	public function reduce_order_stock() {

		if ( 'yes' == get_option('woocommerce_manage_stock') && sizeof( $this->get_items() ) > 0 ) {

			// Reduce stock levels and do any other actions with products in the cart
			foreach ( $this->get_items() as $item ) {

				if ( $item['product_id'] > 0 ) {
					$_product = $this->get_product_from_item( $item );

					if ( $_product && $_product->exists() && $_product->managing_stock() ) {
						$qty       = apply_filters( 'woocommerce_order_item_quantity', $item['qty'], $this, $item );
						$new_stock = $_product->reduce_stock( $qty );

						$this->add_order_note( sprintf( __( 'Item #%s stock reduced from %s to %s.', 'woocommerce' ), $item['product_id'], $new_stock + $qty, $new_stock) );
						$this->send_stock_notifications( $_product, $new_stock, $item['qty'] );
					}

				}

			}

			do_action( 'woocommerce_reduce_order_stock', $this );

			$this->add_order_note( __( 'Order item stock reduced successfully.', 'woocommerce' ) );
		}
	}

	/**
	 * send_stock_notifications function.
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

		if ( 'yes' == get_option( 'woocommerce_notify_no_stock' ) && get_option('woocommerce_notify_no_stock_amount') >= $new_stock ) {
			do_action( 'woocommerce_no_stock', $product );
			$notification_sent = true;
		}

		if ( ! $notification_sent && 'yes' == get_option( 'woocommerce_notify_low_stock' ) && get_option('woocommerce_notify_low_stock_amount') >= $new_stock ) {
			do_action( 'woocommerce_low_stock', $product );
			$notification_sent = true;
		}
	}


	/**
	 * List order notes (public) for the customer
	 *
	 * @return array
	 */
	public function get_customer_order_notes() {

		$notes = array();

		$args = array(
			'post_id' => $this->id,
			'approve' => 'approve',
			'type' => ''
		);

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		$comments = get_comments( $args );

		foreach ( $comments as $comment ) {

			$is_customer_note = get_comment_meta( $comment->comment_ID, 'is_customer_note', true );
			$comment->comment_content = make_clickable( $comment->comment_content );

			if ( $is_customer_note ) {
				$notes[] = $comment;
			}
		}

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ) );

		return (array) $notes;

	}

	/**
	 * Checks if an order needs payment, based on status and order total
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
	 * Checks if an order needs display the shipping address, based on shipping method
	 *
	 * @return boolean
	 */
	public function needs_shipping_address() {
		$hide  = apply_filters( 'woocommerce_order_hide_shipping_address', array( 'local_pickup' ), $this );
		$needs = false;

		foreach ( $this->get_shipping_methods() as $shipping_method ) {

			if ( ! in_array( $shipping_method['method_id'], $hide ) ) {
				$needs = true;
				break;
			}
		}

		return $needs;
	}

	/**
	 * Checks if an order can be edited, specifically for use on the Edit Order screen
	 *
	 * @access public
	 * @return bool
	 */
	public function is_editable() {
		if ( ! isset( $this->editable ) ) {
			$this->editable = in_array( $this->get_status(), array( 'pending', 'on-hold', 'auto-draft' ) );
		}
		return apply_filters( 'wc_order_is_editable', $this->editable, $this );
	}
}
