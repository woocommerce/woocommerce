<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Orders;

use Automattic\WooCommerce\Internal\CostOfGoodsSold\CostOfGoodsSoldController;
use WC_Abstract_Order;
use WC_Order_Item_Product;

/**
 * Calculates gross margin and profitability data for orders.
 *
 * Margin is computed from the order's Cost of Goods Sold (COGS) value against
 * the net revenue (subtotal minus discounts). Requires the Cost of Goods Sold
 * feature to be enabled; returns zeroed data when it is not.
 *
 * @since 10.8.0
 */
class OrderMarginCalculator {

	/**
	 * The instance of CostOfGoodsSoldController to use.
	 *
	 * @var CostOfGoodsSoldController
	 */
	private CostOfGoodsSoldController $cogs_controller;

	/**
	 * Initialize the instance.
	 *
	 * @internal
	 * @param CostOfGoodsSoldController $cogs_controller The instance of CostOfGoodsSoldController to use.
	 */
	final public function init( CostOfGoodsSoldController $cogs_controller ): void {
		$this->cogs_controller = $cogs_controller;
	}

	/**
	 * Get margin data for a single product line item.
	 *
	 * Returns an associative array with:
	 * - `item_id`      (int)    The order item ID.
	 * - `product_id`   (int)    The product ID.
	 * - `name`         (string) The line item name.
	 * - `quantity`     (float)  The quantity ordered.
	 * - `revenue`      (float)  Line total (post-discount, pre-tax).
	 * - `cogs`         (float)  COGS value for this line item.
	 * - `gross_profit` (float)  revenue minus cogs.
	 * - `margin`       (float)  gross_profit / revenue * 100, or 0 when revenue is zero.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Order_Item_Product $item         The line item to calculate margin for.
	 * @param bool                  $cogs_enabled Whether the COGS feature is active.
	 * @return array{item_id: int, product_id: int, name: string, quantity: float, revenue: float, cogs: float, gross_profit: float, margin: float}
	 */
	public function get_margin_for_order_item( WC_Order_Item_Product $item, bool $cogs_enabled = true ): array {
		$revenue      = (float) $item->get_total();
		$cogs         = $cogs_enabled ? (float) $item->get_cogs_value() : 0.0;
		$gross_profit = $revenue - $cogs;
		$margin       = $revenue > 0.0 ? ( $gross_profit / $revenue ) * 100.0 : 0.0;

		return array(
			'item_id'      => (int) $item->get_id(),
			'product_id'   => (int) $item->get_product_id(),
			'name'         => $item->get_name(),
			'quantity'     => (float) $item->get_quantity(),
			'revenue'      => round( $revenue, 4 ),
			'cogs'         => round( $cogs, 4 ),
			'gross_profit' => round( $gross_profit, 4 ),
			'margin'       => round( $margin, 2 ),
		);
	}
}
