<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

use Automattic\WooCommerce\Blueprint\Exporters\ExportsStep;
use Automattic\WooCommerce\Blueprint\Exporters\HasAlias;

class ExportCoreProfilerSettings implements ExportsStep, HasAlias {
	public function export() {
		return array(
			'blogname'                       => get_option( 'blogname' ),
			'woocommerce_allow_tracking'     => get_option( 'woocommerce_allow_tracking', false ),
			'woocommerce_onboarding_profile' => get_option( 'woocommerce_onboarding_profile', array() ),
		);
	}
	public function export_step() {
		return array(
			'step'    => $this->get_step_name(),
			'alias'   => $this->get_alias(),
			'options' => $this->export(),
			'meta'    => array(
				'plugin' => 'woocommerce',
			),
		);
	}

	public function get_step_name() {
		return 'setOptions';
	}

	public function get_alias() {
		return 'configureCoreProfilerSettings';
	}
}
