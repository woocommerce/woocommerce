<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

class ExportInstallPluginSteps implements StepExporter {
	use UseWPFunctions;
	private bool $include_private_plugins = false;

	public function include_private_plugins( bool $boolean ) {
		$this->include_private_plugins = $boolean;
	}

	public function export() {
		$plugins = $this->wp_get_plugins();

		// @todo temporary fix for JN site -- it includes WooCommerce as a custom plugin
		// since JN sites are using a different slug.
		$exclude = array( 'WooCommerce', 'WooCommerce Beta Tester' );
		$steps   = array();
		foreach ( $plugins as $path => $plugin ) {
			if ( in_array( $plugin['Name'], $exclude, true ) ) {
				continue;
			}
			$slug = dirname( $path );
			// single-file plugin
			if ( $slug === '.' ) {
				$slug = pathinfo( $path )['filename'];
			}
			$info = $this->wp_plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			$has_download_link = isset( $info->download_link );
			if ( $this->include_private_plugins === false && ! $has_download_link ) {
				continue;
			}

			$resource = $has_download_link ? 'wordpress.org/plugins' : 'self/plugins';
			$steps[]  = new InstallPlugin(
				$slug,
				$resource,
				array(
					'activate' => $this->wp_is_plugin_active( $path ),
				)
			);
		}

		return $steps;
	}

	public function get_step_name() {
		return InstallPlugin::get_step_name();
	}
}
