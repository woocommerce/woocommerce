<?php
/**
 * WooPaymentsProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;

/**
 * First-party WooPayments provider skeleton for the native payments runtime.
 *
 * A3 exposes WooPayments money-moving operations behind the provider contract.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsProvider implements ProviderContract {

	/**
	 * WooPayments gateway adapter.
	 *
	 * @var WooPaymentsProviderGatewayAdapter
	 */
	private WooPaymentsProviderGatewayAdapter $gateway_adapter;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsProviderGatewayAdapter $gateway_adapter WooPayments gateway adapter.
	 */
	final public function init( WooPaymentsProviderGatewayAdapter $gateway_adapter ): void {
		$this->gateway_adapter = $gateway_adapter;
	}

	/**
	 * Get the provider/gateway ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return OrderPaymentStore::GATEWAY_ID;
	}

	/**
	 * Get the provider capability manifest.
	 *
	 * @return CapabilityManifest
	 */
	public function get_capability_manifest(): CapabilityManifest {
		return CapabilityManifest::from_array(
			array(
				CapabilityManifest::CAPABILITY_CARDS,
				CapabilityManifest::CAPABILITY_SAVED_TOKENS,
				CapabilityManifest::CAPABILITY_MANDATES,
				CapabilityManifest::CAPABILITY_ASYNC_REDIRECT,
				CapabilityManifest::CAPABILITY_REFUNDS,
				CapabilityManifest::CAPABILITY_PARTIAL_REFUNDS,
				CapabilityManifest::CAPABILITY_MANUAL_CAPTURE,
				CapabilityManifest::CAPABILITY_EXPRESS_CHECKOUT,
				CapabilityManifest::CAPABILITY_HOSTED_SESSION,
				CapabilityManifest::CAPABILITY_SUBSCRIPTIONS,
				CapabilityManifest::CAPABILITY_IN_PERSON,
			)
		);
	}

	/**
	 * Tell whether WooPayments can currently process native money operations.
	 *
	 * @return bool
	 */
	public function can_process_payments(): bool {
		return $this->get_gateway_adapter()->is_available();
	}

	/**
	 * Charge an order through WooPayments.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function charge( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		return $this->get_gateway_adapter()->charge( $context, $idempotency_key );
	}

	/**
	 * Capture an authorized WooPayments charge.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		return $this->get_gateway_adapter()->capture( $context, $idempotency_key );
	}

	/**
	 * Cancel an authorized WooPayments charge.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		return $this->get_gateway_adapter()->cancel( $context, $idempotency_key );
	}

	/**
	 * Refund a WooPayments charge.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function refund( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		return $this->get_gateway_adapter()->refund( $context, $idempotency_key );
	}

	/**
	 * Get the WooPayments gateway adapter.
	 *
	 * @return WooPaymentsProviderGatewayAdapter
	 */
	private function get_gateway_adapter(): WooPaymentsProviderGatewayAdapter {
		return $this->gateway_adapter;
	}
}
