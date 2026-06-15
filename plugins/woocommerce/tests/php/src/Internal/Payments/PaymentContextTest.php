<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentContext class.
 */
class PaymentContextTest extends WC_Unit_Test_Case {

	/**
	 * @testdox PaymentContext exposes neutral order, gateway, payment, and provider payload data.
	 */
	public function test_exposes_neutral_payment_context_data(): void {
		$order = wc_create_order();

		$context = new PaymentContext(
			$order,
			OrderPaymentStore::GATEWAY_ID,
			'card',
			array(
				'capture' => true,
			),
			array(
				'stripe_customer_id' => 'cus_123',
			)
		);

		$this->assertSame( $order, $context->get_order() );
		$this->assertSame( $order->get_id(), $context->get_order_id() );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $context->get_gateway_id() );
		$this->assertSame( 'card', $context->get_payment_method_id() );
		$this->assertSame( array( 'capture' => true ), $context->get_payment_data() );
		$this->assertSame( array( 'stripe_customer_id' => 'cus_123' ), $context->get_provider_data() );
	}

	/**
	 * @testdox Checkout factory should preserve payment and provider data.
	 */
	public function test_checkout_factory_preserves_payment_and_provider_data(): void {
		$order = wc_create_order();

		$context = PaymentContext::for_checkout(
			$order,
			OrderPaymentStore::GATEWAY_ID,
			'pm_123',
			array(
				'save_payment_method' => true,
			),
			array(
				'confirmation_token' => 'ctoken_123',
			)
		);

		$this->assertSame( $order, $context->get_order() );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $context->get_gateway_id() );
		$this->assertSame( 'pm_123', $context->get_payment_method_id() );
		$this->assertSame( array( 'save_payment_method' => true ), $context->get_payment_data() );
		$this->assertSame( array( 'confirmation_token' => 'ctoken_123' ), $context->get_provider_data() );
	}

	/**
	 * @testdox Refund factory should expose amount and reason as generic payment data.
	 */
	public function test_refund_factory_exposes_amount_and_reason(): void {
		$order = wc_create_order();

		$context = PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 7.25, 'Requested by customer' );

		$this->assertSame(
			array(
				'amount' => 7.25,
				'reason' => 'Requested by customer',
			),
			$context->get_payment_data()
		);
	}

	/**
	 * @testdox Capture and cancel factories should expose provider data without card-specific assumptions.
	 */
	public function test_capture_and_cancel_factories_expose_provider_data(): void {
		$order = wc_create_order();

		$capture = PaymentContext::for_capture(
			$order,
			OrderPaymentStore::GATEWAY_ID,
			array(
				'include_level3' => true,
			)
		);
		$cancel  = PaymentContext::for_cancel(
			$order,
			OrderPaymentStore::GATEWAY_ID,
			array(
				'source' => 'order_action',
			)
		);

		$this->assertSame( array( 'include_level3' => true ), $capture->get_provider_data() );
		$this->assertSame( array( 'source' => 'order_action' ), $cancel->get_provider_data() );
	}
}
