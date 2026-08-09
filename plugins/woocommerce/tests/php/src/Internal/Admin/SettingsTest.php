<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin;

use Automattic\WooCommerce\Internal\Admin\Settings;
use WC_REST_Setting_Options_Controller;
use WC_Unit_Test_Case;

/**
 * Tests for the Settings class.
 */
class SettingsTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Should register the date type setting with a 'date_paid' default, matching the reports data stores.
	 */
	public function test_date_type_setting_has_date_paid_default(): void {
		$sut = new Settings();

		$date_type = $this->get_setting_by_id( $sut->add_settings( array() ), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_paid', $date_type['default'] ?? null, 'The date type default should match the date_paid fallback used by the reports data stores' );
	}

	/**
	 * @testdox Should resolve the date type value to 'date_paid' when the option has never been saved.
	 */
	public function test_date_type_value_falls_back_to_date_paid_when_option_is_unset(): void {
		delete_option( 'woocommerce_date_type' );

		$date_type = $this->get_setting_by_id( $this->get_wc_admin_group_settings(), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_paid', $date_type['value'], 'An unset date type option should resolve to the effective default, date_paid' );
	}

	/**
	 * @testdox Should resolve the date type value to the saved option value when one exists.
	 */
	public function test_date_type_value_reflects_saved_option(): void {
		update_option( 'woocommerce_date_type', 'date_completed' );

		$date_type = $this->get_setting_by_id( $this->get_wc_admin_group_settings(), 'woocommerce_date_type' );

		$this->assertNotNull( $date_type, 'The woocommerce_date_type setting should be registered in the wc_admin group' );
		$this->assertSame( 'date_completed', $date_type['value'], 'A saved date type option should be reflected as the setting value' );
	}

	/**
	 * Get the resolved wc_admin group settings via the REST settings controller.
	 *
	 * @return array
	 */
	private function get_wc_admin_group_settings(): array {
		$settings = ( new WC_REST_Setting_Options_Controller() )->get_group_settings( 'wc_admin' );

		$this->assertIsArray( $settings, 'The wc_admin settings group should resolve to an array of settings' );

		return $settings;
	}

	/**
	 * Find a setting by id in a list of settings.
	 *
	 * @param array  $settings List of setting definitions.
	 * @param string $id       Setting id to look for.
	 * @return array|null The first matching setting, or null if none matches.
	 */
	private function get_setting_by_id( array $settings, string $id ): ?array {
		foreach ( $settings as $setting ) {
			if ( ( $setting['id'] ?? '' ) === $id ) {
				return $setting;
			}
		}
		return null;
	}
}
