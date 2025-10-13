<?php
declare(strict_types=1);
namespace Automattic\WooCommerce\Internal\Agentic\Enums\Specs;

/**
 * Total types as defined in the Agentic Commerce Protocol.
 */
class TotalType {
	/**
	 * Base amount of all items before discounts.
	 */
	const ITEMS_BASE_AMOUNT = 'items_base_amount';

	/**
	 * Total discount on items.
	 */
	const ITEMS_DISCOUNT = 'items_discount';

	/**
	 * Subtotal after item discounts.
	 */
	const SUBTOTAL = 'subtotal';

	/**
	 * Additional discount applied to order.
	 */
	const DISCOUNT = 'discount';

	/**
	 * Fulfillment/shipping cost.
	 */
	const FULFILLMENT = 'fulfillment';

	/**
	 * Tax amount.
	 */
	const TAX = 'tax';

	/**
	 * Additional fee.
	 */
	const FEE = 'fee';

	/**
	 * Final total amount.
	 */
	const TOTAL = 'total';
}
