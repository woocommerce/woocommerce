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
	 * Returns all catalog visibility values.
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
