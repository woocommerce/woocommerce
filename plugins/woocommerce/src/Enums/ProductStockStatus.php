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
	 * Returns every stock status value defined by this enum, as a flat list.
	 *
	 * It is wider than the set of statuses products store. self::LOW_STOCK is a reporting
	 * aggregate: the Analytics stock report counts in-stock products whose quantity has fallen to
	 * their low-stock threshold, so a low-stock product stores self::IN_STOCK. That report adds
	 * self::LOW_STOCK to its own enum for exactly that reason.
	 *
	 * WC_Product::set_stock_status() coerces anything outside wc_get_product_stock_status_options()
	 * to self::IN_STOCK, so use that helper for values you intend to store or filter on. It returns
	 * a value => label map rather than a list, and being filterable it also carries statuses an
	 * extension added.
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
