<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

abstract class Step {
	protected array $meta_values = array();
	public abstract static function get_step_name();
	public abstract function get_schema($version = 1);
	public abstract function prepare_json_array();

	public function set_meta_values(array $meta_values) {
	    $this->meta_values = $meta_values;
	}

	public function get_json_array() {
	    $json_array = $this->prepare_json_array();
		if (!empty($this->meta_values)){
			$json_array['meta'] = $this->meta_values;
		}
		return $json_array;
	}
}
