<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

/**
 * Deliberately non-WooPayments provider for genericity validation.
 */
class NonStripeProvider extends RecordingProvider {

	/**
	 * Get the provider/gateway ID.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return 'offline_redirect_provider';
	}
}
