<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class DeletePlugin extends Step {
	private string $plugin_name;
	public function __construct($plugin_name) {
	    $this->plugin_name = $plugin_name;
	}
	public static function get_step_name() {
		return "deletePlugin";
	}

	public static function get_schema( $version = 1 ) {
		return array(
			'type' => 'object',
			'properties' => array(
				'step' => array(
					'type' => 'string',
					'enum' => array( static::get_step_name() ),
				),
				'pluginName' => array(
					'type' => 'string',
				),
			),
			'required' => array( 'step', 'pluginName' ),
		);
	}

	public function prepare_json_array() {
		return array(
			'step' => static::get_step_name(),
			'pluginName' => $this->plugin_name,
		);
	}
}
