<?php
/**
 * WooCommerce Stock Functions
 *
 * Functions used to manage product stock levels.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update a product's stock amount.
 *
 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
 *
 * @since  2.7.0 this supports set, increase and decrease.
 * @param  int|WC_Product $product
 * @param  int|null $stock_quantity
 * @param  string $operation set, increase and decrease.
 */
function wc_update_product_stock( $product, $stock_quantity = null, $operation = 'set' ) {
	global $wpdb;

	$product = wc_get_product( $product );

	if ( ! is_null( $stock_quantity ) && $product->managing_stock() ) {
		// Some products (variations) can have their stock managed by their parent. Get the correct ID to reduce here.
		$product_id_with_stock = $product->get_stock_managed_by_id();
		$product_with_stock    = $product->get_id() !== $product_id_with_stock ? wc_get_product( $product_id_with_stock ) : $product;

		add_post_meta( $product_id_with_stock, '_stock', 0, true );

		// Update stock in DB directly
		switch ( $operation ) {
			case 'increase' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
			case 'decrease' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
			default :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product_id_with_stock ) );
				break;
		}

		wp_cache_delete( $product_id_with_stock, 'post_meta' );
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );

		// Re-read product data after updating stock, then have stock status calculated and saved.
		$product_with_stock->read( $product_with_stock->get_id() );
		$product_with_stock->set_stock_status();
		$product_with_stock->save();

		do_action( $product_with_stock->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product_with_stock );

		return $product_with_stock->get_stock_quantity();
	}
	return $product->get_stock_quantity();
}

/**
 * Update a product's stock status.
 *
 * @param  int $product_id
 * @param  int $status
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
 * @since 2.7.0
 * @param int $order_id
 */
function wc_maybe_reduce_stock_levels( $order_id ) {
	if ( apply_filters( 'woocommerce_payment_complete_reduce_order_stock', ! get_post_meta( $order_id, '_order_stock_reduced', true ), $order_id ) ) {
		wc_reduce_stock_levels( $order_id );
		add_post_meta( $order_id, '_order_stock_reduced', '1', true );
	}
}
add_action( 'woocommerce_payment_complete', 'wc_maybe_reduce_stock_levels' );

/**
 * Reduce stock levels for items within an order.
 * @since 2.7.0
 * @param int $order_id
 */
function wc_reduce_stock_levels( $order_id ) {
	$order = wc_get_order( $order_id );

	if ( 'yes' === get_option( 'woocommerce_manage_stock' ) && $order && apply_filters( 'woocommerce_can_reduce_order_stock', true, $order ) && sizeof( $order->get_items() ) > 0 ) {
		foreach ( $order->get_items() as $item ) {
			if ( $item->is_type( 'line_item' ) && ( $product = $item->get_product() ) && $product->managing_stock() ) {
				$qty       = apply_filters( 'woocommerce_order_item_quantity', $item->get_quantity(), $order, $item );
				$item_name = $product->get_formatted_name();
				$new_stock = wc_update_product_stock( $product, $qty, 'subtract' );

				if ( ! is_wp_error( $new_stock ) ) {
					$order->add_order_note( sprintf( __( '%1$s stock reduced from %2$s to %3$s.', 'woocommerce' ), $item_name, $new_stock + $qty, $new_stock ) );

					if ( '' !== get_option( 'woocommerce_notify_no_stock_amount' ) && $new_stock <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
						do_action( 'woocommerce_no_stock', $product );
					} elseif ( '' !== get_option( 'woocommerce_notify_low_stock_amount' ) && $new_stock <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
						do_action( 'woocommerce_low_stock', $product );
					}

					if ( $new_stock < 0 ) {
						do_action( 'woocommerce_product_on_backorder', array( 'product' => $product, 'order_id' => $order_id, 'quantity' => $qty_ordered ) );
					}
				}
			}
		}

		do_action( 'woocommerce_reduce_order_stock', $order );
	}
}
