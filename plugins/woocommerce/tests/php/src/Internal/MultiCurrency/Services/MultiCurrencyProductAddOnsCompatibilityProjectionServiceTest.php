<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\MultiCurrency\Services;

use Automattic\WooCommerce\Internal\MultiCurrency\Services\MultiCurrencyProductAddOnsCompatibilityProjectionService;
use WC_Unit_Test_Case;

/**
 * Tests for the MultiCurrencyProductAddOnsCompatibilityProjectionService class.
 */
class MultiCurrencyProductAddOnsCompatibilityProjectionServiceTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should project Product Add-ons compatibility hook manifest.
	 */
	public function test_projects_product_addons_compatibility_hook_manifest(): void {
		$manifest = MultiCurrencyProductAddOnsCompatibilityProjectionService::get_hook_manifest();

		$this->assertSame(
			array(
				'woocommerce_product_addons_option_price_raw',
				'woocommerce_product_addons_price_raw',
				'woocommerce_product_addons_params',
				'woocommerce_product_addons_get_item_data',
				'woocommerce_product_addons_update_product_price',
				'woocommerce_product_addons_order_line_item_meta',
				'wcpay_multi_currency_should_convert_product_price',
				'woocommerce_product_addons_ajax_get_product_price_including_tax',
				'woocommerce_product_addons_ajax_get_product_price_excluding_tax',
			),
			array_column( $manifest['filters'], 'hook' )
		);
		$this->assertSame( array(), $manifest['actions'] );
		$this->assertSame( 'get_addons_price', $manifest['filters'][0]['callback'] );
		$this->assertSame( 'product_addons_params', $manifest['filters'][2]['callback'] );
		$this->assertSame( 'get_item_data', $manifest['filters'][3]['callback'] );
		$this->assertSame( 'update_product_price', $manifest['filters'][4]['callback'] );
		$this->assertSame( 'order_line_item_meta', $manifest['filters'][5]['callback'] );
		$this->assertSame( 'should_convert_product_price', $manifest['filters'][6]['callback'] );
		$this->assertSame( 'get_product_calculation_price', $manifest['filters'][7]['callback'] );
		$this->assertSame( 50, $manifest['filters'][0]['priority'] );
		$this->assertSame( 2, $manifest['filters'][0]['accepted_args'] );
		$this->assertSame( 4, $manifest['filters'][4]['accepted_args'] );
		$this->assertSame( 3, $manifest['filters'][7]['accepted_args'] );
	}

	/**
	 * @testdox Should register Product Add-ons hooks only for supported request contexts.
	 */
	public function test_registers_product_addons_hooks_only_for_supported_request_contexts(): void {
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register( true, false, false, false ) );
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register( true, true, true, false ) );
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register( false, false, false, false ) );
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register( true, true, false, false ) );
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_register( true, false, false, true ) );
	}

	/**
	 * @testdox Should convert add-on prices except percentage-based values.
	 */
	public function test_converts_addon_prices_except_percentage_based_values(): void {
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_addon_price( 'percentage_based' ) );
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_addon_price( 'flat_fee' ) );
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_addon_price( 'quantity_based' ) );
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_addon_price( '' ) );
	}

	/**
	 * @testdox Should project Product Add-ons product conversion decisions.
	 */
	public function test_projects_product_addons_product_conversion_decisions(): void {
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_product_price( false, false ) );
		$this->assertFalse( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_product_price( true, true ) );
		$this->assertTrue( MultiCurrencyProductAddOnsCompatibilityProjectionService::should_convert_product_price( true, false ) );
	}

	/**
	 * @testdox Should calculate add-on conversion amount for input multipliers.
	 */
	public function test_calculates_addon_conversion_amount_for_input_multipliers(): void {
		$this->assertSame( 42.0, MultiCurrencyProductAddOnsCompatibilityProjectionService::get_addon_conversion_amount( 84, 2, 'input_multiplier' ) );
		$this->assertSame( 84.0, MultiCurrencyProductAddOnsCompatibilityProjectionService::get_addon_conversion_amount( 84, 0, 'input_multiplier' ) );
		$this->assertSame( 84.0, MultiCurrencyProductAddOnsCompatibilityProjectionService::get_addon_conversion_amount( 84, 2, 'checkbox' ) );
	}
}
