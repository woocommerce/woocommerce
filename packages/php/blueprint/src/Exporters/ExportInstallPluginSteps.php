<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\InstallPlugin;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportInstallPluginSteps
 *
 * @package Automattic\WooCommerce\Blueprint\Exporters
 */
class ExportInstallPluginSteps implements StepExporter {
	use UseWPFunctions;

	/**
	 * Whether to include private plugins in the export.
	 *
	 * @var bool Whether to include private plugins in the export.
	 */
	private bool $include_private_plugins = false;

	/**
	 * Set whether to include private plugins in the export.
	 *
	 * @param bool $boolean Whether to include private plugins.
	 */
	public function include_private_plugins( bool $boolean ) {
		$this->include_private_plugins = $boolean;
	}

	/**
	 * Export the steps required to install plugins.
	 *
	 * @return array The array of InstallPlugin steps.
	 */
	public function export() {
		$plugins = $this->wp_get_plugins();

		// @todo temporary fix for JN site -- it includes WooCommerce as a custom plugin
		// since JN sites are using a different slug.
		$exclude = array( 'WooCommerce Beta Tester' );
		$steps   = array();
		foreach ( $plugins as $path => $plugin ) {
			if ( in_array( $plugin['Name'], $exclude, true ) ) {
				continue;
			}
			// skip inactive plugins for now.
			if ( ! $this->wp_is_plugin_active( $path ) ) {
				continue;
			}
			$slug = dirname( $path );
			// single-file plugin.
			if ( '.' === $slug ) {
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
			if ( false === $this->include_private_plugins && ! $has_download_link ) {
				continue;
			}

			$resource = $has_download_link ? 'wordpress.org/plugins' : 'self/plugins';
			$steps[]  = new InstallPlugin(
				$slug,
				$resource,
				array(
					'activate' => true,
				)
			);
		}

		return $steps;
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string The step name.
	 */
	public function get_step_name() {
		return InstallPlugin::get_step_name();
	}
}
