<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

/**
 * Interface ResourceStorage
 *
 * ResourceStorage is an abstraction layer for various storages for WordPress files
 * such as plugins and themes. It provides a common interface for downloading
 * the files whether they are stored locally or remotely.
 *
 * @package Automattic\WooCommerce\Blueprint\ResourceStorages
 */
interface ResourceStorage {
	/**
	 * Return supported resource type.
	 *
	 * @return string
	 */
	public function get_supported_resource(): string;

	/**
	 * Download the resource.
	 *
	 * @param string $slug resource slug.
	 *
	 * @return string|null downloaded local path.
	 */
	public function download( $slug ): ?string;
}
