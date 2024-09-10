<?php

namespace Automattic\WooCommerce\Blueprint\Steps;

/**
 * Abstract class Step
 *
 * This class defines the structure for a Step that requires arguments to perform an action.
 * You can think it as a function described in JSON format.
 *
 * A Step should also be capable of returning formatted data that can be imported later.
 * Additionally, a Step can validate data.
 */
abstract class Step {
	/**
	 * Meta values for the step.
	 *
	 * @var array $meta_values
	 */
	protected array $meta_values = array();

	/**
	 * Get the step name.
	 *
	 * @return string
	 */
	abstract public static function get_step_name(): string;

	/**
	 * Get the schema for this step.
	 *
	 * @param int $version The schema version.
	 *
	 * @return array
	 */
	abstract public static function get_schema( int $version = 1 ): array;

	/**
	 * Prepare the JSON array for this step.
	 *
	 * @return array The JSON array for the step.
	 */
	abstract public function prepare_json_array(): array;

	/**
	 * Set meta values for the step.
	 *
	 * @param array $meta_values The meta values.
	 *
	 * @return void
	 */
	public function set_meta_values( array $meta_values ) {
		$this->meta_values = $meta_values;
	}

	/**
	 * Get the JSON array for the step.
	 *
	 * @return mixed
	 */
	public function get_json_array() {
		$json_array = $this->prepare_json_array();
		if ( ! empty( $this->meta_values ) ) {
			$json_array['meta'] = $this->meta_values;
		}
		return $json_array;
	}
}
