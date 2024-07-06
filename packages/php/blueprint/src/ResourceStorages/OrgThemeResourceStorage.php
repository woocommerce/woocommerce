<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

class OrgThemeResourceStorage extends OrgPluginResourceStorage {
	protected function get_download_link( $slug ) {
		if ( ! function_exists( 'themes_api' ) ) {
			include_once ABSPATH . '/wp-admin/includes/themes.php';
		}
		$info = \themes_api(
			'theme_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		return $info->download_link;
	}

	public function get_supported_resource(): string {
		return 'wordpress.org/themes';
	}
}
