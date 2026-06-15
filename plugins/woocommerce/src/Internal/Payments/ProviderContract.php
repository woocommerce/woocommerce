<?php
/**
 * ProviderContract interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

/**
 * Internal metadata contract implemented by native payments providers.
 *
 * A1 intentionally exposes provider identity and capability metadata only. Money-moving operation
 * contracts are introduced with the A2/A3 call sites that need them.
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
}
