<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentOperationIdempotency;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentOperationIdempotency class.
 */
class PaymentOperationIdempotencyTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should derive the same key for identical charge inputs.
	 */
	public function test_derives_same_key_for_identical_charge_inputs(): void {
		$order = wc_create_order();
		$order->set_currency( 'USD' );
		$order->set_total( '12.34' );
		$order->save();

		$sut = new PaymentOperationIdempotency();

		$first  = $sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'charge', 12.34, 'USD' );
		$second = $sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'charge', 12.34, 'USD' );

		$this->assertSame( $first, $second, 'Retries of the same operation must collapse to one provider operation.' );
		$this->assertStringStartsWith( 'wc_native_payments_', $first );
	}

	/**
	 * @testdox Should change the key when the operation changes.
	 */
	public function test_changes_key_when_operation_changes(): void {
		$order = wc_create_order();
		$sut   = new PaymentOperationIdempotency();

		$this->assertNotSame(
			$sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'charge', 20.00, 'USD' ),
			$sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'refund', 20.00, 'USD' ),
			'Different operations on the same order need different idempotency keys.'
		);
	}

	/**
	 * @testdox Should change the key when refund details change.
	 */
	public function test_changes_key_when_refund_details_change(): void {
		$order = wc_create_order();
		$sut   = new PaymentOperationIdempotency();

		$this->assertNotSame(
			$sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'refund', 5.00, 'USD', 'Customer request' ),
			$sut->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'refund', 6.00, 'USD', 'Customer request' ),
			'Refund amount must participate in the key so distinct refunds do not collapse together.'
		);
	}
}
