<?php
/**
 * Base settings section implementation.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for extensions that register a section under an existing WooCommerce settings page.
 *
 * @since 10.9.0
 */
abstract class SettingsSection implements SettingsSectionInterface {

	/**
	 * Get script handles that must be loaded before the settings UI app mounts.
	 *
	 * @since 10.9.0
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return string[]
	 */
	public function get_script_handles( \WC_Settings_Page $parent_page ): array {
		return array();
	}

	/**
	 * Get the default save adapter for fields in this section.
	 *
	 * @since 10.9.0
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return string
	 */
	public function get_save_adapter( \WC_Settings_Page $parent_page ): string {
		return 'form_post';
	}
}
