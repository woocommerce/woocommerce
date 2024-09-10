<?php

namespace Automattic\WooCommerce\Blueprint\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Steps\ActivatePlugin;
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

		$switch = $this->wp_switch_theme( $name );
		$switch && $result->add_debug( "Switched theme to '{$name}'." );

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
}
