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

	/** @public string Order type */
	public $order_type = 'simple';

	/*
    |--------------------------------------------------------------------------
    | Conditionals
    |--------------------------------------------------------------------------
    |
    | Checks if a condition is true or false.
    |
    */

	/**
     * Checks if an order can be edited, specifically for use on the Edit Order screen.
     *
     * @return bool
     */
    public function is_editable() {
        return apply_filters( 'wc_order_is_editable', in_array( $this->get_status(), array( 'pending', 'on-hold', 'auto-draft' ) ), $this );
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
	 * Output items for display in html emails.
	 * @param array $args Items args.
	 * @return string
	 */
	public function email_order_items_table( $args = array() ) {
		ob_start();

		$defaults = array(
			'show_sku'   => false,
			'show_image' => false,
			'image_size' => array( 32, 32 ),
			'plain_text' => false
		);

		$args     = wp_parse_args( $args, $defaults );
		$template = $args['plain_text'] ? 'emails/plain/email-order-items.php' : 'emails/email-order-items.php';

		wc_get_template( $template, array(
			'order'               => $this,
			'items'               => $this->get_items(),
			'show_download_links' => $this->is_download_permitted(),
			'show_sku'            => $args['show_sku'],
			'show_purchase_note'  => $this->is_paid(),
			'show_image'          => $args['show_image'],
			'image_size'          => $args['image_size'],
		) );

		return apply_filters( 'woocommerce_email_order_items_table', ob_get_clean(), $this );
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
        if ( ! $this->get_order_id() ) {
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
        wp_update_post( array( 'ID' => $this->get_order_id(), 'post_status' => 'wc-' . $new_status ) );
        $this->post_status = 'wc-' . $new_status;

        $this->add_order_note( trim( $note . ' ' . sprintf( __( 'Order status changed from %s to %s.', 'woocommerce' ), wc_get_order_status_name( $old_status ), wc_get_order_status_name( $new_status ) ) ), 0, $manual );

        // Status was changed.
        do_action( 'woocommerce_order_status_' . $new_status, $this->get_order_id() );
        do_action( 'woocommerce_order_status_' . $old_status . '_to_' . $new_status, $this->get_order_id() );
        do_action( 'woocommerce_order_status_changed', $this->get_order_id(), $old_status, $new_status );

        switch ( $new_status ) {

            case 'completed' :
                // Record the sales.
                $this->record_product_sales();

                // Increase coupon usage counts.
                $this->increase_coupon_usage_counts();

                // Record the completed date of the order.
                update_post_meta( $this->get_order_id(), '_completed_date', current_time('mysql') );

                // Update reports.
                wc_delete_shop_order_transients( $this->get_order_id() );
                break;

            case 'processing' :
            case 'on-hold' :
                // Record the sales.
                $this->record_product_sales();

                // Increase coupon usage counts.
                $this->increase_coupon_usage_counts();

                // Update reports.
                wc_delete_shop_order_transients( $this->get_order_id() );
                break;

            case 'cancelled' :
                // If the order is cancelled, restore used coupons.
                $this->decrease_coupon_usage_counts();

                // Update reports.
                wc_delete_shop_order_transients( $this->get_order_id() );
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
        do_action( 'woocommerce_pre_payment_complete', $this->get_order_id() );

        if ( null !== WC()->session ) {
            WC()->session->set( 'order_awaiting_payment', false );
        }

        $valid_order_statuses = apply_filters( 'woocommerce_valid_order_statuses_for_payment_complete', array( 'on-hold', 'pending', 'failed', 'cancelled' ), $this );

        if ( $this->get_order_id() && $this->has_status( $valid_order_statuses ) ) {
            $order_needs_processing = false;

            if ( sizeof( $this->get_items() ) > 0 ) {
                foreach ( $this->get_items() as $item ) {
                    if ( $_product = $this->get_product_from_item( $item ) ) {
                        $virtual_downloadable_item = $_product->is_downloadable() && $_product->is_virtual();

                        if ( apply_filters( 'woocommerce_order_item_needs_processing', ! $virtual_downloadable_item, $_product, $this->get_order_id() ) ) {
                            $order_needs_processing = true;
                            break;
                        }
                    }
                }
            }

            $this->update_status( apply_filters( 'woocommerce_payment_complete_order_status', $order_needs_processing ? 'processing' : 'completed', $this->get_order_id() ) );

            add_post_meta( $this->get_order_id(), '_paid_date', current_time( 'mysql' ), true );

            if ( ! empty( $transaction_id ) ) {
                update_post_meta( $this->get_order_id(), '_transaction_id', $transaction_id );
            }

            wp_update_post( array(
                'ID'            => $this->get_order_id(),
                'post_date'     => current_time( 'mysql', 0 ),
                'post_date_gmt' => current_time( 'mysql', 1 )
            ) );

            // Payment is complete so reduce stock levels
            if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! get_post_meta( $this->get_order_id(), '_order_stock_reduced', true ), $this->get_order_id() ) ) {
                $this->reduce_order_stock();
            }

            do_action( 'woocommerce_payment_complete', $this->get_order_id() );
        } else {
            do_action( 'woocommerce_payment_complete_order_status_' . $this->get_status(), $this->get_order_id() );
        }
    }

    /**
     * Record sales.
     */
    public function record_product_sales() {
        if ( 'yes' === get_post_meta( $this->get_order_id(), '_recorded_sales', true ) ) {
            return;
        }

        if ( sizeof( $this->get_items() ) > 0 ) {
            foreach ( $this->get_items() as $item ) {
                if ( $item['product_id'] > 0 ) {
                    update_post_meta( $item['product_id'], 'total_sales', absint( get_post_meta( $item['product_id'], 'total_sales', true ) ) + absint( $item['qty'] ) );
                }
            }
        }

        update_post_meta( $this->get_order_id(), '_recorded_sales', 'yes' );

        /**
         * Called when sales for an order are recorded
         *
         * @param int $order_id order id
         */
        do_action( 'woocommerce_recorded_sales', $this->get_order_id() );
    }

    /**
     * Increase applied coupon counts.
     */
    public function increase_coupon_usage_counts() {
        if ( 'yes' === get_post_meta( $this->get_order_id(), '_recorded_coupon_usage_counts', true ) ) {
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

            update_post_meta( $this->get_order_id(), '_recorded_coupon_usage_counts', 'yes' );
        }
    }

    /**
     * Decrease applied coupon counts.
     */
    public function decrease_coupon_usage_counts() {
        if ( 'yes' !== get_post_meta( $this->get_order_id(), '_recorded_coupon_usage_counts', true ) ) {
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

            delete_post_meta( $this->get_order_id(), '_recorded_coupon_usage_counts' );
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

            add_post_meta( $this->get_order_id(), '_order_stock_reduced', '1', true );

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
            do_action( 'woocommerce_product_on_backorder', array( 'product' => $product, 'order_id' => $this->get_order_id(), 'quantity' => $qty_ordered ) );
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

	/**
     * Adds a note (comment) to the order.
     *
     * @param string $note Note to add.
     * @param int $is_customer_note (default: 0) Is this a note for the customer?
     * @param  bool added_by_user Was the note added by a user?
     * @return int Comment ID.
     */
    public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
        if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->get_order_id() ) && $added_by_user ) {
            $user                 = get_user_by( 'id', get_current_user_id() );
            $comment_author       = $user->display_name;
            $comment_author_email = $user->user_email;
        } else {
            $comment_author       = __( 'WooCommerce', 'woocommerce' );
            $comment_author_email = strtolower( __( 'WooCommerce', 'woocommerce' ) ) . '@';
            $comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', $_SERVER['HTTP_HOST'] ) : 'noreply.com';
            $comment_author_email = sanitize_email( $comment_author_email );
        }

        $comment_post_ID        = $this->get_order_id();
        $comment_author_url     = '';
        $comment_content        = $note;
        $comment_agent          = 'WooCommerce';
        $comment_type           = 'order_note';
        $comment_parent         = 0;
        $comment_approved       = 1;
        $commentdata            = apply_filters( 'woocommerce_new_order_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'order_id' => $this->get_order_id(), 'is_customer_note' => $is_customer_note ) );

        $comment_id = wp_insert_comment( $commentdata );

        if ( $is_customer_note ) {
            add_comment_meta( $comment_id, 'is_customer_note', 1 );

            do_action( 'woocommerce_new_customer_note', array( 'order_id' => $this->get_order_id(), 'customer_note' => $commentdata['comment_content'] ) );
        }

        return $comment_id;
    }

    /**
     * Generates a URL so that a customer can pay for their (unpaid - pending) order. Pass 'true' for the checkout version which doesn't offer gateway choices.
     *
     * @param  bool $on_checkout
     * @return string
     */
    public function get_checkout_payment_url( $on_checkout = false ) {
        $pay_url = wc_get_endpoint_url( 'order-pay', $this->get_order_id(), wc_get_page_permalink( 'checkout' ) );

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
        $order_received_url = wc_get_endpoint_url( 'order-received', $this->get_order_id(), wc_get_page_permalink( 'checkout' ) );

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
            'order_id'     => $this->get_order_id(),
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
            'order_id'     => $this->get_order_id(),
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
        return apply_filters( 'woocommerce_get_view_order_url', wc_get_endpoint_url( 'view-order', $this->get_order_id(), wc_get_page_permalink( 'myaccount' ) ), $this );
    }

	/**
     * List order notes (public) for the customer.
     *
     * @return array
     */
    public function get_customer_order_notes() {
        $notes = array();
        $args  = array(
            'post_id' => $this->get_order_id(),
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
	 * Get order refunds.
	 * @since 2.2
	 * @return array of WC_Order_Refund objects
	 */
	public function get_refunds() {
		if ( empty( $this->refunds ) && ! is_array( $this->refunds ) ) {
			$refunds      = array();
			$refund_items = get_posts(
				array(
					'post_type'      => 'shop_order_refund',
					'post_parent'    => $this->get_order_id(),
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
		", $this->get_order_id() ) );

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
		", $this->get_order_id() ) );

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
		", $this->get_order_id() ) );

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
				if ( isset( $refunded_item['refunded_item_id'] ) && $refunded_item['refunded_item_id'] == $item_id ) {
					$qty += $refunded_item['qty'];
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
