<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Shadow;

use Automattic\WooCommerce\Internal\Payments\Shadow\PaymentSurfaceDiffer;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentSurfaceDiffer class.
 */
class PaymentSurfaceDifferTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Identical payment surfaces produce no differences.
	 */
	public function test_identical_payment_surfaces_produce_no_differences(): void {
		$differ = new PaymentSurfaceDiffer();

		$this->assertSame(
			array(),
			$differ->diff(
				array(
					'meta'  => array(
						'_intent_id' => 'pi_123',
					),
					'total' => '10.00',
				),
				array(
					'meta'  => array(
						'_intent_id' => 'pi_123',
					),
					'total' => '10.00',
				)
			)
		);
	}

	/**
	 * @testdox Differences are reported by stable dot paths.
	 */
	public function test_differences_are_reported_by_stable_dot_paths(): void {
		$differ = new PaymentSurfaceDiffer();

		$diff = $differ->diff(
			array(
				'meta'  => array(
					'_intent_id' => 'pi_expected',
				),
				'total' => '10.00',
			),
			array(
				'meta'   => array(
					'_intent_id' => 'pi_actual',
				),
				'status' => 'processing',
			)
		);

		$this->assertSame( 'pi_expected', $diff['meta._intent_id']['expected'] );
		$this->assertSame( 'pi_actual', $diff['meta._intent_id']['actual'] );
		$this->assertSame( '10.00', $diff['total']['expected'] );
		$this->assertNull( $diff['total']['actual'] );
		$this->assertNull( $diff['status']['expected'] );
		$this->assertSame( 'processing', $diff['status']['actual'] );
	}
}
