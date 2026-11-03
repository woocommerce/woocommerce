<?php
/**
 * LineItemStock class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * Stock changes for order lines.
 *
 * @since 11.4.0
 */
final class LineItemStock {

	/**
	 * Give the stock that a deleted product line reduced back to its product.
	 * Call it only after the delete succeeds, so a failed delete leaves the stock as it is.
	 * It reads the reduced amount from the line in memory and does not save the line.
	 *
	 * @param \WC_Order_Item $item The deleted order line.
	 * @return array{from: int|float, to: int|float}|false The stock before and after the change, or false when the stock does not change.
	 */
	public static function restore_after_delete( \WC_Order_Item $item ) {
		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return false;
		}

		/**
		 * This filter is documented in includes/admin/wc-admin-functions.php.
		 *
		 * @since 3.7.1
		 */
		if ( apply_filters( 'woocommerce_prevent_adjust_line_item_product_stock', false, $item, 0 ) ) {
			return false;
		}

		$product = $item->get_product();
		$reduced = wc_stock_amount( $item->get_meta( '_reduced_stock', true ) );

		if ( ! $product instanceof \WC_Product || ! $product->managing_stock() || $reduced <= 0 ) {
			return false;
		}

		$new_stock = wc_update_product_stock( $product, $reduced, 'increase' );

		if ( ! is_numeric( $new_stock ) ) {
			return false;
		}

		return array(
			'from' => $new_stock - $reduced,
			'to'   => $new_stock,
		);
	}
}
