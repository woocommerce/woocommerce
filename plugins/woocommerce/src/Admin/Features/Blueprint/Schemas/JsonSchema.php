<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class JsonSchema extends Schema {
	public function __construct($json_path) {
		$schema = json_decode(file_get_contents($json_path));
		if (!$this->validate()) {
			if (!$this->validate()) {
				throw new \InvalidArgumentException($json_path . ' is not a valid JSON.');
			}
		}

		$this->schema = $schema;
	}

	public function validate() {
		if (json_last_error() !== JSON_ERROR_NONE) {
			return false;
		}
		return true;
	}
}
