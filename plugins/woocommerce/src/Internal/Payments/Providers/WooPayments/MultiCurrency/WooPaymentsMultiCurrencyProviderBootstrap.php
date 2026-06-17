<?php
/**
 * WooPaymentsMultiCurrencyProviderBootstrap class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistryFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\MultiCurrencyProviderAccountResolver;
use Automattic\WooCommerce\Internal\RegisterHooksInterface;

/**
 * Configures WooPayments-owned native multi-currency provider boundaries.
 *
 * @since 11.0.0
 * @internal Transitional bootstrap while WooPayments multi-currency runtime is absorbed into core.
 */
class WooPaymentsMultiCurrencyProviderBootstrap implements RegisterHooksInterface {

	/**
	 * Provider account resolver.
	 *
	 * @var MultiCurrencyProviderAccountResolver
	 */
	private MultiCurrencyProviderAccountResolver $account_resolver;

	/**
	 * WooPayments account adapter.
	 *
	 * @var WooPaymentsLegacyAccountAdapter
	 */
	private WooPaymentsLegacyAccountAdapter $account_adapter;

	/**
	 * Rate provider registry factory.
	 *
	 * @var CurrencyRateProviderRegistryFactory
	 */
	private CurrencyRateProviderRegistryFactory $provider_registry_factory;

	/**
	 * WooPayments rate provider registrar.
	 *
	 * @var WooPaymentsCurrencyRateProviderRegistrar
	 */
	private WooPaymentsCurrencyRateProviderRegistrar $provider_registrar;

	/**
	 * Initialize the bootstrap.
	 *
	 * @internal
	 *
	 * @param MultiCurrencyProviderAccountResolver     $account_resolver          Provider account resolver.
	 * @param WooPaymentsLegacyAccountAdapter          $account_adapter           WooPayments account adapter.
	 * @param CurrencyRateProviderRegistryFactory      $provider_registry_factory Rate provider registry factory.
	 * @param WooPaymentsCurrencyRateProviderRegistrar $provider_registrar    WooPayments rate provider registrar.
	 */
	final public function init(
		MultiCurrencyProviderAccountResolver $account_resolver,
		WooPaymentsLegacyAccountAdapter $account_adapter,
		CurrencyRateProviderRegistryFactory $provider_registry_factory,
		WooPaymentsCurrencyRateProviderRegistrar $provider_registrar
	): void {
		$this->account_resolver          = $account_resolver;
		$this->account_adapter           = $account_adapter;
		$this->provider_registry_factory = $provider_registry_factory;
		$this->provider_registrar        = $provider_registrar;
	}

	/**
	 * Register WooPayments multi-currency provider boundaries.
	 *
	 * @since 11.0.0
	 */
	public function register(): void {
		$this->account_resolver->set_account( $this->account_adapter );
		$this->provider_registry_factory->set_provider_registrars( array( $this->provider_registrar ) );
	}
}
