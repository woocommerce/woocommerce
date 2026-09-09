<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataQuery;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceDataQuery value object.
 */
class FinanceDataQueryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should default to the first page with the default page size.
	 */
	public function test_defaults(): void {
		$sut = new FinanceDataQuery();

		$this->assertNull( $sut->get_cursor() );
		$this->assertSame( 10, $sut->get_per_page() );
	}

	/**
	 * @testdox Should clamp the page size to the allowed range.
	 *
	 * @testWith [0, 1]
	 *           [-5, 1]
	 *           [25, 25]
	 *           [100, 100]
	 *           [101, 100]
	 *
	 * @param int $per_page The per page.
	 * @param int $expected The expected.
	 */
	public function test_clamps_per_page( int $per_page, int $expected ): void {
		$sut = new FinanceDataQuery( null, $per_page );

		$this->assertSame( $expected, $sut->get_per_page() );
	}

	/**
	 * @testdox Should keep a cursor and turn an empty one into null.
	 *
	 * @testWith ["abc", "abc"]
	 *           ["", null]
	 *           [null, null]
	 *
	 * @param string|null $cursor   The cursor.
	 * @param string|null $expected The expected.
	 */
	public function test_normalizes_cursor( ?string $cursor, ?string $expected ): void {
		$sut = new FinanceDataQuery( $cursor );

		$this->assertSame( $expected, $sut->get_cursor() );
	}
}
