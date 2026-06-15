<?php
/**
 * CurrencyRateProvider interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Interfaces;

/**
 * Provider-neutral seam for automatic multi-currency FX rates.
 *
 * Manual rates do not require a provider. Automatic rates are supplied through
 * implementations of this seam, including the WooPayments provider in native
 * payments mode.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface CurrencyRateProvider {

	/**
	 * Get the provider identifier.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Tell whether automatic rates are currently available.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Get currency rates.
	 *
	 * @param string        $currency_from Currency to convert from.
	 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
	 * @return array<string,mixed>
	 */
	public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array;
}
