<?php
/**
 * WooPaymentsLegacyApiClientAdapter class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyApiClientInterface;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsLegacyRuntime;

/**
 * Adapts the local WooPayments API client to the native multi-currency boundary.
 *
 * @since 11.0.0
 * @internal Transitional bridge while WooPayments provider transport is absorbed into core.
 */
class WooPaymentsLegacyApiClientAdapter implements MultiCurrencyApiClientInterface {

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime ): void {
		$this->legacy_runtime = $legacy_runtime;
	}

	/**
	 * Tell whether the API client is connected to its server.
	 *
	 * @return bool
	 */
	public function is_server_connected(): bool {
		$api_client = $this->get_legacy_api_client();
		if ( ! $api_client || ! is_callable( array( $api_client, 'is_server_connected' ) ) ) {
			return false;
		}

		try {
			return (bool) $api_client->is_server_connected();
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Get currency rates.
	 *
	 * @param string        $currency_from Currency to convert from.
	 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
	 * @return array<string,mixed>
	 */
	public function get_currency_rates( string $currency_from, $currencies_to = null ): array {
		$api_client = $this->get_legacy_api_client();
		if ( ! $api_client || ! is_callable( array( $api_client, 'get_currency_rates' ) ) ) {
			return array();
		}

		try {
			$rates = $api_client->get_currency_rates( $currency_from, $currencies_to );
		} catch ( \Throwable $e ) {
			return array();
		}

		return is_array( $rates ) ? $rates : array();
	}

	/**
	 * Get the local WooPayments API client when the legacy runtime is loaded.
	 *
	 * @return object|null
	 */
	private function get_legacy_api_client(): ?object {
		return $this->legacy_runtime->get_payments_api_client();
	}
}
