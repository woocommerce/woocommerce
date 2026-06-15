<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Exceptions\InvalidCurrencyException;
use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyLocalizationInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyCurrency;
use Automattic\WooCommerce\Internal\MultiCurrency\MultiCurrencyState;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyFrontendProjectionService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyGeolocationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyFrontendProjectionService class.
 */
class MultiCurrencyFrontendProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Original currency position.
	 *
	 * @var string
	 */
	private string $original_currency_pos;

	/**
	 * Original WooCommerce session.
	 *
	 * @var mixed
	 */
	private $original_session;

	/**
	 * Option keys touched by these tests.
	 *
	 * @var string[]
	 */
	private array $option_keys = array(
		'wcpay_multi_currency_enable_auto_currency',
		'wcpay_multi_currency_enable_storefront_switcher',
		'wcpay_multi_currency_rendering_mode',
		'_wcpay_feature_mc_cache_optimized',
		'wcpay_multi_currency_exchange_rate_gbp',
		'wcpay_multi_currency_manual_rate_gbp',
		'wcpay_multi_currency_price_rounding_gbp',
		'wcpay_multi_currency_price_charm_gbp',
	);

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_currency     = get_option( 'woocommerce_currency', 'USD' );
		$this->original_currency_pos = get_option( 'woocommerce_currency_pos', 'left' );
		$this->original_session      = WC()->session;

		$this->delete_projection_options();
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency_pos', 'left' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		$this->delete_projection_options();
		update_option( 'woocommerce_currency', $this->original_currency );
		update_option( 'woocommerce_currency_pos', $this->original_currency_pos );
		WC()->session = $this->original_session;

		parent::tear_down();
	}

	/**
	 * @testdox Should project selected currency formatting.
	 */
	public function test_projects_selected_currency_formatting(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame( 'GBP', $sut->get_woocommerce_currency() );
		$this->assertSame( 2, $sut->get_price_decimals( 4 ) );
		$this->assertSame( ',', $sut->get_price_decimal_separator( '.' ) );
		$this->assertSame( '.', $sut->get_price_thousand_separator( ',' ) );
		$this->assertSame( '%2$s&nbsp;%1$s', $sut->get_woocommerce_price_format( '%1$s%2$s' ) );
		$this->assertSame( 'right_space', $sut->get_woocommerce_currency_pos( 'left' ) );
	}

	/**
	 * @testdox Should leave default currency formatting unchanged.
	 */
	public function test_leaves_default_currency_formatting_unchanged(): void {
		$sut = $this->create_service( $this->create_state( 'USD' ) );

		$this->assertSame( 'USD', $sut->get_woocommerce_currency() );
		$this->assertSame( 4, $sut->get_price_decimals( 4 ) );
		$this->assertSame( '.', $sut->get_price_decimal_separator( '.' ) );
		$this->assertSame( ',', $sut->get_price_thousand_separator( ',' ) );
		$this->assertSame( '%1$s%2$s', $sut->get_woocommerce_price_format( '%1$s%2$s' ) );
		$this->assertSame( 'left', $sut->get_woocommerce_currency_pos( 'left' ) );
	}

	/**
	 * @testdox Should include selected currency and rate in cart hash.
	 */
	public function test_cart_hash_includes_selected_currency_and_rate(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame( md5( 'base-hashGBP0.82' ), $sut->add_currency_to_cart_hash( 'base-hash' ) );
	}

	/**
	 * @testdox Should project public config with preserved keys.
	 */
	public function test_projects_public_config_with_preserved_keys(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$config = $sut->get_public_config();

		$this->assertSame(
			array( 'default_currency', 'selected_currency', 'charm_only_products', 'currencies' ),
			array_keys( $config )
		);
		$this->assertSame( 'USD', $config['default_currency'] );
		$this->assertSame( 'USD', $config['selected_currency'] );
		$this->assertTrue( $config['charm_only_products'] );
		$this->assertSame(
			array( 'code', 'symbol', 'rate', 'decimals', 'decimal_sep', 'thousand_sep', 'symbol_pos', 'rounding', 'charm' ),
			array_keys( $config['currencies']['GBP'] )
		);
		$this->assertSame( 'GBP', $config['currencies']['GBP']['code'] );
		$this->assertSame( get_woocommerce_currency_symbol( 'GBP' ), $config['currencies']['GBP']['symbol'] );
		$this->assertSame( 0.82, $config['currencies']['GBP']['rate'] );
		$this->assertSame( 2, $config['currencies']['GBP']['decimals'] );
		$this->assertSame( ',', $config['currencies']['GBP']['decimal_sep'] );
		$this->assertSame( '.', $config['currencies']['GBP']['thousand_sep'] );
		$this->assertSame( 'right_space', $config['currencies']['GBP']['symbol_pos'] );
		$this->assertSame( 0.5, $config['currencies']['GBP']['rounding'] );
		$this->assertSame( -0.1, $config['currencies']['GBP']['charm'] );
	}

	/**
	 * @testdox Should use active session currency without writes.
	 */
	public function test_public_config_uses_active_session_currency_without_writes(): void {
		$session      = $this->create_session( true, 'JPY' );
		WC()->session = $session;
		$sut          = $this->create_service( $this->create_state( 'GBP' ) );

		$config = $sut->get_public_config();

		$this->assertSame( 'JPY', $config['selected_currency'] );
		$this->assertSame( 0, $session->writes );
	}

	/**
	 * @testdox Should use geolocation when auto currency is enabled.
	 */
	public function test_public_config_uses_geolocation_when_auto_currency_is_enabled(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		WC()->session = $this->create_session( false, null );
		$sut          = $this->create_service( $this->create_state( 'USD' ), 'GB' );

		$config = $sut->get_public_config();

		$this->assertSame( 'GBP', $config['selected_currency'] );
	}

	/**
	 * @testdox Should project store currencies and settings.
	 */
	public function test_projects_store_currencies_and_settings(): void {
		update_option( 'wcpay_multi_currency_enable_auto_currency', 'yes' );
		update_option( 'wcpay_multi_currency_enable_storefront_switcher', 'no' );
		update_option( 'wcpay_multi_currency_rendering_mode', 'cache' );
		update_option( '_wcpay_feature_mc_cache_optimized', '1' );
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$currencies = $sut->get_store_currencies();
		$settings   = $sut->get_settings();

		$this->assertSame( array( 'available', 'enabled', 'default' ), array_keys( $currencies ) );
		$this->assertSame( array( 'USD', 'GBP', 'JPY' ), array_keys( $currencies['enabled'] ) );
		$this->assertSame( 'USD', $currencies['default']->get_code() );
		$this->assertTrue( $settings['wcpay_multi_currency_enable_auto_currency'] );
		$this->assertFalse( $settings['wcpay_multi_currency_enable_storefront_switcher'] );
		$this->assertSame( 'cache', $settings['wcpay_multi_currency_rendering_mode'] );
		$this->assertTrue( $settings['is_cache_optimized_feature_enabled'] );
		$this->assertTrue( $sut->is_cache_optimized_mode() );
	}

	/**
	 * @testdox Should return single currency settings for available currency.
	 */
	public function test_returns_single_currency_settings_for_available_currency(): void {
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.82' );
		update_option( 'wcpay_multi_currency_price_rounding_gbp', '0.50' );
		update_option( 'wcpay_multi_currency_price_charm_gbp', '-0.10' );
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->assertSame(
			array(
				'exchange_rate_type' => 'manual',
				'manual_rate'        => '0.82',
				'price_rounding'     => '0.50',
				'price_charm'        => '-0.10',
			),
			$sut->get_single_currency_settings( 'GBP' )
		);
	}

	/**
	 * @testdox Should reject single currency settings for unavailable currency.
	 */
	public function test_rejects_single_currency_settings_for_unavailable_currency(): void {
		$sut = $this->create_service( $this->create_state( 'GBP' ) );

		$this->expectException( InvalidCurrencyException::class );

		$sut->get_single_currency_settings( 'EUR' );
	}

	/**
	 * Create the projection service.
	 *
	 * @param MultiCurrencyState $state       Multi-currency state.
	 * @param string             $geo_country Geolocated country code.
	 * @return MultiCurrencyFrontendProjectionService
	 */
	private function create_service( MultiCurrencyState $state, string $geo_country = 'US' ): MultiCurrencyFrontendProjectionService {
		$localization = $this->create_localization();

		return new MultiCurrencyFrontendProjectionService(
			$this->create_state_builder( $state ),
			$localization,
			new MultiCurrencyGeolocationService( $localization, static fn() => $geo_country )
		);
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
		$usd = $this->create_currency( 'USD', 1.0, true );
		$gbp = $this->create_currency( 'GBP', 0.82, false, '0.50', -0.10 );
		$jpy = $this->create_currency( 'JPY', 151.0, false, '100', 0.0 );

		$enabled = array(
			'USD' => $usd,
			'GBP' => $gbp,
			'JPY' => $jpy,
		);

		return new MultiCurrencyState( $enabled, $enabled, $usd, $enabled[ $selected_code ] );
	}

	/**
	 * Create a currency.
	 *
	 * @param string $code       Currency code.
	 * @param float  $rate       Currency rate.
	 * @param bool   $is_default Whether this is the default currency.
	 * @param string $rounding   Rounding amount.
	 * @param float  $charm      Charm amount.
	 * @return MultiCurrencyCurrency
	 */
	private function create_currency( string $code, float $rate, bool $is_default, string $rounding = '0', float $charm = 0.0 ): MultiCurrencyCurrency {
		$currency = new MultiCurrencyCurrency( $this->create_localization(), $code, $rate, $is_default );
		$currency->set_rounding( $rounding );
		$currency->set_charm( $charm );

		return $currency;
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
				$formats = array(
					'GBP' => array(
						'currency_pos' => 'right_space',
						'thousand_sep' => '.',
						'decimal_sep'  => ',',
						'num_decimals' => 2,
					),
					'JPY' => array(
						'currency_pos' => 'left',
						'thousand_sep' => ',',
						'decimal_sep'  => '.',
						'num_decimals' => 0,
					),
				);

				return $formats[ strtoupper( (string) $currency_code ) ] ?? array(
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

	/**
	 * Create a session test double.
	 *
	 * @param bool        $has_session Whether the session is active.
	 * @param string|null $currency    Stored currency code.
	 * @return object
	 */
	private function create_session( bool $has_session, ?string $currency ): object {
		return new class( $has_session, $currency ) {
			/**
			 * Number of session writes.
			 *
			 * @var int
			 */
			public int $writes = 0;

			/**
			 * Whether the session is active.
			 *
			 * @var bool
			 */
			private bool $has_session;

			/**
			 * Stored currency code.
			 *
			 * @var string|null
			 */
			private ?string $currency;

			/**
			 * Constructor.
			 *
			 * @param bool        $has_session Whether the session is active.
			 * @param string|null $currency    Stored currency code.
			 */
			public function __construct( bool $has_session, ?string $currency ) {
				$this->has_session = $has_session;
				$this->currency    = $currency;
			}

			/**
			 * Tell whether the session is active.
			 *
			 * @return bool
			 */
			public function has_session(): bool {
				return $this->has_session;
			}

			/**
			 * Get a session value.
			 *
			 * @param string $key Session key.
			 * @return string|null
			 */
			public function get( string $key ) {
				return 'wcpay_currency' === $key ? $this->currency : null;
			}

			/**
			 * Set a session value.
			 *
			 * @param string $key   Session key.
			 * @param mixed  $value Session value.
			 */
			public function set( string $key, $value ): void {
				unset( $key, $value );
				++$this->writes;
			}
		};
	}

	/**
	 * Delete options touched by these tests.
	 */
	private function delete_projection_options(): void {
		foreach ( $this->option_keys as $option_key ) {
			delete_option( $option_key );
		}
	}
}
