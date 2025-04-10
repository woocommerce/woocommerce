<?php

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

/**
 * Class EmptySetSiteOptionsExporter
 *
 * Exports an empty SetSiteOptions step for testing.
 */
class EmptySetSiteOptionsExporter implements StepExporter {
	/**
	 * Export the step.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		return new SetSiteOptions( array() );
	}

	/**
	 * Get the step name.
	 *
	 * @return string The step name.
	 */
	public function get_step_name() {
		return SetSiteOptions::get_step_name();
	}
}
