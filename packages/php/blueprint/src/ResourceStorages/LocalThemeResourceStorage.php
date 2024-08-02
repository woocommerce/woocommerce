<?php

namespace Automattic\WooCommerce\Blueprint\ResourceStorages;

/**
 * Class LocalThemeResourceStorage
 */
class LocalThemeResourceStorage extends LocalPluginResourceStorage {
	/**
	 * The suffix.
	 *
	 * @var string The suffix.
	 */
	protected string $suffix = 'themes';

	/**
	 * Get the supported resource.
	 *
	 * @return string The supported resource.
	 */
	public function get_supported_resource(): string {
		return 'self/themes';
	}
}
