<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\CashTimestamp;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the CashTimestamp class.
 */
class CashTimestampTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should convert explicit-offset timestamps to UTC storage format.
	 *
	 * @testWith ["2026-09-25T12:32:10Z", "2026-09-25 12:32:10"]
	 *           ["2026-09-25T14:32:10+02:00", "2026-09-25 12:32:10"]
	 *           ["2026-09-25T00:30:00-05:30", "2026-09-25 06:00:00"]
	 *           ["2026-09-25T12:32:10.987Z", "2026-09-25 12:32:10"]
	 *           ["2026-12-31T23:30:00-01:00", "2027-01-01 00:30:00"]
	 *
	 * @param string $value    Test value.
	 * @param string $expected Test value.
	 */
	public function test_to_gmt( string $value, string $expected ): void {
		$this->assertSame( $expected, CashTimestamp::to_gmt( $value ) );
	}

	/**
	 * @testdox Should reject timestamps without an explicit offset or with invalid parts.
	 *
	 * @testWith ["2026-09-25T12:32:10"]
	 *           ["2026-09-25 12:32:10Z"]
	 *           ["2026-02-30T12:00:00Z"]
	 *           ["2026-09-25T24:00:00Z"]
	 *           ["2026-09-25T12:00:00+2:00"]
	 *           ["2026-09-25T12:00:00+24:00"]
	 *           ["yesterday"]
	 *           [""]
	 *
	 * @param string $value Test value.
	 */
	public function test_to_gmt_rejects_invalid( string $value ): void {
		$this->expectException( InvalidArgumentException::class );
		CashTimestamp::to_gmt( $value );
	}

	/**
	 * @testdox Should format stored UTC values with an explicit Z suffix.
	 */
	public function test_format(): void {
		$this->assertSame( '2026-09-25T12:32:10Z', CashTimestamp::format( '2026-09-25 12:32:10' ) );
		$this->assertNull( CashTimestamp::format( null ) );
	}
}
