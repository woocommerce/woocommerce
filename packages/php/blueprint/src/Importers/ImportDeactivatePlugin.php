<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeactivatePlugin;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;

class ImportDeactivatePlugin implements StepProcessor {
	use UsePluginHelpers;

	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( 'DeactivatePlugins' );
		$name   = $schema->pluginName;

		$this->deactivate_plugin_by_slug( $name );
		$result->add_info( "Deactivated {$name}." );

		return $result;
	}

	public function get_step_class(): string {
		return DeactivatePlugin::class;
	}
}
