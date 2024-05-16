<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportTaskOptions implements ExportsStepSchema {
	public function export() {
	    return array(
			'woocommerce_admin_customize_store_completed' => get_option('woocommerce_admin_customize_store_completed', 'no'),
		    'woocommerce_task_list_tracked_completed_actions' => get_option('woocommerce_task_list_tracked_completed_actions', array())
	    );
	}
	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'options' => $this->export()
		);
	}

	public function get_step_name() {
		return 'configureTaskOptions';
	}
}
