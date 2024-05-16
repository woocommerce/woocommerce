<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

interface ExportsStepSchema {
	public function export_step_schema();
	public function get_step_name();
}
