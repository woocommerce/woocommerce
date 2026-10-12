<?php
/**
 * OrderItemPermalink class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use WC_Order_Item_Product;
use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps purchased products linked from order details when
 * they are hidden from the catalog only because they are out of stock.
 */
class OrderItemPermalink {

	/**
	 * Class initialization, to be executed when the class is resolved by the container.
	 *
	 * @internal
	 */
	final public function init(): void {
		add_filter( 'woocommerce_order_item_permalink', array( $this, 'handle_woocommerce_order_item_permalink' ), 5, 2 );
	}

	/**
	 * Handle the woocommerce_order_item_permalink filter.
	 *
	 * @internal
	 *
	 * @param string|mixed $permalink The order item permalink, empty when the product is not visible.
	 * @param mixed        $item      The order item.
	 * @return string|mixed
	 */
	public function handle_woocommerce_order_item_permalink( $permalink, $item ) {
		if ( '' !== $permalink || ! $item instanceof WC_Order_Item_Product || 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			return $permalink;
		}

		$product = $item->get_product();
		if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
			return $permalink;
		}

		$ignore_out_of_stock_setting = static fn() => 'no';
		add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', $ignore_out_of_stock_setting );
		try {
			$is_visible = $product->is_visible();
		} finally {
			remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', $ignore_out_of_stock_setting );
		}

		// @phpstan-ignore-next-line arguments.count (Variations take the item to add its attributes, as the order item templates do.)
		return $is_visible ? $product->get_permalink( $item ) : $permalink;
	}
}
