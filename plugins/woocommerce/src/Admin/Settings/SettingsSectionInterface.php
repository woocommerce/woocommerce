<?php
/**
 * Settings section registration contract.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Contract for extensions that register a section under an existing WooCommerce settings page.
 *
 * @since 10.9.0
 */
interface SettingsSectionInterface {

	/**
	 * Get the parent WooCommerce settings page id.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public function get_parent_page_id(): string;

	/**
	 * Get the section id.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public function get_id(): string;

	/**
	 * Get the section label.
	 *
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public function get_label(): string;

	/**
	 * Get legacy settings for this section.
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return array
	 *
	 * @since 10.9.0
	 */
	public function get_settings( \WC_Settings_Page $parent_page ): array;

	/**
	 * Get script handles that must be loaded before the settings UI app mounts.
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return string[]
	 *
	 * @since 10.9.0
	 */
	public function get_script_handles( \WC_Settings_Page $parent_page ): array;

	/**
	 * Get the default save adapter for fields in this section.
	 *
	 * Supported values are `form_post` and `none`.
	 *
	 * @param \WC_Settings_Page $parent_page Parent settings page.
	 * @return string
	 *
	 * @since 10.9.0
	 */
	public function get_save_adapter( \WC_Settings_Page $parent_page ): string;
}
