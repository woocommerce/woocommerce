<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Stubs;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

/**
 * Dummy exporter for testing purposes.
 */
class DummyExporter implements StepExporter {

	/**
	 * Export the step.
	 *
	 * @return null The result of the export.
	 */
	public function export() {
		return null;
	}

	/**
	 * Get the description of the step.
	 *
	 * @return string The description of the step.
	 */
	public function get_description() {
		return 'description';
	}

	/**
	 * Get the label of the step.
	 *
	 * @return string The label of the step.
	 */
	public function get_label() {
		return 'Dummy';
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string The name of the step.
	 */
	public function get_step_name() {
		return 'dummy';
	}

	/**
	 * Check if the step is capable of being exported.
	 *
	 * @return bool True if the step is capable of being exported, false otherwise.
	 */
	public function check_step_capabilities(): bool {
		return true;
	}
}
