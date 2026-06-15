<?php
/**
 * MultiCurrencyTrackingProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;

/**
 * Projects multi-currency tracking data without registering tracker hooks.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyTrackingProjectionService {

	public const TRACKER_KEY = 'wcpay_multi_currency';

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyStateBuilder $state_builder State builder.
	 */
	public function __construct( MultiCurrencyStateBuilder $state_builder ) {
		$this->state_builder = $state_builder;
	}

	/**
	 * Project multi-currency tracker data.
	 *
	 * @param array<string,mixed> $data         Existing tracker data.
	 * @param array<string,mixed> $order_counts Precomputed order-count payload.
	 * @return array<string,mixed>
	 */
	public function project_tracker_data( array $data, array $order_counts = array() ): array {
		$state = $this->state_builder->build();

		$data[ self::TRACKER_KEY ] = array(
			'enabled_currencies' => $this->get_enabled_currencies_data( $state ),
			'default_currency'   => $this->get_currency_data_array( $state->get_default_currency(), $state->get_default_currency() ),
			'order_counts'       => array() === $order_counts
				? array(
					'counts'     => 0,
					'currencies' => array(),
				)
				: $order_counts,
		);

		return $data;
	}

	/**
	 * Get enabled non-default currency data.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_enabled_currencies_data( MultiCurrencyState $state ): array {
		$enabled_currencies = $state->get_enabled_currencies();
		$default_currency   = $state->get_default_currency();
		unset( $enabled_currencies[ $default_currency->get_code() ] );

		$rate_option_keys = array_map(
			static function ( MultiCurrencyCurrency $currency ): string {
				return 'wcpay_multi_currency_exchange_rate_' . $currency->get_id();
			},
			$enabled_currencies
		);

		if ( array() !== $rate_option_keys ) {
			// Prime caches to reduce future queries.
			wp_prime_option_caches( $rate_option_keys );
		}

		$enabled_data = array();
		foreach ( $enabled_currencies as $currency ) {
			$enabled_data[ $currency->get_code() ] = $this->get_currency_data_array( $currency, $default_currency );
		}

		return $enabled_data;
	}

	/**
	 * Get tracker data for a currency.
	 *
	 * @param MultiCurrencyCurrency $currency         Currency.
	 * @param MultiCurrencyCurrency $default_currency Default currency.
	 * @return array<string,mixed>
	 */
	private function get_currency_data_array( MultiCurrencyCurrency $currency, MultiCurrencyCurrency $default_currency ): array {
		$data = array(
			'code' => $currency->get_code(),
			'name' => html_entity_decode( $currency->get_name(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
		);

		if ( $currency->get_code() === $default_currency->get_code() ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				'is_zero_decimal' => $currency->get_is_zero_decimal(),
				'rate_type'       => $this->get_rate_type_label( $currency ),
				'price_rounding'  => $this->get_price_rounding_label( $currency ),
				'price_charm'     => $this->get_price_charm_label( $currency ),
			)
		);
	}

	/**
	 * Get the rate type label.
	 *
	 * @param MultiCurrencyCurrency $currency Currency.
	 * @return string
	 */
	private function get_rate_type_label( MultiCurrencyCurrency $currency ): string {
		$rate_type = (string) get_option( 'wcpay_multi_currency_exchange_rate_' . $currency->get_id(), 'automatic' );

		return 'automatic' === $rate_type ? 'automatic (default)' : $rate_type;
	}

	/**
	 * Get the rounding label.
	 *
	 * @param MultiCurrencyCurrency $currency Currency.
	 * @return string
	 */
	private function get_price_rounding_label( MultiCurrencyCurrency $currency ): string {
		$default_rounding = $currency->get_is_zero_decimal() ? '100' : '1.00';
		$rounding         = $currency->get_rounding();

		return $default_rounding === $rounding ? $rounding . ' (default)' : $rounding;
	}

	/**
	 * Get the charm label.
	 *
	 * @param MultiCurrencyCurrency $currency Currency.
	 * @return float|string
	 */
	private function get_price_charm_label( MultiCurrencyCurrency $currency ) {
		$charm = $currency->get_charm();

		return 0.0 === $charm ? '0.00 (default)' : $charm;
	}
}
