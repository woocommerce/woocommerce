<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Enums\PaymentGatewayFeature;
use Automattic\WooCommerce\Internal\Payments\NativeWooPaymentsGateway;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use WC_Order;
use WC_Unit_Test_Case;

/**
 * Tests for the NativeWooPaymentsGateway class.
 */
class NativeWooPaymentsGatewayTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should preserve the WooPayments gateway identity and settings option key.
	 */
	public function test_preserves_gateway_identity(): void {
		$gateway = wc_get_container()->get( NativeWooPaymentsGateway::class );

		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $gateway->id );
		$this->assertSame( 'woocommerce_woocommerce_payments_settings', $gateway->get_option_key() );
		$this->assertContains( 'refunds', $gateway->supports );
		$this->assertContains( PaymentGatewayFeature::TOKENIZATION, $gateway->supports );
		$this->assertNotContains( PaymentGatewayFeature::ADD_PAYMENT_METHOD, $gateway->supports );
	}

	/**
	 * @testdox Should process payments through the native processing service.
	 */
	public function test_process_payment_delegates_to_processing_service(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame( 'success', $result['result'] );
		$this->assertInstanceOf( PaymentContext::class, $service->last_checkout_context );
		$this->assertSame( $order->get_id(), $service->last_checkout_context->get_order_id() );
		$this->assertSame( OrderPaymentStore::GATEWAY_ID, $service->last_checkout_context->get_gateway_id() );
	}

	/**
	 * @testdox Should process refunds through the native processing service.
	 */
	public function test_process_refund_delegates_to_processing_service(): void {
		$order = $this->create_order();
		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertTrue( $result );
		$this->assertInstanceOf( PaymentContext::class, $service->last_refund_context );
		$this->assertSame(
			array(
				'amount' => 4.25,
				'reason' => 'Adjustment',
			),
			$service->last_refund_context->get_payment_data()
		);
	}

	/**
	 * @testdox Should only allow refunds for orders with a WooPayments charge.
	 */
	public function test_can_refund_order_requires_charge_id(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$this->assertFalse( $gateway->can_refund_order( $order ) );

		$order->update_meta_data( '_charge_id', 'ch_test' );
		$order->save();

		$this->assertTrue( $gateway->can_refund_order( wc_get_order( $order->get_id() ) ) );
	}

	/**
	 * @testdox Should fail refunds that do not have a WooPayments charge.
	 */
	public function test_process_refund_fails_without_charge_id(): void {
		$order   = $this->create_order();
		$service = new RecordingPaymentProcessingService();
		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider() );

		$result = $gateway->process_refund( $order->get_id(), 4.25, 'Adjustment' );

		$this->assertWPError( $result );
		$this->assertSame( 'native_payment_refund_missing_charge', $result->get_error_code() );
		$this->assertNull( $service->last_refund_context );
	}

	/**
	 * @testdox Should resolve native dependencies when WooCommerce instantiates the gateway directly.
	 */
	public function test_process_payment_resolves_dependencies_without_explicit_init(): void {
		$order   = $this->create_order();
		$gateway = new NativeWooPaymentsGateway();

		$result = $gateway->process_payment( $order->get_id() );

		$this->assertSame(
			array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			),
			$result
		);
	}

	/**
	 * @testdox Should delegate payment fields rendering to the checkout bridge.
	 */
	public function test_payment_fields_delegate_to_checkout_bridge(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		$service = new RecordingPaymentProcessingService();
		$bridge  = $this->getMockBuilder( WooPaymentsCheckoutBridge::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'render_payment_fields' ) )
			->getMock();
		$bridge
			->expects( $this->once() )
			->method( 'render_payment_fields' )
			->willReturnCallback(
				static function (): void {
					echo '<div id="wcpay-bridge-marker"></div>';
				}
			);

		$gateway = new NativeWooPaymentsGateway();
		$gateway->init( $service, new WooPaymentsProvider(), $bridge );

		ob_start();
		try {
			$gateway->payment_fields();
			$output = (string) ob_get_clean();
		} finally {
			remove_filter( 'woocommerce_is_checkout', '__return_true' );
		}

		$this->assertStringContainsString( 'wcpay-bridge-marker', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-new-payment-method', $output );
		$this->assertStringContainsString( 'wc-woocommerce_payments-payment-token-new', $output );
	}

	/**
	 * @testdox Should resolve checkout bridge dependencies when payment fields are rendered directly.
	 */
	public function test_payment_fields_resolve_dependencies_without_explicit_init(): void {
		$gateway = new NativeWooPaymentsGateway();

		ob_start();
		$gateway->payment_fields();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'wcpay-core-checkout-form', $output );
	}

	/**
	 * Create an order for gateway tests.
	 *
	 * @return WC_Order
	 */
	private function create_order(): WC_Order {
		$order = wc_create_order();
		$order->set_total( '12.00' );
		$order->save();

		return $order;
	}
}
