<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

class ActivateTheme extends Step {
	private string $theme_name;
	public function __construct($theme_name) {
	    $this->theme_name = $theme_name;
	}
	public static function get_step_name() {
		return 'activateTheme';
	}

	public static function get_schema( $version = 1 ) {
		return array();
	}

	public function prepare_json_array() {
		return array(
			'step' => static::get_step_name(),
			'themeName' => $this->theme_name,
		);
	}
}
