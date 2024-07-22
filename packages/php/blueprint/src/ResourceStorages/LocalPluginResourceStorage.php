<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

class LocalPluginResourceStorage implements ResourceStorage {
	protected array $paths   = array();
	protected string $suffix = 'plugins';
	public function __construct( $path ) {
		$this->paths[] = $path;
	}

	/**
	 * Local plugins are already included (downloaded) in the zip file.
	 * Return the full path.
	 *
	 * @param $slug
	 *
	 * @return false|string
	 */
	public function download( $slug ): string {
		foreach ( $this->paths as $path ) {
			$full_path = $path . "/{$this->suffix}/" . $slug . '.zip';
			if ( is_file( $full_path ) ) {
				return $full_path;
			}
		}
		return false;
	}

	public function get_supported_resource(): string {
		return 'self/plugins';
	}
}
