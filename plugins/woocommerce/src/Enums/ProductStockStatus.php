<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the product stock statuses.
 */
final class ProductStockStatus {
	/**
	 * The product is in stock.
	 *
	 * @var string
	 */
	public const IN_STOCK = 'instock';

	/**
	 * The product is out of stock.
	 *
	 * @var string
	 */
	public const OUT_OF_STOCK = 'outofstock';

	/**
	 * The product is on backorder.
	 *
	 * @var string
	 */
	public const ON_BACKORDER = 'onbackorder';

	/**
	 * The product is low in stock.
	 *
	 * @var string
	 */
	public const LOW_STOCK = 'lowstock';

	/**
	 * Returns all product stock status values.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::IN_STOCK,
			self::OUT_OF_STOCK,
			self::ON_BACKORDER,
			self::LOW_STOCK,
		);
	}
}
