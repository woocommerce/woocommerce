<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\PluginsHelper;

class InstallPlugins implements StepProcessor {
	public function process($schema) {
		foreach ($schema->plugins as $plugin) {
			PluginsHelper::install_plugins( array( $plugin->slug ) );
			PluginsHelper::activate_plugins( array( $plugin->slug ) );
		}
		return true;
	}
}
