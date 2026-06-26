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
	 * Meters.
	 *
	 * @var string
	 */
	public const METER = 'm';

	/**
	 * Centimeters.
	 *
	 * @var string
	 */
	public const CENTIMETER = 'cm';

	/**
	 * Millimeters.
	 *
	 * @var string
	 */
	public const MILLIMETER = 'mm';

	/**
	 * Inches.
	 *
	 * @var string
	 */
	public const INCH = 'in';

	/**
	 * Yards.
	 *
	 * @var string
	 */
	public const YARD = 'yd';
}
