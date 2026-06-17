<?php
/**
 * Settings UI page resolver.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsSectionRegistry;
use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the Settings UI adapter for a settings page and section.
 *
 * @since 10.9.0
 */
class SettingsUIPageResolver {

	/**
	 * Get the Settings UI adapter for a settings page and section.
	 *
	 * @param \WC_Settings_Page $settings_page Settings page.
	 * @param string            $section Section id. Empty string means the default section.
	 * @return SettingsUIPageInterface|null
	 *
	 * @since 10.9.0
	 */
	public static function get_settings_ui_page( \WC_Settings_Page $settings_page, string $section ): ?SettingsUIPageInterface {
		$registered_section = SettingsSectionRegistry::get_instance()->get_registered( $settings_page->get_id(), $section );

		if ( $registered_section ) {
			return new RegisteredSettingsSectionAdapter( $settings_page, $registered_section );
		}

		$settings_ui_page = $settings_page->get_settings_ui_page();
		return $settings_ui_page instanceof SettingsUIPageInterface ? $settings_ui_page : null;
	}
}
