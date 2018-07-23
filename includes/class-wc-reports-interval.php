<?php
/**
 * Class for time interval handling for reports
 *
 * @package  WooCommerce/Classes
 * @version  3.5.0
 * @since    3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date & time interval handling class for Reporting API.
 */
class WC_Reports_Interval {

	/**
	 * Returns date format to be used as grouping clause in SQL.
	 *
	 * @param string $time_interval Time interval.
	 * @return mixed
	 */
	public static function mysql_datetime_format( $time_interval ) {
		$first_day_of_week = absint( get_option( 'start_of_week' ) );

		if ( 1 === $first_day_of_week ) {
			// Week begins on Monday.
			$week_format = '%v';
			$year_format = '%x';
		} elseif ( 0 === $first_day_of_week ) {
			// Week begins on Sunday.
			$week_format = '%V';
			$year_format = '%X';
		} else {
			// TODO: there are some countries where the week starts on Saturday (Egypt, etc), how to handle it in MySQL?
			// maybe adapt this: https://stackoverflow.com/questions/10656996/week-of-the-year-for-weeks-starting-with-saturday.
			$week_format = '%V';
			$year_format = '%X';
		}

		$mysql_date_format_mapping = array(
			'hour'    => "DATE_FORMAT(hour, '%Y-%m-%d %k')",
			'day'     => "DATE_FORMAT(hour, '%Y-%m-%d')",
			'week'    => "DATE_FORMAT(hour, '$year_format-$week_format')",
			'month'   => "DATE_FORMAT(hour, '%Y-%m')",
			'quarter' => "CONCAT(YEAR(hour), '-', QUARTER(hour))",
			'year'    => 'YEAR(hour)',

		);

		return $mysql_date_format_mapping[ $time_interval ];
	}

	/**
	 * Returns quarter for the DateTime.
	 *
	 * @param DateTime $datetime Date & time.
	 * @return int|null
	 */
	public static function quarter( $datetime ) {
		// TODO: is there a smarter way? Is floor((m - 1)/3) + 1 better?
		switch ( (int) $datetime->format( 'm' ) ) {
			case 1:
			case 2:
			case 3:
				return 1;
			case 4:
			case 5:
			case 6:
				return 2;
			case 7:
			case 8:
			case 9:
				return 3;
			case 10:
			case 11:
			case 12:
				return 4;

		}
		return null;
	}

	/**
	 * Returns time interval id for the DateTime.
	 *
	 * @param string   $time_interval Time interval type (week, day, etc).
	 * @param DateTime $datetime      Date & time.
	 * @return string
	 */
	public static function time_interval_id( $time_interval, $datetime ) {
		$php_time_format_for = array(
			'hour'    => 'Y-m-d H',
			'day'     => 'Y-m-d',
			'week'    => 'o-W',
			'month'   => 'Y-m',
			'quarter' => 'Y-' . WC_Reports_Interval::quarter( $datetime ),
			'year'    => 'Y',
		);

		// If the week does not begin on Monday.
		$first_day_of_week = absint( get_option( 'start_of_week' ) );
		if ( 0 === $first_day_of_week ) {
			$first_day_of_week = 7;
		}
		if ( 'week' === $time_interval && 1 !== $first_day_of_week ) {
			// Side note: strftime week number %U is odd, so something else is needed.
			$week_no     = $datetime->format( 'W' );
			$year_no     = $datetime->format( 'Y' ); // maybe 'o' need to be used?
			$day_of_week = $datetime->format( 'N' );
			if ( $day_of_week >= $first_day_of_week ) {
				$week_no++;
			}
			// TODO: this is not right, just an interim solution.
			if ( $week_no >= 52 ) {
				$week_no = 1;
			}
			return "$year_no-$week_no";
		}

		return $datetime->format( $php_time_format_for[ $time_interval ] );
	}

	/**
	 * Returns DateTime object representing the next hour start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_hour_start( $datetime ) {
		// Ignoring leap seconds.
		$timestamp          = (int) $datetime->format( 'U' );
		$next_day_timestamp = ( floor( $timestamp / HOUR_IN_SECONDS ) + 1 ) * HOUR_IN_SECONDS;
		$next_day           = new DateTime();
		$next_day->setTimestamp( $next_day_timestamp );
		return $next_day;
	}

	/**
	 * Returns DateTime object representing the next day start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_day_start( $datetime ) {
		// Ignoring leap seconds.
		$timestamp          = (int) $datetime->format( 'U' );
		$next_day_timestamp = ( floor( $timestamp / DAY_IN_SECONDS ) + 1 ) * DAY_IN_SECONDS;
		$next_day           = new DateTime();
		$next_day->setTimestamp( $next_day_timestamp );
		return $next_day;
	}

	/**
	 * Returns DateTime object representing the next week start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_week_start( $datetime ) {
		$first_day_of_week = absint( get_option( 'start_of_week' ) );
		if ( $datetime->format( 'w' ) === $first_day_of_week ) {
			return $datetime->modify( '+1 week' );
		} else {
			do {
				$datetime = WC_Reports_Interval::next_day_start( $datetime );
			} while ( $first_day_of_week !== (int) $datetime->format( 'w' ) );
		}
		return $datetime;
	}

	/**
	 * Returns DateTime object representing the next month start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_month_start( $datetime ) {
		$year  = $datetime->format( 'Y' );
		$month = (int) $datetime->format( 'm' ) + 1;
		$day   = '01';
		return new DateTime( "$year-$month-$day 00:00:00" );
	}

	/**
	 * Returns DateTime object representing the next quarter start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_quarter_start( $datetime ) {
		$year  = $datetime->format( 'Y' );
		$month = (int) $datetime->format( 'm' ) + 3;
		$day   = '01';
		return new DateTime( "$year-$month-$day 00:00:00" );
	}

	/**
	 * Return DateTime object representing the next year start.
	 *
	 * @param DateTime $datetime Date and time.
	 * @return DateTime
	 */
	public static function next_year_start( $datetime ) {
		$year  = (int) $datetime->format( 'Y' ) + 1;
		$month = '01';
		$day   = '01';
		return new DateTime( "$year-$month-$day 00:00:00" );
	}

	/**
	 * Returns beginning of next time interval for provided DateTime.
	 *
	 * E.g. for current DateTime, beginning of next day, week, quarter, etc.
	 *
	 * @param DateTime $datetime      Date and time.
	 * @param string   $time_interval Time interval, e.g. week, day, hour.
	 * @return DateTime
	 */
	public static function iterate( $datetime, $time_interval ) {
		return call_user_func( array( __CLASS__, "next_{$time_interval}_start" ), $datetime );
	}

}
