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
 * @since 10.8.0
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
	 * @since 10.8.0
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
	 * {@inheritDoc}
	 */
	public function get_schema( string $section ): array {
		$schema = ModernSettingsSchema::from_legacy_settings(
			$this->settings_page->get_id(),
			$section,
			$this->settings_page->get_label(),
			$this->settings_page->get_settings( $section ),
			$this->get_save_adapter( $section )
		);

		$schema['shell']['title']             = __( 'Settings', 'woocommerce' );
		$schema['shell']['navigation']        = $this->get_page_navigation();
		$schema['shell']['sectionNavigation'] = $this->get_section_navigation( $section );

		return $schema;
	}

	/**
	 * Get primary settings page navigation for the modern shell.
	 *
	 * @return array<int, array{id: string, label: string, href: string, active: bool}>
	 */
	private function get_page_navigation(): array {
		$tabs = apply_filters( 'woocommerce_settings_tabs_array', array() );

		if ( array_key_exists( 'advanced', $tabs ) ) {
			$advanced = $tabs['advanced'];
			unset( $tabs['advanced'] );
			$tabs['advanced'] = $advanced;
		}

		$navigation = array();
		foreach ( $tabs as $slug => $label ) {
			$navigation[] = array(
				'id'     => (string) $slug,
				'label'  => wp_strip_all_tags( html_entity_decode( (string) $label ) ),
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( (string) $slug ) ),
				'active' => $this->settings_page->get_id() === (string) $slug,
			);
		}

		return $navigation;
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
				'label'  => wp_strip_all_tags( html_entity_decode( (string) $label ) ),
				'href'   => admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_page->get_id() . '&section=' . sanitize_title( $section_id ) ),
				'active' => $current_section === $section_id,
			);
		}

		return $navigation;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_script_handles( string $section ): array {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_save_adapter( string $section ): string {
		return 'form_post';
	}
}
