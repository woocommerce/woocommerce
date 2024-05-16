<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\Util;
use WC_Admin_Settings;
use WC_Settings_Page;

class ExportSettings implements ExportsStepSchema {
	/**
	 * @var WC_Settings_Page[]
	 */
	private array $setting_pages;
	private array $exclude_pages = array('integration', 'site-visibility');

	public function __construct(array $setting_pages = array()) {
		if (empty($setting_pages)){
			$setting_pages = WC_Admin_Settings::get_settings_pages();
		}
		$this->setting_pages = $setting_pages;
		$this->add_filter('wooblueprint_export_setttings', array($this, 'add_site_visibility_setttings'));
	}

	public function add_filter($name, $callback) {
		return \add_filter($name, $callback);
	}

	public function apply_filters($name, $args) {
		return \apply_filters($name, $args);
	}

	public function export() {
		$pages = [];
		$options = [];

		foreach ($this->setting_pages as $page) {
			$id = $page->get_id();
			if (in_array($id, $this->exclude_pages, true)) {
				continue;
			}

			$pages[$id] = $this->get_page_info($page);
			foreach ($pages[$id]['options'] as $option) {
				$options[] = $option;
			}
			unset($pages[$id]['options']);
		}

		$export_data =  compact('pages', 'options');

		$this->apply_filters('wooblueprint_export_setttings', $export_data);

		return $export_data;
 	}

	 protected function get_page_info(WC_Settings_Page $page) {
		$info = array(
			'label' => $page->get_label(),
			'sections' => array(),
		);

		foreach ($page->get_sections() as $id => $section) {
			$section_id = Util::camel_to_snake(strtolower($section));
			$info['sections'][$section_id] = array(
				'label' => $section,
				'subsections' => array(),
			);

			$settings = $page->get_settings_for_section($id);

			// Get subsections
			$subsections = array_filter($settings, function($setting) {
				return isset($setting['type']) && $setting['type'] === 'title' && isset($setting['title']);
			});


			foreach ($subsections as $subsection) {
				if (!isset($subsection['id'])) {
					$subsection['id'] = Util::camel_to_snake( strtolower( $subsection['title'] ) );
				}

				$info['sections'][$section_id]['subsections'][$subsection['id']] = array(
					'label' => $subsection['title']
				);
			}

			// Ge opttions
			$info['options'] = $this->get_page_section_settings($settings, $page->get_id(), $section_id);
		}
		return $info;
	 }

	private function get_page_section_settings($settings, $page, $section = '') {
		$current_title = '';
		$data = array();
		foreach ($settings as $setting) {
			if ($setting['type'] === 'sectionend' || $setting['type'] === 'slotfill_placeholder' || !isset($setting['id']) ) {
				continue;
			}

			if ($setting['type'] == 'title') {
				$current_title = Util::camel_to_snake(strtolower($setting['title']));
			} else {
				$location = $page.'.'.$section;
				if ($current_title) {
					$location .= '.'.$current_title;
				}

				$data[] = array(
					'id' => $setting['id'],
					'value' => get_option($setting['id'], $setting['default'] ?? null),
					'title' => $setting['title'] ?? $setting['desc'] ?? '',
					'location' => $location,
				);
			}
		}
		return $data;
	}

	public function add_site_visibility_setttings($export_data) {
		$export_data['pages']['site_visibility'] = array(
			'label' => 'Site Visibility',
			'sections'=> array(
				'general' => array(
					'label' => 'General'
				)
			)
		);

		$export_data['options'][] = array(
			'id' => 'woocommerce_coming_soon',
			'value' => get_option('woocommerce_coming_soon'),
			'title' => 'Coming soon',
			'location' => 'site_visibilitty.general'
		);

		$export_data['options'][] = array(
			'id' => 'woocommerce_store_pages_only',
			'value' => get_option('woocommerce_store_pages_only'),
			'title' => 'Restrict to store pages only',
			'location' => 'site_visibilitty.general',
		);

		return $export_data;
	}

	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'values' => $this->export()
		);
	}

	public function get_step_name() {
	    return 'configureSettings';
	}
}
