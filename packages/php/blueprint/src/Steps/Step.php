<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

abstract class Step {
	protected array $meta_values = array();
	abstract public static function get_step_name();
	abstract public function get_schema( $version = 1 );
	abstract public function prepare_json_array();

	public function set_meta_values( array $meta_values ) {
		$this->meta_values = $meta_values;
	}

	public function get_json_array() {
		$json_array = $this->prepare_json_array();
		if ( ! empty( $this->meta_values ) ) {
			$json_array['meta'] = $this->meta_values;
		}
		return $json_array;
	}
}
