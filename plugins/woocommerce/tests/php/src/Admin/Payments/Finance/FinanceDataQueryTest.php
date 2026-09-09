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

		$this->assertNull( $sut->get_next_cursor() );
		$this->assertNull( $sut->get_prev_cursor() );
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
		$sut = new FinanceDataQuery( null, null, $per_page );

		$this->assertSame( $expected, $sut->get_per_page() );
	}

	/**
	 * @testdox Should keep each cursor separately and turn an empty one into null.
	 *
	 * @testWith ["to-next", "to-prev", "to-next", "to-prev"]
	 *           ["to-next", null, "to-next", null]
	 *           [null, "to-prev", null, "to-prev"]
	 *           ["", "to-prev", null, "to-prev"]
	 *           ["to-next", "", "to-next", null]
	 *           ["", "", null, null]
	 *
	 * @param string|null $next_cursor   The next cursor.
	 * @param string|null $prev_cursor   The previous cursor.
	 * @param string|null $expected_next The expected next cursor.
	 * @param string|null $expected_prev The expected previous cursor.
	 */
	public function test_normalizes_cursors( ?string $next_cursor, ?string $prev_cursor, ?string $expected_next, ?string $expected_prev ): void {
		$sut = new FinanceDataQuery( $next_cursor, $prev_cursor );

		$this->assertSame( $expected_next, $sut->get_next_cursor() );
		$this->assertSame( $expected_prev, $sut->get_prev_cursor() );
	}
}
