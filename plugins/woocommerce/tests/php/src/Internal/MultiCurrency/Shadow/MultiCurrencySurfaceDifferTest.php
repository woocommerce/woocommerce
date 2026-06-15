<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Shadow;

use Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencySurfaceDiffer;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySurfaceDiffer class.
 */
class MultiCurrencySurfaceDifferTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should return no differences for identical surfaces.
	 */
	public function test_returns_no_differences_for_identical_surfaces(): void {
		$sut = new MultiCurrencySurfaceDiffer();

		$this->assertSame(
			array(),
			$sut->diff(
				array( 'meta' => array( 'rate' => 0.82 ) ),
				array( 'meta' => array( 'rate' => 0.82 ) )
			)
		);
	}

	/**
	 * @testdox Should report differences by stable dot paths.
	 */
	public function test_reports_differences_by_stable_dot_paths(): void {
		$sut = new MultiCurrencySurfaceDiffer();

		$diff = $sut->diff(
			array(
				'meta'     => array( 'rate' => 0.82 ),
				'currency' => 'GBP',
			),
			array(
				'meta'   => array( 'rate' => 0.81 ),
				'status' => 'actual',
			)
		);

		$this->assertSame( 0.82, $diff['meta.rate']['expected'] );
		$this->assertSame( 0.81, $diff['meta.rate']['actual'] );
		$this->assertSame( 'GBP', $diff['currency']['expected'] );
		$this->assertNull( $diff['currency']['actual'] );
		$this->assertNull( $diff['status']['expected'] );
		$this->assertSame( 'actual', $diff['status']['actual'] );
	}
}
