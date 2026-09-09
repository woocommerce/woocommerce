<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\Payments\Finance;

use Automattic\WooCommerce\Admin\Payments\Finance\FinanceDataPage;
use WC_Unit_Test_Case;

/**
 * Tests for the FinanceDataPage value object.
 */
class FinanceDataPageTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should reindex the items and default to a single page without cursors.
	 */
	public function test_defaults_to_single_page(): void {
		$item = new \stdClass();

		$sut = new FinanceDataPage( array( 5 => $item ) );

		$this->assertSame( array( $item ), $sut->get_items() );
		$this->assertFalse( $sut->has_more() );
		$this->assertNull( $sut->get_next_cursor() );
		$this->assertNull( $sut->get_prev_cursor() );
	}

	/**
	 * @testdox Should keep the pagination state and turn empty cursors into null.
	 *
	 * @testWith ["next", "prev", "next", "prev"]
	 *           ["", "", null, null]
	 *
	 * @param string      $next          The next.
	 * @param string      $prev          The prev.
	 * @param string|null $expected_next The expected next.
	 * @param string|null $expected_prev The expected prev.
	 */
	public function test_keeps_pagination_state( string $next, string $prev, ?string $expected_next, ?string $expected_prev ): void {
		$sut = new FinanceDataPage( array(), true, $next, $prev );

		$this->assertTrue( $sut->has_more() );
		$this->assertSame( $expected_next, $sut->get_next_cursor() );
		$this->assertSame( $expected_prev, $sut->get_prev_cursor() );
	}

	/**
	 * @testdox Should reject items that are not objects.
	 *
	 * @testWith [[1]]
	 *           [["balance"]]
	 *           [[null]]
	 *           [[{"currency": "USD"}]]
	 *
	 * @param array $items The items.
	 */
	public function test_rejects_non_object_items( array $items ): void {
		$this->expectException( \InvalidArgumentException::class );

		new FinanceDataPage( $items );
	}
}
