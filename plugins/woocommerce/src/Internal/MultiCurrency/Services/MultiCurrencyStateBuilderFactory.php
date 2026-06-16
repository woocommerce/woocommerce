<?php
/**
 * MultiCurrencyStateBuilderFactory class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistryFactory;

/**
 * Creates native multi-currency state builders with the live rate-provider registry.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStateBuilderFactory {

	/**
	 * Rate provider registry factory.
	 *
	 * @var CurrencyRateProviderRegistryFactory
	 */
	private CurrencyRateProviderRegistryFactory $provider_registry_factory;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param CurrencyRateProviderRegistryFactory $provider_registry_factory Rate provider registry factory.
	 */
	final public function init( CurrencyRateProviderRegistryFactory $provider_registry_factory ): void {
		$this->provider_registry_factory = $provider_registry_factory;
	}

	/**
	 * Create a state builder.
	 *
	 * @param MultiCurrencyLocalizationInterface|null $localization_service Optional localization boundary.
	 * @param MultiCurrencyCacheInterface|null        $cache                Optional cache boundary.
	 * @return MultiCurrencyStateBuilder
	 */
	public function create(
		?MultiCurrencyLocalizationInterface $localization_service = null,
		?MultiCurrencyCacheInterface $cache = null
	): MultiCurrencyStateBuilder {
		$localization_service = $localization_service ?? new MultiCurrencyLocalizationService();
		$cache                = $cache ?? new MultiCurrencyDatabaseCache();

		return new MultiCurrencyStateBuilder(
			$localization_service,
			new MultiCurrencyRateService( $this->provider_registry_factory->create() ),
			$cache
		);
	}
}
