<?php

namespace Automattic\WooCommerce\Blueprint;

/**
 * Interface StepProcessor
 */
interface StepProcessor {
	/**
	 * Process the schema.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return StepProcessorResult
	 */
	public function process( $schema ): StepProcessorResult;

	/**
	 * Get the step class.
	 *
	 * @return string
	 */
	public function get_step_class(): string;
	/**
	 * Check if the current user has the required capabilities for this step.
	 *
	 * @param object $schema The schema to process.
	 *
	 * @return bool True if the user has the required capabilities. False otherwise.
	 */
	public function check_step_capabilities( $schema ): bool;
}
