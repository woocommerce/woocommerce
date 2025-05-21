<?php

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Admin\RemoteSpecs\RuleProcessors;

/**
 * Evaluates `overrides` property in the spec and returns the evaluated spec.
 */
class EvaluateOverrides {
	/**
	 * Evaluates the spec and returns a status.
	 *
	 * @param array $spec The spec to evaluate.
	 * @param array $context The context variables.
	 *
	 * @return array The evaluated spec.
	 */
	public function evaluate( array $spec, array $context = array() ) {
		$rule_evaluator = new RuleEvaluator( new GetRuleProcessorForContext( $context ) );

		foreach ( $spec as $spec_item ) {
			if ( isset( $spec_item->overrides ) && is_array( $spec_item->overrides ) ) {
				foreach ( $spec_item->overrides as $override ) {
					if ( ! isset( $override->rules ) || ! is_array( $override->rules ) || ! isset( $override->field ) || ! isset( $override->value ) ) {
						continue;
					}

					if ( $rule_evaluator->evaluate( $override->rules ) ) {
						// If value exisit and can be accessed directly, update it.
						if ( isset( $spec_item->{$override->field} ) ) {
							$spec_item->{$override->field} = $override->value;
						} else {
							// Otherwise, try updating it using dot notation.
							$this->set_value_with_dot_notation( $spec_item, $override->field, $override->value );
						}
					}
				}
			}
		}

		return $spec;
	}

	/**
	 * Set a new value to $data with dot notation.
	 *
	 * This is a slightly modified version of the simple dot notation to support objects.
	 *
	 * @param mixed  $data The data to update.
	 * @param string $path The path to the value to update.
	 * @param mixed  $new_value The new value.
	 *
	 * @return mixed|\stdClass
	 */
	public function set_value_with_dot_notation( &$data, $path, $new_value ) {
		$keys     = explode( '.', $path );
		$last_key = array_pop( $keys );

		foreach ( $keys as $key ) {
			if ( is_numeric( $key ) ) {
				$key = (int) $key;
				if ( ! isset( $data[ $key ] ) || ! is_object( $data[ $key ] ) ) {
					$data[ $key ] = new \stdClass();
				}
				$data = &$data[ $key ];
			} else {
				if ( ! isset( $data->$key ) || ( ! is_array( $data->$key ) && ! is_object( $data->$key ) ) ) {
					$data->$key = new \stdClass();
				}
				$data = &$data->$key;
			}
		}

		// Assign the new value.
		if ( is_numeric( $last_key ) ) {
			$data[ (int) $last_key ] = $new_value;
		} else {
			$data->$last_key = $new_value;
		}

		return $data;
	}
}
