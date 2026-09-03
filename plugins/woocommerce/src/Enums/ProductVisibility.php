<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the terms of the `product_visibility` taxonomy.
 *
 * Two members of that taxonomy are deliberately absent. The `outofstock` term is referenced
 * through {@see ProductStockStatus::OUT_OF_STOCK}, which is the value already used for it
 * across the plugin, and the per-rating `rated-1` to `rated-5` terms are built from a number
 * at runtime rather than being a fixed vocabulary.
 *
 * @since 11.2.0
 */
final class ProductVisibility {
	/**
	 * The product is hidden from the shop catalog.
	 *
	 * @var string
	 */
	public const EXCLUDE_FROM_CATALOG = 'exclude-from-catalog';

	/**
	 * The product is hidden from search results.
	 *
	 * @var string
	 */
	public const EXCLUDE_FROM_SEARCH = 'exclude-from-search';

	/**
	 * The product is marked as featured.
	 *
	 * @var string
	 */
	public const FEATURED = 'featured';
}
