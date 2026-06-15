<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyTrackingProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyTrackingProjectionService class.
 */
class MultiCurrencyTrackingProjectionServiceTest extends WC_Unit_Test_Case {

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
		delete_option( 'wcpay_multi_currency_exchange_rate_gbp' );
		delete_option( 'wcpay_multi_currency_exchange_rate_jpy' );

		parent::tear_down();
	}

	/**
	 * @testdox Should project tracker data with enabled non-default currencies.
	 */
	public function test_projects_tracker_data_with_enabled_non_default_currencies(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );
		update_option( 'wcpay_multi_currency_exchange_rate_jpy', 'manual' );

		$order_counts = array(
			'counts'     => 3,
			'currencies' => array(
				'GBP' => array( 'counts' => 2 ),
				'JPY' => array( 'counts' => 1 ),
			),
		);
		$sut          = $this->create_service( $this->create_state() );

		$result  = $sut->project_tracker_data( array( 'existing' => 'value' ), $order_counts );
		$payload = $result[ MultiCurrencyTrackingProjectionService::TRACKER_KEY ];

		$this->assertSame( 'value', $result['existing'] );
		$this->assertSame(
			array(
				'code' => 'USD',
				'name' => html_entity_decode( get_woocommerce_currencies()['USD'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			),
			$payload['default_currency']
		);
		$this->assertSame( array( 'GBP', 'JPY' ), array_keys( $payload['enabled_currencies'] ) );
		$this->assertSame( $order_counts, $payload['order_counts'] );
		$this->assertSame( 'automatic (default)', $payload['enabled_currencies']['GBP']['rate_type'] );
		$this->assertSame( 'manual', $payload['enabled_currencies']['JPY']['rate_type'] );
		$this->assertFalse( $payload['enabled_currencies']['GBP']['is_zero_decimal'] );
		$this->assertTrue( $payload['enabled_currencies']['JPY']['is_zero_decimal'] );
	}

	/**
	 * @testdox Should mark default rate rounding and charm values.
	 */
	public function test_marks_default_rate_rounding_and_charm_values(): void {
		$sut = $this->create_service( $this->create_state() );

		$result  = $sut->project_tracker_data( array() );
		$payload = $result[ MultiCurrencyTrackingProjectionService::TRACKER_KEY ];

		$this->assertSame( '1.00 (default)', $payload['enabled_currencies']['GBP']['price_rounding'] );
		$this->assertSame( '100 (default)', $payload['enabled_currencies']['JPY']['price_rounding'] );
		$this->assertSame( '0.00 (default)', $payload['enabled_currencies']['GBP']['price_charm'] );
	}

	/**
	 * @testdox Should preserve custom rounding and charm values.
	 */
	public function test_preserves_custom_rounding_and_charm_values(): void {
		$state = $this->create_state(
			array(
				'GBP' => array(
					'rounding' => '0.50',
					'charm'    => -0.01,
				),
			)
		);
		$sut   = $this->create_service( $state );

		$result  = $sut->project_tracker_data( array() );
		$payload = $result[ MultiCurrencyTrackingProjectionService::TRACKER_KEY ];

		$this->assertSame( '0.50', $payload['enabled_currencies']['GBP']['price_rounding'] );
		$this->assertSame( -0.01, $payload['enabled_currencies']['GBP']['price_charm'] );
	}

	/**
	 * @testdox Should default order counts when none are supplied.
	 */
	public function test_defaults_order_counts_when_none_are_supplied(): void {
		$sut = $this->create_service( $this->create_state() );

		$result  = $sut->project_tracker_data( array() );
		$payload = $result[ MultiCurrencyTrackingProjectionService::TRACKER_KEY ];

		$this->assertSame(
			array(
				'counts'     => 0,
				'currencies' => array(),
			),
			$payload['order_counts']
		);
	}

	/**
	 * Create the tracking projection service.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencyTrackingProjectionService
	 */
	private function create_service( MultiCurrencyState $state ): MultiCurrencyTrackingProjectionService {
		return new MultiCurrencyTrackingProjectionService( $this->create_state_builder( $state ) );
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
	 * @param array<string,array<string,mixed>> $overrides Currency setting overrides.
	 * @return MultiCurrencyState
	 */
	private function create_state( array $overrides = array() ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', 1.0, true, $overrides['USD'] ?? array() );
		$gbp = $this->create_currency( 'GBP', 0.5, false, $overrides['GBP'] ?? array() );
		$jpy = $this->create_currency( 'JPY', 151.0, false, $overrides['JPY'] ?? array() );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
			'JPY' => $jpy,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $usd );
	}

	/**
	 * Create a currency.
	 *
	 * @param string              $code        Currency code.
	 * @param float               $rate        Currency rate.
	 * @param bool                $is_default  Whether this is the default currency.
	 * @param array<string,mixed> $overrides   Currency setting overrides.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default, array $overrides ): MultiCurrencyCurrency {
		$currency = new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
		$currency->set_rounding( (string) ( $overrides['rounding'] ?? ( $currency->get_is_zero_decimal() ? '100' : '1.00' ) ) );
		$currency->set_charm( $overrides['charm'] ?? 0.0 );

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
					'num_decimals' => 'JPY' === $currency_code ? 0 : 2,
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
