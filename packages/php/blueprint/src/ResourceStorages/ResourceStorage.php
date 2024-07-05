<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

interface ResourceStorage {
	public function get_supported_resource(): string;

	/**
	 * @param $slug
	 *
	 * @return string downloaded local path.
	 */
	public function download($slug): string;
}
