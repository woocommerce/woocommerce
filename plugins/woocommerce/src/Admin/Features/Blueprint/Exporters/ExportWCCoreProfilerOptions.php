<?php

declare( strict_types = 1);

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\StepExporter;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;
use Automattic\WooCommerce\Blueprint\Steps\SetSiteOptions;
use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * ExportWCCoreProfilerOptions class
 */
class ExportWCCoreProfilerOptions implements StepExporter, HasAlias {
	use UseWPFunctions;

	/**
	 * Export the step
	 *
	 * @return SetSiteOptions
	 */
	public function export() {
		$step = new SetSiteOptions(
			array(
				'blogname'                       => $this->wp_get_option( 'blogname' ),
				'woocommerce_allow_tracking'     => $this->wp_get_option( 'woocommerce_allow_tracking' ),
				'woocommerce_onboarding_profile' => $this->wp_get_option( 'woocommerce_onboarding_profile', array() ),
				'woocommerce_default_country'    => $this->wp_get_option( 'woocommerce_default_country' ),
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
