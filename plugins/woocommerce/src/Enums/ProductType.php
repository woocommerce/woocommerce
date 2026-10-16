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
	 * Returns every product type value defined by this enum, as a flat list.
	 *
	 * It includes self::VARIATION. Variations are real, creatable products, and
	 * WC_Product_Variation::get_type() returns this value, but each belongs to a parent rather than
	 * standing on its own.
	 *
	 * For the top-level types a merchant can pick, use wc_get_product_types(). It returns a
	 * value => label map rather than a list, and being filterable it also carries types an
	 * extension registered.
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
