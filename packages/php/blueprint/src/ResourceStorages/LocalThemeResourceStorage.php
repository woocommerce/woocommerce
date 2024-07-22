<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

class LocalThemeResourceStorage extends LocalPluginResourceStorage {
	protected string $suffix = 'themes';

	public function get_supported_resource(): string {
		return 'self/themes';
	}
}
