<?php
/**
 * WooPaymentsCurrencyRateProvider class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\CurrencyRateProvider;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyApiClientInterface;

/**
 * WooPayments-backed automatic FX rate provider.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class WooPaymentsCurrencyRateProvider implements CurrencyRateProvider {

	const PROVIDER_ID = 'woopayments';

	/**
	 * Account boundary.
	 *
	 * @var MultiCurrencyAccountInterface
	 */
	private MultiCurrencyAccountInterface $account;

	/**
	 * API client boundary.
	 *
	 * @var MultiCurrencyApiClientInterface
	 */
	private MultiCurrencyApiClientInterface $api_client;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyAccountInterface   $account    Account boundary.
	 * @param MultiCurrencyApiClientInterface $api_client API client boundary.
	 */
	public function __construct( MultiCurrencyAccountInterface $account, MultiCurrencyApiClientInterface $api_client ) {
		$this->account    = $account;
		$this->api_client = $api_client;
	}

	/**
	 * Get the provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return self::PROVIDER_ID;
	}

	/**
	 * Tell whether automatic rates are currently available.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		return $this->api_client->is_server_connected()
			&& $this->account->is_provider_connected()
			&& ! $this->account->is_account_rejected();
	}

	/**
	 * Get currency rates.
	 *
	 * @param string        $currency_from Currency to convert from.
	 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
	 * @return array<string,mixed>
	 */
	public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array {
		return $this->api_client->get_currency_rates( $currency_from, $currencies_to );
	}
}
