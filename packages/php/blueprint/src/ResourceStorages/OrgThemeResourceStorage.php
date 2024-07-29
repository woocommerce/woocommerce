<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

class OrgThemeResourceStorage extends OrgPluginResourceStorage {
	protected function get_download_link( $slug ): ?string {
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
			return $info->download_link;
		}

		return null;
	}

	public function get_supported_resource(): string {
		return 'wordpress.org/themes';
	}
}
