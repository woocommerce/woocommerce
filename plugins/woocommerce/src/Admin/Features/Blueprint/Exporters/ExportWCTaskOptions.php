<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class ExportWCTaskOptions
 *
 * This class exports WooCommerce task options and implements the StepExporter and HasAlias interfaces.
 *
 * @package Automattic\WooCommerce\Admin\Features\Blueprint\Exporters
 */
class ExportWCTaskOptions implements StepExporter, HasAlias {
	use UseWPFunctions;

	/**
	 * Export WooCommerce task options.
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$step = new SetSiteOptions(
			array(
				'woocommerce_admin_customize_store_completed' => $this->wp_get_option( 'woocommerce_admin_customize_store_completed', 'no' ),
				'woocommerce_task_list_tracked_completed_actions' => $this->wp_get_option( 'woocommerce_task_list_tracked_completed_actions', array() ),
			)
		);

		$step->set_meta_values(
			array(
				'plugin' => 'woocommerce',
				'alias'  => $this->get_alias(),
			)
		);

		return $step;
	}

	/**
	 * Get the name of the step.
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setOptions';
	}

	/**
	 * Get the alias for this exporter.
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCTaskOptions';
	}
}
