<?php
/**
 * MultiCurrencyPriceProjectionService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\MultiCurrency\Services;

/**
 * Projects multi-currency prices for native shadow comparisons.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native multi-currency runtime.
 */
class MultiCurrencyPriceProjectionService {

	public const META_KEY_ORDER_EXCHANGE_RATE    = '_wcpay_multi_currency_order_exchange_rate';
	public const META_KEY_ORDER_DEFAULT_CURRENCY = '_wcpay_multi_currency_order_default_currency';
	public const META_KEY_STRIPE_EXCHANGE_RATE   = '_wcpay_multi_currency_stripe_exchange_rate';

	/**
	 * State builder.
	 *
	 * @var MultiCurrencyStateBuilder
	 */
	private MultiCurrencyStateBuilder $state_builder;

	/**
	 * Price calculator.
	 *
	 * @var MultiCurrencyPriceCalculator
	 */
	private MultiCurrencyPriceCalculator $price_calculator;

	/**
	 * Constructor.
	 *
	 * @param MultiCurrencyStateBuilder    $state_builder     State builder.
	 * @param MultiCurrencyPriceCalculator $price_calculator Price calculator.
	 */
	public function __construct( MultiCurrencyStateBuilder $state_builder, MultiCurrencyPriceCalculator $price_calculator ) {
		$this->state_builder    = $state_builder;
		$this->price_calculator = $price_calculator;
	}

	/**
	 * Project a converted price for the selected currency.
	 *
	 * @param mixed  $price Price.
	 * @param string $type  Price type.
	 * @return float
	 */
	public function get_price( $price, string $type ): float {
		$state = $this->state_builder->build();

		return $this->price_calculator->get_price( $price, $type, $state->get_selected_currency() );
	}

	/**
	 * Project a raw conversion between enabled currencies.
	 *
	 * @param float  $amount        Amount.
	 * @param string $to_currency   Target currency code.
	 * @param string $from_currency Source currency code.
	 * @return float
	 */
	public function get_raw_conversion( float $amount, string $to_currency, string $from_currency ): float {
		$state = $this->state_builder->build();

		return $this->price_calculator->get_raw_conversion(
			$amount,
			$to_currency,
			$from_currency,
			$state->get_enabled_currencies()
		);
	}

	/**
	 * Project order meta candidates for a selected-currency order.
	 *
	 * @param string $order_currency Order currency code.
	 * @return array<string,float|string>
	 */
	public function get_order_meta_candidates( string $order_currency ): array {
		$state            = $this->state_builder->build();
		$default_currency = $state->get_default_currency();

		if ( $default_currency->get_code() === strtoupper( $order_currency ) ) {
			return array();
		}

		return array(
			self::META_KEY_ORDER_EXCHANGE_RATE    => $this->price_calculator->get_price( 1, 'exchange_rate', $state->get_selected_currency() ),
			self::META_KEY_ORDER_DEFAULT_CURRENCY => $default_currency->get_code(),
		);
	}

	/**
	 * Project refund meta candidates copied from an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<string,mixed>
	 */
	public function get_refund_meta_candidates( \WC_Order $order ): array {
		$state            = $this->state_builder->build();
		$default_currency = $state->get_default_currency();

		if ( $default_currency->get_code() === strtoupper( $order->get_currency() ) ) {
			return array();
		}

		$meta = array(
			self::META_KEY_ORDER_EXCHANGE_RATE    => $order->get_meta( self::META_KEY_ORDER_EXCHANGE_RATE, true ),
			self::META_KEY_ORDER_DEFAULT_CURRENCY => $order->get_meta( self::META_KEY_ORDER_DEFAULT_CURRENCY, true ),
		);

		$stripe_exchange_rate = $order->get_meta( self::META_KEY_STRIPE_EXCHANGE_RATE, true );
		if ( $stripe_exchange_rate ) {
			$meta[ self::META_KEY_STRIPE_EXCHANGE_RATE ] = $stripe_exchange_rate;
		}

		return $meta;
	}
}
