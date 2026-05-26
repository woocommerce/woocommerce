<?php
/**
 * Legacy WC_Settings_Page adapter for modern settings.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

use Automattic\WooCommerce\Admin\Settings\ModernSettingsPageInterface as PublicModernSettingsPageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Adapts a WC_Settings_Page instance into the modern settings page contract.
 *
 * Internal implementation of the legacy settings adapter. Extensions should use
 * Automattic\WooCommerce\Admin\Settings\LegacySettingsPageAdapter.
 *
 * @since 10.9.0
 */
class LegacySettingsPageAdapter implements PublicModernSettingsPageInterface {

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
	 * @param string $section Section id. Empty string means the default section.
	 * @return array
	 */
	public function get_schema( string $section ): array {
		$schema = ModernSettingsSchema::from_legacy_settings(
			$this->settings_page->get_id(),
			$section,
			$this->settings_page->get_label(),
			$this->settings_page->get_settings( $section ),
			$this->get_save_adapter( $section )
		);

		$schema['shell']['sectionNavigation'] = $this->get_section_navigation( $section );

		return $schema;
	}

	/**
	 * Get secondary settings section navigation for the modern shell.
	 *
	 * @param string $current_section Current section id.
	 * @return array<int, array{id: string, label: string, href: string, active: bool}>
	 */
	private function get_section_navigation( string $current_section ): array {
		$sections = $this->settings_page->get_sections();
		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return array();
		}

		$navigation = array();
		foreach ( $sections as $id => $label ) {
			$section_id   = (string) $id;
			$navigation[] = array(
				'id'     => '' === $section_id ? 'default' : $section_id,
				'label'  => wp_strip_all_tags( html_entity_decode( (string) $label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_page->get_id() . '&section=' . sanitize_title( $section_id ) ),
				'active' => $current_section === $section_id,
			);
		}

		return $navigation;
	}

	/**
	 * Get script handles that must be loaded before the modern settings app mounts.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string[]
	 */
	public function get_script_handles( string $section ): array {
		return array();
	}

	/**
	 * Get the default save adapter for fields on this page.
	 *
	 * @param string $section Section id. Empty string means the default section.
	 * @return string
	 */
	public function get_save_adapter( string $section ): string {
		return 'form_post';
	}
}
