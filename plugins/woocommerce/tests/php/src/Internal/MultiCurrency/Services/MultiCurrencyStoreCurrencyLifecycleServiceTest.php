<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Interfaces\MultiCurrencyCacheInterface;
use Automattic\WooCommerce\Internal\MultiCurrency\Providers\CurrencyRateProviderRegistry;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyDatabaseCache;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyLocalizationService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyRateService;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStateBuilder;
use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyStoreCurrencyLifecycleService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyStoreCurrencyLifecycleService class.
 */
class MultiCurrencyStoreCurrencyLifecycleServiceTest extends WC_Unit_Test_Case {

	private const STORE_CURRENCY_OPTION = 'wcpay_multi_currency_store_currency';
	private const NOTICE_OPTION         = 'wcpay_multi_currency_show_store_currency_changed_notice';

	/**
	 * Original store currency.
	 *
	 * @var string
	 */
	private string $original_currency;

	/**
	 * Set up test fixtures.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_currency = get_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_currency', 'USD' );
		$this->delete_options();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down(): void {
		$this->delete_options();
		update_option( 'woocommerce_currency', $this->original_currency );

		parent::tear_down();
	}

	/**
	 * @testdox Should seed missing store currency without clearing cache.
	 */
	public function test_seeds_missing_store_currency_without_clearing_cache(): void {
		update_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, array( 'data' => array() ), false );

		$changed = $this->create_service()->synchronize_store_currency();

		$this->assertFalse( $changed );
		$this->assertSame( 'USD', get_option( self::STORE_CURRENCY_OPTION ) );
		$this->assertIsArray( get_option( MultiCurrencyCacheInterface::CURRENCIES_KEY ) );
		$this->assertFalse( get_option( self::NOTICE_OPTION, false ) );
	}

	/**
	 * @testdox Should keep options when store currency is unchanged.
	 */
	public function test_keeps_options_when_store_currency_is_unchanged(): void {
		update_option( self::STORE_CURRENCY_OPTION, 'USD' );
		update_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, array( 'data' => array() ), false );

		$changed = $this->create_service()->synchronize_store_currency();

		$this->assertFalse( $changed );
		$this->assertSame( 'USD', get_option( self::STORE_CURRENCY_OPTION ) );
		$this->assertIsArray( get_option( MultiCurrencyCacheInterface::CURRENCIES_KEY ) );
	}

	/**
	 * @testdox Should update store currency, clear cache, and write manual rate notice.
	 */
	public function test_updates_store_currency_clears_cache_and_writes_manual_rate_notice(): void {
		update_option( self::STORE_CURRENCY_OPTION, 'USD' );
		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'CAD', 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_cad', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_cad', '1.47' );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'manual' );
		update_option( 'wcpay_multi_currency_manual_rate_gbp', '0.84' );
		update_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, array( 'data' => array() ), false );

		$changed = $this->create_service()->synchronize_store_currency();

		$this->assertTrue( $changed );
		$this->assertSame( 'EUR', get_option( self::STORE_CURRENCY_OPTION ) );
		$this->assertFalse( get_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, false ) );
		$this->assertSame( array( 'Canadian dollar', 'Pound sterling' ), get_option( self::NOTICE_OPTION ) );
	}

	/**
	 * @testdox Should not write notice when changed currencies have no manual rates.
	 */
	public function test_does_not_write_notice_when_changed_currencies_have_no_manual_rates(): void {
		update_option( self::STORE_CURRENCY_OPTION, 'USD' );
		update_option( 'woocommerce_currency', 'EUR' );
		update_option( 'wcpay_multi_currency_enabled_currencies', array( 'GBP' ) );
		update_option( 'wcpay_multi_currency_exchange_rate_gbp', 'automatic' );

		$changed = $this->create_service()->synchronize_store_currency();

		$this->assertTrue( $changed );
		$this->assertFalse( get_option( self::NOTICE_OPTION, false ) );
	}

	/**
	 * @testdox Should skip unknown store currency without mutating state.
	 */
	public function test_skips_unknown_store_currency_without_mutating_state(): void {
		update_option( self::STORE_CURRENCY_OPTION, 'USD' );
		update_option( 'woocommerce_currency', 'XYZ' );
		update_option( MultiCurrencyCacheInterface::CURRENCIES_KEY, array( 'data' => array() ), false );

		$changed = $this->create_service()->synchronize_store_currency();

		$this->assertFalse( $changed );
		$this->assertSame( 'USD', get_option( self::STORE_CURRENCY_OPTION ) );
		$this->assertIsArray( get_option( MultiCurrencyCacheInterface::CURRENCIES_KEY ) );
	}

	/**
	 * Create the lifecycle service.
	 *
	 * @return MultiCurrencyStoreCurrencyLifecycleService
	 */
	private function create_service(): MultiCurrencyStoreCurrencyLifecycleService {
		$cache = new MultiCurrencyDatabaseCache();

		return new MultiCurrencyStoreCurrencyLifecycleService(
			$cache,
			new MultiCurrencyStateBuilder(
				new MultiCurrencyLocalizationService(),
				new MultiCurrencyRateService( new CurrencyRateProviderRegistry() ),
				$cache
			)
		);
	}

	/**
	 * Delete options touched by the tests.
	 */
	private function delete_options(): void {
		foreach (
			array(
				self::STORE_CURRENCY_OPTION,
				self::NOTICE_OPTION,
				'wcpay_multi_currency_enabled_currencies',
				'wcpay_multi_currency_exchange_rate_cad',
				'wcpay_multi_currency_manual_rate_cad',
				'wcpay_multi_currency_exchange_rate_gbp',
				'wcpay_multi_currency_manual_rate_gbp',
				MultiCurrencyCacheInterface::CURRENCIES_KEY,
			) as $option_name
		) {
			delete_option( $option_name );
		}
	}
}
