<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;

class ExportTaskOptions implements ExportsStep {
	public function export() {
	    return array(
			'woocommerce_admin_customize_store_completed' => get_option('woocommerce_admin_customize_store_completed', 'no'),
		    'woocommerce_task_list_tracked_completed_actions' => get_option('woocommerce_task_list_tracked_completed_actions', array())
	    );
	}
	public function export_step() {
		return array(
			'step' => $this->get_step_name(),
			'alias' => 'configureTaskOptions',
			'options' => $this->export(),
			'meta' => array(
				'plugin' => 'woocommerce'
			)
		);
	}

	public function get_step_name() {
		return 'setOptions';
	}
}
