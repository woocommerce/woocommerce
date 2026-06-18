<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the `woocommerce_weight_unit` option,
 * which determines the weight unit used throughout the store.
 *
 * @since 11.0.0
 */
final class WeightUnit {
	/**
	 * Kilogram.
	 *
	 * @var string
	 */
	public const KILOGRAM = 'kg';

	/**
	 * Gram.
	 *
	 * @var string
	 */
	public const GRAM = 'g';

	/**
	 * Pound.
	 *
	 * @var string
	 */
	public const POUND = 'lbs';

	/**
	 * Ounce.
	 *
	 * @var string
	 */
	public const OUNCE = 'oz';

	/**
	 * Returns all weight unit values defined in this class.
	 *
	 * @since 11.0.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::KILOGRAM,
			self::GRAM,
			self::POUND,
			self::OUNCE,
		);
	}
}
