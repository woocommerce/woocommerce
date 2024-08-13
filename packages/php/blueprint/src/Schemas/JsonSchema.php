<?php

namespace Automattic\WooCommerce\Blueprint\Schemas;

/**
 * Class JsonSchema
 */
class JsonSchema {
	/**
	 * The schema.
	 *
	 * @var object The schema.
	 */
	protected $schema;

	/**
	 * JsonSchema constructor.
	 *
	 * @param string $json_path The path to the JSON file.
	 * @throws \InvalidArgumentException If the JSON is invalid or missing 'steps' field.
	 */
	public function __construct( $json_path ) {
		// phpcs:ignore
		$schema       = json_decode( file_get_contents( $json_path ) );
		$this->schema = $schema;

		if ( ! $this->validate() ) {
			throw new \InvalidArgumentException( "Invalid JSON or missing 'steps' field." );
		}
	}

	/**
	 * Returns the steps from the schema.
	 *
	 * @return array
	 */
	public function get_steps() {
		return $this->schema->steps;
	}

	/**
	 * Returns steps by name.
	 *
	 * @param string $name The name of the step.
	 *
	 * @return array
	 */
	public function get_step( $name ) {
		$steps = array();
		foreach ( $this->schema->steps as $step ) {
			if ( $step->step === $name ) {
				$steps[] = $step;
			}
		}

		return $steps;
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
