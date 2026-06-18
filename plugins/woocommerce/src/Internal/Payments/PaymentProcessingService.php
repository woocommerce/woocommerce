<?php
/**
 * PaymentProcessingService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use Throwable;
use WC_Order;
use WC_Order_Refund;
use WP_Error;

/**
 * Generic native payment processing template.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentProcessingService {

	/**
	 * Order payment store.
	 *
	 * @var OrderPaymentStore
	 */
	private OrderPaymentStore $order_payment_store;

	/**
	 * Order payment lifecycle service.
	 *
	 * @var OrderPaymentLifecycleService
	 */
	private OrderPaymentLifecycleService $lifecycle_service;

	/**
	 * Payment operation idempotency service.
	 *
	 * @var PaymentOperationIdempotency
	 */
	private PaymentOperationIdempotency $idempotency;

	/**
	 * Payment exception policy.
	 *
	 * @var PaymentExceptionPolicy
	 */
	private PaymentExceptionPolicy $exception_policy;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param OrderPaymentStore            $order_payment_store Order payment store.
	 * @param OrderPaymentLifecycleService $lifecycle_service  Order payment lifecycle service.
	 * @param PaymentOperationIdempotency  $idempotency          Payment operation idempotency service.
	 * @param PaymentExceptionPolicy       $exception_policy     Payment exception policy.
	 */
	final public function init(
		OrderPaymentStore $order_payment_store,
		OrderPaymentLifecycleService $lifecycle_service,
		PaymentOperationIdempotency $idempotency,
		PaymentExceptionPolicy $exception_policy
	): void {
		$this->order_payment_store = $order_payment_store;
		$this->lifecycle_service   = $lifecycle_service;
		$this->idempotency         = $idempotency;
		$this->exception_policy    = $exception_policy;
	}

	/**
	 * Process checkout payment through a provider.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return array<string,string>
	 */
	public function process_checkout( PaymentContext $context, ProviderContract $provider ): array {
		$outcome = $this->process_checkout_outcome( $context, $provider );

		return $this->format_checkout_result( $context, $context->get_order(), $outcome );
	}

	/**
	 * Process checkout payment through a provider and return the neutral outcome.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return PaymentOutcome
	 */
	public function process_checkout_outcome( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
		$order           = $context->get_order();
		$amount          = (float) $order->get_total();
		$currency        = (string) $order->get_currency();
		$idempotency_key = $this->idempotency->derive_key( $order, $provider->get_id(), 'charge', $amount, $currency );

		if ( ! $this->order_payment_store->claim_order_payment_lock( $order, $idempotency_key ) ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array(
					'error_message' => __( 'A payment operation is already in progress for this order.', 'woocommerce' ),
				)
			);
		}

		try {
			$outcome = 0.0 >= $amount && ! $this->should_call_provider_for_zero_total_checkout( $context, $provider )
				? new PaymentOutcome( PaymentOutcome::STATUS_NO_EXTERNAL_PAYMENT )
				: $this->charge_provider( $context, $provider, $idempotency_key );

			$this->apply_checkout_outcome( $order, $outcome );

			return $outcome;
		} finally {
			$this->order_payment_store->unlock_order_payment( $order );
		}
	}

	/**
	 * Process a refund through a provider.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return bool|WP_Error
	 */
	public function process_refund( PaymentContext $context, ProviderContract $provider ) {
		$order        = $context->get_order();
		$payment_data = $context->get_payment_data();
		$amount       = isset( $payment_data['amount'] ) ? (float) $payment_data['amount'] : 0.0;
		$reason       = isset( $payment_data['reason'] ) ? (string) $payment_data['reason'] : '';

		if ( '0.00' === sprintf( '%0.2f', $amount ) ) {
			return true;
		}

		$idempotency_key = $this->idempotency->derive_key( $order, $provider->get_id(), 'refund', $amount, (string) $order->get_currency(), $reason );
		if ( ! $this->order_payment_store->claim_order_payment_lock( $order, $idempotency_key ) ) {
			return new WP_Error( 'native_payment_refund_locked', __( 'A refund is already in progress for this order.', 'woocommerce' ) );
		}

		try {
			try {
				$outcome = $provider->refund( $context, $idempotency_key );
			} catch ( Throwable $exception ) {
				$outcome = $this->exception_policy->to_failed_outcome( $exception );
			}

			if ( $outcome->is_successful() ) {
				$this->apply_refund_outcome( $order, $outcome, $amount, $reason );
			}
		} finally {
			$this->order_payment_store->unlock_order_payment( $order );
		}

		if ( $outcome->is_successful() ) {
			return true;
		}

		$data          = $outcome->get_data();
		$error_code    = isset( $data['error_code'] ) && '' !== (string) $data['error_code'] ? (string) $data['error_code'] : 'native_payment_refund_failed';
		$error_message = isset( $data['error_message'] ) ? (string) $data['error_message'] : __( 'The refund failed.', 'woocommerce' );

		return new WP_Error( $error_code, $error_message );
	}

	/**
	 * Apply provider refund metadata to the matching WooCommerce refund.
	 *
	 * @param WC_Order       $order   Parent order.
	 * @param PaymentOutcome $outcome Provider refund outcome.
	 * @param float          $amount  Refund amount.
	 * @param string         $reason  Refund reason.
	 */
	private function apply_refund_outcome( WC_Order $order, PaymentOutcome $outcome, float $amount, string $reason ): void {
		$data        = $outcome->get_data();
		$refund_meta = isset( $data['refund_meta'] ) && is_array( $data['refund_meta'] ) ? $data['refund_meta'] : array();
		$order_meta  = isset( $data['order_meta'] ) && is_array( $data['order_meta'] ) ? $data['order_meta'] : array();
		$refund_note = isset( $data['refund_note'] ) && is_string( $data['refund_note'] ) ? $data['refund_note'] : '';

		if ( empty( $refund_meta ) && empty( $order_meta ) && '' === $refund_note ) {
			return;
		}

		$matched_refund = $this->find_matching_refund( $order, $amount, $reason, array_map( 'strval', array_keys( $refund_meta ) ) );
		$reloaded_order = wc_get_order( $order->get_id() );
		if ( ! $matched_refund instanceof WC_Order_Refund ) {
			return;
		}
		if ( ! $reloaded_order instanceof WC_Order ) {
			return;
		}

		foreach ( $refund_meta as $meta_key => $meta_value ) {
			$matched_refund->update_meta_data( (string) $meta_key, $meta_value );
		}
		$matched_refund->save_meta_data();

		foreach ( $order_meta as $meta_key => $meta_value ) {
			$reloaded_order->update_meta_data( (string) $meta_key, $meta_value );
		}

		if ( '' !== $refund_note ) {
			$this->maybe_add_refund_note( $reloaded_order, $refund_note );
		}
		$reloaded_order->save_meta_data();
	}

	/**
	 * Find the local refund row created before the gateway refund call.
	 *
	 * @param WC_Order $order  Parent order.
	 * @param float    $amount Refund amount.
	 * @param string   $reason Refund reason.
	 * @param string[] $provider_refund_meta_keys Provider refund meta keys that mark a refund as linked.
	 * @return WC_Order_Refund|null
	 */
	private function find_matching_refund( WC_Order $order, float $amount, string $reason, array $provider_refund_meta_keys ): ?WC_Order_Refund {
		$reloaded_order = wc_get_order( $order->get_id() );
		if ( ! $reloaded_order instanceof WC_Order ) {
			return null;
		}

		$expected_amount = wc_format_decimal( $amount );
		$refunds         = array_reverse( $reloaded_order->get_refunds() );

		foreach ( $refunds as $refund ) {
			if ( ! $refund instanceof WC_Order_Refund ) {
				continue;
			}

			if ( $this->refund_has_any_meta_value( $refund, $provider_refund_meta_keys ) ) {
				continue;
			}

			if ( wc_format_decimal( $refund->get_amount() ) !== $expected_amount ) {
				continue;
			}

			if ( '' !== $reason && $reason !== (string) $refund->get_reason() ) {
				continue;
			}

			return $refund;
		}

		return null;
	}

	/**
	 * Tell whether a refund already has any provider supplied link meta.
	 *
	 * @param WC_Order_Refund $refund    Refund object.
	 * @param string[]        $meta_keys Provider refund meta keys.
	 * @return bool
	 */
	private function refund_has_any_meta_value( WC_Order_Refund $refund, array $meta_keys ): bool {
		foreach ( $meta_keys as $meta_key ) {
			if ( '' === $meta_key ) {
				continue;
			}

			$meta_value = $refund->get_meta( $meta_key, true );
			if ( null !== $meta_value && '' !== $meta_value && array() !== $meta_value ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add a WooPayments-compatible provider refund note if it does not already exist.
	 *
	 * @param WC_Order $order Parent order.
	 * @param string   $note  Provider refund note.
	 */
	private function maybe_add_refund_note( WC_Order $order, string $note ): void {
		if ( ! $this->order_note_exists( $order, $note ) ) {
			$order->add_order_note( $note );
		}
	}

	/**
	 * Tell whether an order already has a note.
	 *
	 * @param WC_Order $order        Order object.
	 * @param string   $note_content Note content.
	 * @return bool
	 */
	private function order_note_exists( WC_Order $order, string $note_content ): bool {
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order->get_id(),
				'type'     => 'any',
			)
		);

		foreach ( $notes as $note ) {
			if ( $note_content === $note->content ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Capture an authorized payment through a provider.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
		return $this->run_provider_order_operation( $context, $provider, 'capture' );
	}

	/**
	 * Cancel an authorized payment through a provider.
	 *
	 * @since 11.0.0
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, ProviderContract $provider ): PaymentOutcome {
		return $this->run_provider_order_operation( $context, $provider, 'cancel' );
	}

	/**
	 * Charge a provider and normalize exceptions.
	 *
	 * @param PaymentContext   $context         Payment context.
	 * @param ProviderContract $provider        Provider.
	 * @param string           $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	private function charge_provider( PaymentContext $context, ProviderContract $provider, string $idempotency_key ): PaymentOutcome {
		try {
			return $provider->charge( $context, $idempotency_key );
		} catch ( Throwable $exception ) {
			return $this->exception_policy->to_failed_outcome( $exception );
		}
	}

	/**
	 * Tell whether a zero-total checkout still needs a provider-owned setup operation.
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return bool
	 */
	private function should_call_provider_for_zero_total_checkout( PaymentContext $context, ProviderContract $provider ): bool {
		if ( ! $provider->get_capability_manifest()->supports( CapabilityManifest::CAPABILITY_ZERO_AMOUNT_SETUP ) ) {
			return false;
		}

		return '' !== $context->get_payment_method_id() || $this->has_saved_payment_token( $context );
	}

	/**
	 * Tell whether checkout context carries an existing saved payment token.
	 *
	 * @param PaymentContext $context Payment context.
	 * @return bool
	 */
	private function has_saved_payment_token( PaymentContext $context ): bool {
		$payment_data  = $context->get_payment_data();
		$payment_token = isset( $payment_data['payment_token'] ) ? (string) $payment_data['payment_token'] : '';

		return '' !== $payment_token && 'new' !== $payment_token;
	}

	/**
	 * Run a capture/cancel provider operation under the shared order lock.
	 *
	 * @param PaymentContext   $context   Payment context.
	 * @param ProviderContract $provider  Provider.
	 * @param string           $operation Operation name.
	 * @return PaymentOutcome
	 */
	private function run_provider_order_operation( PaymentContext $context, ProviderContract $provider, string $operation ): PaymentOutcome {
		$order           = $context->get_order();
		$idempotency_key = $this->idempotency->derive_key( $order, $provider->get_id(), $operation, (float) $order->get_total(), (string) $order->get_currency() );

		if ( ! $this->order_payment_store->claim_order_payment_lock( $order, $idempotency_key ) ) {
			return new PaymentOutcome(
				PaymentOutcome::STATUS_FAILED,
				'',
				'',
				'',
				'',
				array( 'error_message' => __( 'A payment operation is already in progress for this order.', 'woocommerce' ) )
			);
		}

		try {
			try {
				$outcome = 'capture' === $operation
					? $provider->capture( $context, $idempotency_key )
					: $provider->cancel( $context, $idempotency_key );
			} catch ( Throwable $exception ) {
				$outcome = $this->exception_policy->to_failed_outcome( $exception );
			}

			$this->apply_checkout_outcome( $order, $outcome );

			return $outcome;
		} finally {
			$this->order_payment_store->unlock_order_payment( $order );
		}
	}

	/**
	 * Apply a provider checkout outcome to the order lifecycle.
	 *
	 * @param WC_Order       $order   Order object.
	 * @param PaymentOutcome $outcome Provider outcome.
	 */
	private function apply_checkout_outcome( WC_Order $order, PaymentOutcome $outcome ): void {
		$this->lifecycle_service->apply_unlocked(
			$order,
			new PaymentLifecycleEvent(
				$this->get_lifecycle_status( $outcome ),
				$this->get_lifecycle_payment_reference( $outcome ),
				$this->get_lifecycle_meta( $outcome ),
				array(),
				$this->get_lifecycle_note( $outcome )
			)
		);
	}

	/**
	 * Map a provider outcome status to an order lifecycle status.
	 *
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return string
	 */
	private function get_lifecycle_status( PaymentOutcome $outcome ): string {
		switch ( $outcome->get_status() ) {
			case PaymentOutcome::STATUS_COMPLETED:
			case PaymentOutcome::STATUS_NO_EXTERNAL_PAYMENT:
				return PaymentLifecycleEvent::STATUS_COMPLETED;

			case PaymentOutcome::STATUS_AUTHORIZED:
				return PaymentLifecycleEvent::STATUS_AUTHORIZED;

			case PaymentOutcome::STATUS_FAILED:
				return PaymentLifecycleEvent::STATUS_FAILED;

			case PaymentOutcome::STATUS_CANCELED:
				return PaymentLifecycleEvent::STATUS_CANCELED;

			case PaymentOutcome::STATUS_PENDING_ASYNC:
			case PaymentOutcome::STATUS_REQUIRES_REDIRECT:
			case PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION:
				return PaymentLifecycleEvent::STATUS_STARTED;
		}

		return PaymentLifecycleEvent::STATUS_FAILED;
	}

	/**
	 * Build lifecycle meta from a provider outcome.
	 *
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return array<string,string>
	 */
	private function get_lifecycle_meta( PaymentOutcome $outcome ): array {
		$data = $outcome->get_data();
		$meta = isset( $data['meta'] ) && is_array( $data['meta'] ) ? $data['meta'] : array();

		if ( '' !== $outcome->get_provider_payment_id() ) {
			$meta['_intent_id'] = $outcome->get_provider_payment_id();
		}

		if ( '' !== $outcome->get_payment_method_id() ) {
			$meta['_payment_method_id'] = $outcome->get_payment_method_id();
		}

		if ( '' !== $outcome->get_customer_id() ) {
			$meta['_stripe_customer_id'] = $outcome->get_customer_id();
		}

		if ( ! isset( $meta['_intention_status'] ) ) {
			$meta['_intention_status'] = $this->get_default_intention_status( $outcome );
		}

		ksort( $meta );

		return array_map( 'strval', $meta );
	}

	/**
	 * Get a default WooPayments-compatible intention status for an outcome.
	 *
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return string
	 */
	private function get_default_intention_status( PaymentOutcome $outcome ): string {
		switch ( $outcome->get_status() ) {
			case PaymentOutcome::STATUS_COMPLETED:
			case PaymentOutcome::STATUS_NO_EXTERNAL_PAYMENT:
				return 'succeeded';

			case PaymentOutcome::STATUS_AUTHORIZED:
				return 'requires_capture';

			case PaymentOutcome::STATUS_PENDING_ASYNC:
				return 'processing';

			case PaymentOutcome::STATUS_REQUIRES_REDIRECT:
			case PaymentOutcome::STATUS_REQUIRES_CUSTOMER_ACTION:
				return 'requires_action';

			case PaymentOutcome::STATUS_FAILED:
				return 'requires_payment_method';

			case PaymentOutcome::STATUS_CANCELED:
				return 'canceled';
		}

		return '';
	}

	/**
	 * Get the lifecycle payment reference from an outcome.
	 *
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return string|null
	 */
	private function get_lifecycle_payment_reference( PaymentOutcome $outcome ): ?string {
		return '' === $outcome->get_provider_payment_id() ? null : $outcome->get_provider_payment_id();
	}

	/**
	 * Get the order note from an outcome.
	 *
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return string|null
	 */
	private function get_lifecycle_note( PaymentOutcome $outcome ): ?string {
		$data = $outcome->get_data();

		if ( isset( $data['note'] ) && is_string( $data['note'] ) && '' !== $data['note'] ) {
			return $data['note'];
		}

		return null;
	}

	/**
	 * Format a WooCommerce checkout result from an outcome.
	 *
	 * @param PaymentContext $context Payment context.
	 * @param WC_Order       $order   Order object.
	 * @param PaymentOutcome $outcome Provider outcome.
	 * @return array<string,string>
	 */
	private function format_checkout_result( PaymentContext $context, WC_Order $order, PaymentOutcome $outcome ): array {
		if ( PaymentOutcome::STATUS_FAILED === $outcome->get_status() ) {
			return array(
				'result'         => 'fail',
				'redirect'       => '',
				'payment_method' => '',
			);
		}

		$payment_method_id = '' !== $outcome->get_payment_method_id() ? $outcome->get_payment_method_id() : $context->get_payment_method_id();
		$data              = $outcome->get_data();
		$redirect          = array_key_exists( 'checkout_redirect', $data )
			? (string) $data['checkout_redirect']
			: ( '' !== $outcome->get_redirect_url() ? $outcome->get_redirect_url() : $order->get_checkout_order_received_url() );

		return array(
			'result'         => 'success',
			'redirect'       => $redirect,
			'payment_method' => $payment_method_id,
		);
	}
}
