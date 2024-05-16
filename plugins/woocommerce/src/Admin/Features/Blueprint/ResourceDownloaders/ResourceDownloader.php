<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders;

interface ResourceDownloader {
	public function get_supported_resource(): string;

	/**
	 * @param $slug
	 *
	 * @return string downloaded local path.
	 */
	public function download($slug): string;
}
