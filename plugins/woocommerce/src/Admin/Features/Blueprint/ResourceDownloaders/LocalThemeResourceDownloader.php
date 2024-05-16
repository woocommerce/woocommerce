<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\ResourceDownloaders;

class LocalThemeResourceDownloader extends LocalPluginResourceDownloader {
	protected string $suffix = 'themes';

	public function get_supported_resource(): string {
		return 'self/themes';
	}
}
