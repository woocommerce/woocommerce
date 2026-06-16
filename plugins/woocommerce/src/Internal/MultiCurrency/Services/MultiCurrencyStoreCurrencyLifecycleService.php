<?php
/**
 * MultiCurrencyStoreCurrencyLifecycleService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;

/**
 * Handles store-currency lifecycle mutations for native multi-currency.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyStoreCurrencyLifecycleService {

	private const OPTION_PREFIX         = 'wcpay_multi_currency';
	private const STORE_CURRENCY_OPTION = self::OPTION_PREFIX . '_store_currency';
	private const NOTICE_OPTION         = self::OPTION_PREFIX . '_show_store_currency_changed_notice';

	/**
	 * Multi-currency cache.
	 *
	 * @var MultiCurrencyCacheInterface
	 */
	private MultiCurrencyCacheInterface $cache;

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyCacheInterface $cache         Multi-currency cache.
	 * @param MultiCurrencyStateBuilder   $state_builder State builder.
	 */
	public function __construct( MultiCurrencyCacheInterface $cache, MultiCurrencyStateBuilder $state_builder ) {
		$this->cache         = $cache;
		$this->state_builder = $state_builder;
	}

	/**
	 * Synchronize store-currency options and dependent cache state.
	 *
	 * @return bool True when the store currency changed.
	 *
	 * @since 11.0.0
	 */
	public function synchronize_store_currency(): bool {
		$store_currency = strtoupper( get_woocommerce_currency() );

		if ( ! array_key_exists( $store_currency, get_woocommerce_currencies() ) ) {
			return false;
		}

		$last_known_currency = get_option( self::STORE_CURRENCY_OPTION, false );
		if ( ! $last_known_currency ) {
			update_option( self::STORE_CURRENCY_OPTION, $store_currency );
			return false;
		}

		if ( strtoupper( (string) $last_known_currency ) === $store_currency ) {
			return false;
		}

		update_option( self::STORE_CURRENCY_OPTION, $store_currency );
		$this->cache->delete( MultiCurrencyCacheInterface::CURRENCIES_KEY );
		$this->update_manual_rate_currencies_notice_option();

		return true;
	}

	/**
	 * Update the notice option with enabled manual-rate currency names.
	 */
	private function update_manual_rate_currencies_notice_option(): void {
		$manual_currencies = array();

		foreach ( $this->state_builder->build()->get_enabled_currencies() as $currency ) {
			$rate_type = get_option( self::OPTION_PREFIX . '_exchange_rate_' . $currency->get_id(), false );
			if ( 'manual' === $rate_type ) {
				$manual_currencies[] = $currency->get_name();
			}
		}

		if ( ! empty( $manual_currencies ) ) {
			update_option( self::NOTICE_OPTION, $manual_currencies );
		}
	}
}
