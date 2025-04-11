<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Automattic\WooCommerce\Blueprint\Util;
use WC_Admin_Settings;
use WC_Settings_Page;

/**
 * Handles getting options from WooCommerce settings pages.
 *
 * Class SettingOptions
 */
class SettingOptions {
	use UseWPFunctions;

	/**
	 * Array of WC_Settings_Page objects.
	 *
	 * @var WC_Settings_Page[]
	 */
	private array $setting_pages;
	/**
	 * Constructor.
	 *
	 * @param array $setting_pages Optional array of setting pages.
	 */
	public function __construct( array $setting_pages = array() ) {
		if ( empty( $setting_pages ) ) {
			$setting_pages = WC_Admin_Settings::get_settings_pages();
		}
		$this->setting_pages = $setting_pages;
	}

	/**
	 * Get options for a specific settings page.
	 *
	 * @param string $page_id The page ID.
	 * @return array
	 */
	public function get_page_options( $page_id ) {
		$page      = $this->get_page( $page_id );
		$page_info = $this->get_page_info( $page );
		$options   = $this->merge_page_info_options( $page_info );
		return array_column( $options, 'value', 'id' );
	}

	/**
	 * Get a settings page by ID.
	 *
	 * @param string $page_id The page ID.
	 * @return WC_Settings_Page|null
	 */
	public function get_page( $page_id ) {
		foreach ( $this->setting_pages as $page ) {
			$id = $page->get_id();
			if ( $id === $page_id ) {
				return $page;
			}
		}

		return null;
	}

	/**
	 * Get information about a settings page.
	 *
	 * @param WC_Settings_Page $page The settings page.
	 * @return array
	 */
	protected function get_page_info( WC_Settings_Page $page ) {
		$info = array(
			'label'    => $page->get_label(),
			'sections' => array(),
			'options'  => array(),

		);

		foreach ( $page->get_sections() as $id => $section ) {
			$section_id                      = Util::camel_to_snake( strtolower( $section ) );
			$info['sections'][ $section_id ] = array(
				'label'       => $section,
				'subsections' => array(),
			);

			$settings = $page->get_settings_for_section( $id );

			// Get subsections.
			$subsections = array_filter(
				$settings,
				function ( $setting ) {
					return isset( $setting['type'] ) && 'title' === $setting['type'] && isset( $setting['title'] );
				}
			);

			foreach ( $subsections as $subsection ) {
				if ( ! isset( $subsection['id'] ) ) {
					$subsection['id'] = Util::camel_to_snake( strtolower( $subsection['title'] ) );
				}

				$info['sections'][ $section_id ]['subsections'][ $subsection['id'] ] = array(
					'label' => $subsection['title'],
				);
			}

			// Get options.
			$info['sections'][ $section_id ]['options'] = $this->get_page_section_settings( $settings, $page->get_id(), $section_id );
		}
		return $info;
	}


	/**
	 * Get settings for a specific page section.
	 *
	 * @param array  $settings The settings.
	 * @param string $page The page ID.
	 * @param string $section The section ID.
	 * @return array
	 */
	private function get_page_section_settings( $settings, $page, $section = '' ) {
		$current_title = '';
		$data          = array();
		foreach ( $settings as $setting ) {
			if ( 'sectionend' === $setting['type'] || 'slotfill_placeholder' === $setting['type'] || ! isset( $setting['id'] ) ) {
				continue;
			}

			if ( 'title' === $setting['type'] ) {
				$current_title = Util::camel_to_snake( strtolower( $setting['title'] ) );
			} else {
				$location = $page . '.' . $section;
				if ( $current_title ) {
					$location .= '.' . $current_title;
				}

				$data[] = array(
					'id'       => $setting['id'],
					'value'    => $this->wp_get_option( $setting['id'], $setting['default'] ?? null ),
					'title'    => $setting['title'] ?? $setting['desc'] ?? '',
					'location' => $location,
				);
			}
		}
		return $data;
	}

	/**
	 * Merge page info options.
	 *
	 * @param array $page_info The page info.
	 *
	 * @return array|mixed
	 */
	private function merge_page_info_options( array $page_info ) {
		$options = $page_info['options'];
		foreach ( $page_info['sections'] as $section ) {
			$options = array_merge( $options, $section['options'] );
		}
		return $options;
	}
}
