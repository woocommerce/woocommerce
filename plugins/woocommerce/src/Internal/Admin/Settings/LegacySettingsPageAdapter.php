<?php
/**
 * Legacy WC_Settings_Page adapter for settings UI.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface as PublicSettingsUIPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts a WC_Settings_Page instance into the settings UI page contract.
 *
 * Internal implementation of the legacy settings adapter. Extensions should use
 * Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter.
 *
 * Kept with its signatures because extensions subclass the public adapter and call these methods.
 *
 * @since 10.9.0
 * @deprecated 11.4.0 The Settings UI was removed. Settings pages use the classic renderer.
 */
class LegacySettingsPageAdapter implements PublicSettingsUIPageInterface {

	/**
	 * Legacy settings page.
	 *
	 * @var \WC_Settings_Page
	 */
	protected \WC_Settings_Page $settings_page;

	/**
	 * Constructor.
	 *
	 * @since 10.9.0
	 *
	 * @param \WC_Settings_Page $settings_page Legacy settings page.
	 */
	public function __construct( \WC_Settings_Page $settings_page ) {
		$this->settings_page = $settings_page;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_page_id(): string {
		return $this->settings_page->get_id();
	}

	/**
	 * Build the canonical settings schema for a section.
	 *
	 * @deprecated 11.4.0 Returns an empty schema.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return array
	 */
	public function get_schema( string $section ): array {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return array();
	}

	/**
	 * Get script handles that must be loaded before the settings UI app mounts.
	 *
	 * @deprecated 11.4.0 Returns no handles.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string[]
	 */
	public function get_script_handles( string $section ): array {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return array();
	}

	/**
	 * Get the default save adapter for fields on this page.
	 *
	 * @deprecated 11.4.0 Always returns `form_post`.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string
	 */
	public function get_save_adapter( string $section ): string {
		SettingsUIDeprecation::record_usage( __METHOD__ );
		return 'form_post';
	}
}
