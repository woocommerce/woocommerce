<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class OrgPluginResourceStorage
 *
 * This class handles the storage and downloading of plugins from wordpress.org.
 *
 * @package Automattic\WooCommerce\Blueprint\ResourceStorages
 */
class OrgPluginResourceStorage implements ResourceStorage {
	use UseWPFunctions;

	/**
	 * Download the plugin from wordpress.org
	 *
	 * @param string $slug The slug of the plugin to be downloaded.
	 *
	 * @return string|null The path to the downloaded plugin file, or null on failure.
	 */
	public function download( $slug ): ?string {
		$download_link = $this->get_download_link( $slug );
		if ( ! $download_link ) {
			return false;
		}
		return $this->download_url( $download_link );
	}

	/**
	 * Download the file from the given URL.
	 *
	 * @param string $url The URL to download the file from.
	 *
	 * @return string|null The path to the downloaded file, or null on failure.
	 */
	protected function download_url( $url ) {
		return $this->wp_download_url( $url );
	}

	/**
	 * Get the download link for a plugin from wordpress.org.
	 *
	 * @param string $slug The slug of the plugin.
	 *
	 * @return string|null The download link, or null if not found.
	 */
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

	/**
	 * Get the supported resource type.
	 *
	 * @return string The supported resource type.
	 */
	public function get_supported_resource(): string {
		return 'wordpress.org/plugins';
	}
}
