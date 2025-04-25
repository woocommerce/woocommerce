<?php

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Importers;

use Automattic\WooCommerce\Blueprint\StepProcessor;
use Automattic\WooCommerce\Blueprint\StepProcessorResult;
use Automattic\WooCommerce\Blueprint\Tests\stubs\Steps\DummyStep;

/**
 * Dummy importer class.
 */
class DummyImporter implements StepProcessor {
	/**
	 * Process the step.
	 *
	 * @param object $schema The schema to process.
	 * @return StepProcessorResult The result of the step.
	 */
	public function process( $schema ): StepProcessorResult {
		return StepProcessorResult::success( DummyStep::get_step_name() );
	}

	/**
	 * Get the step class.
	 *
	 * @return string The step class.
	 */
	public function get_step_class(): string {
		return DummyStep::class;
	}

	/**
	 * Check the step capabilities.
	 *
	 * @param object $schema The schema to check.
	 * @return bool True if the step capabilities are valid.
	 */
	public function check_step_capabilities( $schema ): bool {
		return true;
	}
}
