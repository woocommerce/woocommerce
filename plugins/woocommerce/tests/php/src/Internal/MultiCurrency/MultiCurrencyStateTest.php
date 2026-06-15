<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyState class.
 */
class MultiCurrencyStateTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should expose currencies and selected state.
	 */
	public function test_exposes_currencies_and_selected_state(): void {
		$default = $this->create_currency( 'USD', 1.0, true );
		$gbp     = $this->create_currency( 'GBP', 0.8, false );
		$state   = new MultiCurrencyState(
			array(
				'USD' => $default,
				'GBP' => $gbp,
			),
			array(
				'USD' => $default,
				'GBP' => $gbp,
			),
			$default,
			$gbp
		);

		$this->assertSame( array( 'USD', 'GBP' ), array_keys( $state->get_available_currencies() ) );
		$this->assertSame( array( 'USD', 'GBP' ), array_keys( $state->get_enabled_currencies() ) );
		$this->assertSame( $default, $state->get_default_currency() );
		$this->assertSame( $gbp, $state->get_selected_currency() );
	}

	/**
	 * @testdox Should report whether additional currencies are enabled.
	 */
	public function test_reports_whether_additional_currencies_are_enabled(): void {
		$default = $this->create_currency( 'USD', 1.0, true );
		$gbp     = $this->create_currency( 'GBP', 0.8, false );

		$default_only = new MultiCurrencyState(
			array( 'USD' => $default ),
			array( 'USD' => $default ),
			$default,
			$default
		);
		$with_gbp     = new MultiCurrencyState(
			array(
				'USD' => $default,
				'GBP' => $gbp,
			),
			array(
				'USD' => $default,
				'GBP' => $gbp,
			),
			$default,
			$default
		);

		$this->assertFalse( $default_only->has_additional_currencies_enabled() );
		$this->assertTrue( $with_gbp->has_additional_currencies_enabled() );
	}

	/**
	 * @testdox Should expose customer currencies.
	 */
	public function test_exposes_customer_currencies(): void {
		$default = $this->create_currency( 'USD', 1.0, true );
		$state   = new MultiCurrencyState(
			array( 'USD' => $default ),
			array( 'USD' => $default ),
			$default,
			$default,
			array( 'GBP', 'JPY' )
		);

		$this->assertSame( array( 'GBP', 'JPY' ), $state->get_customer_currencies() );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default ): MultiCurrencyCurrency {
		return new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
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
