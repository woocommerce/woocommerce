<?php
declare( strict_types = 1 );

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
	 *
	 * @param CostOfGoodsSoldController $cogs_controller The instance of CostOfGoodsSoldController to use.
	 */
	final public function init( CostOfGoodsSoldController $cogs_controller ): void {
		$this->cogs_controller = $cogs_controller;
	}

	/**
	 * Get margin data for an entire order, including a per-line-item breakdown.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Abstract_Order $order The order to calculate margin for.
	 * @return array{net_revenue: float, cogs: float, gross_profit: float, margin: float, cogs_enabled: bool, items: array}
	 */
	public function get_margin_for_order( WC_Abstract_Order $order ): array {
		$cogs_enabled   = $this->cogs_controller->feature_is_enabled();
		$subtotal       = (float) $order->get_subtotal();
		$discount_total = (float) $order->get_discount_total();
		$net_revenue    = $subtotal - $discount_total;
		$cogs           = $cogs_enabled ? (float) $order->get_cogs_total_value() : 0.0;
		$gross_profit   = $net_revenue - $cogs;
		$margin         = $net_revenue > 0.0 ? ( $gross_profit / $net_revenue ) * 100.0 : 0.0;

		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$items[] = self::get_margin_for_order_item( $item, $cogs_enabled );
			}
		}

		$data = array(
			'net_revenue'  => round( $net_revenue, 4 ),
			'cogs'         => round( $cogs, 4 ),
			'gross_profit' => round( $gross_profit, 4 ),
			'margin'       => round( $margin, 2 ),
			'cogs_enabled' => $cogs_enabled,
			'items'        => $items,
		);

		/**
		 * Filters the margin data calculated for an order.
		 *
		 * @since 10.8.0
		 *
		 * @param array             $data  The calculated margin data.
		 * @param WC_Abstract_Order $order The order the data was calculated for.
		 */
		return (array) apply_filters( 'woocommerce_order_margin_data', $data, $order );
	}

	/**
	 * Get margin data for a single product line item.
	 *
	 * @since 10.8.0
	 *
	 * @param WC_Order_Item_Product $item         The line item to calculate margin for.
	 * @param bool                  $cogs_enabled Whether the COGS feature is active.
	 * @return array{item_id: int, product_id: int, name: string, quantity: float, revenue: float, cogs: float, gross_profit: float, margin: float}
	 */
	public static function get_margin_for_order_item( WC_Order_Item_Product $item, bool $cogs_enabled = true ): array {
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
