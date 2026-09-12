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
	 * Test that the real General settings save path persists the form values.
	 */
	public function test_save_persists_general_setting_values() {
		$sut                      = new WC_Settings_General();
		$settings                 = $sut->get_settings_for_section( '' );
		$option_states            = array();
		$original_post            = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Direct settings save test.
		$had_current_section      = array_key_exists( 'current_section', $GLOBALS );
		$original_current_section = $had_current_section ? $GLOBALS['current_section'] : null;

		foreach ( $settings as $setting ) {
			if ( empty( $setting['id'] ) || ( isset( $setting['is_option'] ) && false === $setting['is_option'] ) ) {
				continue;
			}

			$option_states[ $setting['id'] ] = $this->get_raw_option_state( $setting['id'] );
		}

		try {
			$_POST                      = array(
				'woocommerce_store_address'               => '5th Avenue',
				'woocommerce_store_address_2'             => 'Suite 4',
				'woocommerce_store_city'                  => 'New York',
				'woocommerce_default_country'             => 'US:NY',
				'woocommerce_store_postcode'              => '10010',
				'woocommerce_allowed_countries'           => 'specific',
				'woocommerce_all_except_countries'        => array( 'CA', 'FR' ),
				'woocommerce_specific_allowed_countries'  => array( 'US', 'CA' ),
				'woocommerce_ship_to_countries'           => 'specific',
				'woocommerce_specific_ship_to_countries'  => array( 'US' ),
				'woocommerce_default_customer_address'    => 'base',
				'woocommerce_calc_taxes'                  => 'yes',
				'woocommerce_enable_coupons'              => 'yes',
				'woocommerce_calc_discounts_sequentially' => 'no',
				'woocommerce_currency'                    => 'CAD',
				'woocommerce_currency_pos'                => 'left_space',
				'woocommerce_price_thousand_sep'          => '.',
				'woocommerce_price_decimal_sep'           => ',',
				'woocommerce_price_num_decimals'          => '1',
			);
			$GLOBALS['current_section'] = '';

			$sut->save();

			$expected_values = array(
				'woocommerce_store_address'               => '5th Avenue',
				'woocommerce_store_address_2'             => 'Suite 4',
				'woocommerce_store_city'                  => 'New York',
				'woocommerce_default_country'             => 'US:NY',
				'woocommerce_store_postcode'              => '10010',
				'woocommerce_allowed_countries'           => 'specific',
				'woocommerce_all_except_countries'        => array( 'CA', 'FR' ),
				'woocommerce_specific_allowed_countries'  => array( 'US', 'CA' ),
				'woocommerce_ship_to_countries'           => 'specific',
				'woocommerce_specific_ship_to_countries'  => array( 'US' ),
				'woocommerce_default_customer_address'    => 'base',
				'woocommerce_calc_taxes'                  => 'yes',
				'woocommerce_enable_coupons'              => 'yes',
				'woocommerce_calc_discounts_sequentially' => 'no',
				'woocommerce_currency'                    => 'CAD',
				'woocommerce_currency_pos'                => 'left_space',
				'woocommerce_price_thousand_sep'          => '.',
				'woocommerce_price_decimal_sep'           => ',',
				'woocommerce_price_num_decimals'          => 1,
			);

			foreach ( $expected_values as $option_name => $expected_value ) {
				wp_cache_delete( $option_name, 'options' );
				$this->assertSame( $expected_value, get_option( $option_name ), "Unexpected persisted value for {$option_name}." );
			}
		} finally {
			$_POST = $original_post;

			if ( $had_current_section ) {
				$GLOBALS['current_section'] = $original_current_section;
			} else {
				unset( $GLOBALS['current_section'] );
			}

			foreach ( $option_states as $option_name => $state ) {
				$this->restore_raw_option_state( $option_name, $state );
			}
		}
	}

	/**
	 * Test for get_settings (triggers the woocommerce_general_settings filter).
	 */
	public function test_get_settings__triggers_filter() {
		$actual_settings_via_filter = null;

		add_filter(
			'woocommerce_general_settings',
			function ( $settings ) use ( &$actual_settings_via_filter ) {
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

		$settings              = $sut->get_settings_for_section( '' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'woocommerce_store_address'                => 'text',
			'woocommerce_store_address_2'              => 'text',
			'woocommerce_store_city'                   => 'text',
			'woocommerce_default_country'              => 'single_select_country',
			'woocommerce_store_postcode'               => 'text',
			'store_address'                            => array( 'title', 'sectionend' ),
			'woocommerce_allowed_countries'            => 'select',
			'woocommerce_all_except_countries'         => 'multi_select_countries',
			'woocommerce_specific_allowed_countries'   => 'multi_select_countries',
			'woocommerce_ship_to_countries'            => 'select',
			'woocommerce_specific_ship_to_countries'   => 'multi_select_countries',
			'woocommerce_default_customer_address'     => 'select',
			'woocommerce_address_autocomplete_enabled' => 'checkbox',
			'woocommerce_calc_taxes'                   => 'checkbox',
			'woocommerce_enable_coupons'               => 'checkbox',
			'woocommerce_calc_discounts_sequentially'  => 'checkbox',
			'general_options'                          => array( 'title', 'sectionend' ),
			'woocommerce_currency'                     => 'select',
			'woocommerce_currency_pos'                 => 'select',
			'woocommerce_price_thousand_sep'           => 'text',
			'woocommerce_price_decimal_sep'            => 'text',
			'woocommerce_price_num_decimals'           => 'number',
			'pricing_options'                          => array( 'title', 'sectionend' ),
			'taxes_and_coupons_options'                => array( 'title', 'sectionend' ),
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * Test for get_settings (retrieves currencies properly).
	 */
	public function test_get_settings__currencies() {
		FunctionsMockerHack::add_function_mocks(
			array(
				'get_woocommerce_currencies'      => function () {
					return array(
						'c1' => 'Currency 1',
						'c2' => 'Currency 2',
					);
				},
				'get_woocommerce_currency_symbol' => function ( $currency = '' ) {
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

	/**
	 * Read an option without default filters or value coercion.
	 *
	 * @param string $option_name Option name.
	 * @return array{exists: bool, value: string|null, autoload: string|null}
	 */
	private function get_raw_option_state( string $option_name ): array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);

		return null === $row
			? array(
				'exists'   => false,
				'value'    => null,
				'autoload' => null,
			)
			: array(
				'exists'   => true,
				'value'    => $row['option_value'],
				'autoload' => $row['autoload'],
			);
	}

	/**
	 * Restore an option row without invoking settings sanitizers.
	 *
	 * @param string                                                         $option_name Option name.
	 * @param array{exists: bool, value: string|null, autoload: string|null} $state Raw option state.
	 */
	private function restore_raw_option_state( string $option_name, array $state ): void {
		global $wpdb;

		if ( ! $state['exists'] ) {
			$result = $wpdb->delete( $wpdb->options, array( 'option_name' => $option_name ) );
		} elseif ( $this->get_raw_option_state( $option_name )['exists'] ) {
			$result = $wpdb->update(
				$wpdb->options,
				array(
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				),
				array( 'option_name' => $option_name )
			);
		} else {
			$result = $wpdb->insert(
				$wpdb->options,
				array(
					'option_name'  => $option_name,
					'option_value' => $state['value'],
					'autoload'     => $state['autoload'],
				)
			);
		}

		wp_cache_delete( $option_name, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		$this->assertNotFalse( $result, "Failed to restore option {$option_name}." );
	}
}
