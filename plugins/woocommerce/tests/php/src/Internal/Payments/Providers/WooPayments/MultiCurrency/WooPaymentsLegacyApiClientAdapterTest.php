<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Payments\Providers\WooPayments\MultiCurrency;

use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\MultiCurrency\WooPaymentsLegacyApiClientAdapter;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsLegacyApiClientAdapter class.
 */
class WooPaymentsLegacyApiClientAdapterTest extends WC_Unit_Test_Case {

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->reset_legacy_proxy_mocks();

		parent::tearDown();
	}

	/**
	 * @testdox Should fail closed when the WooPayments runtime is absent.
	 */
	public function test_fails_closed_when_woopayments_runtime_is_absent(): void {
		$this->mock_woopayments_runtime( false, null );
		$sut = wc_get_container()->get( WooPaymentsLegacyApiClientAdapter::class );

		$this->assertFalse( $sut->is_server_connected() );
		$this->assertSame( array(), $sut->get_currency_rates( 'usd', array( 'gbp' ) ) );
	}

	/**
	 * @testdox Should delegate to the legacy API client when available.
	 */
	public function test_delegates_to_legacy_api_client_when_available(): void {
		$api_client = $this->create_recording_api_client();
		$this->mock_woopayments_runtime( true, $api_client );
		$sut = wc_get_container()->get( WooPaymentsLegacyApiClientAdapter::class );

		$this->assertTrue( $sut->is_server_connected() );
		$this->assertSame( array( 'gbp' => 0.82 ), $sut->get_currency_rates( 'usd', array( 'gbp' ) ) );
		$this->assertSame( 'usd', $api_client->last_currency_from );
		$this->assertSame( array( 'gbp' ), $api_client->last_currencies_to );
	}

	/**
	 * @testdox Should return empty rates when the legacy API client throws.
	 */
	public function test_returns_empty_rates_when_legacy_api_client_throws(): void {
		$this->mock_woopayments_runtime( true, $this->create_throwing_api_client() );
		$sut = wc_get_container()->get( WooPaymentsLegacyApiClientAdapter::class );

		$this->assertFalse( $sut->is_server_connected() );
		$this->assertSame( array(), $sut->get_currency_rates( 'usd', array( 'gbp' ) ) );
	}

	/**
	 * Mock WooPayments API-client access.
	 *
	 * @param bool        $class_loaded Whether WC_Payments is available.
	 * @param object|null $api_client   API client.
	 */
	private function mock_woopayments_runtime( bool $class_loaded, ?object $api_client ): void {
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
						'get_payments_api_client' => static fn() => $api_client,
					),
				)
			);
		}
	}

	/**
	 * Create a recording legacy API client test double.
	 *
	 * @return object
	 */
	private function create_recording_api_client(): object {
		return new class() {
			/**
			 * Last source currency.
			 *
			 * @var string|null
			 */
			public ?string $last_currency_from = null;

			/**
			 * Last target currencies.
			 *
			 * @var string[]|null
			 */
			public ?array $last_currencies_to = null;

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
				$this->last_currency_from = $currency_from;
				$this->last_currencies_to = $currencies_to;

				return array( 'gbp' => 0.82 );
			}
		};
	}

	/**
	 * Create a throwing legacy API client test double.
	 *
	 * @return object
	 */
	private function create_throwing_api_client(): object {
		return new class() {
			/**
			 * Throw when checking connection.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function is_server_connected(): bool {
				throw new \RuntimeException( 'API failed' );
			}

			/**
			 * Throw when fetching rates.
			 *
			 * @throws \RuntimeException Always thrown.
			 */
			public function get_currency_rates(): array {
				throw new \RuntimeException( 'API failed' );
			}
		};
	}
}
