<?php
/**
 * DateQueryUtil class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

/**
 * Helpers for building `date_query` arguments.
 *
 * @since 11.3.0
 */
class DateQueryUtil {

	/**
	 * Convert a REST date that names its own UTC offset to the UTC 'Y-m-d H:i:s' form that GMT date columns store.
	 *
	 * WP_Date_Query re-expresses offset dates in the site timezone even when the column is GMT, shifting them by the site offset.
	 * Dates without an offset and values that can't be parsed are returned unchanged.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $date The date, usually an RFC 3339 string from a REST request.
	 * @return mixed The equivalent UTC date string, or the original value.
	 */
	public static function to_gmt_date_query_value( $date ) {
		// Same offset check as rest_get_date_with_gmt(), so values without one stay byte-identical.
		if ( ! is_string( $date ) || ! preg_match( '#(Z|[+-]\d{2}(:\d{2})?)$#', $date ) ) {
			return $date;
		}

		$dates = rest_get_date_with_gmt( $date, true );

		return null === $dates ? $date : $dates[1];
	}
}
