<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Steps\Step;

interface StepExporter {
	/**
	 * @return Step
	 */
	public function export();
	public function get_step_name();
}
