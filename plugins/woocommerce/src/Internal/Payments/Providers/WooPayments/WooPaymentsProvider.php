<?php
/**
 * WooPaymentsProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\WooCommerce\Internal\Payments\CapabilityManifest;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\ProviderContract;

/**
 * First-party WooPayments provider skeleton for the native payments runtime.
 *
 * A1 intentionally exposes identity and capability shape only. Mutating operation contracts are not
 * published until native processing introduces real call sites in later stages.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsProvider implements ProviderContract {

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
}
