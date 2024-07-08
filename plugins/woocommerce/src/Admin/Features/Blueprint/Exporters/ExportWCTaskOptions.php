<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

class ExportWCTaskOptions implements StepExporter, HasAlias {
	public function export() {
		$step = new SetSiteOptions(array(
			'woocommerce_admin_customize_store_completed' => get_option( 'woocommerce_admin_customize_store_completed', 'no' ),
			'woocommerce_task_list_tracked_completed_actions' => get_option( 'woocommerce_task_list_tracked_completed_actions', array() ),
		));

		$step->set_meta_values(array(
			'plugin' => 'woocommerce',
			'alias'  => $this->get_alias(),
		) );

		return $step;
	}

	public function get_step_name() {
		return 'setOptions';
	}

	public function get_alias() {
		return 'setWCTaskOptions';
	}
}
