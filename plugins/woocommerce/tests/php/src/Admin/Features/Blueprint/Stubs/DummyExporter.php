<?php

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Stubs;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;

class DummyExporter implements StepExporter {

	public function export() {
		return null;
	}

	public function get_description() {
	    return 'description';
	}

	public function get_label() {
		return 'Dummy';
	}

	public function get_step_name() {
		return 'dummy';
	}
}
