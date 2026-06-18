<?php
/**
 * Registered settings section adapter for settings UI.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsSectionInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts a registered settings section into the settings UI page contract.
 *
 * @since 10.9.0
 */
class RegisteredSettingsSectionAdapter extends LegacySettingsPageAdapter {

	/**
	 * Registered settings section.
	 *
	 * @var SettingsSectionInterface
	 */
	private SettingsSectionInterface $section;

	/**
	 * Constructor.
	 *
	 * @since 10.9.0
	 *
	 * @param \WC_Settings_Page        $settings_page Parent settings page.
	 * @param SettingsSectionInterface $section Registered settings section.
	 */
	public function __construct( \WC_Settings_Page $settings_page, SettingsSectionInterface $section ) {
		parent::__construct( $settings_page );
		$this->section = $section;
	}

	/**
	 * Get script handles that must be loaded before the settings UI app mounts.
	 *
	 * @param string $section Unused. This adapter wraps a single registered section.
	 * @return string[]
	 */
	public function get_script_handles( string $section ): array {
		return $this->section->get_script_handles( $this->settings_page );
	}

	/**
	 * Get the default save adapter for fields in this section.
	 *
	 * @param string $section Unused. This adapter wraps a single registered section.
	 * @return string
	 */
	public function get_save_adapter( string $section ): string {
		return $this->section->get_save_adapter( $this->settings_page );
	}
}
