<?php
/**
 * CurrencyRateProviderRegistryFactory class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Providers;

/**
 * Creates native multi-currency automatic-rate provider registries.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class CurrencyRateProviderRegistryFactory {

	/**
	 * Rate provider registrars.
	 *
	 * @var CurrencyRateProviderRegistrarInterface[]
	 */
	private array $provider_registrars = array();

	/**
	 * Set explicit rate-provider registrars.
	 *
	 * @internal Used by tests and provider bootstrap wiring.
	 *
	 * @param CurrencyRateProviderRegistrarInterface[] $provider_registrars Provider registrars.
	 */
	public function set_provider_registrars( array $provider_registrars ): void {
		$this->provider_registrars = array_values( $provider_registrars );
	}

	/**
	 * Create a fresh rate provider registry.
	 *
	 * @return CurrencyRateProviderRegistry
	 */
	public function create(): CurrencyRateProviderRegistry {
		$registry = new CurrencyRateProviderRegistry();

		foreach ( $this->provider_registrars as $provider_registrar ) {
			$provider_registrar->register( $registry );
		}

		return $registry;
	}
}
