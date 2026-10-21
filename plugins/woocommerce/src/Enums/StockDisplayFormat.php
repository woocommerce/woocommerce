<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the 'woocommerce_stock_format' option.
 *
 * @since 11.1.0
 */
final class StockDisplayFormat {
	/**
	 * Always show the quantity remaining in stock (e.g. "12 in stock").
	 *
	 * @var string
	 */
	public const ALWAYS_SHOW = '';

	/**
	 * Only show the quantity remaining in stock when it is low (e.g. "Only 2 left in stock").
	 *
	 * @var string
	 */
	public const LOW_AMOUNT = 'low_amount';

	/**
	 * Never show the quantity remaining in stock.
	 *
	 * @var string
	 */
	public const NEVER = 'no_amount';
}
