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
}
