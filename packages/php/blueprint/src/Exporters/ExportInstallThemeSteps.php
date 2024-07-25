<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

class ExportInstallThemeSteps implements StepExporter {
	use UseWPFunctions;

	public function export() {
		$steps        = array();
		$themes       = $this->wp_get_themes();
		$active_theme = $this->wp_get_theme();

		foreach ( $themes as $slug => $theme ) {
			// Check if the theme is active
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

	public function get_step_name() {
		return InstallTheme::get_step_name();
	}
}
