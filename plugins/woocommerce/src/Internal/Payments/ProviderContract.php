<?php
/**
 * ProviderContract interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

/**
 * Internal metadata contract implemented by native payments providers.
 *
 * A3 introduces money-moving operation contracts used by the native processing service.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
interface ProviderContract {

	/**
	 * Get the provider/gateway ID.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Get the provider capability manifest.
	 *
	 * @return CapabilityManifest
	 */
	public function get_capability_manifest(): CapabilityManifest;

	/**
	 * Charge an order through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function charge( PaymentContext $context, string $idempotency_key ): PaymentOutcome;

	/**
	 * Capture a previously authorized payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, string $idempotency_key ): PaymentOutcome;

	/**
	 * Cancel a previously authorized payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, string $idempotency_key ): PaymentOutcome;

	/**
	 * Refund a payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function refund( PaymentContext $context, string $idempotency_key ): PaymentOutcome;
}
