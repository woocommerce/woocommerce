<?php
/**
 * Class WC_Settings_General_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_General class.
 */
class WC_Settings_General_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * Test for get_settings (triggers the woocommerce_general_settings filter).
	 */
	public function test_get_settings__triggers_filter() {
		$actual_settings_via_filter = null;

		add_filter(
			'woocommerce_general_settings',
			function( $settings ) use ( &$actual_settings_via_filter ) {
				$actual_settings_via_filter = $settings;
				return $settings;
			},
			10,
			1
		);

		$sut = new WC_Settings_General();

		$actual_settings_returned = $sut->get_settings_for_section( '' );
		remove_all_filters( 'woocommerce_general_settings' );

		$this->assertSame( $actual_settings_returned, $actual_settings_via_filter );
	}

	/**
	 * Test for get_settings (all settings are present).
	 */
	public function test_get_settings__all_settings_are_present() {
		$sut = new WC_Settings_General();

		$settings               = $sut->get_settings_for_section( '' );
		$settings_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'woocommerce_store_address'                    => 'text',
			'woocommerce_store_address_2'                  => 'text',
			'woocommerce_store_city'                       => 'text',
			'woocommerce_default_country'                  => 'single_select_country',
			'woocommerce_store_postcode'                   => 'text',
			'store_address'                                => array( 'title', 'sectionend' ),
			'woocommerce_allowed_countries'                => 'select',
			'woocommerce_all_except_countries'             => 'multi_select_countries',
			'woocommerce_specific_allowed_countries'       => 'multi_select_countries',
			'woocommerce_ship_to_countries'                => 'select',
			'woocommerce_specific_ship_to_countries'       => 'multi_select_countries',
			'woocommerce_default_customer_address'         => 'select',
			'woocommerce_calc_taxes'                       => 'checkbox',
			'woocommerce_enable_coupons'                   => 'checkbox',
			'woocommerce_calc_discounts_sequentially'      => 'checkbox',
			'general_options'                              => array( 'title', 'sectionend' ),
			'woocommerce_currency'                         => 'select',
			'woocommerce_currency_pos'                     => 'select',
			'woocommerce_price_thousand_sep'               => 'text',
			'woocommerce_price_decimal_sep'                => 'text',
			'woocommerce_price_num_decimals'               => 'number',
			'pricing_options'                              => array( 'title', 'sectionend' ),
		);

		$this->assertEquals( $expected, $settings_ids_and_types );
	}

	/**
	 * Test for get_settings (retrieves currencies properly).
	 */
	public function test_get_settings__currencies() {
		FunctionsMockerHack::add_function_mocks(
			array(
				'get_woocommerce_currencies'      => function() {
					return array(
						'c1' => 'Currency 1',
						'c2' => 'Currency 2',
					);
				},
				'get_woocommerce_currency_symbol' => function( $currency = '' ) {
					return "symbol for $currency";
				},
			)
		);

		$sut = new WC_Settings_General();

		$settings         = $sut->get_settings_for_section( '' );
		$currency_setting = $this->setting_by_id( $settings, 'woocommerce_currency' );
		$currencies       = $currency_setting['options'];

		$expected = array(
			'c1' => 'Currency 1 (symbol for c1) — c1',
			'c2' => 'Currency 2 (symbol for c2) — c2',
		);

		$this->assertEquals( $expected, $currencies );
	}
}
