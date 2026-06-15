<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyAnalyticsProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyAnalyticsProjectionService class.
 */
class MultiCurrencyAnalyticsProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_currency = get_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency', 'USD' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		update_option( 'woocommerce_currency', $this->original_currency );

		parent::tear_down();
	}

	/**
	 * @testdox Should convert order stats to default currency.
	 */
	public function test_converts_order_stats_to_default_currency(): void {
		$order = $this->create_order( 'GBP', '0.5', 'USD' );
		$sut   = $this->create_service( $this->create_state() );

		$this->assertSame(
			array(
				'net_total'      => 20.0,
				'shipping_total' => 6.0,
				'tax_total'      => 4.0,
				'total_sales'    => 30.0,
			),
			$sut->update_order_stats_data( $this->create_order_stats_args(), $order )
		);
	}

	/**
	 * @testdox Should prefer Stripe exchange rate for order stats.
	 */
	public function test_prefers_stripe_exchange_rate_for_order_stats(): void {
		$order = $this->create_order( 'GBP', '0.5', 'USD', '1.25' );
		$sut   = $this->create_service( $this->create_state() );

		$this->assertSame(
			array(
				'net_total'      => 12.5,
				'shipping_total' => 3.75,
				'tax_total'      => 2.5,
				'total_sales'    => 18.75,
			),
			$sut->update_order_stats_data( $this->create_order_stats_args(), $order )
		);
	}

	/**
	 * @testdox Should leave default currency order stats unchanged.
	 */
	public function test_leaves_default_currency_order_stats_unchanged(): void {
		$order = $this->create_order( 'USD', '0.5', 'USD' );
		$args  = $this->create_order_stats_args();
		$sut   = $this->create_service( $this->create_state() );

		$this->assertSame( $args, $sut->update_order_stats_data( $args, $order ) );
	}

	/**
	 * @testdox Should leave order stats unchanged when required meta is missing.
	 */
	public function test_leaves_order_stats_unchanged_when_required_meta_is_missing(): void {
		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->save();
		$args = $this->create_order_stats_args();
		$sut  = $this->create_service( $this->create_state() );

		$this->assertSame( $args, $sut->update_order_stats_data( $args, $order ) );
	}

	/**
	 * @testdox Should apply customer currency request args.
	 */
	public function test_applies_customer_currency_request_args(): void {
		$sut = $this->create_service( $this->create_state() );

		$this->assertSame(
			array(
				'period'          => 'month',
				'currency_is'     => array( 'gbp', 'jpy' ),
				'currency_is_not' => array( 'usd' ),
				'currency'        => 'eur',
			),
			$sut->apply_customer_currency_args(
				array( 'period' => 'month' ),
				array(
					'currency_is'     => array( 'gbp', '<b>jpy</b>' ),
					'currency_is_not' => array( 'usd' ),
					'currency'        => ' eur ',
				)
			)
		);
	}

	/**
	 * @testdox Should project customer currency options with default currency.
	 */
	public function test_projects_customer_currency_options_with_default_currency(): void {
		$sut = $this->create_service( $this->create_state( 'USD', array( 'GBP', 'AUD', 'GBP' ) ) );

		$options = $sut->get_customer_currency_options();

		$this->assertSame( array( 'GBP', 'USD' ), array_column( $options, 'value' ) );
		$this->assertSame( html_entity_decode( get_woocommerce_currencies()['GBP'] ), $options[0]['label'] );
		$this->assertSame( html_entity_decode( get_woocommerce_currencies()['USD'] ), $options[1]['label'] );
	}

	/**
	 * @testdox Should skip customer currency options not available in state.
	 */
	public function test_skips_customer_currency_options_not_available_in_state(): void {
		$sut = $this->create_service( $this->create_state( 'USD', array( 'AUD' ) ) );

		$this->assertSame(
			array(
				array(
					'label' => html_entity_decode( get_woocommerce_currencies()['USD'] ),
					'value' => 'USD',
				),
			),
			$sut->get_customer_currency_options()
		);
	}

	/**
	 * Create order stats args.
	 *
	 * @return array<string,float>
	 */
	private function create_order_stats_args(): array {
		return array(
			'net_total'      => 10.0,
			'shipping_total' => 3.0,
			'tax_total'      => 2.0,
			'total_sales'    => 15.0,
		);
	}

	/**
	 * Create an order with multi-currency meta.
	 *
	 * @param string      $currency             Order currency.
	 * @param string|null $order_exchange_rate  Order exchange rate.
	 * @param string|null $default_currency     Default currency.
	 * @param string|null $stripe_exchange_rate Stripe exchange rate.
	 * @return \WC_Order
	 */
	private function create_order(
		string $currency,
		?string $order_exchange_rate,
		?string $default_currency,
		?string $stripe_exchange_rate = null
	): \WC_Order {
		$order = wc_create_order();
		$order->set_currency( $currency );

		if ( null !== $order_exchange_rate ) {
			$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_EXCHANGE_RATE, $order_exchange_rate );
		}

		if ( null !== $default_currency ) {
			$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_ORDER_DEFAULT_CURRENCY, $default_currency );
		}

		if ( null !== $stripe_exchange_rate ) {
			$order->update_meta_data( MultiCurrencyPriceProjectionService::META_KEY_STRIPE_EXCHANGE_RATE, $stripe_exchange_rate );
		}

		$order->save();

		return $order;
	}

	/**
	 * Create the analytics projection service.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyAnalyticsProjectionService
	 */
	private function create_service( MultiCurrencyState $state ): MultiCurrencyAnalyticsProjectionService {
		return new MultiCurrencyAnalyticsProjectionService( $this->create_state_builder( $state ) );
	}

	/**
	 * Create a state builder test double.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_state_builder( MultiCurrencyState $state ): MultiCurrencyStateBuilder {
		return new class( $state ) extends MultiCurrencyStateBuilder {
			/**
			 * Multi-currency state.
			 *
			 * @var MultiCurrencyState
			 */
			private MultiCurrencyState $state;

			/**
			 * Constructor.
			 *
			 * @param MultiCurrencyState $state Multi-currency state.
			 */
			public function __construct( MultiCurrencyState $state ) {
				$this->state = $state;
			}

			/**
			 * Build a multi-currency state snapshot.
			 *
			 * @return MultiCurrencyState
			 */
			public function build(): MultiCurrencyState {
				return $this->state;
			}
		};
	}

	/**
	 * Create multi-currency state.
	 *
	 * @param string   $selected_code       Selected currency code.
	 * @param string[] $customer_currencies Customer-used currencies.
	 * @return MultiCurrencyState
	 */
	private function create_state( string $selected_code = 'USD', array $customer_currencies = array() ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', 1.0, true );
		$gbp = $this->create_currency( 'GBP', 0.5 );
		$jpy = $this->create_currency( 'JPY', 151.0 );

		$available = array(
			'USD' => $usd,
			'GBP' => $gbp,
			'JPY' => $jpy,
		);

		return new MultiCurrencyState( $available, $available, $usd, $available[ $selected_code ], $customer_currencies );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default = false ): MultiCurrencyCurrency {
		return new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
	}

	/**
	 * Create a localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_localization(): MultiCurrencyLocalizationInterface {
		return new class() implements MultiCurrencyLocalizationInterface {
			/**
			 * Get a currency format.
			 *
			 * @param string $currency_code Currency code.
			 * @return array<string,mixed>
			 */
			public function get_currency_format( $currency_code ): array {
				unset( $currency_code );

				return array(
					'currency_pos' => 'left',
					'thousand_sep' => ',',
					'decimal_sep'  => '.',
					'num_decimals' => 2,
				);
			}

			/**
			 * Get locale data for a country.
			 *
			 * @param string $country Country code.
			 * @return array<string,mixed>
			 */
			public function get_country_locale_data( $country ): array {
				unset( $country );

				return array();
			}
		};
	}
}
