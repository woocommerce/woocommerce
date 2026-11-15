<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the 'woocommerce_default_catalog_orderby' option.
 *
 * @since 11.1.0
 */
final class CatalogSortOrder {
	/**
	 * Default sorting (custom ordering + name).
	 *
	 * @var string
	 */
	public const MENU_ORDER = 'menu_order';

	/**
	 * Sort by popularity (number of sales).
	 *
	 * @var string
	 */
	public const POPULARITY = 'popularity';

	/**
	 * Sort by average rating.
	 *
	 * @var string
	 */
	public const RATING = 'rating';

	/**
	 * Sort by most recent.
	 *
	 * @var string
	 */
	public const DATE = 'date';

	/**
	 * Sort by price ascending.
	 *
	 * @var string
	 */
	public const PRICE = 'price';

	/**
	 * Sort by price descending.
	 *
	 * @var string
	 */
	public const PRICE_DESC = 'price-desc';
}
