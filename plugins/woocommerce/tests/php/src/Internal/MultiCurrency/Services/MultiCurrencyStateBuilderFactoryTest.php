<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilderFactory;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStateBuilderFactory class.
 */
class MultiCurrencyStateBuilderFactoryTest extends WC_Unit_Test_Case {

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->original_currency = get_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency', 'USD' );
		$this->delete_options();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->delete_options();
		$this->reset_legacy_proxy_mocks();
		update_option( 'woocommerce_currency', $this->original_currency );

		parent::tearDown();
	}

	/**
	 * @testdox Should build automatic currencies from the WooPayments provider registry.
	 */
	public function test_builds_automatic_currencies_from_woopayments_provider_registry(): void {
		$this->mock_woopayments_runtime(
			$this->create_recording_account(),
			$this->create_recording_api_client()
		);
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP', 'EUR' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'automatic' );

		$state = wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )->create()->build();

		$this->assertSame( array( 'USD', 'GBP' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( 0.82, $state->get_enabled_currencies()['GBP']->get_rate() );
		$this->assertArrayNotHasKey( 'EUR', $state->get_enabled_currencies() );
	}

	/**
	 * @testdox Should create state builders with supplied localization and cache boundaries.
	 */
	public function test_create_uses_supplied_localization_and_cache_boundaries(): void {
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );

		$state = wc_get_container()->get( MultiCurrencyStateBuilderFactory::class )
			->create(
				$this->create_custom_localization(),
				$this->create_cached_rates_cache()
			)
			->build();

		$this->assertSame( array( 'USD', 'GBP' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( 0.82, $state->get_enabled_currencies()['GBP']->get_rate() );
		$this->assertSame( 'right', $state->get_enabled_currencies()['GBP']->get_symbol_position() );
	}

	/**
	 * Delete options touched by these tests.
	 */
	private function delete_options(): void {
		foreach (
			array(
				'wcpay_multi_currency_enabled_currencies',
				'wcpay_multi_currency_exchange_rate_gbp',
				'wcpay_multi_currency_exchange_rate_eur',
				MultiCurrencyCacheInterface::CURRENCIES_KEY,
			) as $option_key
		) {
			delete_option( $option_key );
		}
	}

	/**
	 * Mock WooPayments account and API-client access.
	 *
	 * @param object $account    Account service.
	 * @param object $api_client API client.
	 */
	private function mock_woopayments_runtime( object $account, object $api_client ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => function ( $class_name, $autoload = true ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return true;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);

		$this->register_legacy_proxy_static_mocks(
			array(
				'WC_Payments' => array(
					'get_account_service'     => static fn() => $account,
					'get_payments_api_client' => static fn() => $api_client,
				),
			)
		);
	}

	/**
	 * Create a recording legacy account test double.
	 *
	 * @return object
	 */
	private function create_recording_account(): object {
		return new class() {
			/**
			 * Tell whether the provider account is connected.
			 *
			 * @param bool $on_error Error fallback.
			 * @return bool
			 */
			public function is_provider_connected( bool $on_error = false ): bool {
				unset( $on_error );

				return true;
			}

			/**
			 * Tell whether the account is rejected.
			 *
			 * @return bool
			 */
			public function is_account_rejected(): bool {
				return false;
			}

			/**
			 * Get cached account data.
			 *
			 * @param bool $force_refresh Whether to refresh.
			 * @return array<string,mixed>
			 */
			public function get_cached_account_data( bool $force_refresh = false ): array {
				unset( $force_refresh );

				return array( 'customer_currencies' => array( 'supported' => array( 'GBP' ) ) );
			}

			/**
			 * Get account-supported customer currencies.
			 *
			 * @return string[]
			 */
			public function get_account_customer_supported_currencies(): array {
				return array( 'GBP' );
			}
		};
	}

	/**
	 * Create a recording legacy API client test double.
	 *
	 * @return object
	 */
	private function create_recording_api_client(): object {
		return new class() {
			/**
			 * Tell whether the server is connected.
			 *
			 * @return bool
			 */
			public function is_server_connected(): bool {
				return true;
			}

			/**
			 * Get currency rates.
			 *
			 * @param string        $currency_from Source currency.
			 * @param string[]|null $currencies_to Target currencies.
			 * @return array<string,float>
			 */
			public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array {
				unset( $currency_from, $currencies_to );

				return array(
					'gbp' => 0.82,
					'eur' => 0.91,
				);
			}
		};
	}

	/**
	 * Create a custom localization test double.
	 *
	 * @return MultiCurrencyLocalizationInterface
	 */
	private function create_custom_localization(): MultiCurrencyLocalizationInterface {
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
					'currency_pos' => 'right',
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

	/**
	 * Create a cached-rate cache test double.
	 *
	 * @return MultiCurrencyCacheInterface
	 */
	private function create_cached_rates_cache(): MultiCurrencyCacheInterface {
		return new class() implements MultiCurrencyCacheInterface {
			/**
			 * Get a value from cache.
			 *
			 * @param string $key   Cache key.
			 * @param bool   $force Whether to return cached data.
			 * @return mixed
			 */
			public function get( string $key, bool $force = false ) {
				unset( $force );

				if ( MultiCurrencyCacheInterface::CURRENCIES_KEY !== $key ) {
					return null;
				}

				return array(
					'currencies' => array(
						'gbp' => 0.82,
					),
					'updated'    => 123,
				);
			}

			/**
			 * Get a value from cache or regenerate and store it.
			 *
			 * @param string   $key           Cache key.
			 * @param callable $generator     Regenerates missing data.
			 * @param callable $validate_data Validates cached data.
			 * @param bool     $force_refresh Whether to force regeneration.
			 * @param bool     $refreshed     Set true when cache is refreshed successfully.
			 * @return mixed|null
			 */
			public function get_or_add( string $key, callable $generator, callable $validate_data, bool $force_refresh = false, bool &$refreshed = false ) {
				unset( $generator, $validate_data, $force_refresh, $refreshed );

				return $this->get( $key );
			}

			/**
			 * Delete a cache value.
			 *
			 * @param string $key Cache key.
			 */
			public function delete( string $key ): void {
				unset( $key );
			}
		};
	}
}
