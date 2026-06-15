<?php
/**
 * MultiCurrencyCacheInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Interfaces;

/**
 * Cache boundary for the native multi-currency runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface MultiCurrencyCacheInterface {

	const CURRENCIES_KEY = 'wcpay_multi_currency_cached_currencies';

	/**
	 * Get a value from cache.
	 *
	 * @param string $key   Cache key.
	 * @param bool   $force Whether to return cached data without checking expiry.
	 * @return mixed
	 */
	public function get( string $key, bool $force = false );

	/**
	 * Get a value from cache or regenerate and store it.
	 *
	 * @param string   $key           Cache key.
	 * @param callable $generator     Regenerates missing data.
	 * @param callable $validate_data Validates cached data.
	 * @param bool     $force_refresh Whether to force regeneration.
	 * @param bool     $refreshed     Set true when cache is refreshed successfully.
	 * @return mixed|null
	 */
	public function get_or_add( string $key, callable $generator, callable $validate_data, bool $force_refresh = false, bool &$refreshed = false );

	/**
	 * Delete a cache value.
	 *
	 * @param string $key Cache key.
	 */
	public function delete( string $key ): void;
}
