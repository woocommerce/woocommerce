<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;

/**
 * Class ImportActivatePlugin
 */
class ImportActivatePlugin implements StepProcessor {
	use UsePluginHelpers;

	/**
	 * Process the schema.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( ActivatePlugin::get_step_name() );

		// Not snake case because it's a property of the schema.
		$plugin_path = $schema->pluginPath; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$activate = $this->wp_activate_plugin( $plugin_path );

		if ( $this->is_wp_error( $activate ) ) {
			$result->add_error( "Unable to activate {$plugin_path}." );
		} else {
			$result->add_info( "Activated {$plugin_path}." );
		}

		return $result;
	}

	/**
	 * Get the step class.
	 *
	 * @return string
	 */
	public function get_step_class(): string {
		return ActivatePlugin::class;
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		return current_user_can( 'activate_plugins' );
	}
}
