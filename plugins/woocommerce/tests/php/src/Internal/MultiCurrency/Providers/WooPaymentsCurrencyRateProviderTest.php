<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyAccountInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyApiClientInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\WooPaymentsCurrencyRateProvider;
use WC_Unit_Test_Case;

/**
 * Tests for the WooPaymentsCurrencyRateProvider class.
 */
class WooPaymentsCurrencyRateProviderTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should expose the WooPayments provider identifier.
	 */
	public function test_exposes_provider_identifier(): void {
		$provider = new WooPaymentsCurrencyRateProvider(
			$this->create_account( true, false ),
			$this->create_api_client( true )
		);

		$this->assertSame( 'woopayments', $provider->get_id() );
	}

	/**
	 * @testdox Should be available only when API and account are usable.
	 *
	 * @dataProvider availability_provider
	 *
	 * @param bool $server_connected Whether the API client is connected.
	 * @param bool $account_connected Whether the account is connected.
	 * @param bool $account_rejected Whether the account is rejected.
	 * @param bool $expected Expected availability.
	 */
	public function test_availability_requires_usable_api_and_account( bool $server_connected, bool $account_connected, bool $account_rejected, bool $expected ): void {
		$provider = new WooPaymentsCurrencyRateProvider(
			$this->create_account( $account_connected, $account_rejected ),
			$this->create_api_client( $server_connected )
		);

		$this->assertSame( $expected, $provider->is_available() );
	}

	/**
	 * Get availability cases.
	 *
	 * @return array<string,array{bool,bool,bool,bool}>
	 */
	public function availability_provider(): array {
		return array(
			'usable'               => array( true, true, false, true ),
			'server disconnected'  => array( false, true, false, false ),
			'account disconnected' => array( true, false, false, false ),
			'account rejected'     => array( true, true, true, false ),
		);
	}

	/**
	 * @testdox Should delegate currency rate requests to the API client.
	 */
	public function test_delegates_currency_rate_requests_to_api_client(): void {
		$api_client = $this->create_api_client( true, array( 'eur' => 1.2 ) );
		$provider   = new WooPaymentsCurrencyRateProvider(
			$this->create_account( true, false ),
			$api_client
		);

		$this->assertSame( array( 'eur' => 1.2 ), $provider->get_currency_rates( 'usd', array( 'eur' ) ) );
		$this->assertSame( 'usd', $api_client->last_currency_from );
		$this->assertSame( array( 'eur' ), $api_client->last_currencies_to );
	}

	/**
	 * Create a fake account implementation.
	 *
	 * @param bool $connected Whether the account is connected.
	 * @param bool $rejected  Whether the account is rejected.
	 * @return MultiCurrencyAccountInterface
	 */
	private function create_account( bool $connected, bool $rejected ): MultiCurrencyAccountInterface {
		return new class( $connected, $rejected ) implements MultiCurrencyAccountInterface {
			/**
			 * Whether the account is connected.
			 *
			 * @var bool
			 */
			private bool $connected;

			/**
			 * Whether the account is rejected.
			 *
			 * @var bool
			 */
			private bool $rejected;

			/**
			 * Constructor.
			 *
			 * @param bool $connected Whether the account is connected.
			 * @param bool $rejected  Whether the account is rejected.
			 */
			public function __construct( bool $connected, bool $rejected ) {
				$this->connected = $connected;
				$this->rejected  = $rejected;
			}

			/**
			 * Tell whether the rate provider account is connected.
			 *
			 * @param bool $on_error Value to return on provider errors.
			 * @return bool
			 */
			public function is_provider_connected( bool $on_error = false ): bool {
				return $this->connected;
			}

			/**
			 * Tell whether the connected account is rejected.
			 *
			 * @return bool
			 */
			public function is_account_rejected(): bool {
				return $this->rejected;
			}

			/**
			 * Get cached provider account data.
			 *
			 * @param bool $force_refresh Whether to force-refresh provider data.
			 * @return array<string,mixed>|bool
			 */
			public function get_cached_account_data( bool $force_refresh = false ) {
				return array();
			}

			/**
			 * Get account-supported customer currencies.
			 *
			 * @return string[]
			 */
			public function get_account_customer_supported_currencies(): array {
				return array();
			}

			/**
			 * Get provider-supported countries.
			 *
			 * @return string[]
			 */
			public function get_supported_countries(): array {
				return array();
			}

			/**
			 * Get the provider onboarding URL.
			 *
			 * @return string
			 */
			public function get_provider_onboarding_page_url(): string {
				return '';
			}
		};
	}

	/**
	 * Create a fake API client implementation.
	 *
	 * @param bool                $connected Whether the API client is connected.
	 * @param array<string,mixed> $rates     Rates to return.
	 * @return MultiCurrencyApiClientInterface
	 */
	private function create_api_client( bool $connected, array $rates = array() ): MultiCurrencyApiClientInterface {
		return new class( $connected, $rates ) implements MultiCurrencyApiClientInterface {
			/**
			 * Whether the API client is connected.
			 *
			 * @var bool
			 */
			private bool $connected;

			/**
			 * Rates to return.
			 *
			 * @var array<string,mixed>
			 */
			private array $rates;

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
			 * Constructor.
			 *
			 * @param bool                $connected Whether the API client is connected.
			 * @param array<string,mixed> $rates     Rates to return.
			 */
			public function __construct( bool $connected, array $rates ) {
				$this->connected = $connected;
				$this->rates     = $rates;
			}

			/**
			 * Tell whether the API client is connected to its server.
			 *
			 * @return bool
			 */
			public function is_server_connected(): bool {
				return $this->connected;
			}

			/**
			 * Get currency rates.
			 *
			 * @param string        $currency_from Currency to convert from.
			 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
			 * @return array<string,mixed>
			 */
			public function get_currency_rates( string $currency_from, $currencies_to = null ): array {
				$this->last_currency_from = $currency_from;
				$this->last_currencies_to = $currencies_to;

				return $this->rates;
			}
		};
	}
}
