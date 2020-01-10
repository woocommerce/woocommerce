<?php
/**
 * Helper class to handle product stock reservation.
 *
 * @package WooCommerce/Blocks
 */

namespace Automattic\WooCommerce\Blocks\RestApi\StoreApi\Utilities;

defined( 'ABSPATH' ) || exit;

use \WP_Error;

/**
 * Stock Reservation class.
 */
class ReserveStock {
	/**
	 * Put a temporary hold on stock for an order if enough is available.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool|WP_Error
	 */
	public function reserve_stock_for_order( \WC_Order $order ) {
		$stock_to_reserve = [];
		$items            = array_filter(
			$order->get_items(),
			function( $item ) {
				return $item->is_type( 'line_item' ) && $item->get_product() instanceof \WC_Product;
			}
		);

		foreach ( $items as $item ) {
			$product = $item->get_product();

			if ( ! $product->is_in_stock() ) {
				return new WP_Error(
					'product_out_of_stock',
					sprintf(
						/* translators: %s: product name */
						__( '%s is out of stock and cannot be purchased.', 'woo-gutenberg-products-block' ),
						$product->get_name()
					),
					[ 'status' => 403 ]
				);
			}

			// If stock management is off, no need to reserve any stock here.
			if ( ! $product->managing_stock() || $product->backorders_allowed() ) {
				continue;
			}

			$product_id                      = $product->get_stock_managed_by_id();
			$stock_to_reserve[ $product_id ] = isset( $stock_to_reserve[ $product_id ] ) ? $stock_to_reserve[ $product_id ] : 0;
			$reserved_stock                  = $this->get_reserved_stock( $product, $order->get_id() );

			if ( ( $product->get_stock_quantity() - $reserved_stock - $stock_to_reserve[ $product_id ] ) < $item->get_quantity() ) {
				return new WP_Error(
					'product_not_enough_stock',
					sprintf(
						/* translators: %s: product name */
						__( 'Not enough units of %s are available in stock to fulfil this order.', 'woo-gutenberg-products-block' ),
						$product->get_name()
					),
					[ 'status' => 403 ]
				);
			}

			// Queue for later DB insertion.
			$stock_to_reserve[ $product_id ] += $item->get_quantity();
		}

		$this->reserve_stock( $stock_to_reserve, $order->get_id() );

		return true;
	}

	/**
	 * Reserve stock by inserting rows into the DB.
	 *
	 * @param array   $stock_to_reserve Array of Product ID => Qty pairs.
	 * @param integer $order_id Order ID for which to reserve stock.
	 */
	protected function reserve_stock( $stock_to_reserve, $order_id ) {
		global $wpdb;

		$stock_to_reserve = array_filter( $stock_to_reserve );

		if ( ! $stock_to_reserve ) {
			return;
		}

		$stock_to_reserve_rows = [];

		foreach ( $stock_to_reserve as $product_id => $stock_quantity ) {
			$stock_to_reserve_rows[] = '(' . esc_sql( $order_id ) . ',"' . esc_sql( $product_id ) . '","' . esc_sql( $stock_quantity ) . '")';
		}

		$values = implode( ',', $stock_to_reserve_rows );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "REPLACE INTO {$wpdb->wc_reserved_stock} ( order_id, product_id, stock_quantity ) VALUES {$values};" );
	}

	/**
	 * Query for any existing holds on stock for this item.
	 *
	 * - Can ignore reserved stock for a specific order.
	 * - Ignores stock for orders which are no longer drafts (assuming real stock reduction was performed).
	 * - Ignores stock reserved over 10 mins ago.
	 *
	 * @param \WC_Product $product Product to get reserved stock for.
	 * @param integer     $exclude_order_id Optional order to exclude from the results.
	 * @return integer Amount of stock already reserved.
	 */
	public function get_reserved_stock( \WC_Product $product, $exclude_order_id = 0 ) {
		global $wpdb;

		$reserved_stock = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT SUM( stock_table.`stock_quantity` ) FROM $wpdb->wc_reserved_stock stock_table
				LEFT JOIN $wpdb->posts posts ON stock_table.`order_id` = posts.ID
				WHERE stock_table.`product_id` = %d
				AND posts.post_status = 'wc-checkout-draft'
				AND stock_table.`order_id` != %d
				AND stock_table.`timestamp` > ( NOW() - INTERVAL 10 MINUTE )
				",
				$product->get_stock_managed_by_id(),
				$exclude_order_id
			)
		);

		// Deals with legacy stock reservation which the core Woo checkout performs.
		$hold_stock_minutes = (int) get_option( 'woocommerce_hold_stock_minutes', 0 );
		$reserved_stock    += ( $hold_stock_minutes > 0 ) ? wc_get_held_stock_quantity( $product ) : 0;

		return $reserved_stock;
	}
}
