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
		return new SetSiteOptions(
			array(
				'blogname'                       => $this->wp_get_option( 'blogname' ),
				'woocommerce_allow_tracking'     => $this->wp_get_option( 'woocommerce_allow_tracking' ),
				'woocommerce_onboarding_profile' => $this->wp_get_option( 'woocommerce_onboarding_profile', array() ),
				'woocommerce_default_country'    => $this->wp_get_option( 'woocommerce_default_country' ),
			)
		);
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

	/**
	 * Return label used in the frontend.
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Onboarding Configuration', 'woocommerce' );
	}

	/**
	 * Return description used in the frontend.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'It includes onboarding configuration options', 'woocommerce' );
	}
}
