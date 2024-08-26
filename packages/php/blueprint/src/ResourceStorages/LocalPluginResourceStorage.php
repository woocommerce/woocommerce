<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

/**
 * Class LocalPluginResourceStorage
 */
class LocalPluginResourceStorage implements ResourceStorage {
	/**
	 * Paths to the directories containing the plugins.
	 *
	 * @var array The paths to the directories containing the plugins.
	 */
	protected array $paths = array();

	/**
	 * Suffix of the plugin files.
	 *
	 * @var string The suffix of the plugin files.
	 */
	protected string $suffix = 'plugins';

	/**
	 * LocalPluginResourceStorage constructor.
	 *
	 * @param string $path The path to the directory containing the plugins.
	 */
	public function __construct( $path ) {
		$this->paths[] = $path;
	}

	/**
	 * Local plugins are already included (downloaded) in the zip file.
	 * Return the full path.
	 *
	 * @param string $slug The slug of the plugin to be downloaded.
	 *
	 * @return string|null
	 */
	public function download( $slug ): ?string {
		foreach ( $this->paths as $path ) {
			$full_path = $path . "/{$this->suffix}/" . $slug . '.zip';
			if ( is_file( $full_path ) ) {
				return $full_path;
			}
		}
		return null;
	}

	/**
	 * Get the supported resource.
	 *
	 * @return string The supported resource.
	 */
	public function get_supported_resource(): string {
		return 'self/plugins';
	}
}
