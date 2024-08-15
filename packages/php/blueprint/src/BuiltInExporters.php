<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;

/**
 * Built-in exporters.
 */
class BuiltInExporters {
	/**
	 * Get all built-in exporters.
	 *
	 * @return array List of all built-in exporters.
	 */
	public function get_all() {
		return array(
			new ExportInstallPluginSteps(),
			new ExportInstallThemeSteps(),
		);
	}
}
