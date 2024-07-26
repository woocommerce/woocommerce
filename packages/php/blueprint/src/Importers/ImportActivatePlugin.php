<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;

class ImportActivatePlugin implements StepProcessor {
	use UsePluginHelpers;

	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( 'DeactivatePlugins' );
		$name   = $schema->pluginName;

		$activate = $this->activate_plugin_by_slug( $name );
		if ( $activate ) {
			$result->add_info( "Activated {$name}." );
		} else {
			$result->add_error( "Unable to activate {$name}." );
		}

		return $result;
	}

	public function get_step_class(): string {
		return ActivatePlugin::class;
	}
}
