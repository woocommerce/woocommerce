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
}
