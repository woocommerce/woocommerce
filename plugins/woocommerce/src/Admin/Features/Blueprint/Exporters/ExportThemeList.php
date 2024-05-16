<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportThemeList implements ExportsStepSchema {
	public function export() {
		if ( ! function_exists( 'wp_get_themes' ) ) {
			require_once ABSPATH . 'wp-includes/theme.php';
		}
		if ( ! function_exists( 'themes_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/theme.php';
		}
		$export       = array();
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
				$export[] = array(
					'slug'     => $slug,
					'resource' => 'wordpress.org/themes',
					'activate'   => $is_active,
				);
			}
		}

		return $export;
	}

	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'themes' => $this->export()
		);
	}

	public function get_step_name() {
	    return 'installThemes';
	}
}
