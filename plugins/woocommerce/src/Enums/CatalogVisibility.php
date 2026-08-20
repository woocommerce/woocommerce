<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the catalog visibility values.
 */
final class CatalogVisibility {
	/**
	 * Product is visible on both shop and search results.
	 *
	 * @var string
	 */
	public const VISIBLE = 'visible';

	/**
	 * Product is visible on the shop page only.
	 */
	public const CATALOG = 'catalog';

	/**
	 * Product visible in the search results only.
	 */
	public const SEARCH = 'search';

	/**
	 * Product is invisible on both shop and search results, but can still be accessed directly.
	 */
	public const HIDDEN = 'hidden';

	/**
	 * Returns every catalog visibility value defined by this enum, as a flat list.
	 *
	 * These happen to be the same four values wc_get_product_visibility_options() returns today, so
	 * the two look interchangeable. They are not: that helper returns a value => label map, it is
	 * filterable, and it is what WC_Product::set_catalog_visibility() validates against -- so a site
	 * can legitimately accept a visibility this list does not contain.
	 *
	 * Use wc_get_product_visibility_options() for anything that validates or presents a choice.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::VISIBLE,
			self::CATALOG,
			self::SEARCH,
			self::HIDDEN,
		);
	}
}
