<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivateTheme;
use Automattic\WooCommerce\Blueprint\UsePluginHelpers;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ImportActivateTheme
 *
 * @package Automattic\WooCommerce\Blueprint\Importers
 */
class ImportActivateTheme implements StepProcessor {
	use UsePluginHelpers;
	use UseWPFunctions;

	/**
	 * Process the step.
	 *
	 * @param object $schema The schema for the step.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult {
		$result = StepProcessorResult::success( ActivateTheme::get_step_name() );
		// phpcs:ignore
		$name   = $schema->themeName;

		$this->wp_switch_theme( $name );

		$current_theme = $this->wp_get_theme()->get_stylesheet();

		if ( $current_theme === $name ) {
			$result->add_debug( "Switched theme to '$name'." );
		}

		return $result;
	}

	/**
	 * Returns the class name of the step this processor handles.
	 *
	 * @return string The class name of the step this processor handles.
	 */
	public function get_step_class(): string {
		return ActivateTheme::class;
	}

	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool {
		return current_user_can( 'switch_themes' );
	}
}
