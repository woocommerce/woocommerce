<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;
use Automattic\WooCommerce\Blueprint\Util;
use WC_Admin_Settings;
use WC_Settings_Page;

/**
 * Class ExportWCSettings
 *
 * This class exports WooCommerce settings and implements the StepExporter and HasAlias interfaces.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCSettings implements StepExporter, HasAlias {
	use UseWPFunctions;

	/**
	 * Array of WC_Settings_Page objects.
	 *
	 * @var WC_Settings_Page[]
	 */
	private array $setting_pages;

	/**
	 * Array of page IDs to exclude from export.
	 *
	 * @var array
	 */
	private array $exclude_pages = array( 'integration', 'site-visibility' );

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
		$this->wp_add_filter( 'wooblueprint_export_settings', array( $this, 'add_site_visibility_settings' ), 10, 3 );
	}

	/**
	 * Export WooCommerce settings.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$pages       = array();
		$options     = array();
		$option_info = array();

		foreach ( $this->setting_pages as $page ) {
			$id = $page->get_id();
			if ( in_array( $id, $this->exclude_pages, true ) ) {
				continue;
			}
			$pages[ $id ] = $this->get_page_info( $page );
			foreach ( $pages[ $id ]['options'] as $option ) {
				$options[ $option['id'] ]     = $option['value'];
				$option_info[ $option['id'] ] = array(
					'location' => $option['location'],
					'title'    => $option['title'],
				);
			}
			unset( $pages[ $id ]['options'] );
		}

		$filtered = $this->wp_apply_filters( 'wooblueprint_export_settings', $options, $pages, $option_info );

		$step = new SetSiteOptions( $filtered['options'] );
		$step->set_meta_values(
			array(
				'plugin' => 'woocommerce',
				'pages'  => $filtered['pages'],
				'info'   => $option_info,
				'alias'  => $this->get_alias(),
			)
		);

		return $step;
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
			$info['options'] = $this->get_page_section_settings( $settings, $page->get_id(), $section_id );
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
	 * Add site visibility settings.
	 *
	 * @param array $options The options array.
	 * @param array $pages The pages array.
	 * @param array $option_info The option information array.
	 * @return array
	 */
	public function add_site_visibility_settings( array $options, array $pages, array $option_info ) {
		$pages['site_visibility'] = array(
			'label'    => 'Site Visibility',
			'sections' => array(
				'general' => array(
					'label' => 'General',
				),
			),
		);

		$options['woocommerce_coming_soon']      = $this->wp_get_option( 'woocommerce_coming_soon' );
		$options['woocommerce_store_pages_only'] = $this->wp_get_option( 'woocommerce_store_pages_only' );

		$option_info['woocommerce_coming_soon'] = array(
			'location' => 'site_visibility.general',
			'title'    => 'Coming soon',
		);

		$option_info['woocommerce_store_pages_only'] = array(
			'location' => 'site_visibility.general',
			'title'    => 'Apply to store pages only',
		);

		return compact( 'options', 'pages', 'option_info' );
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setSiteOptions';
	}

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCSettings';
	}
}
