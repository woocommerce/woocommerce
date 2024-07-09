<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;

/**
 * ExportWCCoreProfilerOptions class
 */
class ExportWCCoreProfilerOptions implements StepExporter, HasAlias {
	/**
	 * Export the step
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$step = new SetSiteOptions(
			array(
				'blogname'                       => get_option( 'blogname' ),
				'woocommerce_allow_tracking'     => get_option( 'woocommerce_allow_tracking', false ),
				'woocommerce_onboarding_profile' => get_option( 'woocommerce_onboarding_profile', array() ),
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
	 * Get the step name
	 *
	 * @return string
	 */
	public function get_step_name() {
		return 'setSiteOptions';
	}

	/**
	 * Get the alias
	 *
	 * @return string
	 */
	public function get_alias() {
		return 'setWCCoreProfilerOptions';
	}
}
