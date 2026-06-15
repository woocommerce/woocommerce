<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;

/**
 * Static WooPayments provider for registry tests.
 */
class StaticWooPaymentsProvider extends WooPaymentsProvider {

	/**
	 * Whether the provider can process payments.
	 *
	 * @var bool
	 */
	private bool $can_process_payments;

	/**
	 * Constructor.
	 *
	 * @param bool $can_process_payments Whether the provider can process payments.
	 */
	public function __construct( bool $can_process_payments ) {
		$this->can_process_payments = $can_process_payments;
	}

	/**
	 * Tell whether WooPayments can currently process native money operations.
	 *
	 * @return bool
	 */
	public function can_process_payments(): bool {
		return $this->can_process_payments;
	}
}
