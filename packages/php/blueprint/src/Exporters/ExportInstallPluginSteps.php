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
	 * Filter callback.
	 *
	 * @var callable
	 */
	private $filter_callback;

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
	 * Register a filter callback to filter the plugins to export.
	 *
	 * @param callable $callback Filter callback.
	 *
	 * @return void
	 */
	public function filter( callable $callback ) {
		$this->filter_callback = $callback;
	}

	/**
	 * Export the steps required to install plugins.
	 *
	 * @return array The array of InstallPlugin steps.
	 */
	public function export() {
		$plugins = $this->sort_plugins_by_dep( $this->wp_get_plugins() );

		if ( is_callable( $this->filter_callback ) ) {
			$plugins = call_user_func( $this->filter_callback, $plugins );
		}

		// @todo temporary fix for JN site -- it includes WooCommerce as a custom plugin
		// since JN sites are using a different slug.
		$exclude = array( 'WooCommerce Beta Tester' );
		$steps   = array();
		foreach ( $plugins as $path => $plugin ) {
			if ( in_array( $plugin['Name'], $exclude, true ) ) {
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
	 * Sort plugins by dependencies -- put the dependencies at the top.
	 *
	 * @param array $plugins List of plugins to sort (from wp_get_plugins function).
	 *
	 * @return array
	 */
	public function sort_plugins_by_dep( array $plugins ) {
		$sorted  = array();
		$visited = array();

		// Create a mapping of lowercase titles to plugin keys for quick lookups.
		$title_map = array_reduce(
			array_keys( $plugins ),
			function ( $carry, $key ) use ( $plugins ) {
				$title = strtolower( $plugins[ $key ]['Title'] ?? '' );
				if ( $title ) {
					$carry[ $title ] = $key;
				}
				return $carry;
			},
			array()
		);

		// Recursive function for topological sort.
		$visit = function ( $plugin_key ) use ( &$visit, &$sorted, &$visited, $plugins, $title_map ) {
			if ( isset( $visited[ $plugin_key ] ) ) {
				return;
			}
			$visited[ $plugin_key ] = true;

			$requires = $plugins[ $plugin_key ]['RequiresPlugins'] ?? array();
			foreach ( (array) $requires as $dependency ) {
				$dependency_key = $title_map[ strtolower( $dependency ) ] ?? null;
				if ( $dependency_key ) {
					$visit( $dependency_key );
				}
			}
			$sorted[ $plugin_key ] = $plugins[ $plugin_key ];
		};

		// Perform sort for each plugin.
		foreach ( array_keys( $plugins ) as $plugin_key ) {
			$visit( $plugin_key );
		}

		return $sorted;
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string The step name.
	 */
	public function get_step_name() {
		return InstallPlugin::get_step_name();
	}
	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities(): bool {
		return current_user_can( 'activate_plugins' );
	}
}
