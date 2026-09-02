<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers for the order item meta keys WooCommerce manages internally.
 *
 * @since 11.0.0
 */
final class OrderItemMetaUtil {

	/**
	 * Get the order item meta keys hidden from the admin order screen.
	 *
	 * @return string[]
	 */
	public static function get_hidden_keys(): array {
		/**
		 * Filters the order item meta keys hidden from the admin order screen.
		 *
		 * @since 2.2.0
		 * @param string[] $hidden_keys Hidden order item meta keys.
		 */
		return apply_filters(
			'woocommerce_hidden_order_itemmeta',
			array(
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'method_id',
				'cost',
				'_reduced_stock',
				'_restock_refunded_items',
			)
		);
	}

	/**
	 * Get the meta keys that cannot be added or edited as custom meta on an order item.
	 *
	 * Combines the hidden keys with the item's own internal meta keys, which back core item data.
	 *
	 * @param WC_Order_Item $item Order item to check.
	 * @return string[]
	 */
	public static function get_reserved_keys( WC_Order_Item $item ): array {
		// @phpstan-ignore-next-line method.notFound Proxied via WC_Data_Store::__call() on the order item data store.
		$internal_meta_keys = (array) $item->get_data_store()->get_internal_meta_keys();

		return array_values( array_unique( array_merge( self::get_hidden_keys(), $internal_meta_keys ) ) );
	}
}
