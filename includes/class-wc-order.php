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
        do_action( 'woocommerce_pre_payment_complete', $this->get_id() );

        if ( ! empty( WC()->session ) ) {
            WC()->session->set( 'order_awaiting_payment', false );
        }

        if ( $this->get_id() && $this->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ), $this ) ) ) {
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
        return apply_filters( 'woocommerce_order_is_paid', $this->has_status( apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) ) ), $this );
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
            if ( ! in_array( $shipping_method['method_id'], $hide ) ) {
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

	/**
	 * Gets order total - formatted for display.
	 * @return string
	 */
	public function get_formatted_order_total( $tax_display = '', $display_refunded = true ) {
		$formatted_total = wc_price( $this->get_total(), array( 'currency' => $this->get_order_currency() ) );
		$order_total    = $this->get_total();
		$total_refunded = $this->get_total_refunded();
		$tax_string     = '';

		// Tax for inclusive prices
		if ( wc_tax_enabled() && 'incl' == $tax_display ) {
			$tax_string_array = array();

			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = ( $total_refunded && $display_refunded ) ? wc_price( WC_Tax::round( $tax->amount - $this->get_total_tax_refunded_by_rate_id( $tax->rate_id ) ), array( 'currency' => $this->get_order_currency() ) ) : $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}
			} else {
				$tax_amount         = ( $total_refunded && $display_refunded ) ? $this->get_total_tax() - $this->get_total_tax_refunded() : $this->get_total_tax();
				$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, array( 'currency' => $this->get_order_currency() ) ), WC()->countries->tax_or_vat() );
			}
			if ( ! empty( $tax_string_array ) ) {
				$tax_string = ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) );
			}
		}

		if ( $total_refunded && $display_refunded ) {
			$formatted_total = '<del>' . strip_tags( $formatted_total ) . '</del> <ins>' . wc_price( $order_total - $total_refunded, array( 'currency' => $this->get_order_currency() ) ) . $tax_string . '</ins>';
		} else {
			$formatted_total .= $tax_string;
		}

		return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, $this );
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
            'order_id'     => $this->get_id(),
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
        return apply_filters( 'woocommerce_get_view_order_url', wc_get_endpoint_url( 'view-order', $this->get_id(), wc_get_page_permalink( 'myaccount' ) ), $this );
    }

	/*
    |--------------------------------------------------------------------------
    | Order notes.
    |--------------------------------------------------------------------------
    */

	/**
     * Adds a note (comment) to the order.
     *
     * @param string $note Note to add.
     * @param int $is_customer_note (default: 0) Is this a note for the customer?
     * @param  bool added_by_user Was the note added by a user?
     * @return int Comment ID.
     */
    public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
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

        $comment_post_ID        = $this->get_id();
        $comment_author_url     = '';
        $comment_content        = $note;
        $comment_agent          = 'WooCommerce';
        $comment_type           = 'order_note';
        $comment_parent         = 0;
        $comment_approved       = 1;
        $commentdata            = apply_filters( 'woocommerce_new_order_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'order_id' => $this->get_id(), 'is_customer_note' => $is_customer_note ) );

        $comment_id = wp_insert_comment( $commentdata );

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
		$refunds      = array();
		$refund_items = get_posts(
			array(
				'post_type'      => 'shop_order_refund',
				'post_parent'    => $this->get_id(),
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids'
			)
		);

		foreach ( $refund_items as $refund_id ) {
			$refunds[] = new WC_Order_Refund( $refund_id );
		}

		return $refunds;
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
				$count += $refunded_item->get_qty();
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
				$qty += $refunded_item->get_qty();
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
					$qty += $refunded_item->get_qty();
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
					$total += abs( $refunded_item->tax_total() ) + abs( $refunded_item->shipping_tax_total() );
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
