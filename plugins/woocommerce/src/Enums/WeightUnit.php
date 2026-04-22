<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the `woocommerce_weight_unit` option,
 * which determines the weight unit used throughout the store.
 *
 * @since 10.8.0
 */
final class WeightUnit {
	/**
	 * Kilograms.
	 *
	 * @var string
	 */
	public const KILOGRAMS = 'kg';

	/**
	 * Grams.
	 *
	 * @var string
	 */
	public const GRAMS = 'g';

	/**
	 * Pounds.
	 *
	 * @var string
	 */
	public const POUNDS = 'lbs';

	/**
	 * Ounces.
	 *
	 * @var string
	 */
	public const OUNCES = 'oz';
}
