<?php
/**
 * Settings UI page contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for settings pages that opt into the settings UI renderer.
 *
 * @since 10.9.0
 */
interface SettingsUIPageInterface {

	/**
	 * Get the stable page id used for scoping the settings UI.
	 *
	 * @since 10.9.0
	 *
	 * @return string
	 */
	public function get_page_id(): string;

	/**
	 * Build the canonical settings schema for a section.
	 *
	 * @since 10.9.0
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return array
	 */
	public function get_schema( string $section ): array;

	/**
	 * Get script handles that must be loaded before the settings UI app mounts.
	 *
	 * @since 10.9.0
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string[]
	 */
	public function get_script_handles( string $section ): array;

	/**
	 * Get the default save adapter for fields on this page.
	 *
	 * Supported values are `form_post` and `none`.
	 *
	 * @since 10.9.0
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string
	 */
	public function get_save_adapter( string $section ): string;
}
