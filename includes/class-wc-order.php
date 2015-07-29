<?php
/**
 * Order
 *
 * @class    WC_Order
 * @version  2.2.0
 * @package  WooCommerce/Classes
 * @category Class
 * @author   WooThemes
 */
class WC_Order extends WC_Abstract_Order {

	/** @public string Order type */
	public $order_type = 'simple';

	/**
	 * Gets order total - formatted for display.
	 *
	 * @return string
	 */
	public function get_formatted_order_total( $tax_display = '' ) {
		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_order_currency() ) );
		$order_total    = $this->get_total();
		$total_refunded = $this->get_total_refunded();
		$tax_string     = '';

		// Tax for inclusive prices
		if ( wc_tax_enabled() && 'incl' == $tax_display ) {
			$tax_string_array = array();

			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = $total_refunded ? wc_price( WC_Tax::round( $tax->amount - $this->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), array( 'currency' => $this->get_order_currency() ) ) : $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}
			} else {
				$tax_string_array[] = sprintf( '%s %s', wc_price( $this->get_total_tax() - $this->get_total_tax_refunded(), array( 'currency' => $this->get_order_currency() ) ), WC()->countries->tax_or_vat() );
			}
			if ( ! empty( $tax_string_array ) ) {
				$tax_string = ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
			}
		}

		if ( $total_refunded ) {
			$formatted_total = '<del>' . strip_tags( $formatted_total ) . '</del> <ins>' . wc_price( $order_total - $total_refunded, array( 'currency' => $this->get_order_currency() ) ) . $tax_string . '</ins>';
		} else {
			$formatted_total .= $tax_string;
		}

		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this );
	}

	/**
	 * Get order refunds
	 *
	 * @since 2.2
	 * @return array
	 */
	public function get_refunds() {
		if ( empty( $this->refunds ) && ! is_array( $this->refunds ) ) {
			$refunds      = array();
			$refund_items = get_posts(
				array(
					'post_type'      => 'shop_order_refund',
					'post_parent'    => $this->id,
					'posts_per_page' => -1,
					'post_status'    => 'any',
					'fields'         => 'ids'
				)
			);

			foreach ( $refund_items as $refund_id ) {
				$refunds[] = new WC_Order_Refund( $refund_id );
			}

			$this->refunds = $refunds;
		}
		return $this->refunds;
	}

	/**
	 * Get amount already refunded
	 *
	 * @since 2.2
	 * @return int|float
	 */
	public function get_total_refunded() {
		global $wpdb;

		$total = $wpdb->get_var( $wpdb->prepare( "
			SELECT SUM( postmeta.meta_value )
			FROM $wpdb->postmeta AS postmeta
			INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
			WHERE postmeta.meta_key = '_refund_amount'
			AND postmeta.post_id = posts.ID
		", $this->id ) );

		return $total;
	}

	/**
	 * Get the total tax refunded
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
		", $this->id ) );

		return abs( $total );
	}

	/**
	 * Get the total shipping refunded
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
		", $this->id ) );

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
				$count += empty( $refunded_item['qty'] ) ? 0 : $refunded_item['qty'];
			}
		}

		return apply_filters( 'woocommerce_get_item_count_refunded', $count, $item_type, $this );
	}

	/**
	 * Get the total number of items refunded.
	 *
	 * @since  2.4.0
	 * @param  int $item_id ID of the item we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_total_qty_refunded( $item_type = 'line_item' ) {
		$qty = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$qty += $refunded_item['qty'];
			}
		}
		return $qty;
	}

	/**
	 * Get the refunded amount for a line item
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_qty_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		$qty = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( isset( $refunded_item['refunded_item_id'] ) && $refunded_item['refunded_item_id'] == $item_id ) {
					$qty += $refunded_item['qty'];
				}
			}
		}
		return $qty;
	}

	/**
	 * Get the refunded amount for a line item
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_total_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( isset( $refunded_item['refunded_item_id'] ) && $refunded_item['refunded_item_id'] == $item_id ) {
					switch ( $item_type ) {
						case 'shipping' :
							$total += $refunded_item['cost'];
						break;
						default :
							$total += $refunded_item['line_total'];
						break;
					}
				}
			}
		}
		return $total * -1;
	}

	/**
	 * Get the refunded amount for a line item
	 *
	 * @param  int $item_id ID of the item we're checking
	 * @param  int $tax_id ID of the tax we're checking
	 * @param  string $item_type type of the item we're checking, if not a line_item
	 * @return integer
	 */
	public function get_tax_refunded_for_item( $item_id, $tax_id, $item_type = 'line_item' ) {
		$total = 0;
		foreach ( $this->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				if ( isset( $refunded_item['refunded_item_id'] ) && $refunded_item['refunded_item_id'] == $item_id ) {
					switch ( $item_type ) {
						case 'shipping' :
							$tax_data = maybe_unserialize( $refunded_item['taxes'] );
							if ( isset( $tax_data[ $tax_id ] ) ) {
								$total += $tax_data[ $tax_id ];
							}
						break;
						default :
							$tax_data = maybe_unserialize( $refunded_item['line_tax_data'] );
							if ( isset( $tax_data['total'][ $tax_id ] ) ) {
								$total += $tax_data['total'][ $tax_id ];
							}
						break;
					}
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
				if ( isset( $refunded_item['rate_id'] ) && $refunded_item['rate_id'] == $rate_id ) {
					$total += abs( $refunded_item['tax_amount'] ) + abs( $refunded_item['shipping_tax_amount'] );
				}
			}
		}

		return $total;
	}
}
