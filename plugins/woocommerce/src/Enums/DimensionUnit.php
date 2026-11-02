<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Enums;

/**
 * Enum class for the possible values of the 'woocommerce_dimension_unit' option.
 *
 * @since 11.0.0
 */
final class DimensionUnit {
	/**
	 * Meter.
	 *
	 * @var string
	 */
	public const METER = 'm';

	/**
	 * Centimeter.
	 *
	 * @var string
	 */
	public const CENTIMETER = 'cm';

	/**
	 * Millimeter.
	 *
	 * @var string
	 */
	public const MILLIMETER = 'mm';

	/**
	 * Inch.
	 *
	 * @var string
	 */
	public const INCH = 'in';

	/**
	 * Yard.
	 *
	 * @var string
	 */
	public const YARD = 'yd';

	/**
	 * Returns all dimension unit values defined in this class.
	 *
	 * @since 11.0.0
	 *
	 * @return string[]
	 */
	public static function get_all(): array {
		return array(
			self::METER,
			self::CENTIMETER,
			self::MILLIMETER,
			self::INCH,
			self::YARD,
		);
	}
}
