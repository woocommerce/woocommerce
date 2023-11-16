<?php

namespace Automattic\WooCommerce\Utilities;

use \DateTime;
use \DateTimeZone;
use \Exception;

/**
 * Class with date and time utilities.
 */
class TimeUtil {

	/**
	 * Instance of a DateTimeZone object representing UTC.
	 *
	 * @var DateTimeZone
	 */
	private static DateTimeZone $utc_date_time_zone;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		self::$utc_date_time_zone = new DateTimeZone( 'UTC' );
	}

	/**
	 * Get the instance of the DateTimeZone object representing UTC.
	 *
	 * @return DateTimeZone DateTimeZone object representing UTC.
	 */
	public static function get_utc_date_time_zone(): DateTimeZone {
		return self::$utc_date_time_zone;
	}

	/**
	 * Check if a string represents a valid date in a given format.
	 *
	 * @param string $date The date string to check.
	 * @param string $format The format to verify the date string against.
	 * @return bool True if $date represents a valid date/time according to $format, false otherwise.
	 */
	public static function is_valid_date( string $date, string $format = 'Y-m-d H:i:s' ): bool {
		$d = DateTime::createFromFormat( $format, $date );
		return $d && $d->format( $format ) === $date;
	}
}
