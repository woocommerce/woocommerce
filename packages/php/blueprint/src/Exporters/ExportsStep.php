<?php

namespace Automattic\WooCommerce\Blueprint\Exporters;

interface ExportsStep {
	public function export_step();
	public function get_step_name();
}
