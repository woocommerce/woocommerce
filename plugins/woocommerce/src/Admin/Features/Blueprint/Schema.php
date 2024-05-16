<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

/**
 * @todo interface should be good enough?
 */
abstract class Schema {
	protected object $schema;
	abstract public function validate();
	public function get_steps() {
		return $this->schema->steps;
	}

	public function get_step($name) {
	    if (isset($this->schema->steps->{$name})) {
			return $this->schema->steps->{$name};
	    }
		return null;
	}
}
