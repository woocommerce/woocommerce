<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Admin\Features\Blueprint\UseHooks;

class ExportRedirectToAfter implements ExportsStepSchema {
	use UseHooks;
	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'url' => $this->export(),
		);
	}

	public function export() {
		return $this->apply_filters('wooblueprint_export_redirect_to_after', 'admin.php?page=wc-admin');
	}

	public function get_step_name() {
		return 'redirectToAfter';
	}
}
