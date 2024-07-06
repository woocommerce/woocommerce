<?php

namespace Automattic\WooCommerce\Blueprint;

use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallPluginSteps;
use Automattic\WooCommerce\Blueprint\Exporters\ExportInstallThemeSteps;

class BuiltInExporters {
	public function get_all() {
		return array(
			new ExportInstallPluginSteps(),
			new ExportInstallThemeSteps(),
		);
	}
}
