<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Providers;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\CurrencyRateProvider;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use WC_Unit_Test_Case;

/**
 * Tests for the CurrencyRateProviderRegistry class.
 */
class CurrencyRateProviderRegistryTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should store providers by identifier.
	 */
	public function test_stores_providers_by_identifier(): void {
		$registry = new CurrencyRateProviderRegistry();
		$provider = $this->create_provider( 'alpha', true );

		$registry->register( $provider );

		$this->assertSame( $provider, $registry->get_provider( 'alpha' ) );
		$this->assertSame( array( 'alpha' => $provider ), $registry->get_providers() );
	}

	/**
	 * @testdox Should replace duplicate provider identifiers.
	 */
	public function test_replaces_duplicate_provider_identifier(): void {
		$registry = new CurrencyRateProviderRegistry();
		$first    = $this->create_provider( 'alpha', false );
		$second   = $this->create_provider( 'alpha', true );

		$registry->register( $first );
		$registry->register( $second );

		$this->assertSame( $second, $registry->get_provider( 'alpha' ) );
		$this->assertSame( $second, $registry->get_available_provider() );
	}

	/**
	 * @testdox Should return the first available provider.
	 */
	public function test_returns_first_available_provider(): void {
		$registry    = new CurrencyRateProviderRegistry();
		$unavailable = $this->create_provider( 'alpha', false );
		$available   = $this->create_provider( 'beta', true );

		$registry->register( $unavailable );
		$registry->register( $available );

		$this->assertSame( $available, $registry->get_available_provider() );
	}

	/**
	 * @testdox Should return null when no provider is available.
	 */
	public function test_returns_null_when_no_provider_is_available(): void {
		$registry = new CurrencyRateProviderRegistry();

		$registry->register( $this->create_provider( 'alpha', false ) );

		$this->assertNull( $registry->get_available_provider() );
	}

	/**
	 * Create a fake rate provider.
	 *
	 * @param string $id           Provider ID.
	 * @param bool   $is_available Whether the provider is available.
	 * @return CurrencyRateProvider
	 */
	private function create_provider( string $id, bool $is_available ): CurrencyRateProvider {
		return new class( $id, $is_available ) implements CurrencyRateProvider {
			/**
			 * Provider ID.
			 *
			 * @var string
			 */
			private string $id;

			/**
			 * Whether the provider is available.
			 *
			 * @var bool
			 */
			private bool $is_available;

			/**
			 * Constructor.
			 *
			 * @param string $id           Provider ID.
			 * @param bool   $is_available Whether the provider is available.
			 */
			public function __construct( string $id, bool $is_available ) {
				$this->id           = $id;
				$this->is_available = $is_available;
			}

			/**
			 * Get the provider identifier.
			 *
			 * @return string
			 */
			public function get_id(): string {
				return $this->id;
			}

			/**
			 * Tell whether automatic rates are currently available.
			 *
			 * @return bool
			 */
			public function is_available(): bool {
				return $this->is_available;
			}

			/**
			 * Get currency rates.
			 *
			 * @param string        $currency_from Currency to convert from.
			 * @param string[]|null $currencies_to Currencies to convert into, or null for all supported.
			 * @return array<string,mixed>
			 */
			public function get_currency_rates( string $currency_from, ?array $currencies_to = null ): array {
				return array();
			}
		};
	}
}
