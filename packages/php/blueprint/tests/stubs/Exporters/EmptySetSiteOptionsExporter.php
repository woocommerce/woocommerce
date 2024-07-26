<?php

namespace Automattic\WooCommerce\Blueprint\Tests\stubs\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

/**
 * Class EmptySetSiteOptionsExporter
 *
 * Exports an empty SetSiteOptions step for testing.
 *
 */
class EmptySetSiteOptionsExporter implements StepExporter {
	public function export() {
		return new SetSiteOptions( array() );
	}

	public function get_step_name() {
		return SetSiteOptions::get_step_name();
	}
}
