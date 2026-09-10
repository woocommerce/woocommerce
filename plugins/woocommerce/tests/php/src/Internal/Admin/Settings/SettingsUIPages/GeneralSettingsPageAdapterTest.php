<?php
/**
 * GeneralSettingsPageAdapter tests.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings\SettingsUIPages
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Settings\SettingsUIPages;

use Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUIPages\GeneralSettingsPageAdapter;
use Automattic\WooCommerce\Internal\Admin\Settings\SettingsUISchema;
use WC_Unit_Test_Case;

/**
 * Tests for GeneralSettingsPageAdapter.
 */
class GeneralSettingsPageAdapterTest extends WC_Unit_Test_Case {

	/**
	 * Load the legacy General settings page.
	 *
	 * @return void
	 */
	private function load_general_settings_page(): void {
		if ( ! class_exists( 'WC_Settings_General', false ) ) {
			require_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-general.php';
		}
	}

	/**
	 * @testdox It provides the General adapter only for the core settings page.
	 */
	public function test_real_general_page_uses_the_settings_ui_adapter(): void {
		$this->load_general_settings_page();

		$settings_page = new \WC_Settings_General();
		$adapter       = $settings_page->get_settings_ui_page();
		$subclass      = new class() extends \WC_Settings_General {};

		$this->assertInstanceOf( GeneralSettingsPageAdapter::class, $adapter );
		$this->assertSame( 'general', $adapter->get_page_id() );

		// The General visibility rules assume this page's exact field set, so a
		// subclass keeps the default adapter instead. Settings UI rendering is
		// opt-out, so it must not drop the subclass onto the classic renderer.
		$subclass_adapter = $subclass->get_settings_ui_page();
		$this->assertInstanceOf( LegacySettingsPageAdapter::class, $subclass_adapter );
		$this->assertNotInstanceOf( GeneralSettingsPageAdapter::class, $subclass_adapter );
	}

	/**
	 * @testdox It builds the General country picker visibility rules.
	 */
	public function test_get_schema_builds_general_visibility(): void {
		$this->load_general_settings_page();

		$adapter = ( new \WC_Settings_General() )->get_settings_ui_page();
		$schema  = $adapter->get_schema( '' );
		$fields  = $this->get_fields_by_id( $schema );

		SettingsUISchema::assert_valid_schema( $schema );
		$this->assertSame(
			array(
				'controller' => 'woocommerce_allowed_countries',
				'value'      => 'all_except',
			),
			$fields['woocommerce_all_except_countries']['visibility']
		);
		$this->assertSame(
			array(
				'controller' => 'woocommerce_allowed_countries',
				'value'      => 'specific',
			),
			$fields['woocommerce_specific_allowed_countries']['visibility']
		);
		$this->assertSame(
			array(
				'controller' => 'woocommerce_ship_to_countries',
				'value'      => 'specific',
			),
			$fields['woocommerce_specific_ship_to_countries']['visibility']
		);

		// The preferred provider picker only exists with more than one provider.
		if ( isset( $fields['woocommerce_address_autocomplete_provider'] ) ) {
			$this->assertSame(
				array(
					'controller' => 'woocommerce_address_autocomplete_enabled',
					'value'      => true,
				),
				$fields['woocommerce_address_autocomplete_provider']['visibility']
			);
		}
	}

	/**
	 * @testdox It rejects General when an extension removes a required field.
	 */
	public function test_get_schema_rejects_missing_required_extension_field(): void {
		$this->load_general_settings_page();

		add_filter(
			'woocommerce_general_settings',
			static function ( array $settings ): array {
				return array_values(
					array_filter(
						$settings,
						static function ( array $setting ): bool {
							return 'woocommerce_specific_allowed_countries' !== ( $setting['id'] ?? null );
						}
					)
				);
			}
		);

		$this->expectException( \InvalidArgumentException::class );
		( new \WC_Settings_General() )->get_settings_ui_page()->get_schema( '' );
	}

	/**
	 * Get fields indexed by identifier.
	 *
	 * @param array $schema Settings UI schema.
	 * @return array<string, array>
	 */
	private function get_fields_by_id( array $schema ): array {
		$fields = array();
		foreach ( $schema['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				$fields[ $field['id'] ] = $field;
			}
		}

		return $fields;
	}
}
