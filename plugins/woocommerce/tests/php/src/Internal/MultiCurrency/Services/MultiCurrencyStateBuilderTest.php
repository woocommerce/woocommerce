<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\CurrencyRateProvider;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStateBuilder class.
 */
class MultiCurrencyStateBuilderTest extends WC_Unit_Test_Case {

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Option keys touched by these tests.
	 *
	 * @var string[]
	 */
	private array $option_keys = array(
		'wcpay_multi_currency_enabled_currencies',
		'wcpay_multi_currency_exchange_rate_gbp',
		'wcpay_multi_currency_manual_rate_gbp',
		'wcpay_multi_currency_price_rounding_gbp',
		'wcpay_multi_currency_price_charm_gbp',
		'wcpay_multi_currency_exchange_rate_eur',
		'wcpay_multi_currency_manual_rate_eur',
		'wcpay_multi_currency_price_rounding_eur',
		'wcpay_multi_currency_price_charm_eur',
		'wcpay_multi_currency_exchange_rate_jpy',
		'wcpay_multi_currency_manual_rate_jpy',
		'wcpay_multi_currency_price_rounding_jpy',
		'wcpay_multi_currency_price_charm_jpy',
		'wcpay_multi_currency_stored_customer_currencies',
		MultiCurrencyCacheInterface::CURRENCIES_KEY,
	);

	/**
	 * Set up test state.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_currency = get_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency', 'USD' );
		$this->delete_options();
		wp_set_current_user( 0 );
	}

	/**
	 * Clean up test state.
	 */
	public function tear_down(): void {
		$this->delete_options();
		update_option( 'woocommerce_currency', $this->original_currency );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * @testdox Should build default-only state when no currencies are enabled.
	 */
	public function test_builds_default_only_state_without_enabled_option(): void {
		$state = $this->create_builder()->build();

		$this->assertSame( array( 'USD' ), array_keys( $state->get_available_currencies() ) );
		$this->assertSame( array( 'USD' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( 'USD', $state->get_default_currency()->get_code() );
		$this->assertSame( 'USD', $state->get_selected_currency()->get_code() );
	}

	/**
	 * @testdox Should build manual enabled currencies without a provider.
	 */
	public function test_builds_manual_enabled_currencies_without_provider(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP', 'EUR', 'JPY' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.8' );
		update_option( 'wcpay_multi_currency_price_rounding_gbp', '0.50' );
		update_option( 'wcpay_multi_currency_price_charm_gbp', '-0.10' );
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_eur', '0.91' );
		update_option( 'wcpay_multi_currency_exchange_rate_jpy', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_jpy', '151' );

		$state = $this->create_builder()->build();

		$this->assertSame( array( 'USD', 'EUR', 'JPY', 'GBP' ), array_keys( $state->get_available_currencies() ) );
		$this->assertSame( array( 'USD', 'EUR', 'JPY', 'GBP' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( 0.8, $state->get_enabled_currencies()['GBP']->get_rate() );
		$this->assertSame( 0.91, $state->get_enabled_currencies()['EUR']->get_rate() );
		$this->assertSame( '0.50', $state->get_enabled_currencies()['GBP']->get_rounding() );
		$this->assertSame( -0.1, $state->get_enabled_currencies()['GBP']->get_charm() );
		$this->assertSame( '100', $state->get_enabled_currencies()['JPY']->get_rounding() );
	}

	/**
	 * @testdox Should not call providers when building automatic currencies.
	 */
	public function test_does_not_call_provider_for_automatic_currency_snapshots(): void {
		$registry = new CurrencyRateProviderRegistry();
		$registry->register( $this->create_available_rate_provider() );
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );

		$state = $this->create_builder( $registry )->build();

		$this->assertSame( array( 'USD' ), array_keys( $state->get_enabled_currencies() ) );
	}

	/**
	 * @testdox Should skip automatic currencies when no provider rate is available.
	 */
	public function test_skips_automatic_currency_without_provider_rate(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );

		$state = $this->create_builder()->build();

		$this->assertSame( array( 'USD' ), array_keys( $state->get_enabled_currencies() ) );
	}

	/**
	 * @testdox Should build automatic currencies from the preserved cache.
	 */
	public function test_builds_automatic_currencies_from_preserved_cache(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );
		update_option(
			MultiCurrencyCacheInterface::CURRENCIES_KEY,
			array(
				'data'               => array(
					'currencies' => array(
						'gbp' => 0.82,
						'eur' => 0.91,
					),
					'updated'    => 123456,
				),
				'fetched'            => time(),
				'errored'            => false,
				'consecutive_errors' => 0,
			),
			false
		);

		$state = $this->create_builder()->build();

		$this->assertSame( array( 'USD', 'EUR', 'GBP' ), array_keys( $state->get_available_currencies() ) );
		$this->assertSame( array( 'USD', 'GBP' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( 0.82, $state->get_enabled_currencies()['GBP']->get_rate() );
		$this->assertSame( 123456, $state->get_enabled_currencies()['GBP']->get_last_updated() );
	}

	/**
	 * @testdox Should select the enabled user-meta currency.
	 */
	public function test_selects_enabled_user_meta_currency(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'wcpay_currency', 'GBP' );
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.8' );

		$state = $this->create_builder()->build();

		$this->assertSame( 'GBP', $state->get_selected_currency()->get_code() );
	}

	/**
	 * @testdox Should fall back to default when stored currency is not enabled.
	 */
	public function test_falls_back_to_default_when_stored_currency_is_not_enabled(): void {
		$user_id = self::factory()->user->create();
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'wcpay_currency', 'EUR' );

		$state = $this->create_builder()->build();

		$this->assertSame( 'USD', $state->get_selected_currency()->get_code() );
	}

	/**
	 * @testdox Should read stored customer currencies without mutating them.
	 */
	public function test_reads_stored_customer_currencies(): void {
		update_option( 'wcpay_multi_currency_stored_customer_currencies', array( 'gbp', 'JPY', 'bad-code' ) );

		$state = $this->create_builder()->build();

		$this->assertSame( array( 'GBP', 'JPY' ), $state->get_customer_currencies() );
		$this->assertSame( array( 'gbp', 'JPY', 'bad-code' ), get_option( 'wcpay_multi_currency_stored_customer_currencies' ) );
	}

	/**
	 * Delete options touched by these tests.
	 */
	private function delete_options(): void {
		foreach ( $this->option_keys as $option_key ) {
			delete_option( $option_key );
		}
	}

	/**
	 * Create the state builder.
	 *
	 * @param CurrencyRateProviderRegistry|null $registry Rate provider registry.
	 * @return MultiCurrencyStateBuilder
	 */
	private function create_builder( ?CurrencyRateProviderRegistry $registry = null ): MultiCurrencyStateBuilder {
		return new MultiCurrencyStateBuilder(
			$this->create_localization(),
			new MultiCurrencyRateService( $registry ?? new CurrencyRateProviderRegistry() ),
			new MultiCurrencyDatabaseCache()
		);
	}

	/**
	 * Create a rate provider whose automatic rate should be ignored.
	 *
	 * @return CurrencyRateProvider
	 */
	private function create_available_rate_provider(): CurrencyRateProvider {
		return new class() implements CurrencyRateProvider {
			/**
			 * Get the provider identifier.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'available-provider';
			}

			/**
			 * Tell whether this provider is available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return true;
			}

			/**
			 * Get provider rates.
			 *
			 * @param string        $currency_from Currency to convert from.
			 * @param string[]|null $currencies_to Currencies to convert into.
			 * @return array<string,mixed>
			 */
			public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array {
				unset( $currency_from, $currencies_to );

				return array( 'gbp' => 0.82 );
			}
		};
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
					'num_decimals' => 'JPY' === strtoupper( (string) $currency_code ) ? 0 : 2,
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
