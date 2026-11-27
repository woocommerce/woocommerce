<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Cart;

use Automattic\WooCommerce\Internal\Cart\PriceDisplayPrecision;
use WC_Unit_Test_Case;

/**
 * Tests for the PriceDisplayPrecision class.
 */
class PriceDisplayPrecisionTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Shows a unit amount with the decimals needed for unit × quantity to make the line shown.
	 * @testWith [8.325, 3, 24.975, 3]
	 *           [1.6625, 3, 4.9875, 4]
	 *           [1.17473414, 7, 8.22313898, 6]
	 *           [1.66, 3, 4.98, 2]
	 *           [1.99, 3, 5.97, 2]
	 *           [8.325, 0, 0, 2]
	 *
	 * @param float $unit     Exact unit amount.
	 * @param float $quantity Quantity.
	 * @param float $line     Exact line amount.
	 * @param int   $expected Decimals.
	 */
	public function test_unit_decimals_explain_the_line( float $unit, float $quantity, float $line, int $expected ): void {
		$this->assertSame( $expected, PriceDisplayPrecision::get_unit_decimals_for_line( $unit, $quantity, $line ) );
	}

	/**
	 * @testdox Counts the unit's decimals at 6 places, even when the internal rounding precision is lowered.
	 */
	public function test_unit_decimals_ignore_lowered_rounding_precision(): void {
		$precision = fn() => 2;
		add_filter( 'woocommerce_internal_rounding_precision', $precision );
		$decimals  = PriceDisplayPrecision::get_unit_decimals_for_line( 8.325, 3, 24.975 );
		remove_filter( 'woocommerce_internal_rounding_precision', $precision );

		$this->assertSame( 3, $decimals );
	}
}
