<?php
/**
 * Order Functions
 *
 * @package  WooCommerce Admin
 */

/**
 * Make an entry in the wc_admin_order_product_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_admin_order_product_lookup_entry( $order_id ) {
	global $wpdb;

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	foreach ( $order->get_items() as $order_item ) {
		// Shipping amount based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
		// distributed simply based on number of line items.
		$order_items = $order->get_item_count();
		$refunded    = $order->get_total_shipping_refunded();
		if ( $refunded > 0 ) {
			$total_shipping_amount = $order->get_shipping_total() - $refunded;
		} else {
			$total_shipping_amount = $order->get_shipping_total();
		}
		$shipping_amount = $total_shipping_amount / $order_items;

		// Shipping amount tax based on woocommerce code in includes/admin/meta-boxes/views/html-order-item(s).php
		// distribute simply based on number of line items.
		$shipping_tax_amount = 0;
		// TODO: if WC is currently not tax enabled, but it was before (or vice versa), would this work correctly?
		if ( wc_tax_enabled() ) {
			$order_taxes               = $order->get_taxes();
			$line_items_shipping       = $order->get_items( 'shipping' );
			$total_shipping_tax_amount = 0;
			foreach ( $line_items_shipping as $item_id => $item ) {
				$tax_data = $item->get_taxes();
				if ( $tax_data ) {
					foreach ( $order_taxes as $tax_item ) {
						$tax_item_id    = $tax_item->get_rate_id();
						$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';
						$refunded       = $order->get_tax_refunded_for_item( $item_id, $tax_item_id, 'shipping' );
						if ( $refunded ) {
							$total_shipping_tax_amount += $tax_item_total - $refunded;
						} else {
							$total_shipping_tax_amount += $tax_item_total;
						}
					}
				}
			}
			$shipping_tax_amount = $total_shipping_tax_amount / $order_items;
		}

		// Tax amount.
		// TODO: check if this calculates tax correctly with refunds.
		$tax_amount = 0;
		if ( wc_tax_enabled() ) {
			$order_taxes = $order->get_taxes();
			$tax_data    = $order_item->get_taxes();
			foreach ( $order_taxes as $tax_item ) {
				$tax_item_id = $tax_item->get_rate_id();
				$tax_amount += isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : 0;
			}
		}

		$net_revenue = $order_item->get_subtotal( 'edit' );

		// Coupon calculation based on woocommerce code in includes/admin/meta-boxes/views/html-order-item.php.
		$coupon_amount = $order_item->get_subtotal( 'edit' ) - $order_item->get_total( 'edit' );

		$wpdb->replace(
			$wpdb->prefix . 'wc_order_product_lookup',
			array(
				'order_item_id'         => $order_item->get_id(),
				'order_id'              => $order->get_id(),
				'product_id'            => $order_item->get_product_id( 'edit' ),
				'variation_id'          => $order_item->get_variation_id( 'edit' ),
				'customer_id'           => ( 0 < $order->get_customer_id( 'edit' ) ) ? $order->get_customer_id( 'edit' ) : null,
				'product_qty'           => $order_item->get_quantity( 'edit' ),
				'product_net_revenue'   => $net_revenue,
				'date_created'          => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				'price'                 => $order_item->get_subtotal( 'edit' ) / $order_item->get_quantity( 'edit' ),
				'coupon_amount'         => $coupon_amount,
				'tax_amount'            => $tax_amount,
				'shipping_amount'       => $shipping_amount,
				'shipping_tax_amount'   => $shipping_tax_amount,
				'product_gross_revenue' => $net_revenue + $tax_amount + $shipping_amount + $shipping_tax_amount,
			),
			array(
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%d',
				'%f',
				'%s',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
				'%f',
			)
		);
	}
}
// TODO: maybe replace these with woocommerce_create_order, woocommerce_update_order, woocommerce_trash_order, woocommerce_delete_order, as clean_post_cache might be called in other circumstances and trigger too many updates?
add_action( 'save_post', 'wc_admin_order_product_lookup_entry', 10, 1 );
add_action( 'clean_post_cache', 'wc_admin_order_product_lookup_entry', 10, 1 );

/**
 * Make an entry in the wc_order_tax_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_order_tax_lookup_entry( $order_id ) {
	global $wpdb;
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	foreach ( $order->get_items( 'tax' ) as $tax_item ) {
		$wpdb->replace(
			$wpdb->prefix . 'wc_order_tax_lookup',
			array(
				'order_id'     => $order->get_id(),
				'date_created' => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
				'tax_rate_id'  => $tax_item->get_rate_id(),
				'shipping_tax' => $tax_item->get_shipping_tax_total(),
				'order_tax'    => $tax_item->get_tax_total(),
				'total_tax'    => $tax_item->get_tax_total() + $tax_item->get_shipping_tax_total(),
			),
			array(
				'%d',
				'%s',
				'%d',
				'%f',
				'%f',
				'%f',
			)
		);
	}
}
add_action( 'save_post', 'wc_order_tax_lookup_entry', 10, 1 );
add_action( 'clean_post_cache', 'wc_order_tax_lookup_entry', 10, 1 );

/**
 * Make an entry in the wc_order_coupon_lookup table for an order.
 *
 * @since 3.5.0
 * @param int $order_id Order ID.
 * @return void
 */
function wc_order_coupon_lookup_entry( $order_id ) {
	global $wpdb;

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}

	$coupon_items = $order->get_items( 'coupon' );
	foreach ( $coupon_items as $coupon_item ) {
		$wpdb->replace(
			$wpdb->prefix . 'wc_order_coupon_lookup',
			array(
				'order_id'        => $order_id,
				'coupon_id'       => wc_get_coupon_id_by_code( $coupon_item->get_code() ),
				'discount_amount' => $coupon_item->get_discount(),
				'date_created'    => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' )->getTimestamp() ),
			),
			array(
				'%d',
				'%d',
				'%f',
				'%s',
			)
		);
	}
}
add_action( 'save_post', 'wc_order_coupon_lookup_entry', 10, 1 );
add_action( 'clean_post_cache', 'wc_order_coupon_lookup_entry', 10, 1 );
