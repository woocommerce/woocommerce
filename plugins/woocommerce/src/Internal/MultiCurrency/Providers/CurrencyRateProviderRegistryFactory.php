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
	 * WooPayments account adapter.
	 *
	 * @var WooPaymentsLegacyAccountAdapter
	 */
	private WooPaymentsLegacyAccountAdapter $account_adapter;

	/**
	 * WooPayments API client adapter.
	 *
	 * @var WooPaymentsLegacyApiClientAdapter
	 */
	private WooPaymentsLegacyApiClientAdapter $api_client_adapter;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyAccountAdapter   $account_adapter    WooPayments account adapter.
	 * @param WooPaymentsLegacyApiClientAdapter $api_client_adapter WooPayments API client adapter.
	 */
	final public function init( WooPaymentsLegacyAccountAdapter $account_adapter, WooPaymentsLegacyApiClientAdapter $api_client_adapter ): void {
		$this->account_adapter    = $account_adapter;
		$this->api_client_adapter = $api_client_adapter;
	}

	/**
	 * Create a fresh rate provider registry.
	 *
	 * @return CurrencyRateProviderRegistry
	 */
	public function create(): CurrencyRateProviderRegistry {
		$registry = new CurrencyRateProviderRegistry();
		$registry->register( new WooPaymentsCurrencyRateProvider( $this->account_adapter, $this->api_client_adapter ) );

		return $registry;
	}
}
