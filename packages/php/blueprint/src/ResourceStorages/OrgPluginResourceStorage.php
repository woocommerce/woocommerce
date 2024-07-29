<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

class OrgPluginResourceStorage implements ResourceStorage {
	use UseWPFunctions;
	/**
	 * Download the plugin from wordpress.org
	 *
	 * @param $slug
	 *
	 * @return string|null
	 */
	public function download( $slug ): ?string {
		$download_link = $this->get_download_link( $slug );
		if ( ! $download_link ) {
			return false;
		}
		return $this->download_url( $download_link );
	}

	protected function download_url( $url ) {
		return $this->wp_download_url( $url );
	}

	protected function get_download_link( $slug ): ?string {
		$info = $this->wp_plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_object( $info ) && isset( $info->download_link ) ) {
			return $info->download_link;
		}

		return null;
	}

	public function get_supported_resource(): string {
		return 'wordpress.org/plugins';
	}
}
