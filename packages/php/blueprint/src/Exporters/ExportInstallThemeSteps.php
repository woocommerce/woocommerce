<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportInstallThemeSteps
 *
 * Exporter for the InstallTheme step.
 *
 * @package Automattic\WooCommerce\Blueprint\Exporters
 */
class ExportInstallThemeSteps implements StepExporter {
	use UseWPFunctions;

	/**
	 * Filter callback.
	 *
	 * @var callable
	 */
	private $filter_callback;

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
	 * Export the steps.
	 *
	 * @return array
	 */
	public function export() {
		$steps  = array();
		$themes = $this->wp_get_themes();
		if ( is_callable( $this->filter_callback ) ) {
			$themes = call_user_func( $this->filter_callback, $themes );
		}
		$active_theme = $this->wp_get_theme();

		foreach ( $themes as $slug => $theme ) {
			// Check if the theme is active.
			$is_active = $theme->get( 'Name' ) === $active_theme->get( 'Name' );

			$info = $this->wp_themes_api(
				'theme_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'sections' => false,
					),
				)
			);
			if ( isset( $info->download_link ) ) {
				$steps[] = new InstallTheme(
					$slug,
					'wordpress.org/themes',
					array(
						'activate' => $is_active,
					)
				);
			}
		}

		return $steps;
	}

	/**
	 * Get the step name.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return InstallTheme::get_step_name();
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities(): bool {
		return current_user_can( 'switch_themes' );
	}
}
