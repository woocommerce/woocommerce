<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyNameYourPriceCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyNameYourPriceCompatibilityProjectionService class.
 */
class MultiCurrencyNameYourPriceCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Name Your Price compatibility hook manifest.
	 */
	public function test_projects_name_your_price_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyNameYourPriceCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'wc_nyp_raw_minimum_price',
				'wc_nyp_raw_maximum_price',
				'wc_nyp_raw_suggested_price',
				'woocommerce_get_cart_item_from_session',
				'wcpay_multi_currency_should_convert_product_price',
				'wc_nyp_edit_in_cart_args',
				'wc_nyp_get_initial_price',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame(
			array( 'woocommerce_add_cart_item_data' ),
			array_column( $manifest['actions'], 'hook' )
		);
		$this->assertSame( 'get_nyp_prices', $manifest['filters'][0]['callback'] );
		$this->assertSame( 'convert_cart_currency', $manifest['filters'][3]['callback'] );
		$this->assertSame( 'should_convert_product_price', $manifest['filters'][4]['callback'] );
		$this->assertSame( 'add_initial_currency', $manifest['actions'][0]['callback'] );
		$this->assertSame( 20, $manifest['actions'][0]['priority'] );
		$this->assertSame( 3, $manifest['actions'][0]['accepted_args'] );
		$this->assertSame( 50, $manifest['filters'][4]['priority'] );
		$this->assertSame( 2, $manifest['filters'][4]['accepted_args'] );
		$this->assertSame( 3, $manifest['filters'][6]['accepted_args'] );
	}

	/**
	 * @testdox Should require Name Your Price runtime before registering hooks.
	 */
	public function test_requires_name_your_price_runtime_before_registering_hooks(): void {
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_register( true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_register( false ) );
	}

	/**
	 * @testdox Should convert only non-empty raw NYP prices.
	 */
	public function test_converts_only_non_empty_raw_nyp_prices(): void {
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_raw_price( '10.00' ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_raw_price( '0' ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_raw_price( 0 ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_raw_price( '' ) );
	}

	/**
	 * @testdox Should store initial currency only for NYP cart items with entered prices.
	 */
	public function test_stores_initial_currency_only_for_nyp_cart_items_with_entered_prices(): void {
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_store_initial_currency( true, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_store_initial_currency( false, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_store_initial_currency( true, false ) );
	}

	/**
	 * @testdox Should convert cart currency only when NYP cart metadata is available.
	 */
	public function test_converts_cart_currency_only_when_nyp_cart_metadata_is_available(): void {
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_cart_currency( true, true, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_cart_currency( false, true, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_cart_currency( true, false, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_cart_currency( true, true, false ) );
	}

	/**
	 * @testdox Should project Name Your Price product conversion decisions.
	 */
	public function test_projects_name_your_price_product_conversion_decisions(): void {
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_product_price( true, true, false ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_product_price( true, false, true ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_product_price( false, false, false ) );
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_product_price( true, false, false ) );
	}

	/**
	 * @testdox Should project initial edit price conversion decisions.
	 */
	public function test_projects_initial_edit_price_conversion_decisions(): void {
		$this->assertTrue( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_initial_price( true, true, 'USD', 'GBP' ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_initial_price( true, true, 'GBP', 'GBP' ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_initial_price( false, true, 'USD', 'GBP' ) );
		$this->assertFalse( MultiCurrencyNameYourPriceCompatibilityProjectionService::should_convert_initial_price( true, false, 'USD', 'GBP' ) );
	}
}
