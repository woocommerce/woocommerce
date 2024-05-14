<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessors;

use Automattic\WooCommerce\Admin\Features\Blueprint\StepProcessor;
use Automattic\WooCommerce\Admin\PluginsHelper;

class InstallPluginProcessor implements StepProcessor {
	private $schema;
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	public function process() {
		PluginsHelper::install_plugins( array( $this->schema->pluginZipFile->slug ) );
		PluginsHelper::activate_plugins( array( $this->schema->pluginZipFile->slug ) );

		return true;
	}
}
