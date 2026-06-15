<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\PaymentContext;
use Automattic\WooCommerce\Internal\Payments\PaymentProcessingService;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;

/**
 * Recording processing service for native gateway tests.
 */
class RecordingPaymentProcessingService extends PaymentProcessingService {

	/**
	 * Last checkout context.
	 *
	 * @var PaymentContext|null
	 */
	public ?PaymentContext $last_checkout_context = null;

	/**
	 * Last refund context.
	 *
	 * @var PaymentContext|null
	 */
	public ?PaymentContext $last_refund_context = null;

	/**
	 * Process checkout payment through a provider.
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return array<string,string>
	 */
	public function process_checkout( PaymentContext $context, ProviderContract $provider ): array {
		$this->last_checkout_context = $context;

		return array(
			'result'   => 'success',
			'redirect' => 'https://example.test/order-received',
		);
	}

	/**
	 * Process a refund through a provider.
	 *
	 * @param PaymentContext   $context  Payment context.
	 * @param ProviderContract $provider Provider.
	 * @return bool
	 */
	public function process_refund( PaymentContext $context, ProviderContract $provider ) {
		$this->last_refund_context = $context;

		return true;
	}
}
