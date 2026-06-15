<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencySwitcherProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencySwitcherProjectionService class.
 */
class MultiCurrencySwitcherProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project widget markup with preserved query parameters and currency labels.
	 */
	public function test_projects_widget_markup_with_preserved_query_parameters_and_currency_labels(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$markup = $sut->get_widget_markup(
			array(
				'title'  => 'Shop currency',
				'symbol' => true,
				'flag'   => true,
			),
			array(
				'before_widget' => '<section class="widget">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2>',
				'after_title'   => '</h2>',
			),
			array(
				's'        => 'shirts',
				'currency' => 'EUR',
				'paged'    => '2',
			)
		);

		$this->assertStringStartsWith( '<section class="widget">', $markup );
		$this->assertStringContainsString( '<h2>Shop currency</h2>', $markup );
		$this->assertStringContainsString( '<form>', $markup );
		$this->assertStringContainsString( 'class="js-woopayments-currency-switcher"', $markup );
		$this->assertStringContainsString( 'aria-label="Shop currency"', $markup );
		$this->assertStringContainsString( 'name="s" value="shirts"', $markup );
		$this->assertStringContainsString( 'name="paged" value="2"', $markup );
		$this->assertStringNotContainsString( 'type="hidden" name="currency"', $markup );
		$this->assertStringContainsString( 'value="GBP" selected', $markup );
		$this->assertStringContainsString(
			$this->get_flag( 'GB' ) . ' ' . get_woocommerce_currency_symbol( 'GBP' ) . ' GBP',
			$markup
		);
		$this->assertStringEndsWith( '</section>', $markup );
	}

	/**
	 * @testdox Should project block markup with styles and default accessible label.
	 */
	public function test_projects_block_markup_with_styles_and_default_accessible_label(): void {
		$sut = $this->create_service( $this->create_state( 'JPY' ) );

		$markup = $sut->get_block_markup(
			array(
				'symbol'          => false,
				'flag'            => true,
				'fontSize'        => 18,
				'fontLineHeight'  => 1.4,
				'fontColor'       => '#123456',
				'border'          => false,
				'borderRadius'    => 6,
				'borderColor'     => '#654321',
				'backgroundColor' => 'transparent',
			),
			array(
				'orderby' => 'price',
			)
		);

		$this->assertStringContainsString( '<form>', $markup );
		$this->assertStringContainsString( 'name="orderby" value="price"', $markup );
		$this->assertStringContainsString( 'class="currency-switcher-holder"', $markup );
		$this->assertStringContainsString( 'line-height: 1.4;', $markup );
		$this->assertStringContainsString( 'class="js-woopayments-currency-switcher"', $markup );
		$this->assertStringContainsString( 'aria-label="Currency"', $markup );
		$this->assertStringContainsString( 'border: 0px solid;', $markup );
		$this->assertStringContainsString( 'border-radius: 6px;', $markup );
		$this->assertStringContainsString( 'border-color: #654321;', $markup );
		$this->assertStringContainsString( 'font-size: 18px;', $markup );
		$this->assertStringContainsString( 'color: #123456;', $markup );
		$this->assertStringContainsString( 'background-color: transparent;', $markup );
		$this->assertStringContainsString( $this->get_flag( 'JP' ) . ' JPY', $markup );
		$this->assertStringContainsString( 'value="JPY" selected', $markup );
	}

	/**
	 * @testdox Should return empty markup when switching is disabled.
	 */
	public function test_returns_empty_markup_when_switching_is_disabled(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame( '', $sut->get_widget_markup( array(), array(), array(), true ) );
		$this->assertSame( '', $sut->get_block_markup( array(), array(), true ) );
	}

	/**
	 * @testdox Should return empty markup when only one currency is enabled.
	 */
	public function test_returns_empty_markup_when_only_one_currency_is_enabled(): void {
		$sut = $this->create_service( $this->create_single_currency_state() );

		$this->assertSame( '', $sut->get_widget_markup() );
		$this->assertSame( '', $sut->get_block_markup() );
	}

	/**
	 * Create the projection service.
	 *
	 * @param MultiCurrencyState $state Multi-currency state.
	 * @return MultiCurrencySwitcherProjectionService
	 */
	private function create_service( MultiCurrencyState $state ): MultiCurrencySwitcherProjectionService {
		return new MultiCurrencySwitcherProjectionService( $this->create_state_builder( $state ) );
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
	 * @param string $selected_code Selected currency code.
	 * @return MultiCurrencyState
	 */
	private function create_state( string $selected_code ): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', true );
		$gbp = $this->create_currency( 'GBP', false );
		$jpy = $this->create_currency( 'JPY', false );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
			'JPY' => $jpy,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $enabled[ $selected_code ] );
	}

	/**
	 * Create a single-currency state.
	 *
	 * @return MultiCurrencyState
	 */
	private function create_single_currency_state(): MultiCurrencyState {
		$usd = $this->create_currency( 'USD', true );

		return new MultiCurrencyState( array( 'USD' => $usd ), array( 'USD' => $usd ), $usd, $usd );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param bool   $is_default Whether this is the default currency.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, bool $is_default ): MultiCurrencyCurrency {
		return new MultiCurrencyCurrency( $this->create_localization(), $code, 1.0, $is_default );
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
				unset( $country );

				return array();
			}
		};
	}

	/**
	 * Get an ISO country flag.
	 *
	 * @param string $country_code Country code.
	 * @return string
	 */
	private function get_flag( string $country_code ): string {
		$first  = 0x1F1E6 + ord( $country_code[0] ) - ord( 'A' );
		$second = 0x1F1E6 + ord( $country_code[1] ) - ord( 'A' );

		return html_entity_decode(
			'&#x' . dechex( $first ) . ';&#x' . dechex( $second ) . ';',
			ENT_NOQUOTES,
			'UTF-8'
		);
	}
}
