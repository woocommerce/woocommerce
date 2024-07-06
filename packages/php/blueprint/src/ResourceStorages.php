<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\ResourceStorages\ResourceStorage;

class ResourceStorages {
	/**
	 * @var ResourceStorages[]
	 */
	protected array $storages = array();
	public function add_storage( ResourceStorage $downloader ) {
		$supported_resource = $downloader->get_supported_resource();
		if ( ! isset( $this->storages[ $supported_resource ] ) ) {
			$this->storages[ $supported_resource ] = array();
		}
		$this->storages[ $supported_resource ][] = $downloader;
	}

	public function is_supported_resource( $resource ) {
		return isset( $this->storages[ $resource ] );
	}

	public function download( $slug, $resource ) {
		if ( ! isset( $this->storages[ $resource ] ) ) {
			return false;
		}
		$storages = $this->storages[ $resource ];
		foreach ( $storages as $storage ) {
			if ( $found = $storage->download( $slug ) ) {
				return $found;
			}
		}

		return false;
	}
}
