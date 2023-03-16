<?php
/**
 * Reports Interval Test
 *
 * @package WooCommerce\Admin\Tests\API
 * @since x.x.0
 */

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;

/**
 * Class WC_Admin_Tests_Reports_Interval_Stats
 */
class WC_Admin_Tests_Reports_Interval_Stats extends WC_Unit_Test_Case {

	/**
	 * Local timezone used throughout the tests.
	 *
	 * @var DateTimeZone
	 */
	protected static $local_tz;

	/**
	 * Set current local timezone.
	 */
	public static function setUpBeforeClass(): void {
		self::$local_tz = new DateTimeZone( wc_timezone_string() );
	}

	/**
	 * Test quarter function.
	 */
	public function test_quarter() {
		$datetime = new DateTime( '2017-12-31T00:00:00', self::$local_tz );
		$this->assertEquals( 4, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-01-01T00:00:00', self::$local_tz );
		$this->assertEquals( 1, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-03-31T23:59:59', self::$local_tz );
		$this->assertEquals( 1, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-04-01T00:00:00', self::$local_tz );
		$this->assertEquals( 2, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-06-30T23:59:59', self::$local_tz );
		$this->assertEquals( 2, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-07-01T00:00:00', self::$local_tz );
		$this->assertEquals( 3, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-09-30T23:59:59', self::$local_tz );
		$this->assertEquals( 3, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-10-01T00:00:00', self::$local_tz );
		$this->assertEquals( 4, TimeInterval::quarter( $datetime ) );

		$datetime = new DateTime( '2018-12-31T23:59:59', self::$local_tz );
		$this->assertEquals( 4, TimeInterval::quarter( $datetime ) );
	}

	/**
	 * Test simple week number function.
	 */
	public function test_simple_week_number() {
		$expected_week_no = array(
			'2010-12-24' => array(
				1 => 52,
				2 => 52,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 52,
				7 => 52,
			),
			'2010-12-25' => array(
				1 => 52,
				2 => 52,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 53,
				7 => 52,
			),
			'2010-12-26' => array(
				1 => 52,
				2 => 52,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 53,
				7 => 53,
			),
			'2010-12-27' => array(
				1 => 53,
				2 => 52,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 53,
				7 => 53,
			),
			'2010-12-28' => array(
				1 => 53,
				2 => 53,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 53,
				7 => 53,
			),
			'2010-12-29' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 52,
				5 => 52,
				6 => 53,
				7 => 53,
			),
			'2010-12-30' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 53,
				5 => 52,
				6 => 53,
				7 => 53,
			),
			'2010-12-31' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 53,
				5 => 53,
				6 => 53,
				7 => 53,
			),
			'2011-01-01' => array(
				1 => 1,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2011-01-02' => array(
				1 => 1,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 2,
			),
			'2011-01-03' => array(
				1 => 2,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 2,
			),
			'2011-01-04' => array(
				1 => 2,
				2 => 2,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 2,
			),
			'2011-01-05' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 2,
			),
			'2011-01-06' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 1,
				6 => 1,
				7 => 2,
			),
			'2011-01-07' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 1,
				7 => 2,
			),
			'2011-01-08' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 2,
				7 => 2,
			),
			'2011-01-09' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 2,
				7 => 3,
			),
			'2011-01-10' => array(
				1 => 3,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 2,
				7 => 3,
			),
			'2011-12-26' => array(
				1 => 53,
				2 => 52,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 52,
				7 => 53,
			),
			'2011-12-27' => array(
				1 => 53,
				2 => 53,
				3 => 52,
				4 => 52,
				5 => 52,
				6 => 52,
				7 => 53,
			),
			'2011-12-28' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 52,
				5 => 52,
				6 => 52,
				7 => 53,
			),
			'2011-12-29' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 53,
				5 => 52,
				6 => 52,
				7 => 53,
			),
			'2011-12-30' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 53,
				5 => 53,
				6 => 52,
				7 => 53,
			),
			'2011-12-31' => array(
				1 => 53,
				2 => 53,
				3 => 53,
				4 => 53,
				5 => 53,
				6 => 53,
				7 => 53,
			),
			'2012-01-01' => array(
				1 => 1,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2012-01-02' => array(
				1 => 2,
				2 => 1,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2012-01-03' => array(
				1 => 2,
				2 => 2,
				3 => 1,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2012-01-04' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 1,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2012-01-05' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 1,
				6 => 1,
				7 => 1,
			),
			'2012-01-06' => array(
				1 => 2,
				2 => 2,
				3 => 2,
				4 => 2,
				5 => 2,
				6 => 1,
				7 => 1,
			),
		);

		foreach ( $expected_week_no as $date => $week_numbers ) {
			for ( $first_day_of_week = 1; $first_day_of_week <= 7; $first_day_of_week++ ) {
				$datetime = new DateTime( $date, self::$local_tz );
				$this->assertEquals( $expected_week_no[ $date ][ $first_day_of_week ], TimeInterval::simple_week_number( $datetime, $first_day_of_week ), "First day of week: $first_day_of_week; Date: $date" );
			}
		}

	}

	/**
	 * Testing ISO week number function.
	 */
	public function test_ISO_week_no() {
		$expected_week_no = array(
			'2010-12-24' => 51,
			'2010-12-25' => 51,
			'2010-12-26' => 51,
			'2010-12-27' => 52,
			'2010-12-28' => 52,
			'2010-12-29' => 52,
			'2010-12-30' => 52,
			'2010-12-31' => 52,
			'2011-01-01' => 52,
			'2011-01-02' => 52,
			'2011-01-03' => 1,
			'2011-01-04' => 1,
			'2011-01-05' => 1,
			'2011-01-06' => 1,
			'2011-01-07' => 1,
			'2011-01-08' => 1,
			'2011-01-09' => 1,
			'2011-01-10' => 2,
			'2011-12-26' => 52,
			'2011-12-27' => 52,
			'2011-12-28' => 52,
			'2011-12-29' => 52,
			'2011-12-30' => 52,
			'2011-12-31' => 52,
			'2012-01-01' => 52,
			'2012-01-02' => 1,
			'2012-01-03' => 1,
			'2012-01-04' => 1,
			'2012-01-05' => 1,
			'2012-01-06' => 1,
		);
		foreach ( $expected_week_no as $date => $week_numbers ) {
			$datetime = new DateTime( $date, self::$local_tz );
			$this->assertEquals( $expected_week_no[ $date ], TimeInterval::week_number( $datetime, 1 ), "ISO week number for date: $date" );
		}
	}

	/**
	 * Test function counting intervals between two datetimes.
	 */
	public function test_intervals_between() {
		// Please note that all intervals are inclusive on both sides.
		$test_settings = array(
			// 0 interval length, should just return 1.
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2017-12-24T11:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 1,
					'day'     => 1,
					'week'    => 1,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// <1 hour interval length -> should return 1 for all
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2017-12-24T11:40:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 1,
					'day'     => 1,
					'week'    => 1,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 1.66 hour interval length -> 2 hours, 1 for the rest
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2017-12-24T12:40:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 2,
					'day'     => 1,
					'week'    => 1,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 8.0186111 hours interval length -> 10 hours, 2 days, 1 for the rest
			array(
				'start'      => '2019-01-16T16:59:00',
				'end'        => '2019-01-17T01:00:07',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 10,
					'day'     => 2,
					'week'    => 1,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 23:59:59 h:m:s interval -> 24 hours, 2 days, 1 for the rest
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2017-12-25T10:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 24,
					'day'     => 2,
					'week'    => 2,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 24 hour inclusive interval -> 25 hours, 2 days, 1 for the rest
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2017-12-25T11:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 25,
					'day'     => 2,
					'week'    => 2,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 1 month interval spanning 1 month -> 720 hours, 30 days, 5 iso weeks, 1 months
			array(
				'start'      => '2017-11-01T00:00:00',
				'end'        => '2017-11-30T23:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 720,
					'day'     => 30,
					'week'    => 5,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 1 month interval spanning 2 months, but 1 quarter and 1 year -> 721 hours, 31 days, 5 iso weeks, 2 months
			array(
				'start'      => '2017-11-24T11:00:00',
				'end'        => '2017-12-24T11:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 30 * 24 + 1, // 30 full days + 1 second from next hour
					'day'     => 31,
					'week'    => 5,
					'month'   => 2,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 1 month + 14 hour interval spanning 2 months in 1 quarter -> 735 hours, 32 days, 6 iso weeks, 2 months
			array(
				'start'      => '2017-11-24T11:00:00',
				'end'        => '2017-12-25T01:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 30 * 24 + 13 + 2, // 30 full days + 13 full hours on Nov 24 + 2 hours on Dec 25
					'day'     => 32,
					'week'    => 6,
					'month'   => 2,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 1 month interval spanning 2 months and 2 quarters, 1 year -> 720 hours, 31 days, 6 iso weeks, 2 months, 2 q, 1 y
			array(
				'start'      => '2017-09-24T11:00:00',
				'end'        => '2017-10-24T10:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 30 * 24,
					'day'     => 31, // Sept has 30 days, but need to include interval for half of Sept 24 and interval for half of Oct 24.
					'week'    => 6,
					'month'   => 2,
					'quarter' => 2,
					'year'    => 1,
				),
			),
			// 1 month interval spanning 2 months and 2 quarters, 2 years -> 744 hours, 32 days, 6 iso weeks, 2 months, 2 quarters, 2 years
			array(
				'start'      => '2017-12-24T11:00:00',
				'end'        => '2018-01-24T10:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 31 * 24,
					'day'     => 32, // Dec has 31 days, plus 1 interval for half day at the end.
					'week'    => 6,
					'month'   => 2,
					'quarter' => 2,
					'year'    => 2,
				),
			),
			// 3 months interval spanning 1 quarter, 1 year -> 2208 hours, 92 days, 14 iso weeks, 3 months, 1 quarters, 1 years
			array(
				'start'      => '2017-10-01T00:00:00',
				'end'        => '2017-12-31T23:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 92 * 24, // 92 days
					'day'     => 92,
					'week'    => 14,
					'month'   => 3,
					'quarter' => 1,
					'year'    => 1,
				),
			),
			// 3 months + 1 day interval spanning 2 quarters, 1 year -> 2208 hours, 92 days, 14 iso weeks, 3 months, 2 quarters, 1 years
			array(
				'start'      => '2017-09-30T00:00:00',
				'end'        => '2017-12-30T23:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 92 * 24, // 92 days
					'day'     => 92,
					'week'    => 14,
					'month'   => 4,
					'quarter' => 2,
					'year'    => 1,
				),
			),
			// 3 months + 1 day interval spanning 2 quarters, 2 years -> 2232 hours, 93 days, 14 iso weeks, 3 months, 2 quarters, 2 years
			array(
				'start'      => '2017-10-31T00:00:00',
				'end'        => '2018-01-31T23:59:59',
				'week_start' => 1,
				'intervals'  => array(
					'hour'    => 93 * 24, // 93 days
					'day'     => 93,      // Jan 31d + Dec 31d + Nov 30d + Oct 1d = 93d.
					'week'    => 14,
					'month'   => 4,
					'quarter' => 2,
					'year'    => 2,
				),
			),
			// 9 months + 1 day interval spanning 2 quarters, 2 years.
			array(
				'start'      => '2017-04-01T00:00:00',
				'end'        => '2018-01-01T00:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'month'   => 9 + 1,
					'quarter' => 3 + 1,
					'year'    => 2,
				),
			),
			// 9 months + 1 day interval spanning 2 quarters, 2 years.
			array(
				'start'      => '2015-04-01T00:00:00',
				'end'        => '2018-01-01T00:00:00',
				'week_start' => 1,
				'intervals'  => array(
					'day'     => 1007,            // This includes leap year in 2016.
					'month'   => 9 + 12 + 12 + 1, // Rest of 2015 + 2016 + 2017 + 1 second in 2018.
					'quarter' => 3 + 4 + 4 + 1,   // Rest of 2015 + 2016 + 2017 + 1 second in 2018.
					'year'    => 4,
				),
			),
			// Test change of DST times.
			array(
				'start'      => '2021-10-01T00:00:00',
				'end'        => '2021-10-31T23:59:59',
				'week_start' => 1,
				'timezone'   => 'EST',
				'intervals'  => array(
					'hour'    => 31 * 24,
					'day'     => 31,
					'week'    => 5,
					'month'   => 1,
					'quarter' => 1,
					'year'    => 1,
				),
			),
		);

		foreach ( $test_settings as $setting ) {
			update_option( 'start_of_week', $setting['week_start'] );
			foreach ( $setting['intervals'] as $interval => $exp_value ) {
				$timezone       = isset( $setting['timezone'] ) ? new DateTimeZone( $setting['timezone'] ) : self::$local_tz;
				$start_datetime = new DateTime( $setting['start'], $timezone );
				$end_datetime   = new DateTime( $setting['end'], $timezone );
				$this->assertEquals( $exp_value, TimeInterval::intervals_between( $start_datetime, $end_datetime, $interval ), "First Day of Week: {$setting['week_start']}; Start: {$setting['start']}; End: {$setting['end']}; Interval: {$interval}" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next hour.
	 */
	public function test_next_hour_start() {
		$settings = array(
			'2017-12-30T00:00:00' => array(
				0 => '2017-12-30T01:00:00',
				1 => '2017-12-29T23:59:59',
			),
			'2017-12-30T10:00:00' => array(
				0 => '2017-12-30T11:00:00',
				1 => '2017-12-30T09:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_hour_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next day.
	 */
	public function test_next_day_start() {
		$settings = array(
			'2017-12-30T00:00:00' => array(
				0 => '2017-12-31T00:00:00',
				1 => '2017-12-29T23:59:59',
			),
			'2017-12-30T10:00:00' => array(
				0 => '2017-12-31T00:00:00',
				1 => '2017-12-29T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_day_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next day with daylight saving. Auckland's 2021 daylight saving
	 * starts on 26th September 2021 at 02:00:00 and ends on 3rd April 2022 at 03:00:00.
	 */
	public function test_next_day_start_daylight_saving() {
		update_option( 'timezone_string', 'Pacific/Auckland' );
		$settings = array(
			'2021-09-26T00:00:00' => array( // Before daylight saving starts.
				0 => '2021-09-27T00:00:00',
				1 => '2021-09-25T23:59:59',
			),
			'2021-09-26T03:00:00' => array( // After daylight saving starts.
				0 => '2021-09-27T00:00:00',
				1 => '2021-09-25T23:59:59',
			),
			'2022-04-03T00:00:00' => array( // Before daylight saving ends.
				0 => '2022-04-04T00:00:00',
				1 => '2022-04-02T23:59:59',
			),
			'2022-04-03T23:59:00' => array( // After daylight saving ends.
				0 => '2022-04-04T00:00:00',
				1 => '2022-04-02T23:59:59',
			),
		);

		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, new DateTimeZone( 'Pacific/Auckland' ) );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_day_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next week  or previous week end if reversed, for weeks starting on Monday.
	 */
	public function test_next_week_start_ISO_week() {
		update_option( 'start_of_week', 1 );
		$settings = array(
			'2017-12-30T00:00:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2017-12-24T23:59:59',
			),
			'2017-12-30T10:00:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2017-12-24T23:59:59',
			),
			'2010-12-25T10:00:00' => array(
				0 => '2010-12-27T00:00:00',
				1 => '2010-12-19T23:59:59',
			),
			'2010-12-26T10:00:00' => array(
				0 => '2010-12-27T00:00:00',
				1 => '2010-12-19T23:59:59',
			),
			'2010-12-27T00:00:00' => array(
				0 => '2011-01-03T00:00:00',
				1 => '2010-12-26T23:59:59',
			),
			'2010-12-31T00:00:00' => array(
				0 => '2011-01-03T00:00:00',
				1 => '2010-12-26T23:59:59',
			),
			'2011-01-01T00:00:00' => array(
				0 => '2011-01-03T00:00:00',
				1 => '2010-12-26T23:59:59',
			),
			'2011-01-03T00:00:00' => array(
				0 => '2011-01-10T00:00:00',
				1 => '2011-01-02T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_week_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Tests for the exact datetime returned by next_week_start to make sure it
	 * returns the exact time and timezone with shifting timezones
	 * between PHP settings and WordPress config.
	 */
	public function test_next_week_start_correct_timezone_calculation() {
		$original_timezone = date_default_timezone_get();
		// @codingStandardsIgnoreStart
		date_default_timezone_set( 'UTC' );
		$start_datetime = new \DateTime( '2022-05-02T00:00:00', new \DateTimeZone( 'Europe/Berlin' ) );
		$next_week_datetime = TimeInterval::next_week_start( $start_datetime, false );
		date_default_timezone_set( $original_timezone );
		// @codingStandardsIgnoreEnd
		$this->assertEquals( '2022-05-09T00:00:00', $next_week_datetime->format( TimeInterval::$iso_datetime_format ) );
		$this->assertEquals( 'Europe/Berlin', $next_week_datetime->getTimezone()->getName() );
	}

	/**
	 * Tests when users manually set timezone by date_default_timezone_set in wp-settings.php.
	 * Since we're using get_weekstartend, next_week_start should preemptively set
	 * the date object with the default timezone.
	 */
	public function test_next_week_start_timezone_loop() {
		$original_timezone = date_default_timezone_get();
		// @codingStandardsIgnoreStart
		date_default_timezone_set( 'Europe/Berlin' );
		$start_datetime = new \DateTime( '01-05-2022T00:00:00', new \DateTimeZone( 'Europe/Berlin' ) );
		$end_datetime = new \DateTime( '26-05-2022T00:00:00', new \DateTimeZone( 'Europe/Berlin' ) );
		$week_count = 0;
		do {
			$start_datetime = TimeInterval::next_week_start( $start_datetime, false );
			$week_count++;
		} while ( $start_datetime <= $end_datetime && $week_count < 10 );
		date_default_timezone_set( $original_timezone );
		// @codingStandardsIgnoreEnd
		// Value more than 5 should mean that loop never terminated.
		$this->assertEquals( 5, $week_count );
	}

	/**
	 * Test function that returns beginning of next week or previous week end if reversed, for weeks starting on Sunday.
	 */
	public function test_next_week_start_Sunday_based_week() {
		update_option( 'start_of_week', 7 );
		$settings = array(
			'2010-12-25T10:00:00' => array(
				0 => '2010-12-26T00:00:00',
				1 => '2010-12-18T23:59:59',
			),
			'2010-12-26T10:00:00' => array(
				0 => '2011-01-02T00:00:00',
				1 => '2010-12-25T23:59:59',
			),
			'2011-01-01T00:00:00' => array(
				0 => '2011-01-02T00:00:00',
				1 => '2010-12-25T23:59:59',
			),
			'2011-01-02T00:00:00' => array(
				0 => '2011-01-09T00:00:00',
				1 => '2011-01-01T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_week_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next month.
	 */
	public function test_next_month_start() {
		$settings = array(
			'2017-12-30T00:00:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2017-11-30T23:59:59',
			),
			// Leap year reversed test.
			'2016-03-05T10:00:00' => array(
				0 => '2016-04-01T00:00:00',
				1 => '2016-02-29T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_month_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next quarter.
	 */
	public function test_next_quarter_start() {
		$settings = array(
			'2017-12-31T00:00:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2017-09-30T23:59:59',
			),
			'2018-01-01T10:00:00' => array(
				0 => '2018-04-01T00:00:00',
				1 => '2017-12-31T23:59:59',
			),
			'2018-02-14T10:00:00' => array(
				0 => '2018-04-01T00:00:00',
				1 => '2017-12-31T23:59:59',
			),
			'2018-04-14T10:00:00' => array(
				0 => '2018-07-01T00:00:00',
				1 => '2018-03-31T23:59:59',
			),
			'2018-07-14T10:00:00' => array(
				0 => '2018-10-01T00:00:00',
				1 => '2018-06-30T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_quarter_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that returns beginning of next year.
	 */
	public function test_next_year_start() {
		$settings = array(
			'2017-12-31T23:59:59' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2016-12-31T23:59:59',
			),
			'2017-01-01T00:00:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2016-12-31T23:59:59',
			),
			'2017-04-23T14:53:00' => array(
				0 => '2018-01-01T00:00:00',
				1 => '2016-12-31T23:59:59',
			),
		);
		foreach ( $settings as $datetime_s => $setting ) {
			$datetime = new DateTime( $datetime_s, self::$local_tz );
			foreach ( $setting as $reversed => $exp_value ) {
				$result_dt = TimeInterval::next_year_start( $datetime, $reversed );
				$this->assertEquals( $exp_value, $result_dt->format( TimeInterval::$iso_datetime_format ), __FUNCTION__ . ": DT: $datetime_s; R: $reversed" );
			}
		}
	}

	/**
	 * Test function that normalizes *_between query parameters to *_min & *_max.
	 */
	public function test_normalize_between_params() {
		$request  = array(
			'a_between' => 'malformed',     // won't be normalized (not an array).
			'b_between' => array( 1, 5 ),   // results in min=1, max=5.
			'c_between' => array( 4, 2 ),   // results in min=2, max=4.
			'd_between' => array( 7 ),      // won't be normalized (only 1 item).
			'f_between' => array( 10, 12 ), // not in params, skipped.
		);
		$params   = array( 'a', 'b', 'c', 'd' );
		$result   = TimeInterval::normalize_between_params( $request, $params, false );
		$expected = array(
			'b_min' => 1,
			'b_max' => 5,
			'c_min' => 2,
			'c_max' => 4,
		);

		$this->assertEquals( $result, $expected );
	}

	/**
	 * Test function that normalizes *_between query parameters for dates to *_after & *_before.
	 */
	public function test_normalize_between_date_params() {
		$request  = array(
			'a_between' => 'malformed',     // won't be normalized (not an array).
			'b_between' => array( 1, 5 ),   // results in after=1, before=5.
			'c_between' => array( 4, 2 ),   // results in after=2, before=4.
			'd_between' => array( 7 ),      // won't be normalized (only 1 item).
			'f_between' => array( 10, 12 ), // not in params, skipped.
		);
		$params   = array( 'a', 'b', 'c', 'd' );
		$result   = TimeInterval::normalize_between_params( $request, $params, true );
		$expected = array(
			'b_after'  => 1,
			'b_before' => 5,
			'c_after'  => 2,
			'c_before' => 4,
		);

		$this->assertEquals( $result, $expected );
	}

	/**
	 * Test function that validates *_between query parameters for numeric values.
	 */
	public function test_rest_validate_between_numeric_arg() {
		$this->assertWPError(
			TimeInterval::rest_validate_between_numeric_arg( 'not array', null, 'param' ),
			'param is not a numerically indexed array.'
		);

		$this->assertWPError(
			TimeInterval::rest_validate_between_numeric_arg( array( 1 ), null, 'param' ),
			'param must contain 2 numbers.'
		);

		$this->assertTrue(
			TimeInterval::rest_validate_between_numeric_arg( array( 1, 2 ), null, 'param' )
		);
	}

	/**
	 * Test function that validates *_between query parameters for date values.
	 */
	public function rest_validate_between_date_arg() {
		$this->assertWPError(
			TimeInterval::rest_validate_between_date_arg( 'not array', null, 'param' ),
			'param is not a numerically indexed array.'
		);

		$this->assertWPError(
			TimeInterval::rest_validate_between_date_arg( array( '2019-01-01T00:00:00' ), null, 'param' ),
			'param must contain 2 valid dates.'
		);

		$this->assertWPError(
			TimeInterval::rest_validate_between_date_arg( array( 'not a valid date' ), null, 'param' ),
			'param must contain 2 valid dates.'
		);

		$this->assertTrue(
			TimeInterval::rest_validate_between_date_arg( array( '2019-01-01T00:00:00', '2019-01-15T00:00:00' ), null, 'param' )
		);
	}

	/**
	 * Test that we get the correct start and end times for "last_week" timeframes.
	 */
	public function test_timeframes_last_week() {
		$datetime = new DateTime( '2022-05-26' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_week', $datetime );

		$this->assertEquals( '2022-05-16 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-05-22 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2022-05-16' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_week', $datetime );

		$this->assertEquals( '2022-05-09 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-05-15 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2022-01-02' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_week', $datetime );

		$this->assertEquals( '2021-12-20 00:00:00', $dates['start'] );
		$this->assertEquals( '2021-12-26 23:59:59', $dates['end'] );
	}

	/**
	 * Test that we get the correct start and end times for "last_month" timeframes.
	 */
	public function test_timeframes_last_month() {
		$datetime = new DateTime( '2022-05-26' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_month', $datetime );

		$this->assertEquals( '2022-04-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-04-30 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2021-01-12' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_month', $datetime );

		$this->assertEquals( '2020-12-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2020-12-31 23:59:59', $dates['end'] );
	}

	/**
	 * Test that we get the correct start and end times for "last_quarter" timeframes.
	 */
	public function test_timeframes_last_quarter() {
		$datetime = new DateTime( '2022-05-26' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_quarter', $datetime );

		$this->assertEquals( '2022-01-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-03-31 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2022-01-12' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_quarter', $datetime );

		$this->assertEquals( '2021-10-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2021-12-31 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2022-07-18' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_quarter', $datetime );

		$this->assertEquals( '2022-04-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-06-30 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2022-11-07' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_quarter', $datetime );

		$this->assertEquals( '2022-07-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2022-09-31 23:59:59', $dates['end'] );
	}

	/**
	 * Test that we get the correct start and end times for "last_6_months" timeframes.
	 */
	public function test_timeframes_last_6_months() {
		$datetime = new DateTime( '2022-05-26' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_6_months', $datetime );

		$this->assertEquals( '2021-07-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2021-12-31 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2021-09-12' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_6_months', $datetime );

		$this->assertEquals( '2021-01-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2021-06-30 23:59:59', $dates['end'] );
	}

	/**
	 * Test that we get the correct start and end times for "last_year" timeframes.
	 */
	public function test_timeframes_last_year() {
		$datetime = new DateTime( '2022-05-26' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_year', $datetime );

		$this->assertEquals( '2021-01-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2021-12-31 23:59:59', $dates['end'] );

		$datetime = new DateTime( '2021-09-12' );
		$dates    = TimeInterval::get_timeframe_dates( 'last_year', $datetime );

		$this->assertEquals( '2020-01-01 00:00:00', $dates['start'] );
		$this->assertEquals( '2020-12-31 23:59:59', $dates['end'] );
	}
}
