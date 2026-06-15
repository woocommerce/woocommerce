<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceCalculator;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyPriceProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyPriceProjectionService class.
 */
class MultiCurrencyPriceProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project selected-currency product prices.
	 */
	public function test_projects_selected_currency_product_prices(): void {
		$state = $this->create_state( 'GBP' );
		$sut   = $this->create_service( $state );

		$this->assertSame( 8.4, $sut->get_price( '10.00', 'product' ) );
	}

	/**
	 * @testdox Should project raw conversions between enabled currencies.
	 */
	public function test_projects_raw_conversions_between_enabled_currencies(): void {
		$state = $this->create_state( 'GBP' );
		$sut   = $this->create_service( $state );

		$this->assertSame( 10.0 * ( 0.82 / 1.25 ), $sut->get_raw_conversion( 10.0, 'GBP', 'CAD' ) );
	}

	/**
	 * @testdox Should project selected-currency price-filter bounds to default currency.
	 */
	public function test_projects_price_filter_bounds_to_default_currency(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame( '10', $sut->get_price_filter_query_value( 8.2, '>=' ) );
		$this->assertSame( '11', $sut->get_price_filter_query_value( 8.21, '<=' ) );
	}

	/**
	 * @testdox Should report whether selected and default currencies differ.
	 */
	public function test_reports_whether_selected_currency_differs_from_default(): void {
		$this->assertTrue( $this->create_service( $this->create_state( 'GBP' ) )->should_project_between_selected_and_default_currency() );
		$this->assertFalse( $this->create_service( $this->create_state( 'USD' ) )->should_project_between_selected_and_default_currency() );
	}

	/**
	 * @testdox Should project order exchange-rate meta for non-default orders.
	 */
	public function test_projects_order_exchange_rate_meta_for_non_default_orders(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame(
			array(
				'_wcpay_multi_currency_order_exchange_rate'    => 0.82,
				'_wcpay_multi_currency_order_default_currency' => 'USD',
			),
			$sut->get_order_meta_candidates( 'GBP' )
		);
	}

	/**
	 * @testdox Should not project order meta for default-currency orders.
	 */
	public function test_does_not_project_order_meta_for_default_currency_orders(): void {
		$sut = $this->create_service( $this->create_state( 'USD' ) );

		$this->assertSame( array(), $sut->get_order_meta_candidates( 'USD' ) );
	}

	/**
	 * @testdox Should project refund meta copied from non-default orders.
	 */
	public function test_projects_refund_meta_from_non_default_orders(): void {
		$order = wc_create_order();
		$order->set_currency( 'GBP' );
		$order->update_meta_data( '_wcpay_multi_currency_order_exchange_rate', 0.82 );
		$order->update_meta_data( '_wcpay_multi_currency_order_default_currency', 'USD' );
		$order->update_meta_data( '_wcpay_multi_currency_stripe_exchange_rate', 1.25 );
		$order->save();
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame(
			array(
				'_wcpay_multi_currency_order_exchange_rate'    => 0.82,
				'_wcpay_multi_currency_order_default_currency' => 'USD',
				'_wcpay_multi_currency_stripe_exchange_rate'   => 1.25,
			),
			$sut->get_refund_meta_candidates( $order )
		);
	}

	/**
	 * @testdox Should not project refund meta for default-currency orders.
	 */
	public function test_does_not_project_refund_meta_for_default_currency_orders(): void {
		$order = wc_create_order();
		$order->set_currency( 'USD' );
		$order->save();
		$sut = $this->create_service( $this->create_state( 'USD' ) );

		$this->assertSame( array(), $sut->get_refund_meta_candidates( $order ) );
	}

	/**
	 * Create the projection service.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyPriceProjectionService
	 */
	private function create_service( MultiCurrencyState $state ): MultiCurrencyPriceProjectionService {
		return new MultiCurrencyPriceProjectionService(
			$this->create_state_builder( $state ),
			new MultiCurrencyPriceCalculator( $this->create_localization() )
		);
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
	 * @param string $selected_code Selected currency code.
	 * @return MultiCurrencyState
	 */
	private function create_state( string $selected_code ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', 1.0, true );
		$gbp = $this->create_currency( 'GBP', 0.82, false, '0.50', -0.10 );
		$cad = $this->create_currency( 'CAD', 1.25, false );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
			'CAD' => $cad,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $enabled[ $selected_code ] );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @param string $rounding   Rounding amount.
	 * @param float  $charm      Charm amount.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default, string $rounding = '0', float $charm = 0.0 ): MultiCurrencyCurrency {
		$currency = new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
		$currency->set_rounding( $rounding );
		$currency->set_charm( $charm );

		return $currency;
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
				return array();
			}
		};
	}
}
