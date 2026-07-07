<?php
/**
 * Settings UI section navigation builder.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Builds section navigation entries for the Settings UI shell.
 *
 * @since 11.0.0
 */
final class SettingsSectionNavigation {

	/**
	 * Build the default settings section navigation for the settings UI shell.
	 *
	 * Lists every section of the settings page, linking back through the classic
	 * settings URLs. Returns an empty array for pages with fewer than two sections.
	 *
	 * @since 11.0.0
	 *
	 * @param \WC_Settings_Page $settings_page Settings page to build the navigation for.
	 * @param string            $current_section Current section id. Empty string means the default section.
	 * @return array<int, array{id: string, label: string, href: string, active: bool}>
	 */
	public static function build_default( \WC_Settings_Page $settings_page, string $current_section ): array {
		$sections = $settings_page->get_sections();
		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return array();
		}

		$navigation = array();
		foreach ( $sections as $id => $label ) {
			$section_id   = (string) $id;
			$navigation[] = array(
				'id'     => '' === $section_id ? 'default' : $section_id,
				'label'  => wp_strip_all_tags( html_entity_decode( (string) $label, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),
				'href'   => add_query_arg(
					array(
						'page'    => 'wc-settings',
						'tab'     => sanitize_title( $settings_page->get_id() ),
						'section' => sanitize_title( $section_id ),
					),
					admin_url( 'admin.php' )
				),
				'active' => $current_section === $section_id,
			);
		}

		return $navigation;
	}
}
