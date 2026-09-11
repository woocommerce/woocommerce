<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Admin\API\Reports;

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use DateTime;
use DateTimeZone;
use WC_Unit_Test_Case;

/**
 * Tests for the timeframe date calculations in TimeInterval.
 *
 * The pre-existing coverage for this class lives in the frozen legacy suite
 * (tests/legacy/unit-tests/woocommerce-admin/api/reports-interval.php); new
 * TimeInterval tests belong here.
 */
class TimeIntervalTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should select timeframe periods from the reference date's local calendar date, not its UTC instant.
	 */
	public function test_timeframes_use_local_calendar_date(): void {
		// 2022-09-01 05:00 in Kiritimati (UTC+14) is 2022-08-31 15:00 UTC: last month is August, not July.
		$datetime = new DateTime( '2022-09-01 05:00:00', new DateTimeZone( 'Pacific/Kiritimati' ) );
		$dates    = TimeInterval::get_timeframe_dates( 'last_month', $datetime );

		$this->assertSame( '2022-08-01 00:00:00', $dates['start'] );
		$this->assertSame( '2022-08-31 23:59:59', $dates['end'] );

		// 2021-12-31 20:00 in Midway (UTC-11) is 2022-01-01 07:00 UTC: last year is 2020, not 2021.
		$datetime = new DateTime( '2021-12-31 20:00:00', new DateTimeZone( 'Pacific/Midway' ) );
		$dates    = TimeInterval::get_timeframe_dates( 'last_year', $datetime );

		$this->assertSame( '2020-01-01 00:00:00', $dates['start'] );
		$this->assertSame( '2020-12-31 23:59:59', $dates['end'] );
	}

	/**
	 * @testdox Should anchor the default reference date to the site timezone, not the PHP default timezone.
	 *
	 * With a real clock this assertion only discriminates a wrong default anchor while the UTC date
	 * and the site-local date fall in different periods (around period-boundary midnights), so an
	 * intermittent failure here means a real timezone regression, not flakiness. The deterministic
	 * production-path guarantee lives in TotalPaymentsVolumeProcessorTest.
	 */
	public function test_timeframes_default_to_site_timezone_now(): void {
		$timeframes        = array( 'last_week', 'last_month', 'last_quarter', 'last_6_months', 'last_year' );
		$original_timezone = get_option( 'timezone_string' );

		try {
			// Extreme offsets on both sides of UTC maximize the window where the site-local date differs from the UTC date.
			foreach ( array( 'Pacific/Kiritimati', 'Pacific/Midway' ) as $timezone ) {
				update_option( 'timezone_string', $timezone );

				foreach ( $timeframes as $timeframe ) {
					// Compute the expectation before and after the call so the assertion also holds if midnight passes mid-test.
					$expected_before = TimeInterval::get_timeframe_dates( $timeframe, new DateTime( 'now', wp_timezone() ) );
					$actual          = TimeInterval::get_timeframe_dates( $timeframe );
					$expected_after  = TimeInterval::get_timeframe_dates( $timeframe, new DateTime( 'now', wp_timezone() ) );

					$this->assertContains(
						$actual,
						array( $expected_before, $expected_after ),
						"Default \"$timeframe\" dates should be based on the current date in the $timezone site timezone"
					);
				}
			}
		} finally {
			update_option( 'timezone_string', $original_timezone );
		}
	}
}
