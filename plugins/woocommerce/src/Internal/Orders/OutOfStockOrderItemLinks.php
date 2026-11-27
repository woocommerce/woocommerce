<?php
/**
 * OutOfStockOrderItemLinks class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\RegisterHooksInterface;
use WC_Order_Item_Product;
use WC_Product;

/**
 * Keeps product links in order details for purchased products that are hidden only because they are out of stock.
 *
 * The "Hide out of stock items from the catalog" setting is meant for the shop catalog, so it should not stop
 * customers from reaching products they already bought from My Account or the order confirmation page.
 *
 * @internal Just for internal use.
 *
 * @since 11.5.0
 */
class OutOfStockOrderItemLinks implements RegisterHooksInterface {

	/**
	 * Register hooks and filters.
	 *
	 * Runs early so extensions that set or clear the link on this filter still have the final say.
	 *
	 * @since 11.5.0
	 */
	public function register(): void {
		add_filter( 'woocommerce_order_item_permalink', array( $this, 'maybe_restore_permalink' ), 0, 2 );
	}

	/**
	 * Restore the product link when the product is hidden from the catalog only because it is out of stock.
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 *
	 * @param mixed $permalink Product permalink, empty when the product is not visible.
	 * @param mixed $item      Order item.
	 * @return mixed
	 */
	public function maybe_restore_permalink( $permalink, $item ) {
		if ( ! empty( $permalink ) || ! $item instanceof WC_Order_Item_Product || 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			return $permalink;
		}

		$product = $item->get_product();

		if ( ! $product instanceof WC_Product || $product->is_in_stock() ) {
			return $permalink;
		}

		// Re-check visibility with out of stock items shown, so every other visibility rule and filter still applies.
		add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', array( $this, 'show_out_of_stock_items' ) );
		try {
			$is_visible = $product->is_visible();
		} finally {
			remove_filter( 'pre_option_woocommerce_hide_out_of_stock_items', array( $this, 'show_out_of_stock_items' ) );
		}

		// Same call as the order details templates: variations read the item's attributes, other products ignore it.
		return $is_visible ? $product->get_permalink( $item ) : $permalink; // @phpstan-ignore arguments.count
	}

	/**
	 * Short-circuit the "Hide out of stock items" option to "no".
	 *
	 * @internal For exclusive usage within this class, backwards compatibility not guaranteed.
	 *
	 * @return string
	 */
	public function show_out_of_stock_items(): string {
		return 'no';
	}
}
