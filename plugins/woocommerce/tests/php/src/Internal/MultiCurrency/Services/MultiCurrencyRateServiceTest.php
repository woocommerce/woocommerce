<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\CurrencyRateProvider;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyRateService class.
 */
class MultiCurrencyRateServiceTest extends WC_Unit_Test_Case {

	/**
	 * Option keys touched by these tests.
	 *
	 * @var string[]
	 */
	private array $option_keys = array(
		'wcpay_multi_currency_exchange_rate_eur',
		'wcpay_multi_currency_manual_rate_eur',
	);

	/**
	 * Clean up options before each test.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->delete_rate_options();
	}

	/**
	 * Clean up options after each test.
	 */
	public function tear_down(): void {
		$this->delete_rate_options();

		parent::tear_down();
	}

	/**
	 * @testdox Should return manual rates without a provider.
	 */
	public function test_returns_manual_rate_without_provider(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_eur', '1.25' );

		$service = new MultiCurrencyRateService( new CurrencyRateProviderRegistry() );

		$this->assertSame( 1.25, $service->get_rate( 'USD', 'EUR' ) );
	}

	/**
	 * @testdox Should return null for invalid manual rates.
	 *
	 * @dataProvider invalid_manual_rate_provider
	 *
	 * @param mixed $manual_rate Manual rate option value.
	 */
	public function test_returns_null_for_invalid_manual_rates( $manual_rate ): void {
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_eur', $manual_rate );

		$service = new MultiCurrencyRateService( new CurrencyRateProviderRegistry() );

		$this->assertNull( $service->get_rate( 'USD', 'EUR' ) );
	}

	/**
	 * Get invalid manual rate cases.
	 *
	 * @return array<string,array{mixed}>
	 */
	public function invalid_manual_rate_provider(): array {
		return array(
			'zero'     => array( '0' ),
			'negative' => array( '-1' ),
			'text'     => array( 'not-a-rate' ),
			'missing'  => array( null ),
		);
	}

	/**
	 * @testdox Should resolve automatic rates from the available provider.
	 */
	public function test_resolves_automatic_rate_from_available_provider(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'automatic' );

		$registry = new CurrencyRateProviderRegistry();
		$registry->register( $this->create_provider( true, array( 'eur' => 1.2 ) ) );
		$service = new MultiCurrencyRateService( $registry );

		$this->assertSame( 1.2, $service->get_rate( 'USD', 'EUR' ) );
	}

	/**
	 * @testdox Should accept uppercase provider rate keys.
	 */
	public function test_accepts_uppercase_provider_rate_keys(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'automatic' );

		$registry = new CurrencyRateProviderRegistry();
		$registry->register( $this->create_provider( true, array( 'EUR' => 1.3 ) ) );
		$service = new MultiCurrencyRateService( $registry );

		$this->assertSame( 1.3, $service->get_rate( 'USD', 'EUR' ) );
	}

	/**
	 * @testdox Should return null for automatic rates when no provider is available.
	 */
	public function test_returns_null_for_automatic_rate_without_provider(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_eur', 'automatic' );

		$registry = new CurrencyRateProviderRegistry();
		$registry->register( $this->create_provider( false, array( 'eur' => 1.2 ) ) );
		$service = new MultiCurrencyRateService( $registry );

		$this->assertNull( $service->get_rate( 'USD', 'EUR' ) );
	}

	/**
	 * Delete rate options touched by these tests.
	 */
	private function delete_rate_options(): void {
		foreach ( $this->option_keys as $option_key ) {
			delete_option( $option_key );
		}
	}

	/**
	 * Create a fake provider.
	 *
	 * @param bool                $available Whether the provider is available.
	 * @param array<string,mixed> $rates     Rates to return.
	 * @return CurrencyRateProvider
	 */
	private function create_provider( bool $available, array $rates ): CurrencyRateProvider {
		return new class( $available, $rates ) implements CurrencyRateProvider {
			/**
			 * Whether the provider is available.
			 *
			 * @var bool
			 */
			private bool $available;

			/**
			 * Rates to return.
			 *
			 * @var array<string,mixed>
			 */
			private array $rates;

			/**
			 * Constructor.
			 *
			 * @param bool                $available Whether the provider is available.
			 * @param array<string,mixed> $rates     Rates to return.
			 */
			public function __construct( bool $available, array $rates ) {
				$this->available = $available;
				$this->rates     = $rates;
			}

			/**
			 * Get the provider identifier.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return 'fake';
			}

			/**
			 * Tell whether automatic rates are currently available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return $this->available;
			}

			/**
			 * Get currency rates.
			 *
			 * @param string        $currency_from Currency to convert from.
			 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
			 * @return array<string,mixed>
			 */
			public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array {
				return $this->rates;
			}
		};
	}
}
