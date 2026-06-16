<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProviderGatewayAdapter;
use WC_Order;
use WC_Unit_Test_Case;
use WP_Error;

/**
 * Tests for the WooPaymentsProviderGatewayAdapter class.
 */
class WooPaymentsProviderGatewayAdapterTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Charge should normalize legacy confirmation redirects to customer-action outcomes.
	 */
	public function test_charge_normalizes_confirmation_redirect_to_customer_action(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array(
				'result'         => 'success',
				'redirect'       => '#wcpay-confirm-pi:123:secret:nonce',
				'payment_method' => 'pm_123',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_123' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION, $outcome->get_status() );
		$this->assertSame( '#wcpay-confirm-pi:123:secret:nonce', $outcome->get_redirect_url() );
		$this->assertSame( 'pm_123', $outcome->get_payment_method_id() );
		$this->assertSame( $order->get_id(), $gateway->processed_order_id );
		$this->assertSame( 'key_charge', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Charge should normalize legacy offsite redirects to redirect outcomes.
	 */
	public function test_charge_normalizes_offsite_redirect_to_redirect_outcome(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result'   => 'success',
					'redirect' => 'https://example.test/redirect',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_REQUIRES_REDIRECT, $outcome->get_status() );
		$this->assertSame( 'https://example.test/redirect', $outcome->get_redirect_url() );
	}

	/**
	 * @testdox Charge should preserve manual-capture outcomes written by the legacy gateway.
	 */
	public function test_charge_preserves_manual_capture_outcome_from_order_meta(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_order_received_url(),
			)
		);

		$gateway->intent_id_to_write         = 'pi_manual';
		$gateway->intention_status_to_write  = 'requires_capture';
		$gateway->payment_method_id_to_write = 'pm_manual';

		$sut = $this->create_adapter( $gateway );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_manual' ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_AUTHORIZED, $outcome->get_status() );
		$this->assertSame( 'pi_manual', $outcome->get_provider_payment_id() );
		$this->assertSame( 'pm_manual', $outcome->get_payment_method_id() );
	}

	/**
	 * @testdox Charge should preserve pending successful legacy responses without completing the order.
	 */
	public function test_charge_preserves_pending_success_without_order_completion(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result'   => 'success',
					'redirect' => '',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_PENDING_ASYNC, $outcome->get_status() );
		$this->assertArrayHasKey( 'checkout_redirect', $outcome->get_data() );
		$this->assertSame( '', $outcome->get_data()['checkout_redirect'] );
	}

	/**
	 * @testdox Charge should normalize legacy failures to failed outcomes.
	 */
	public function test_charge_normalizes_failure_to_failed_outcome(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter(
			new RecordingLegacyGateway(
				array(
					'result' => 'fail',
				)
			)
		);

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'legacy_process_payment_failed', $outcome->get_data()['error_code'] );
	}

	/**
	 * @testdox Refund should normalize legacy success and errors.
	 */
	public function test_refund_normalizes_legacy_success_and_errors(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway( array( 'result' => 'success' ), true );
		$sut     = $this->create_adapter( $gateway );

		$success = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$gateway->refund_result = new WP_Error( 'refund_failed', 'Refund failed.' );
		$failure                = $sut->refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 3.50, 'Adjustment' ), 'key_refund' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $success->get_status() );
		$this->assertSame( PaymentOutcome::STATUS_FAILED, $failure->get_status() );
		$this->assertSame( 'refund_failed', $failure->get_data()['error_code'] );
		$this->assertSame( 3.50, $gateway->refund_amount );
		$this->assertSame( 'Adjustment', $gateway->refund_reason );
		$this->assertSame( 'key_refund', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Capture should normalize legacy capture statuses.
	 */
	public function test_capture_normalizes_legacy_capture_statuses(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array( 'result' => 'success' ),
			true,
			array(
				'status' => 'succeeded',
				'id'     => 'pi_captured',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), 'key_capture' );

		$this->assertSame( PaymentOutcome::STATUS_COMPLETED, $outcome->get_status() );
		$this->assertSame( 'pi_captured', $outcome->get_provider_payment_id() );
		$this->assertSame( 'key_capture', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Cancel should normalize legacy canceled authorizations.
	 */
	public function test_cancel_normalizes_legacy_canceled_authorization(): void {
		$order   = $this->create_woopayments_order();
		$gateway = new RecordingLegacyGateway(
			array( 'result' => 'success' ),
			true,
			array(),
			array(
				'status' => 'canceled',
				'id'     => 'pi_canceled',
			)
		);
		$sut     = $this->create_adapter( $gateway );

		$outcome = $sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), 'key_cancel' );

		$this->assertSame( PaymentOutcome::STATUS_CANCELED, $outcome->get_status() );
		$this->assertSame( 'pi_canceled', $outcome->get_provider_payment_id() );
		$this->assertSame( 'key_cancel', $gateway->last_idempotency_key );
	}

	/**
	 * @testdox Operations should fail closed when no legacy gateway is available.
	 */
	public function test_operations_fail_closed_without_gateway(): void {
		$order = $this->create_woopayments_order();
		$sut   = $this->create_adapter( null );

		$outcome = $sut->charge( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), 'key_charge' );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $outcome->get_status() );
		$this->assertSame( 'wcpay_gateway_unavailable', $outcome->get_data()['error_code'] );
	}

	/**
	 * @testdox Availability should reflect whether the legacy bridge has a gateway.
	 */
	public function test_availability_reflects_legacy_gateway_presence(): void {
		$this->assertTrue( $this->create_adapter( new RecordingLegacyGateway() )->is_available() );
		$this->assertFalse( $this->create_adapter( null )->is_available() );
	}

	/**
	 * @testdox Availability should preserve the legacy gateway availability check.
	 */
	public function test_availability_reflects_legacy_gateway_availability(): void {
		$gateway            = new RecordingLegacyGateway();
		$gateway->available = false;

		$this->assertFalse( $this->create_adapter( $gateway )->is_available() );
	}

	/**
	 * Create adapter with a fake legacy gateway.
	 *
	 * @param RecordingLegacyGateway|null $gateway Legacy gateway.
	 * @return WooPaymentsProviderGatewayAdapter
	 */
	private function create_adapter( ?RecordingLegacyGateway $gateway ): WooPaymentsProviderGatewayAdapter {
		$legacy_runtime = new WooPaymentsLegacyRuntime();
		$legacy_runtime->init( new LegacyProxyWithGateway( $gateway ) );

		$sut = new WooPaymentsProviderGatewayAdapter();
		$sut->init( $legacy_runtime );

		return $sut;
	}

	/**
	 * Create a WooPayments order for adapter tests.
	 *
	 * @return WC_Order
	 */
	private function create_woopayments_order(): WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_total( '10.00' );
		$order->save();

		return $order;
	}
}
