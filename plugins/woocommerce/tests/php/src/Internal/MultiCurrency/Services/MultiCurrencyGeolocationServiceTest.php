<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyGeolocationService class.
 */
class MultiCurrencyGeolocationServiceTest extends WC_Unit_Test_Case {

	/**
	 * Original user agent.
	 *
	 * @var string|null
	 */
	private ?string $original_user_agent = null;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: null;
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		remove_all_filters( 'pre_option_woocommerce_default_country' );
		remove_all_filters( 'woocommerce_customer_default_location' );

		if ( null === $this->original_user_agent ) {
			unset( $_SERVER['HTTP_USER_AGENT'] );
		} else {
			$_SERVER['HTTP_USER_AGENT'] = $this->original_user_agent;
		}

		parent::tear_down();
	}

	/**
	 * @testdox Should return currency from customer country.
	 */
	public function test_returns_currency_from_customer_country(): void {
		$sut = new MultiCurrencyGeolocationService(
			$this->create_localization(),
			static fn() => 'GB'
		);

		$this->assertSame( 'GB', $sut->get_country_by_customer_location() );
		$this->assertSame( 'GBP', $sut->get_currency_by_customer_location() );
	}

	/**
	 * @testdox Should fall back to default country when customer country is not allowed.
	 */
	public function test_falls_back_to_default_country_when_customer_country_is_not_allowed(): void {
		add_filter( 'pre_option_woocommerce_default_country', static fn() => 'US:CA' );
		add_filter(
			'woocommerce_customer_default_location',
			static function ( string $location ): string {
				return $location;
			}
		);

		$sut = new MultiCurrencyGeolocationService(
			$this->create_localization(),
			static fn() => 'ZZ'
		);

		$this->assertSame( 'US', $sut->get_country_by_customer_location() );
		$this->assertSame( 'USD', $sut->get_currency_by_customer_location() );
	}

	/**
	 * @testdox Should ignore bot user agents.
	 */
	public function test_ignores_bot_user_agents(): void {
		$_SERVER['HTTP_USER_AGENT'] = 'ExampleBot/1.0';
		add_filter( 'pre_option_woocommerce_default_country', static fn() => 'US' );

		$sut = new MultiCurrencyGeolocationService(
			$this->create_localization(),
			static fn() => 'GB'
		);

		$this->assertSame( 'US', $sut->get_country_by_customer_location() );
		$this->assertSame( 'USD', $sut->get_currency_by_customer_location() );
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
				$map = array(
					'GB' => array( 'currency_code' => 'GBP' ),
					'US' => array( 'currency_code' => 'USD' ),
				);

				return $map[ strtoupper( (string) $country ) ] ?? array();
			}
		};
	}
}
