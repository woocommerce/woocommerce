<?php
/**
 * Settings UI request context.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\SettingsUIPageInterface;

/**
 * Resolves and caches Settings UI state for the active settings request.
 *
 * Settings UI rendering is never enabled. The methods stay so settings files from an older
 * release, loaded during an update, fall back to the classic renderer instead of failing.
 *
 * @since 10.9.0
 * @deprecated 11.4.0 The Settings UI was removed. Settings pages use the classic renderer.
 */
class SettingsUIRequestContext {

	/**
	 * Legacy settings page.
	 *
	 * @var \WC_Settings_Page
	 */
	private \WC_Settings_Page $settings_page;

	/**
	 * Section id.
	 *
	 * @var string
	 */
	private string $section;

	/**
	 * Constructor.
	 *
	 * @param \WC_Settings_Page $settings_page Legacy settings page.
	 * @param string            $section Section id.
	 */
	private function __construct( \WC_Settings_Page $settings_page, string $section ) {
		$this->settings_page = $settings_page;
		$this->section       = $section;
	}

	/**
	 * Get the context for the current settings request.
	 *
	 * @return SettingsUIRequestContext|null Always null.
	 */
	public static function get_current(): ?SettingsUIRequestContext {
		return null;
	}

	/**
	 * Get a context for a settings page and section.
	 *
	 * @param \WC_Settings_Page $settings_page Legacy settings page.
	 * @param string            $section Section id.
	 * @return SettingsUIRequestContext A context with rendering disabled.
	 */
	public static function for_settings_page( \WC_Settings_Page $settings_page, string $section ): SettingsUIRequestContext {
		return new self( $settings_page, $section );
	}

	/**
	 * Reset cached contexts.
	 */
	public static function reset(): void {}

	/**
	 * Get the key of the current section.
	 *
	 * @return string
	 */
	public function get_current_section_key(): string {
		return '' === $this->section ? 'default' : $this->section;
	}

	/**
	 * Get the Settings UI page.
	 *
	 * @return SettingsUIPageInterface|null Always null.
	 */
	public function get_settings_ui_page(): ?SettingsUIPageInterface {
		return null;
	}

	/**
	 * Get the legacy settings page.
	 *
	 * @return \WC_Settings_Page
	 */
	public function get_settings_page(): \WC_Settings_Page {
		return $this->settings_page;
	}

	/**
	 * Get the page id.
	 *
	 * @return string
	 */
	public function get_page_id(): string {
		return (string) $this->settings_page->get_id();
	}

	/**
	 * Whether the section is a drill-down page.
	 *
	 * @return bool Always false.
	 */
	public function is_drill_down(): bool {
		return false;
	}

	/**
	 * Whether the Settings UI renders this section.
	 *
	 * @return bool Always false.
	 */
	public function is_rendering_enabled(): bool {
		return false;
	}

	/**
	 * Get the script handles for the section.
	 *
	 * @return string[] Always empty.
	 */
	public function get_script_handles(): array {
		return array();
	}

	/**
	 * Enqueue the script handles for the section.
	 *
	 * @return string[] Always empty.
	 */
	public function enqueue_script_handles(): array {
		return array();
	}

	/**
	 * Whether resolving script handles failed.
	 *
	 * @return bool Always false.
	 */
	public function has_script_handles_failed(): bool {
		return false;
	}

	/**
	 * Whether loading script handles failed.
	 *
	 * @return bool Always false.
	 */
	public function has_script_handle_loading_failed(): bool {
		return false;
	}

	/**
	 * Get why script handles failed.
	 *
	 * @return string Always empty.
	 */
	public function get_script_handles_failure_reason(): string {
		return '';
	}

	/**
	 * Get the section schema.
	 *
	 * @return array|null Always null.
	 */
	public function get_schema(): ?array {
		return null;
	}

	/**
	 * Whether building the schema failed.
	 *
	 * @return bool Always false.
	 */
	public function has_schema_failed(): bool {
		return false;
	}

	/**
	 * Get why building the schema failed.
	 *
	 * @return string Always empty.
	 */
	public function get_schema_failure_reason(): string {
		return '';
	}
}
