<?php
/**
 * MultiCurrencyApiClientInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Interfaces;

/**
 * API-client boundary used by provider-backed multi-currency rate sources.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface MultiCurrencyApiClientInterface {

	/**
	 * Tell whether the API client is connected to its server.
	 *
	 * @return bool
	 */
	public function is_server_connected(): bool;

	/**
	 * Get currency rates.
	 *
	 * @param string        $currency_from Currency to convert from.
	 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
	 * @return array<string,mixed>
	 */
	public function get_currency_rates( string $currency_from, $currencies_to = null ): array;
}
