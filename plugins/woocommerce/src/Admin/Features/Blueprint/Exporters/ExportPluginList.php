<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportPluginList implements ExportsStepSchema {
	private bool $include_private_plugins = false;
	public function include_private_plugins(bool $boolean) {
		$this->include_private_plugins = $boolean;
	}
	public function export() {
		if (!function_exists('is_plugin_active') || !function_exists('get_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin-install.php';
		}
		$export = array();
		$plugins = get_plugins();

		// @todo temporary fix for JN site -- it includes WooCommerce as a custom plugin
		// since JN sites are using a different slug.
		$exclude = array('WooCommerce');
		foreach ($plugins as $path => $plugin) {
			if (in_array($plugin['Name'], $exclude, true)) {
				continue;
			}
			$slug = dirname($path);
			// single-file plugin
			if ($slug === '.') {
				$slug = pathinfo($path)['filename'];
			}
			$info = \plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			$has_download_link = isset($info->download_link);
			if ($this->include_private_plugins === false && !$has_download_link) {
				continue;
			}

			$export[] = array(
				'slug' => $slug,
				'resource' => $has_download_link ? 'wordpress.org/plugins' : 'self/plugins',
				'activate' => \is_plugin_active($path)
			);
		}
	    return $export;
	}

	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'plugins' => $this->export()
		);
	}

	public function get_step_name() {
		return 'installPlugins';
	}
}
