<?php
/**
 * CurrencyRateProviderRegistrarInterface interface file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Providers;

/**
 * Registers automatic-rate providers into a registry.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
interface CurrencyRateProviderRegistrarInterface {

	/**
	 * Register rate providers.
	 *
	 * @param CurrencyRateProviderRegistry $registry Rate provider registry.
	 */
	public function register( CurrencyRateProviderRegistry $registry ): void;
}
