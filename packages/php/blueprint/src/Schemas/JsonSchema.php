<?php

namespace Automattic\WooCommerce\Blueprint\Schemas;

class JsonSchema {
	protected $schema;

	public function __construct( $json_path ) {
		$schema = json_decode( file_get_contents( $json_path ) );
		$this->schema = $schema;

		if ( ! $this->validate() ) {
			throw new \InvalidArgumentException("Invalid JSON or missing 'steps' field." );
		}
	}

	public function get_steps() {
		return $this->schema->steps;
	}

	public function get_step( $name ) {
		if ( isset( $this->schema->steps->{$name} ) ) {
			return $this->schema->steps->{$name};
		}
		return null;
	}

	/**
	 * Just makes sure that the JSON contains 'steps' field.
	 *
	 * We're going to validate 'steps' later because we can't know the exact schema
	 * ahead of time. 3rd party plugins can add their step processors.
	 *
	 * @return bool[
	 */
	public function validate() {
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}

		if ( ! isset( $this->schema->steps ) ) {
			return false;
		}

		return true;
	}
}
