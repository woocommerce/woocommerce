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
	 * The list is wider than the set of statuses stored against products: self::LOW_STOCK is
	 * derived from stock quantity rather than persisted, so a query filtering the stock_status
	 * column can never match it. It remains a legitimate choice elsewhere -- the Analytics stock
	 * report offers it, and adds it back explicitly for that reason.
	 *
	 * For the persisted statuses, and the options a stock_status filter should offer, use
	 * wc_get_product_stock_status_options(). It returns a value => label map rather than a list,
	 * and being filterable it also reflects statuses an extension has added.
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
