<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use InvalidArgumentException;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentOutcome class.
 */
class PaymentOutcomeTest extends WC_Unit_Test_Case {

	/**
	 * @testdox PaymentOutcome exposes neutral provider result data.
	 */
	public function test_exposes_neutral_provider_result_data(): void {
		$outcome = new PaymentOutcome(
			PaymentOutcome::STATUS_REQUIRES_REDIRECT,
			'pi_123',
			'https://example.test/redirect',
			'pm_123',
			'cus_123',
			array(
				'order_note' => 'Awaiting redirect completion.',
			)
		);

		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_REDIRECT, $outcome->get_status() );
		$this->assertSame( 'pi_123', $outcome->get_provider_payment_id() );
		$this->assertSame( 'https://example.test/redirect', $outcome->get_redirect_url() );
		$this->assertSame( 'pm_123', $outcome->get_payment_method_id() );
		$this->assertSame( 'cus_123', $outcome->get_customer_id() );
		$this->assertSame( array( 'order_note' => 'Awaiting redirect completion.' ), $outcome->get_data() );
		$this->assertFalse( $outcome->is_successful() );
	}

	/**
	 * @testdox Successful statuses are explicit and provider-neutral.
	 */
	public function test_successful_statuses_are_explicit(): void {
		$this->assertTrue( ( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED ) )->is_successful() );
		$this->assertTrue( ( new PaymentOutcome( PaymentOutcome::STATUS_AUTHORIZED ) )->is_successful() );
		$this->assertTrue( ( new PaymentOutcome( PaymentOutcome::STATUS_NO_EXTERNAL_PAYMENT ) )->is_successful() );
		$this->assertFalse( ( new PaymentOutcome( PaymentOutcome::STATUS_FAILED ) )->is_successful() );
	}

	/**
	 * @testdox Unknown statuses fail closed.
	 */
	public function test_unknown_statuses_fail_closed(): void {
		$this->expectException( InvalidArgumentException::class );

		new PaymentOutcome( 'unknown-status' );
	}
}
