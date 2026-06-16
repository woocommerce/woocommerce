<?php
/**
 * MultiCurrencyRateService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;

/**
 * Resolves multi-currency exchange rates for the native runtime.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyRateService {

	/**
	 * Rate provider registry.
	 *
	 * @var CurrencyRateProviderRegistry
	 */
	private CurrencyRateProviderRegistry $provider_registry;

	/**
	 * Constructor.
	 *
	 * @param CurrencyRateProviderRegistry $provider_registry Rate provider registry.
	 */
	public function __construct( CurrencyRateProviderRegistry $provider_registry ) {
		$this->provider_registry = $provider_registry;
	}

	/**
	 * Tell whether an automatic-rate provider is available.
	 *
	 * @return bool
	 *
	 * @since 11.0.0
	 */
	public function has_available_provider(): bool {
		return null !== $this->provider_registry->get_available_provider();
	}

	/**
	 * Get the exchange rate for a target currency.
	 *
	 * @param string $from_currency Source currency.
	 * @param string $to_currency   Target currency.
	 * @return float|null
	 */
	public function get_rate( string $from_currency, string $to_currency ): ?float {
		$from_currency = strtolower( $from_currency );
		$to_currency   = strtolower( $to_currency );
		$rate_type     = get_option( 'wcpay_multi_currency_exchange_rate_' . $to_currency, 'automatic' );

		if ( 'manual' === $rate_type ) {
			return $this->get_manual_rate( $to_currency );
		}

		$provider = $this->provider_registry->get_available_provider();
		if ( ! $provider ) {
			return null;
		}

		$rates = $provider->get_currency_rates( $from_currency, array( $to_currency ) );
		if ( isset( $rates['currencies'] ) && is_array( $rates['currencies'] ) ) {
			$rates = $rates['currencies'];
		}

		return $this->extract_rate( $rates, $to_currency );
	}

	/**
	 * Get automatic rates from the available provider.
	 *
	 * @param string        $from_currency Source currency.
	 * @param string[]|null $currencies_to Target currencies, or null for all supported.
	 * @return array<string,float>|null
	 *
	 * @since 11.0.0
	 */
	public function get_rates( string $from_currency, ?array $currencies_to = null ): ?array {
		$provider = $this->provider_registry->get_available_provider();
		if ( ! $provider ) {
			return null;
		}

		try {
			$rates = $provider->get_currency_rates(
				strtolower( $from_currency ),
				null === $currencies_to ? null : array_map( 'strtolower', $currencies_to )
			);
		} catch ( \Throwable $e ) {
			return null;
		}

		if ( isset( $rates['currencies'] ) && is_array( $rates['currencies'] ) ) {
			$rates = $rates['currencies'];
		}

		return $this->normalize_rates( $rates );
	}

	/**
	 * Get provider-supported WooCommerce currency codes.
	 *
	 * @return string[]
	 *
	 * @since 11.0.0
	 */
	public function get_supported_currency_codes(): array {
		$provider = $this->provider_registry->get_available_provider();
		if ( ! $provider ) {
			return array();
		}

		$wc_currencies        = array_keys( get_woocommerce_currencies() );
		$supported_currencies = $provider->get_supported_currencies();
		if ( empty( $supported_currencies ) ) {
			return $wc_currencies;
		}

		return array_values(
			array_intersect(
				array_values( array_unique( array_map( 'strtoupper', $supported_currencies ) ) ),
				$wc_currencies
			)
		);
	}

	/**
	 * Get a manual rate from preserved options.
	 *
	 * @param string $to_currency Target currency.
	 * @return float|null
	 */
	private function get_manual_rate( string $to_currency ): ?float {
		$rate = get_option( 'wcpay_multi_currency_manual_rate_' . $to_currency, null );

		return $this->normalize_rate( $rate );
	}

	/**
	 * Extract a target rate from a provider response.
	 *
	 * @param array<string,mixed> $rates       Provider rates.
	 * @param string              $to_currency Target currency.
	 * @return float|null
	 */
	private function extract_rate( array $rates, string $to_currency ): ?float {
		$rate = $rates[ $to_currency ] ?? $rates[ strtoupper( $to_currency ) ] ?? null;

		return $this->normalize_rate( $rate );
	}

	/**
	 * Normalize provider rates.
	 *
	 * @param array<string,mixed> $rates Provider rates.
	 * @return array<string,float>
	 */
	private function normalize_rates( array $rates ): array {
		$normalized_rates = array();

		foreach ( $rates as $currency_code => $rate ) {
			$normalized_rate = $this->normalize_rate( $rate );
			if ( null === $normalized_rate ) {
				continue;
			}

			$normalized_rates[ strtolower( (string) $currency_code ) ] = $normalized_rate;
		}

		return $normalized_rates;
	}

	/**
	 * Normalize a rate value.
	 *
	 * @param mixed $rate Rate value.
	 * @return float|null
	 */
	private function normalize_rate( $rate ): ?float {
		if ( ! is_numeric( $rate ) || 0 >= (float) $rate ) {
			return null;
		}

		return (float) $rate;
	}
}
