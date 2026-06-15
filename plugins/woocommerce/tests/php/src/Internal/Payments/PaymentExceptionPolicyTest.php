<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\PaymentExceptionPolicy;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Exception;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentExceptionPolicy class.
 */
class PaymentExceptionPolicyTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should normalize exceptions to failed payment outcomes.
	 */
	public function test_normalizes_exception_to_failed_outcome(): void {
		$sut = new PaymentExceptionPolicy();

		$outcome = $sut->to_failed_outcome( new Exception( 'Processor unavailable.' ) );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame(
			array(
				'error_code'    => '',
				'error_message' => 'Processor unavailable.',
			),
			$outcome->get_data()
		);
	}
}
