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
 * @param  int|WP_Error $new_stock_level or an error if stock could not be set.
 * @param  string $operation set, add, or subtract
 */
function wc_update_product_stock( $product, $stock_quantity = null, $operation = 'set' ) {
	if ( ! is_null( $stock_quantity ) ) {
		// Find product that needs a stock reduction.
		$product = wc_get_product( $product );

		if ( ! $product->managing_stock() && $product->get_parent_id() ) {
			$product = wc_get_product( $product->get_parent_id() );
		}

		if ( ! $product->managing_stock() ) {
			return new WP_Error( 'error', __( 'Product is not stock managed', 'woocommerce' ) );
		}

		// Ensure key exists
		add_post_meta( $product->get_id(), '_stock', 0, true );

		// Update stock in DB directly
		switch ( $operation ) {
			case 'add' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product->get_id() ) );
			break;
			case 'subtract' :
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product->get_id() ) );
			break;
			default :
				if ( $product->get_stock_quantity() !== $stock_quantity ) {
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $stock_quantity, $product->get_id() ) );
				}
			break;
		}

		wp_cache_delete( $product->get_id(), 'post_meta' );
		delete_transient( 'wc_low_stock_count' );
		delete_transient( 'wc_outofstock_count' );

		// Re-read product data.
		$product->read( $product->get_id() );

		// Check stock status.
		wc_check_product_stock_status( $product );

		// Actions
		do_action( $product->is_type( 'variation' ) ? 'woocommerce_variation_set_stock' : 'woocommerce_product_set_stock', $product );
	}

	return $product->get_stock_quantity();
}

/**
 * Check if the stock status needs changing.
 * @since 2.7.0
 * @param int|WC_Product $product
 */
function wc_check_product_stock_status( $product ) {
	$product = wc_get_product( $product );

	if ( $product->managing_stock() ) {
		if ( $product->backorders_allowed() && 'instock' !== $product->get_stock_status() ) {
			$product->set_stock_status( 'instock' );

		} elseif ( $product->get_stock_amount() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$product->set_stock_status( 'outofstock' );

		} elseif ( $product->get_stock_amount() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			$product->set_stock_status( 'instock' );
		}
	}

	$product->save();
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
		//$product->save(); @todo causes timeout in class-wc-post-data.php
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
