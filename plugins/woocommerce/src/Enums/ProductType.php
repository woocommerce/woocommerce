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
	public const SIMPLE = 'simple';

	/**
	 * Variable product type.
	 *
	 * @var string
	 */
	public const VARIABLE = 'variable';

	/**
	 * Grouped product type.
	 *
	 * @var string
	 */
	public const GROUPED = 'grouped';

	/**
	 * External/Affiliate product type.
	 *
	 * @var string
	 */
	public const EXTERNAL = 'external';

	/**
	 * Variation product type.
	 *
	 * @var string
	 */
	public const VARIATION = 'variation';

	/**
	 * Returns every product type value defined by this enum.
	 *
	 * This is the complete enum, not the set of types a product can be created as. It includes
	 * self::VARIATION, which is a child post type rather than a selectable product type.
	 *
	 * For the selectable types, use wc_get_product_types(). That helper is filterable, so unlike
	 * this method it also reflects types an extension has registered.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::SIMPLE,
			self::VARIABLE,
			self::GROUPED,
			self::EXTERNAL,
			self::VARIATION,
		);
	}
}
