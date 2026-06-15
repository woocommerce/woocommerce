<?php
/**
 * MultiCurrencyAccountInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Interfaces;

/**
 * Account boundary used by provider-backed multi-currency rate sources.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface MultiCurrencyAccountInterface {

	/**
	 * Tell whether the rate provider account is connected.
	 *
	 * @param bool $on_error Value to return on provider errors.
	 * @return bool
	 */
	public function is_provider_connected( bool $on_error = false ): bool;

	/**
	 * Tell whether the connected account is rejected.
	 *
	 * @return bool
	 */
	public function is_account_rejected(): bool;

	/**
	 * Get cached provider account data.
	 *
	 * @param bool $force_refresh Whether to force-refresh provider data.
	 * @return array<string,mixed>|bool
	 */
	public function get_cached_account_data( bool $force_refresh = false );

	/**
	 * Get account-supported customer currencies.
	 *
	 * @return string[]
	 */
	public function get_account_customer_supported_currencies(): array;

	/**
	 * Get provider-supported countries.
	 *
	 * @return string[]
	 */
	public function get_supported_countries(): array;

	/**
	 * Get the provider onboarding URL.
	 *
	 * @return string
	 */
	public function get_provider_onboarding_page_url(): string;
}
