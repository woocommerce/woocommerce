<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\ResourceStorages\ResourceStorage;

/**
 * Class ResourceStorages
 */
class ResourceStorages {
	/**
	 * Storage collection.
	 *
	 * @var ResourceStorages[]
	 */
	protected array $storages = array();

	/**
	 * Add a downloader.
	 *
	 * @param ResourceStorage $downloader The downloader to add.
	 *
	 * @return void
	 */
	public function add_storage( ResourceStorage $downloader ) {
		$supported_resource = $downloader->get_supported_resource();
		if ( ! isset( $this->storages[ $supported_resource ] ) ) {
			$this->storages[ $supported_resource ] = array();
		}
		$this->storages[ $supported_resource ][] = $downloader;
	}

	/**
	 * Check if the resource is supported.
	 *
	 * @param string $resource The resource to check.
	 *
	 * @return bool
	 */
	// phpcs:ignore
	public function is_supported_resource( $resource ) {
		return isset( $this->storages[ $resource ] );
	}

	/**
	 * Download the resource.
	 *
	 * @param string $slug The slug of the resource to download.
	 * @param string $resource The resource to download.
	 *
	 * @return false|string
	 */
	// phpcs:ignore
	public function download( $slug, $resource ) {
		if ( ! isset( $this->storages[ $resource ] ) ) {
			return false;
		}
		$storages = $this->storages[ $resource ];
		foreach ( $storages as $storage ) {
			// phpcs:ignore
			if ( $found = $storage->download( $slug ) ) {
				return $found;
			}
		}

		return false;
	}
}
