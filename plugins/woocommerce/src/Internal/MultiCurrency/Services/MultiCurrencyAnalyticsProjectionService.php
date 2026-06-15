<?php
/**
 * MultiCurrencyAnalyticsProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects analytics multi-currency surfaces without registering filters.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyAnalyticsProjectionService {

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
	 * Project order stats data in the store default currency.
	 *
	 * @param array<string,mixed> $args  Order stats args.
	 * @param \WC_Order           $order Order.
	 * @return array<string,mixed>
	 */
	public function update_order_stats_data( array $args, \WC_Order $order ): array {
		$default_currency_code = $this->state_builder->build()->get_default_currency()->get_code();

		if ( ! $this->should_convert_order_stats( $order, $default_currency_code ) ) {
			return $args;
		}

		$order_exchange_rate = (float) $order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true );
		if ( 0.0 >= $order_exchange_rate ) {
			return $args;
		}

		$stripe_exchange_rate = $order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_STRIPE_EXCHANGE_RATE, true );
		$exchange_rate        = $stripe_exchange_rate ? (float) $stripe_exchange_rate : 1 / $order_exchange_rate;
		$decimals             = wc_get_price_decimals();

		$args['net_total']      = round( $this->convert_amount( (float) $args['net_total'], $exchange_rate ), $decimals );
		$args['shipping_total'] = round( $this->convert_amount( (float) $args['shipping_total'], $exchange_rate ), $decimals );
		$args['tax_total']      = round( $this->convert_amount( (float) $args['tax_total'], $exchange_rate ), $decimals );
		$args['total_sales']    = $args['net_total'] + $args['shipping_total'] + $args['tax_total'];

		return $args;
	}

	/**
	 * Apply customer currency request args to analytics args.
	 *
	 * @param array<string,mixed> $args         Analytics args.
	 * @param array<string,mixed> $request_args Request args.
	 * @return array<string,mixed>
	 */
	public function apply_customer_currency_args( array $args, array $request_args ): array {
		$currency_args = array(
			'currency_is'     => array(),
			'currency_is_not' => array(),
			'currency'        => null,
		);

		if ( isset( $request_args['currency_is'] ) && is_array( $request_args['currency_is'] ) ) {
			$currency_args['currency_is'] = $this->sanitize_currency_list( $request_args['currency_is'] );
		}

		if ( isset( $request_args['currency_is_not'] ) && is_array( $request_args['currency_is_not'] ) ) {
			$currency_args['currency_is_not'] = $this->sanitize_currency_list( $request_args['currency_is_not'] );
		}

		if ( isset( $request_args['currency'] ) ) {
			$currency_args['currency'] = sanitize_text_field( wp_unslash( (string) $request_args['currency'] ) );
		}

		return array_merge( $args, $currency_args );
	}

	/**
	 * Project customer currency selector options.
	 *
	 * @return array<int,array{label:string,value:string}>
	 */
	public function get_customer_currency_options(): array {
		$state                = $this->state_builder->build();
		$available_currencies = $state->get_available_currencies();
		$currencies           = $state->get_customer_currencies();
		$default_currency     = $state->get_default_currency();

		if ( ! in_array( $default_currency->get_code(), $currencies, true ) ) {
			$currencies[] = $default_currency->get_code();
		}

		$options = array();
		foreach ( $currencies as $currency_code ) {
			if ( ! isset( $available_currencies[ $currency_code ] ) ) {
				continue;
			}

			$currency  = $available_currencies[ $currency_code ];
			$options[] = array(
				'label' => html_entity_decode( $currency->get_name(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
				'value' => $currency->get_code(),
			);
		}

		return $options;
	}

	/**
	 * Tell whether order stats should be converted.
	 *
	 * @param \WC_Order $order                 Order.
	 * @param string    $default_currency_code Default currency code.
	 * @return bool
	 */
	private function should_convert_order_stats( \WC_Order $order, string $default_currency_code ): bool {
		return $order->get_currency() !== $default_currency_code
			&& (bool) $order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, true )
			&& $order->get_meta( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, true ) === $default_currency_code;
	}

	/**
	 * Convert an amount with the given exchange rate.
	 *
	 * @param float $amount        Amount.
	 * @param float $exchange_rate Exchange rate.
	 * @return float
	 */
	private function convert_amount( float $amount, float $exchange_rate ): float {
		return $amount * $exchange_rate;
	}

	/**
	 * Sanitize a currency argument list.
	 *
	 * @param array<mixed> $currencies Currency values.
	 * @return string[]
	 */
	private function sanitize_currency_list( array $currencies ): array {
		return array_map(
			static function ( $currency ): string {
				return sanitize_text_field( wp_unslash( (string) $currency ) );
			},
			$currencies
		);
	}
}
