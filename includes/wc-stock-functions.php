<?php
/**
 * WooCommerce Stock Functions
 *
 * Functions used to manage product stock levels.
 *
 * @package WooCommerce/Functions
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Update a product's stock amount.
 *
 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
 *
 * @since  3.0.0 this supports set, increase and decrease.
 *
 * @param  int|WC_Product $product        Product ID or product instance.
 * @param  int|null       $stock_quantity Stock quantity.
 * @param  string         $operation      Type of opertion, allows 'set', 'increase' and 'decrease'.
 *
 * @return bool|int|null
 */
function wc_update_product_stock( $product, $stock_quantity = null, $operation = 'set' ) {
	$product = wc_get_product( $product );

	if ( ! $product ) {
		return false;
	}

	if ( ! is_null( $stock_quantity ) && $product->managing_stock() ) {
		// Some products (variations) can have their stock managed by their parent. Get the correct ID to reduce here.
		$product_id_with_stock = $product->get_stock_managed_by_id();
		$data_store            = WC_Data_Store::load( 'product' );
		$data_store->update_product_stock( $product_id_with_stock, $stock_quantity, $operation );
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );
		delete_transient( 'wc_product_children_' . ( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() ) );
		wp_cache_delete( 'product-' . $product_id_with_stock, 'products' );

		// Re-read product data after updating stock, then have stock status calculated and saved.
		$product_with_stock = wc_get_product( $product_id_with_stock );
		$product_with_stock->set_stock_status();
		$product_with_stock->set_date_modified( current_time( 'timestamp', true ) );
		$product_with_stock->save();

		if ( $product_with_stock->is_type( 'variation' ) ) {
			do_action( 'woocommerce_variation_set_stock', $product_with_stock );
		} else {
			do_action( 'woocommerce_product_set_stock', $product_with_stock );
		}

		return $product_with_stock->get_stock_quantity();
	}
	return $product->get_stock_quantity();
}

/**
 * Update a product's stock status.
 *
 * @param  int $product_id Product ID.
 * @param  int $status     Status.
 */
function wc_update_product_stock_status( $product_id, $status ) {
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$product->set_stock_status( $status );
		$product->save();
	}
}

/**
 * When a payment is complete, we can reduce stock levels for items within an order.
 *
 * @since 3.0.0
 * @param int $order_id Order ID.
 */
function wc_maybe_reduce_stock_levels( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', $order && ! $order->get_data_store()->get_stock_reduced( $order_id ), $order_id ) ) {
		wc_reduce_stock_levels( $order );
	}
}
add_action( 'woocommerce_payment_complete', 'wc_maybe_reduce_stock_levels' );

/**
 * Reduce stock levels for items within an order.
 *
 * @since 3.0.0
 * @param int|WC_Order $order_id Order ID or order instance.
 */
function wc_reduce_stock_levels( $order_id ) {
	if ( is_a( $order_id, 'WC_Order' ) ) {
		$order    = $order_id;
		$order_id = $order->get_id();
	} else {
		$order = wc_get_order( $order_id );
	}
	if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && $order && apply_filters( 'woocommerce_can_reduce_order_stock', true, $order ) && count( $order->get_items() ) > 0 ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product = $item->get_product();

			if ( $product && $product->managing_stock() ) {
				$qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
				$item_name = $product->get_formatted_name();
				$new_stock = wc_update_product_stock( $product, $qty, 'decrease' );

				if ( ! is_wp_error( $new_stock ) ) {
					/* translators: 1: item name 2: old stock quantity 3: new stock quantity */
					$order->add_order_note( sprintf( __( '%1$s stock reduced from %2$s to %3$s.', 'woocommerce' ), $item_name, $new_stock + $qty, $new_stock ) );

					// Get the latest product data.
					$product = wc_get_product( $product->get_id() );

					if ( '' !== get_option( 'woocommerce_notify_no_stock_amount' ) && $new_stock <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
						do_action( 'woocommerce_no_stock', $product );
					} elseif ( '' !== get_option( 'woocommerce_notify_low_stock_amount' ) && $new_stock <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
						do_action( 'woocommerce_low_stock', $product );
					}

					if ( $new_stock < 0 ) {
						do_action(
							'woocommerce_product_on_backorder', array(
								'product'  => $product,
								'order_id' => $order_id,
								'quantity' => $qty,
							)
						);
					}
				}
			}
		}

		// Ensure stock is marked as "reduced" in case payment complete or other stock actions are called.
		$order->get_data_store()->set_stock_reduced( $order_id, true );

		do_action( 'woocommerce_reduce_order_stock', $order );
	}
}
