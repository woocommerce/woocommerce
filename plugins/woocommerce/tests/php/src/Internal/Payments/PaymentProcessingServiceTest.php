<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOperationIdempotency;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\PaymentProcessingService;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;
use WC_Order;
use WC_Order_Refund;
use WC_Unit_Test_Case;

/**
 * Tests for the PaymentProcessingService class.
 */
class PaymentProcessingServiceTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var PaymentProcessingService
	 */
	private $sut;

	/**
	 * Order payment store.
	 *
	 * @var OrderPaymentStore
	 */
	private $store;

	/**
	 * Payment operation idempotency service.
	 *
	 * @var PaymentOperationIdempotency
	 */
	private $idempotency;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut         = wc_get_container()->get( PaymentProcessingService::class );
		$this->store       = wc_get_container()->get( OrderPaymentStore::class );
		$this->idempotency = wc_get_container()->get( PaymentOperationIdempotency::class );
	}

	/**
	 * @testdox Should call the provider with a deterministic key and complete the order for completed outcomes.
	 */
	public function test_process_checkout_completes_order_for_completed_outcome(): void {
		$order    = $this->create_woopayments_order( '10.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_COMPLETED,
				'pi_test',
				'',
				'pm_test',
				'cus_test',
				array(
					'meta' => array(
						'_charge_id'        => 'ch_test',
						'_intention_status' => 'succeeded',
					),
				)
			)
		);

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_test' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'pm_test', $result['payment_method'] );
		$this->assertSame(
			$this->idempotency->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'charge', 10.00, 'USD' ),
			$provider->last_idempotency_key,
			'The provider must receive the deterministic operation key.'
		);
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertSame( 'pi_test', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'pm_test', $order->get_meta( '_payment_method_id', true ) );
		$this->assertSame( 'ch_test', $order->get_meta( '_charge_id', true ) );
	}

	/**
	 * @testdox Should return a redirect result without completing the order for redirect outcomes.
	 */
	public function test_process_checkout_returns_redirect_without_completing_order(): void {
		$order    = $this->create_woopayments_order( '15.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_REQUIRES_REDIRECT,
				'pi_redirect',
				'https://example.test/redirect',
				'pm_redirect',
				'',
				array(
					'meta' => array(
						'_intention_status' => 'requires_action',
					),
				)
			)
		);

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_redirect' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://example.test/redirect', $result['redirect'] );
		$this->assertSame( 'pm_redirect', $result['payment_method'] );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 'pi_redirect', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'requires_action', $order->get_meta( '_intention_status', true ) );
	}

	/**
	 * @testdox Should preserve a provider supplied empty checkout redirect.
	 */
	public function test_process_checkout_preserves_empty_checkout_redirect_override(): void {
		$order    = $this->create_woopayments_order( '15.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_PENDING_ASYNC,
				'',
				'',
				'',
				'',
				array( 'checkout_redirect' => '' )
			)
		);

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID ), $provider );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( '', $result['redirect'] );
	}

	/**
	 * @testdox Should not call the provider while an order operation is locked.
	 */
	public function test_process_checkout_returns_failure_when_order_operation_is_locked(): void {
		$order = $this->create_woopayments_order( '10.00' );
		$key   = $this->idempotency->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'charge', 10.00, 'USD' );
		$this->store->lock_order_payment( $order, $key );

		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_test' ) );

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_test' ), $provider );

		$this->assertSame( 'fail', $result['result'] );
		$this->assertSame( 0, $provider->charge_calls );
		$this->store->unlock_order_payment( $order );
	}

	/**
	 * @testdox Should not call the provider while any order operation is locked.
	 */
	public function test_process_checkout_returns_failure_when_any_order_operation_is_locked(): void {
		$order = $this->create_woopayments_order( '10.00' );
		$this->store->lock_order_payment( $order, 'pi_existing' );

		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_test' ) );

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_test' ), $provider );

		$this->assertSame( 'fail', $result['result'] );
		$this->assertSame( 0, $provider->charge_calls );
		$this->store->unlock_order_payment( $order );
	}

	/**
	 * @testdox Should complete zero-total checkout without calling the provider.
	 */
	public function test_process_checkout_completes_zero_total_without_provider_call(): void {
		$order    = $this->create_woopayments_order( '0.00' );
		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_should_not_be_used' ) );

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_test' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 0, $provider->charge_calls );
		$this->assertSame( 'completed', $order->get_status() );
	}

	/**
	 * @testdox Should return true for zero-amount refunds without calling the provider.
	 */
	public function test_process_refund_zero_amount_returns_true_without_provider_call(): void {
		$order    = $this->create_woopayments_order( '10.00' );
		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED ) );

		$result = $this->sut->process_refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 0.00 ), $provider );

		$this->assertTrue( $result );
		$this->assertSame( 0, $provider->refund_calls );
	}

	/**
	 * @testdox Should return true when the provider refund succeeds.
	 */
	public function test_process_refund_returns_true_when_provider_succeeds(): void {
		$order    = $this->create_woopayments_order( '10.00' );
		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED ) );

		$result = $this->sut->process_refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 2.50, 'Adjustment' ), $provider );

		$this->assertTrue( $result );
		$this->assertSame( 1, $provider->refund_calls );
		$this->assertSame(
			$this->idempotency->derive_key( $order, OrderPaymentStore::GATEWAY_ID, 'refund', 2.50, 'USD', 'Adjustment' ),
			$provider->last_idempotency_key
		);
	}

	/**
	 * @testdox Should persist provider refund metadata on the matching WC refund.
	 */
	public function test_process_refund_persists_provider_refund_metadata(): void {
		$order  = $this->create_woopayments_order( '10.00' );
		$refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 2.50,
				'reason'         => 'Adjustment',
				'refund_payment' => false,
			)
		);

		$this->assertInstanceOf( WC_Order_Refund::class, $refund );

		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_COMPLETED,
				're_native',
				'',
				'',
				'',
				array(
					'order_meta'  => array(
						'_wcpay_refund_status' => 'successful',
					),
					'refund_meta' => array(
						'_wcpay_refund_id'             => 're_native',
						'_wcpay_refund_transaction_id' => 'txn_refund',
					),
					'refund_note' => 'A refund of $2.50 was successfully processed using WooPayments. Reason: Adjustment. (<code>re_native</code>)',
				)
			)
		);

		$result = $this->sut->process_refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 2.50, 'Adjustment' ), $provider );
		$order  = wc_get_order( $order->get_id() );
		$refund = wc_get_order( $refund->get_id() );

		$this->assertTrue( $result );
		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertInstanceOf( WC_Order_Refund::class, $refund );
		$this->assertSame( 'successful', $order->get_meta( '_wcpay_refund_status', true ) );
		$this->assertSame( 're_native', $refund->get_meta( '_wcpay_refund_id', true ) );
		$this->assertSame( 'txn_refund', $refund->get_meta( '_wcpay_refund_transaction_id', true ) );
		$this->assertOrderHasNoteContaining( $order, 'A refund of' );
		$this->assertOrderHasNoteContaining( $order, 'was successfully processed using WooPayments' );
		$this->assertOrderHasNoteContaining( $order, 'Adjustment' );
		$this->assertOrderHasNoteContaining( $order, 're_native' );
	}

	/**
	 * @testdox Should match the first unlinked refund using provider supplied meta keys.
	 */
	public function test_process_refund_skips_refunds_linked_by_provider_meta_keys(): void {
		$order           = $this->create_woopayments_order( '10.00' );
		$unlinked_refund = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 2.50,
				'reason'         => 'Adjustment',
				'refund_payment' => false,
			)
		);
		$linked_refund   = wc_create_refund(
			array(
				'order_id'       => $order->get_id(),
				'amount'         => 2.50,
				'reason'         => 'Adjustment',
				'refund_payment' => false,
			)
		);

		$this->assertInstanceOf( WC_Order_Refund::class, $unlinked_refund );
		$this->assertInstanceOf( WC_Order_Refund::class, $linked_refund );

		$linked_refund->update_meta_data( '_provider_refund_id', 're_existing' );
		$linked_refund->save_meta_data();

		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_COMPLETED,
				're_native',
				'',
				'',
				'',
				array(
					'refund_meta' => array(
						'_provider_refund_id' => 're_native',
					),
				)
			)
		);

		$result          = $this->sut->process_refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 2.50, 'Adjustment' ), $provider );
		$unlinked_refund = wc_get_order( $unlinked_refund->get_id() );
		$linked_refund   = wc_get_order( $linked_refund->get_id() );

		$this->assertTrue( $result );
		$this->assertInstanceOf( WC_Order_Refund::class, $unlinked_refund );
		$this->assertInstanceOf( WC_Order_Refund::class, $linked_refund );
		$this->assertSame( 're_native', $unlinked_refund->get_meta( '_provider_refund_id', true ) );
		$this->assertSame( 're_existing', $linked_refund->get_meta( '_provider_refund_id', true ) );
	}

	/**
	 * @testdox Should preserve provider refund error codes.
	 */
	public function test_process_refund_preserves_provider_error_code(): void {
		$order    = $this->create_woopayments_order( '10.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array(
					'error_code'    => 'uncaptured-payment',
					'error_message' => 'This payment is not captured yet.',
				)
			)
		);

		$result = $this->sut->process_refund( PaymentContext::for_refund( $order, OrderPaymentStore::GATEWAY_ID, 2.50, 'Adjustment' ), $provider );

		$this->assertWPError( $result );
		$this->assertSame( 'uncaptured-payment', $result->get_error_code() );
		$this->assertSame( 'This payment is not captured yet.', $result->get_error_message() );
	}

	/**
	 * @testdox Capture and cancel should use the shared order claim.
	 */
	public function test_capture_and_cancel_use_shared_order_claim(): void {
		$order = $this->create_woopayments_order( '10.00' );
		$this->store->lock_order_payment( $order, 'pi_existing' );

		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'pi_test' ) );

		$capture_outcome = $this->sut->capture( PaymentContext::for_capture( $order, OrderPaymentStore::GATEWAY_ID ), $provider );
		$cancel_outcome  = $this->sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), $provider );

		$this->assertSame( PaymentOutcome::STATUS_FAILED, $capture_outcome->get_status() );
		$this->assertSame( PaymentOutcome::STATUS_FAILED, $cancel_outcome->get_status() );
		$this->assertSame( 0, $provider->capture_calls );
		$this->assertSame( 0, $provider->cancel_calls );

		$this->store->unlock_order_payment( $order );
	}

	/**
	 * @testdox Should support non-Stripe redirect providers through neutral outcomes.
	 */
	public function test_process_checkout_supports_non_stripe_redirect_provider(): void {
		$order    = $this->create_woopayments_order( '12.00' );
		$provider = new NonStripeProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_REQUIRES_REDIRECT,
				'remote_payment_123',
				'https://offline-provider.example/pay/123'
			)
		);

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, 'offline_redirect_provider' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://offline-provider.example/pay/123', $result['redirect'] );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 'remote_payment_123', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Should support non-Stripe asynchronous providers without card data.
	 */
	public function test_process_checkout_supports_non_stripe_pending_async_provider(): void {
		$order    = $this->create_woopayments_order( '12.00' );
		$provider = new NonStripeProvider( new PaymentOutcome( PaymentOutcome::STATUS_PENDING_ASYNC, 'remote_pending_123' ) );

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, 'offline_redirect_provider' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'pending', $order->get_status() );
		$this->assertSame( 'processing', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( '', $order->get_meta( '_payment_method_id', true ) );
	}

	/**
	 * @testdox Should apply canceled provider outcomes to the canceled order lifecycle.
	 */
	public function test_cancel_applies_canceled_lifecycle_state(): void {
		$order    = $this->create_woopayments_order( '12.00' );
		$provider = new RecordingProvider( new PaymentOutcome( PaymentOutcome::STATUS_CANCELED, 'pi_canceled' ) );

		$outcome = $this->sut->cancel( PaymentContext::for_cancel( $order, OrderPaymentStore::GATEWAY_ID ), $provider );
		$order   = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( PaymentOutcome::STATUS_CANCELED, $outcome->get_status() );
		$this->assertSame( 'cancelled', $order->get_status() );
		$this->assertSame( 'canceled', $order->get_meta( '_intention_status', true ) );
		$this->assertSame( 'pi_canceled', $order->get_meta( '_intent_id', true ) );
	}

	/**
	 * @testdox Should support zero-total checkout without a provider-specific payment operation.
	 */
	public function test_process_checkout_supports_non_stripe_zero_total_provider_without_charge(): void {
		$order    = $this->create_woopayments_order( '0.00' );
		$provider = new NonStripeProvider( new PaymentOutcome( PaymentOutcome::STATUS_COMPLETED, 'remote_should_not_be_used' ) );

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, 'offline_redirect_provider' ), $provider );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 0, $provider->charge_calls );
	}

	/**
	 * @testdox Should call setup-capable providers for zero-total checkout when a payment credential is present.
	 */
	public function test_process_checkout_calls_setup_capable_provider_for_zero_total_checkout_with_credential(): void {
		$order    = $this->create_woopayments_order( '0.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_COMPLETED,
				'seti_zero',
				'',
				'pm_zero',
				'cus_zero',
				array(
					'meta' => array(
						'_intention_status' => 'succeeded',
					),
				)
			),
			array( CapabilityManifest::CAPABILITY_ZERO_AMOUNT_SETUP )
		);

		$result = $this->sut->process_checkout( PaymentContext::for_checkout( $order, OrderPaymentStore::GATEWAY_ID, 'pm_zero' ), $provider );
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 1, $provider->charge_calls );
		$this->assertSame( 'seti_zero', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'pm_zero', $order->get_meta( '_payment_method_id', true ) );
	}

	/**
	 * @testdox Should call setup-capable providers for zero-total checkout when a saved payment token is present.
	 */
	public function test_process_checkout_calls_setup_capable_provider_for_zero_total_checkout_with_saved_token(): void {
		$order    = $this->create_woopayments_order( '0.00' );
		$provider = new RecordingProvider(
			new PaymentOutcome(
				PaymentOutcome::STATUS_COMPLETED,
				'seti_saved',
				'',
				'pm_saved',
				'cus_saved',
				array(
					'meta' => array(
						'_intention_status' => 'succeeded',
					),
				)
			),
			array( CapabilityManifest::CAPABILITY_ZERO_AMOUNT_SETUP )
		);

		$result = $this->sut->process_checkout(
			PaymentContext::for_checkout(
				$order,
				OrderPaymentStore::GATEWAY_ID,
				'',
				array( 'payment_token' => '123' )
			),
			$provider
		);
		$order  = wc_get_order( $order->get_id() );

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 1, $provider->charge_calls );
		$this->assertSame( 'seti_saved', $order->get_meta( '_intent_id', true ) );
		$this->assertSame( 'pm_saved', $order->get_meta( '_payment_method_id', true ) );
	}

	/**
	 * Create a WooPayments order for processing tests.
	 *
	 * @param string $total Order total.
	 * @return WC_Order
	 */
	private function create_woopayments_order( string $total ): WC_Order {
		$order = wc_create_order();
		$order->set_payment_method( OrderPaymentStore::GATEWAY_ID );
		$order->set_currency( 'USD' );
		$order->set_total( $total );
		$order->save();

		return $order;
	}

	/**
	 * Assert that an order has a note containing the expected text.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $expected Expected note content.
	 */
	private function assertOrderHasNoteContaining( WC_Order $order, string $expected ): void {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( str_contains( $note->content, $expected ) ) {
				$this->addToAssertionCount( 1 );
				return;
			}
		}

		$this->fail( "Missing order note containing: {$expected}" );
	}
}
