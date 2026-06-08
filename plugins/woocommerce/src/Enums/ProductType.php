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
	 * Returns all product type values.
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
