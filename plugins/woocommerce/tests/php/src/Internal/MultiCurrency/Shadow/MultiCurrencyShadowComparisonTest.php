<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Shadow;

use Automattic\WooCommerce\Internal\MultiCurrency\Shadow\MultiCurrencyShadowComparison;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyShadowComparison class.
 */
class MultiCurrencyShadowComparisonTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should expose compact log payloads.
	 */
	public function test_exposes_compact_log_payloads(): void {
		$sut = new MultiCurrencyShadowComparison(
			'unit_test',
			123,
			array( 'meta' => array( 'rate' => 0.81 ) ),
			array( 'meta' => array( 'rate' => 0.82 ) ),
			array(
				'meta.rate' => array(
					'expected' => 0.82,
					'actual'   => 0.81,
				),
			),
			1.5
		);

		$payload = $sut->to_log_array();

		$this->assertSame( 'unit_test', $payload['trigger'] );
		$this->assertSame( 123, $payload['order_id'] );
		$this->assertSame( 'order', $payload['subject_type'] );
		$this->assertSame( 123, $payload['subject_id'] );
		$this->assertSame( MultiCurrencyShadowComparison::COMPARISON_TYPE_ORDER_META, $payload['comparison_type'] );
		$this->assertTrue( $payload['independent_native_computation'] );
		$this->assertTrue( $payload['has_diff'] );
		$this->assertArrayHasKey( 'actual_hash', $payload );
		$this->assertArrayHasKey( 'native_computed_hash', $payload );
		$this->assertArrayNotHasKey( 'actual', $payload );
		$this->assertArrayNotHasKey( 'native_computed', $payload );
	}

	/**
	 * @testdox Should include full surfaces when requested.
	 */
	public function test_includes_full_surfaces_when_requested(): void {
		$sut = new MultiCurrencyShadowComparison(
			'unit_test',
			123,
			array( 'meta' => array( 'rate' => 0.81 ) ),
			array( 'meta' => array( 'rate' => 0.82 ) ),
			array(),
			1.5
		);

		$payload = $sut->to_log_array( true );

		$this->assertSame( 0.81, $payload['actual']['meta']['rate'] );
		$this->assertSame( 0.82, $payload['native_computed']['meta']['rate'] );
	}
}
