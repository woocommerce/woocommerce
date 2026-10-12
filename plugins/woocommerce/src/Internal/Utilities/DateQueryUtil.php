<?php
/**
 * DateQueryUtil class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Utilities;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Helpers for building `date_query` arguments.
 *
 * @since 11.3.0
 */
class DateQueryUtil {

	/**
	 * Convert a date that names its own UTC offset or timezone to the UTC 'Y-m-d H:i:s' form that GMT date columns store.
	 *
	 * WP_Date_Query re-expresses such dates in the site timezone even when the column is GMT, shifting them by the site offset.
	 * Dates without an offset and values that can't be parsed are returned unchanged.
	 *
	 * @since 11.3.0
	 *
	 * @param mixed $date The date, usually an ISO 8601 string from a REST request.
	 * @return mixed The equivalent UTC date string, or the original value.
	 */
	public static function to_gmt_date_query_value( $date ) {
		if ( ! is_string( $date ) ) {
			return $date;
		}

		$parsed = date_parse( $date );
		if ( $parsed['error_count'] > 0 || empty( $parsed['zone_type'] ) ) {
			return $date;
		}

		try {
			$datetime = new DateTimeImmutable( $date );
		} catch ( Exception $e ) {
			return $date;
		}

		return $datetime->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}
}
