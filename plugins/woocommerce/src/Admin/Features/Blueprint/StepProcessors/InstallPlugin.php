<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\PluginsHelper;

class InstallPlugin implements StepProcessor {
	public function process($schema) {
		PluginsHelper::install_plugins( array( $schema->pluginZipFile->slug ) );
		PluginsHelper::activate_plugins( array( $schema->pluginZipFile->slug ) );

		return true;
	}
}
