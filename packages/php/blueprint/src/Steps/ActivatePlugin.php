<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class ActivatePlugin extends Step {
	private string $plugin_name;
	public function __construct($plugin_name) {
	    $this->plugin_name = $plugin_name;
	}
	public static function get_step_name() {
		return 'activatePlugin';
	}

	public static function get_schema( $version = 1 ) {
		return array();
	}

	public function prepare_json_array() {
		return array(
			'step' => static::get_step_name(),
			'pluginName' => $this->plugin_name,
		);
	}
}
