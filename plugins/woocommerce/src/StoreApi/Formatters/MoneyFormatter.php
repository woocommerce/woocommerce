<?php
namespace Automattic\WooCommerce\StoreApi\Formatters;

/**
 * Money Formatter.
 *
 * Formats monetary values using store settings.
 */
class MoneyFormatter implements FormatterInterface {
	/**
	 * Format a given price value and return the result as a string without decimals.
	 *
	 * @param int|float|string $value Value to format. Int is allowed, as it may also represent a valid price.
	 * @param array            $options Options that influence the formatting.
	 * @return string
	 */
	public function format( $value, array $options = [] ) {

		if ( ! is_int( $value ) && ! is_string( $value ) && ! is_float( $value ) ) {
			wc_doing_it_wrong(
				__FUNCTION__,
				'Function expects a $value arg of type INT, STRING or FLOAT.',
				'9.2'
			);

			return '';
		}

		$options = wp_parse_args(
			$options,
			[
				'decimals'      => wc_get_price_decimals(),
				'rounding_mode' => PHP_ROUND_HALF_UP,
			]
		);

		// Ensure rounding mode is valid.
		$rounding_modes           = [ PHP_ROUND_HALF_UP, PHP_ROUND_HALF_DOWN, PHP_ROUND_HALF_EVEN, PHP_ROUND_HALF_ODD ];
		$options['rounding_mode'] = absint( $options['rounding_mode'] );
		if ( ! in_array( $options['rounding_mode'], $rounding_modes, true ) ) {
			$options['rounding_mode'] = PHP_ROUND_HALF_UP;
		}

		$value = floatval( $value );

		// Remove the price decimal points for rounding purposes. Round to WooCommerce's rounding precision first, so float
		// noise doesn't decide the result: 4.975 is stored as 4.97499999… and would otherwise round to 497, not 498.
		$decimals = absint( $options['decimals'] );
		$value    = round( $value * pow( 10, $decimals ), max( 0, wc_get_rounding_precision() - $decimals ) );
		$value    = round( $value, 0, $options['rounding_mode'] );

		// This ensures returning the value as a string without decimal points ready for price parsing.
		return wc_format_decimal( $value, 0, true );
	}
}
