<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\DateQueryUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the DateQueryUtil class.
 */
class DateQueryUtilTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should convert dates with an explicit offset to UTC, regardless of the site timezone.
	 *
	 * @testWith ["2026-09-24T14:39:52Z", "2026-09-24 14:39:52"]
	 *           ["2026-09-24T14:39:52+00:00", "2026-09-24 14:39:52"]
	 *           ["2026-09-24T16:39:52+02:00", "2026-09-24 14:39:52"]
	 *           ["2026-09-24T16:39:52+02", "2026-09-24 14:39:52"]
	 *           ["2026-09-24T09:09:52-05:30", "2026-09-24 14:39:52"]
	 *           ["2026-09-24T14:39:52.750Z", "2026-09-24 14:39:52"]
	 *           ["2026-09-25T00:30:00+03:00", "2026-09-24 21:30:00"]
	 *
	 * @param string $date     The date as received in a request.
	 * @param string $expected The expected UTC date.
	 */
	public function test_converts_dates_with_offset_to_utc( string $date, string $expected ): void {
		update_option( 'timezone_string', 'Europe/Athens' );

		$this->assertSame( $expected, DateQueryUtil::to_gmt_date_query_value( $date ) );
	}

	/**
	 * @testdox Should return dates without an offset and unparsable values unchanged.
	 *
	 * @testWith ["2026-09-24T14:39:52"]
	 *           ["2026-09-24 14:39:52"]
	 *           ["2026-09-24T14:39:52.123"]
	 *           ["2026-09-24"]
	 *           ["not a date"]
	 *           [""]
	 *           [null]
	 *           [1758724792]
	 *
	 * @param mixed $date The value as received in a request.
	 */
	public function test_returns_values_without_offset_unchanged( $date ): void {
		update_option( 'timezone_string', 'Europe/Athens' );

		$this->assertSame( $date, DateQueryUtil::to_gmt_date_query_value( $date ) );
	}
}
