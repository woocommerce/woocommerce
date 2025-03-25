<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the product tax statuses.
 */
class ProductTaxStatus {
	/**
	 * Tax status for products that are taxable.
	 *
	 * @var string
	 */
	const TAXABLE = 'taxable';

	/**
	 * Indicates that only the shipping cost should be taxed, not the product itself.
	 *
	 * @var string
	 */
	const SHIPPING = 'shipping';

	/**
	 * Tax status for products that are not taxable.
	 *
	 * @var string
	 */
	const NONE = 'none';
}
