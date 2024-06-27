<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint\Exporters;

class ExportCoreProfilerSettings implements ExportsStepSchema {
	public function export() {
	    return array(
		    'blogname' => get_option('blogname'),
		    "woocommerce_allow_tracking"=> get_option('woocommerce_allow_tracking', false),
		    'woocommerce_onboarding_profile' => get_option('woocommerce_onboarding_profile', array())
	    );
	}
	public function export_step_schema() {
		return array(
			'step' => $this->get_step_name(),
			'alias' => 'configureCoreProfilerSettings',
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
