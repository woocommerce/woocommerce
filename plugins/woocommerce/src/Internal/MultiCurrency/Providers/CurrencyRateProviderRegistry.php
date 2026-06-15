<?php
/**
 * CurrencyRateProviderRegistry class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\CurrencyRateProvider;

/**
 * Registry for multi-currency automatic rate providers.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class CurrencyRateProviderRegistry {

	/**
	 * Registered providers keyed by ID.
	 *
	 * @var array<string,CurrencyRateProvider>
	 */
	private array $providers = array();

	/**
	 * Register a rate provider.
	 *
	 * @param CurrencyRateProvider $provider Rate provider.
	 */
	public function register( CurrencyRateProvider $provider ): void {
		$this->providers[ $provider->get_id() ] = $provider;
	}

	/**
	 * Get a provider by ID.
	 *
	 * @param string $provider_id Provider ID.
	 * @return CurrencyRateProvider|null
	 */
	public function get_provider( string $provider_id ): ?CurrencyRateProvider {
		return $this->providers[ $provider_id ] ?? null;
	}

	/**
	 * Get all registered providers.
	 *
	 * @return array<string,CurrencyRateProvider>
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/**
	 * Get the first available provider.
	 *
	 * @return CurrencyRateProvider|null
	 */
	public function get_available_provider(): ?CurrencyRateProvider {
		foreach ( $this->providers as $provider ) {
			if ( $provider->is_available() ) {
				return $provider;
			}
		}

		return null;
	}
}
