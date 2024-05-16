<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders;

class OrgPluginResourceDownloader implements ResourceDownloader {
	public function download( $slug ): string {
		return $this->download_url($this->get_download_link($slug));
	}

	protected function download_url($url) {
		if ( ! function_exists( 'download_url' ) ) {
			include ABSPATH . '/wp-admin/includes/file.php';
		}
		return \download_url($url);
	}

	protected function get_download_link($slug) {
		if ( ! function_exists( 'plugins_api' ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
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

		return $info->download_link;
	}

	public function get_supported_resource(): string {
		return 'wordpress.org/plugins';
	}
}
