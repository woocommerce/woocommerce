<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\InstallTheme;

class ExportInstallThemeSteps implements StepExporter {
	public function export() {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-includes/theme.php';
		}
		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		$steps       = array();
		$thmes        = wp_get_themes();
		$active_theme = wp_get_theme();

		foreach ( $thmes as $slug => $theme ) {
			// Check if the theme is active
			$is_active = $theme->get( 'Name' ) == $active_theme->get( 'Name' );

			$info = \themes_api(
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
