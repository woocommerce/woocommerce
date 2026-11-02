<?php
/**
 * Settings section UI page provider contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Optional contract for registered sections that provide a native Settings UI page.
 *
 * @since 11.0.0
 */
interface SettingsSectionUIPageProviderInterface {

	/**
	 * Get the native Settings UI page for this registered section.
	 *
	 * Return null to use the default registered section adapter.
	 *
	 * @since 11.0.0
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return SettingsUIPageInterface|null
	 */
	public function get_settings_ui_page( \WC_Settings_Page $parent_page ): ?SettingsUIPageInterface;
}
