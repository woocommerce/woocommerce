<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentOutcome;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;

/**
 * Test provider that records operation calls.
 */
class RecordingProvider implements ProviderContract {

	/**
	 * Outcome returned by all operations.
	 *
	 * @var PaymentOutcome
	 */
	private PaymentOutcome $outcome;

	/**
	 * Number of charge calls.
	 *
	 * @var int
	 */
	public int $charge_calls = 0;

	/**
	 * Number of refund calls.
	 *
	 * @var int
	 */
	public int $refund_calls = 0;

	/**
	 * Number of capture calls.
	 *
	 * @var int
	 */
	public int $capture_calls = 0;

	/**
	 * Number of cancel calls.
	 *
	 * @var int
	 */
	public int $cancel_calls = 0;

	/**
	 * Last idempotency key received.
	 *
	 * @var string
	 */
	public string $last_idempotency_key = '';

	/**
	 * Constructor.
	 *
	 * @param PaymentOutcome $outcome Outcome returned by operations.
	 */
	public function __construct( PaymentOutcome $outcome ) {
		$this->outcome = $outcome;
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
		return CapabilityManifest::from_array( array() );
	}

	/**
	 * Charge an order through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function charge( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		++$this->charge_calls;
		$this->last_idempotency_key = $idempotency_key;

		return $this->outcome;
	}

	/**
	 * Capture a previously authorized payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function capture( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		++$this->capture_calls;
		$this->last_idempotency_key = $idempotency_key;

		return $this->outcome;
	}

	/**
	 * Cancel a previously authorized payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function cancel( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		++$this->cancel_calls;
		$this->last_idempotency_key = $idempotency_key;

		return $this->outcome;
	}

	/**
	 * Refund a payment through the provider.
	 *
	 * @param PaymentContext $context         Payment context.
	 * @param string         $idempotency_key Deterministic idempotency key.
	 * @return PaymentOutcome
	 */
	public function refund( PaymentContext $context, string $idempotency_key ): PaymentOutcome {
		++$this->refund_calls;
		$this->last_idempotency_key = $idempotency_key;

		return $this->outcome;
	}
}
