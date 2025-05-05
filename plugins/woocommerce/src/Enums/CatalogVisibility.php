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
	const VISIBLE = 'visible';

	/**
	 * Product is visible on the shop page only.
	 */
	const CATALOG = 'catalog';

	/**
	 * Product visible in the search results only.
	 */
	const SEARCH = 'search';

	/**
	 * Product is invisible on both shop and search results, but can still be accessed directly.
	 */
	const HIDDEN = 'hidden';
}
