<?php
/**
 * MultiCurrencyStateBuilder class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;

/**
 * Builds non-mutating state snapshots for native multi-currency shadow work.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStateBuilder {

	const OPTION_PREFIX           = 'wcpay_multi_currency';
	const CURRENCY_STORAGE_KEY    = 'wcpay_currency';
	const CUSTOMER_CURRENCIES_KEY = 'wcpay_multi_currency_stored_customer_currencies';

	private const FILTER_OVERRIDE_SELECTED_CURRENCY   = 'wcpay_multi_currency_override_selected_currency';
	private const FILTER_SHOULD_RETURN_STORE_CURRENCY = 'wcpay_multi_currency_should_return_store_currency';

	/**
	 * Localization service.
	 *
	 * @var MultiCurrencyLocalizationInterface
	 */
	private MultiCurrencyLocalizationInterface $localization_service;

	/**
	 * Rate service.
	 *
	 * @var MultiCurrencyRateService
	 */
	private MultiCurrencyRateService $rate_service;

	/**
	 * Multi-currency cache.
	 *
	 * @var MultiCurrencyCacheInterface
	 */
	private MultiCurrencyCacheInterface $cache;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyLocalizationInterface $localization_service Localization service.
	 * @param MultiCurrencyRateService           $rate_service         Rate service.
	 * @param MultiCurrencyCacheInterface        $cache                Multi-currency cache.
	 */
	public function __construct(
		MultiCurrencyLocalizationInterface $localization_service,
		MultiCurrencyRateService $rate_service,
		MultiCurrencyCacheInterface $cache
	) {
		$this->localization_service = $localization_service;
		$this->rate_service         = $rate_service;
		$this->cache                = $cache;
	}

	/**
	 * Build a multi-currency state snapshot.
	 *
	 * @return MultiCurrencyState
	 */
	public function build(): MultiCurrencyState {
		$default_code  = strtoupper( (string) get_option( 'woocommerce_currency', 'USD' ) );
		$default       = new MultiCurrencyCurrency( $this->localization_service, $default_code, 1.0, true );
		$available     = array_merge(
			array( $default_code => $default ),
			$this->get_cached_currency_rates( $default_code )
		);
		$enabled       = array( $default_code => $default );
		$enabled_codes = $this->get_enabled_currency_codes( $default_code );

		foreach ( $enabled_codes as $currency_code ) {
			if ( $default_code === $currency_code || ! $this->is_valid_currency_code( $currency_code ) ) {
				continue;
			}

			if ( ! isset( $available[ $currency_code ] ) && $this->uses_manual_rate( $currency_code ) ) {
				$rate = $this->rate_service->get_rate( $default_code, $currency_code );
				if ( null !== $rate ) {
					$available[ $currency_code ] = new MultiCurrencyCurrency( $this->localization_service, $currency_code, $rate );
				}
			}
		}

		$available           = $this->sort_available_currencies( $default_code, $default, $available );
		$enabled_code_lookup = array_fill_keys( $enabled_codes, true );

		foreach ( $available as $currency_code => $available_currency ) {
			if ( $default_code === $currency_code || ! isset( $enabled_code_lookup[ $currency_code ] ) ) {
				continue;
			}

			$currency = $this->create_enabled_currency( $default_code, $available_currency );
			if ( null === $currency ) {
				continue;
			}

			$this->apply_currency_settings( $currency );

			$enabled[ $currency_code ] = $currency;
		}

		$selected_code = $this->get_selected_currency_code( $default_code );
		$selected      = $selected_code && isset( $enabled[ $selected_code ] )
			? $enabled[ $selected_code ]
			: $default;

		return new MultiCurrencyState( $available, $enabled, $default, $selected, $this->get_customer_currencies() );
	}

	/**
	 * Create an enabled currency clone with preserved rate behavior.
	 *
	 * @param string                $default_code       Default currency code.
	 * @param MultiCurrencyCurrency $available_currency Available currency.
	 * @return MultiCurrencyCurrency|null
	 */
	private function create_enabled_currency( string $default_code, MultiCurrencyCurrency $available_currency ): ?MultiCurrencyCurrency {
		$currency_code = $available_currency->get_code();
		$rate          = $available_currency->get_rate();

		if ( $this->uses_manual_rate( $currency_code ) ) {
			$rate = $this->rate_service->get_rate( $default_code, $currency_code ) ?? $rate;
		}

		if ( 0 >= $rate ) {
			return null;
		}

		return new MultiCurrencyCurrency(
			$this->localization_service,
			$currency_code,
			$rate,
			false,
			$available_currency->get_last_updated()
		);
	}

	/**
	 * Get cached automatic currency rates.
	 *
	 * @param string $default_code Default currency code.
	 * @return array<string,MultiCurrencyCurrency>
	 */
	private function get_cached_currency_rates( string $default_code ): array {
		$cache_data = $this->get_cached_currency_data( $default_code );
		if ( ! is_array( $cache_data ) || ! isset( $cache_data['currencies'] ) || ! is_array( $cache_data['currencies'] ) ) {
			return array();
		}

		$last_updated          = isset( $cache_data['updated'] ) && is_numeric( $cache_data['updated'] )
			? (int) $cache_data['updated']
			: null;
		$currencies            = array();
		$supported_code_lookup = $this->rate_service->has_available_provider()
			? array_fill_keys( $this->rate_service->get_supported_currency_codes(), true )
			: null;

		foreach ( $cache_data['currencies'] as $currency_code => $rate ) {
			$currency_code = strtoupper( (string) $currency_code );

			if ( null !== $supported_code_lookup && ! isset( $supported_code_lookup[ $currency_code ] ) ) {
				continue;
			}

			if ( $default_code === $currency_code || ! $this->is_valid_currency_code( $currency_code ) || ! is_numeric( $rate ) || 0 >= (float) $rate ) {
				continue;
			}

			$currency = new MultiCurrencyCurrency(
				$this->localization_service,
				$currency_code,
				(float) $rate,
				false,
				$last_updated
			);

			$currencies[ $currency->get_name() ] = $currency;
		}

		ksort( $currencies );

		$sorted = array();
		foreach ( $currencies as $currency ) {
			$sorted[ $currency->get_code() ] = $currency;
		}

		return $sorted;
	}

	/**
	 * Get cached automatic currency data, refreshing it when a provider is available.
	 *
	 * @param string $default_code Default currency code.
	 * @return array<string,mixed>|null
	 */
	private function get_cached_currency_data( string $default_code ): ?array {
		if ( ! $this->rate_service->has_available_provider() ) {
			$cache_data = $this->cache->get( MultiCurrencyCacheInterface::CURRENCIES_KEY, true );

			return is_array( $cache_data ) ? $cache_data : null;
		}

		$cache_data = $this->cache->get_or_add(
			MultiCurrencyCacheInterface::CURRENCIES_KEY,
			function () use ( $default_code ) {
				$rates = $this->rate_service->get_rates( $default_code );
				if ( null === $rates ) {
					return null;
				}

				return array(
					'currencies' => $rates,
					'updated'    => time(),
				);
			},
			static function ( $data ) {
				return is_array( $data ) && isset( $data['currencies'], $data['updated'] ) && is_array( $data['currencies'] );
			}
		);

		return is_array( $cache_data ) ? $cache_data : null;
	}

	/**
	 * Sort available currencies with the default currency first.
	 *
	 * @param string                              $default_code     Default currency code.
	 * @param MultiCurrencyCurrency               $default_currency Default currency.
	 * @param array<string,MultiCurrencyCurrency> $available        Available currencies.
	 * @return array<string,MultiCurrencyCurrency>
	 */
	private function sort_available_currencies( string $default_code, MultiCurrencyCurrency $default_currency, array $available ): array {
		$named_currencies = array();

		foreach ( $available as $currency_code => $currency ) {
			if ( $default_code === $currency_code ) {
				continue;
			}

			$named_currencies[ $currency->get_name() ] = $currency;
		}

		ksort( $named_currencies );

		$sorted = array( $default_code => $default_currency );
		foreach ( $named_currencies as $currency ) {
			$sorted[ $currency->get_code() ] = $currency;
		}

		return $sorted;
	}

	/**
	 * Get enabled currency codes with the default currency first.
	 *
	 * @param string $default_code Default currency code.
	 * @return string[]
	 */
	private function get_enabled_currency_codes( string $default_code ): array {
		$enabled = get_option( self::OPTION_PREFIX . '_enabled_currencies', array() );
		$enabled = is_array( $enabled ) ? $enabled : array();
		$enabled = array_map(
			static fn( $currency_code ) => strtoupper( (string) $currency_code ),
			$enabled
		);

		return array_values( array_unique( array_merge( array( $default_code ), $enabled ) ) );
	}

	/**
	 * Tell whether a currency code is known to WooCommerce.
	 *
	 * @param string $currency_code Currency code.
	 * @return bool
	 */
	private function is_valid_currency_code( string $currency_code ): bool {
		return array_key_exists( $currency_code, get_woocommerce_currencies() );
	}

	/**
	 * Tell whether a currency uses a manual rate option.
	 *
	 * @param string $currency_code Currency code.
	 * @return bool
	 */
	private function uses_manual_rate( string $currency_code ): bool {
		return 'manual' === get_option( self::OPTION_PREFIX . '_exchange_rate_' . strtolower( $currency_code ), 'automatic' );
	}

	/**
	 * Apply preserved per-currency settings.
	 *
	 * @param MultiCurrencyCurrency $currency Currency.
	 */
	private function apply_currency_settings( MultiCurrencyCurrency $currency ): void {
		$currency_id = $currency->get_id();
		$charm       = get_option( self::OPTION_PREFIX . '_price_charm_' . $currency_id, 0.00 );
		$rounding    = get_option(
			self::OPTION_PREFIX . '_price_rounding_' . $currency_id,
			$currency->get_is_zero_decimal() ? '100' : '1.00'
		);

		$currency->set_charm( $charm );
		$currency->set_rounding( $rounding );
	}

	/**
	 * Get the selected currency code after applying compatibility filters.
	 *
	 * @param string $default_code Default currency code.
	 * @return string|null
	 */
	private function get_selected_currency_code( string $default_code ): ?string {
		/**
		 * Filters whether native multi-currency should force store currency.
		 *
		 * @param bool $should_return_store_currency Whether to force store currency.
		 *
		 * @since 11.0.0
		 */
		if ( (bool) apply_filters( self::FILTER_SHOULD_RETURN_STORE_CURRENCY, false ) ) {
			return $default_code;
		}

		/**
		 * Filters the selected native multi-currency code.
		 *
		 * @param string|false $currency_code Override currency code, or false to use stored state.
		 *
		 * @since 11.0.0
		 */
		$override_currency_code = apply_filters( self::FILTER_OVERRIDE_SELECTED_CURRENCY, false );

		if ( is_scalar( $override_currency_code ) && '' !== trim( (string) $override_currency_code ) ) {
			return strtoupper( trim( (string) $override_currency_code ) );
		}

		return $this->get_stored_currency_code();
	}

	/**
	 * Get the stored user or session currency without mutating session state.
	 *
	 * @return string|null
	 */
	private function get_stored_currency_code(): ?string {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$currency_code = get_user_meta( $user_id, self::CURRENCY_STORAGE_KEY, true );

			return is_string( $currency_code ) && '' !== $currency_code ? strtoupper( $currency_code ) : null;
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$currency_code = WC()->session->get( self::CURRENCY_STORAGE_KEY );

			return is_string( $currency_code ) && '' !== $currency_code ? strtoupper( $currency_code ) : null;
		}

		return null;
	}

	/**
	 * Get stored customer-used currencies.
	 *
	 * @return string[]
	 */
	private function get_customer_currencies(): array {
		$currencies = get_option( self::CUSTOMER_CURRENCIES_KEY, array() );
		if ( ! is_array( $currencies ) ) {
			return array();
		}

		$valid_currencies = array();
		foreach ( $currencies as $currency_code ) {
			$currency_code = strtoupper( (string) $currency_code );
			if ( $this->is_valid_currency_code( $currency_code ) ) {
				$valid_currencies[] = $currency_code;
			}
		}

		return array_values( array_unique( $valid_currencies ) );
	}
}
