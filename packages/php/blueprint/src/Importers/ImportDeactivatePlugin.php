<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\DeactivatePlugin;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;

/**
 * Class ImportDeactivatePlugin
 */
class ImportDeactivatePlugin implements StepProcessor {
	use UsePluginHelpers;

	/**
	 * Process the step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( DeactivatePlugin::get_step_name() );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$name = $schema->pluginName;

		$deactivated = $this->deactivate_plugin_by_slug( $name );

		if ( $this->is_wp_error( $deactivated ) ) {
			$result->add_error( "Unable to deactivate {$name}." );
		} else {
			$result->add_info( "Deactivated {$name}." );
		}

		return $result;
	}

	/**
	 * Get the step class.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return DeactivatePlugin::class;
	}
}
