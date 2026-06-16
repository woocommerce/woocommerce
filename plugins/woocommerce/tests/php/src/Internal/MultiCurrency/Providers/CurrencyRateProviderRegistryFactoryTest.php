<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistryFactory;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\WooPaymentsCurrencyRateProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the CurrencyRateProviderRegistryFactory class.
 */
class CurrencyRateProviderRegistryFactoryTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_legacy_proxy_mocks();

		parent::tearDown();
	}

	/**
	 * @testdox Should create a fresh registry with a fail-closed WooPayments provider.
	 */
	public function test_creates_fresh_registry_with_fail_closed_woopayments_provider(): void {
		$this->mock_woopayments_runtime( false, null, null );
		$sut = wc_get_container()->get( CurrencyRateProviderRegistryFactory::class );

		$first  = $sut->create();
		$second = $sut->create();

		$this->assertNotSame( $first, $second );
		$this->assertInstanceOf( WooPaymentsCurrencyRateProvider::class, $first->get_provider( WooPaymentsCurrencyRateProvider::PROVIDER_ID ) );
		$this->assertNull( $first->get_available_provider() );
	}

	/**
	 * @testdox Should expose an available WooPayments rate provider when legacy boundaries are usable.
	 */
	public function test_exposes_available_woopayments_provider_when_legacy_boundaries_are_usable(): void {
		$this->mock_woopayments_runtime(
			true,
			$this->create_recording_account(),
			$this->create_recording_api_client()
		);
		$sut      = wc_get_container()->get( CurrencyRateProviderRegistryFactory::class );
		$provider = $sut->create()->get_available_provider();

		$this->assertInstanceOf( WooPaymentsCurrencyRateProvider::class, $provider );
		$this->assertSame( array( 'GBP' ), $provider->get_supported_currencies() );
		$this->assertSame( array( 'gbp' => 0.82 ), $provider->get_currency_rates( 'usd', array( 'gbp' ) ) );
	}

	/**
	 * Mock WooPayments account and API-client access.
	 *
	 * @param bool        $class_loaded Whether WC_Payments is available.
	 * @param object|null $account      Account service.
	 * @param object|null $api_client   API client.
	 */
	private function mock_woopayments_runtime( bool $class_loaded, ?object $account, ?object $api_client ): void {
		$this->register_legacy_proxy_function_mocks(
			array(
				'class_exists' => function ( $class_name, $autoload = true ) use ( $class_loaded ) {
					if ( 'WC_Payments' === ltrim( (string) $class_name, '\\' ) ) {
						return $class_loaded;
					}
					return class_exists( $class_name, $autoload );
				},
			)
		);

		if ( $class_loaded ) {
			$this->register_legacy_proxy_static_mocks(
				array(
					'WC_Payments' => array(
						'get_account_service'     => static fn() => $account,
						'get_payments_api_client' => static fn() => $api_client,
					),
				)
			);
		}
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

				return array( 'gbp' => 0.82 );
			}
		};
	}
}
