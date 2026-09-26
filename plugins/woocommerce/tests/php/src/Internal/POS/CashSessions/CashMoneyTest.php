<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\POS\CashSessions;

use Automattic\WooCommerce\Internal\POS\CashSessions\CashMoney;
use DomainException;
use InvalidArgumentException;
use OverflowException;
use WC_Unit_Test_Case;

/**
 * Tests for the CashMoney class.
 */
class CashMoneyTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should parse decimal strings into minor units at the given precision.
	 *
	 * @testWith ["148.50", 2, 14850]
	 *           ["148.5", 2, 14850]
	 *           ["0", 2, 0]
	 *           ["0.00", 2, 0]
	 *           ["007.10", 2, 710]
	 *           ["1500", 0, 1500]
	 *           ["1.234", 3, 1234]
	 *           ["999999999999.999", 3, 999999999999999]
	 *
	 * @param string $value     Test value.
	 * @param int    $precision Test value.
	 * @param int    $expected  Test value.
	 */
	public function test_parse_valid_amounts( string $value, int $precision, int $expected ): void {
		$this->assertSame( $expected, CashMoney::parse( $value, $precision ) );
	}

	/**
	 * @testdox Should reject malformed amounts and amounts with more fractional digits than the precision.
	 *
	 * @testWith [""]
	 *           ["-1.00"]
	 *           ["+1.00"]
	 *           ["1e3"]
	 *           ["1,000.00"]
	 *           [" 1.00"]
	 *           ["1."]
	 *           [".5"]
	 *           ["1.005"]
	 *           ["1.000"]
	 *           ["NaN"]
	 *
	 * @param string $value Test value.
	 */
	public function test_parse_rejects_invalid_amounts( string $value ): void {
		$this->expectException( InvalidArgumentException::class );
		CashMoney::parse( $value, 2 );
	}

	/**
	 * @testdox Should reject any fractional part when the precision is zero.
	 */
	public function test_parse_rejects_fraction_for_zero_precision(): void {
		$this->expectException( InvalidArgumentException::class );
		CashMoney::parse( '10.5', 0 );
	}

	/**
	 * @testdox Should reject amounts above the supported maximum instead of overflowing.
	 */
	public function test_parse_rejects_overflow(): void {
		$this->expectException( OverflowException::class );
		CashMoney::parse( '10000000000000.00', 2 );
	}

	/**
	 * @testdox Should accept source amounts with trailing zeros beyond the precision.
	 *
	 * @testWith ["10.500000", 2, 1050]
	 *           ["10.5", 2, 1050]
	 *           ["7", 2, 700]
	 *
	 * @param string $value     Test value.
	 * @param int    $precision Test value.
	 * @param int    $expected  Test value.
	 */
	public function test_parse_source_accepts_trailing_zeros( string $value, int $precision, int $expected ): void {
		$this->assertSame( $expected, CashMoney::parse_source( $value, $precision ) );
	}

	/**
	 * @testdox Should report a precision mismatch instead of rounding a source amount.
	 */
	public function test_parse_source_rejects_nonzero_excess_digits(): void {
		$this->expectException( DomainException::class );
		CashMoney::parse_source( '10.499999', 2 );
	}

	/**
	 * @testdox Should format minor units at the session precision, including negative values.
	 *
	 * @testWith [14850, 2, "148.50"]
	 *           [0, 2, "0.00"]
	 *           [5, 2, "0.05"]
	 *           [-150, 2, "-1.50"]
	 *           [-5, 3, "-0.005"]
	 *           [1500, 0, "1500"]
	 *
	 * @param int    $minor_units Test value.
	 * @param int    $precision   Test value.
	 * @param string $expected    Test value.
	 */
	public function test_format( int $minor_units, int $precision, string $expected ): void {
		$this->assertSame( $expected, CashMoney::format( $minor_units, $precision ) );
	}

	/**
	 * @testdox Should add amounts and reject totals above the supported maximum.
	 */
	public function test_add_checks_total_limit(): void {
		$this->assertSame( 300, CashMoney::add( 100, 200 ) );
		$this->assertSame( -100, CashMoney::add( 100, -200 ) );

		$this->expectException( OverflowException::class );
		CashMoney::add( CashMoney::MAX_TOTAL_MINOR_UNITS, 1 );
	}

	/**
	 * @testdox Should build a precision-independent canonical form for comparing payloads.
	 *
	 * @testWith ["10.50", "10.5"]
	 *           ["010.500", "10.5"]
	 *           ["0.00", "0"]
	 *           ["7", "7"]
	 *           ["100", "100"]
	 *
	 * @param string $value    Test value.
	 * @param string $expected Test value.
	 */
	public function test_canonical( string $value, string $expected ): void {
		$this->assertSame( $expected, CashMoney::canonical( $value ) );
	}
}
