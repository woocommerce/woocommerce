<?php

namespace Automattic\WooCommerce\Blueprint\Schemas;

use Automattic\WooCommerce\Blueprint\UseWPFunctions;

/**
 * Class JsonSchema
 */
class JsonSchema {
	use UseWPFunctions;

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
	 *
	 * @throws \RuntimeException If the JSON file cannot be read.
	 * @throws \InvalidArgumentException If the JSON is invalid or missing 'steps' field.
	 */
	public function __construct( $json_path ) {
		$real_path = realpath( $json_path );

		if ( false === $real_path ) {
			throw new \InvalidArgumentException( 'Invalid schema path' );
		}

		$contents = $this->wp_filesystem_get_contents( $real_path );

		if ( false === $contents ) {
			throw new \RuntimeException( "Failed to read the JSON file at {$real_path}." );
		}

		$schema       = json_decode( $contents );
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

		if ( ! isset( $this->schema->steps ) || ! is_array( $this->schema->steps ) ) {
			return false;
		}

		return true;
	}
}
