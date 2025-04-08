<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for all the product types.
 */
final class ProductType {
	/**
	 * Simple product type.
	 *
	 * @var string
	 */
	const SIMPLE = 'simple';

	/**
	 * Variable product type.
	 *
	 * @var string
	 */
	const VARIABLE = 'variable';

	/**
	 * Grouped product type.
	 *
	 * @var string
	 */
	const GROUPED = 'grouped';

	/**
	 * External/Affiliate product type.
	 *
	 * @var string
	 */
	const EXTERNAL = 'external';

	/**
	 * Variation product type.
	 *
	 * @var string
	 */
	const VARIATION = 'variation';
}
