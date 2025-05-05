<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Validator;

use JsonSerializable;
use stdClass;
use WP_Error;

/**
 * Validates and sanitizes values based on a schema.
 */
class Validator {
	/**
	 * Strict validation & sanitization implementation.
	 * It only coerces int to float (e.g. 5 to 5.0).
	 *
	 * @param Schema $schema The schema to validate against.
	 * @param mixed  $value The value to validate.
	 * @param string $param_name The parameter name.
	 * @return mixed
	 */
	public function validate( Schema $schema, $value, string $param_name = 'value' ) {
		return $this->validate_schema_array( $schema->to_array(), $value, $param_name );
	}

	/**
	 * Strict validation & sanitization implementation.
	 * It only coerces int to float (e.g. 5 to 5.0).
	 *
	 * @param array  $schema The array must follow the format, which is returned from Schema::toArray().
	 * @param mixed  $value The value to validate.
	 * @param string $param_name The parameter name.
	 * @return mixed
	 * @throws Validation_Exception If the value does not match the schema.
	 */
	public function validate_schema_array( array $schema, $value, string $param_name = 'value' ) {
		$result = $this->validate_and_sanitize_value_from_schema( $value, $schema, $param_name );
		if ( $result instanceof WP_Error ) {
			throw Validation_Exception::create_from_wp_error( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		return $result;
	}

	/**
	 * Mirrors rest_validate_value_from_schema() and rest_sanitize_value_from_schema().
	 *
	 * @param mixed  $value The value to validate.
	 * @param array  $schema The schema to validate against.
	 * @param string $param_name The parameter name.
	 * @return mixed|WP_Error
	 */
	private function validate_and_sanitize_value_from_schema( $value, array $schema, string $param_name ) {
		// nullable.
		$full_type = $schema['type'] ?? null;
		if ( is_array( $full_type ) && in_array( 'null', $full_type, true ) && null === $value ) {
			return null;
		}

		// anyOf, oneOf.
		if ( isset( $schema['anyOf'] ) ) {
			return $this->validate_and_sanitize_any_of( $value, $schema, $param_name );
		} elseif ( isset( $schema['oneOf'] ) ) {
			return $this->validate_and_sanitize_one_of( $value, $schema, $param_name );
		}

		// make types strict.
		$type = is_array( $full_type ) ? $full_type[0] : $full_type;
		switch ( $type ) {
			case 'number':
				if ( ! is_float( $value ) && ! is_int( $value ) ) {
					return $this->get_type_error( $param_name, $full_type );
				}
				break;
			case 'integer':
				if ( ! is_int( $value ) ) {
					return $this->get_type_error( $param_name, $full_type );
				}
				break;
			case 'boolean':
				if ( ! is_bool( $value ) ) {
					return $this->get_type_error( $param_name, $full_type );
				}
				break;
			case 'array':
				if ( ! is_array( $value ) ) {
					return $this->get_type_error( $param_name, $full_type );
				}

				if ( isset( $schema['items'] ) ) {
					foreach ( $value as $i => $v ) {
						$result = $this->validate_and_sanitize_value_from_schema( $v, $schema['items'], $param_name . '[' . $i . ']' );
						if ( is_wp_error( $result ) ) {
								return $result;
						}
					}
				}
				break;
			case 'object':
				if ( ! is_array( $value ) && ! $value instanceof stdClass && ! $value instanceof JsonSerializable ) {
					return $this->get_type_error( $param_name, $full_type );
				}

				// ensure string keys.
				$value = (array) ( $value instanceof JsonSerializable ? $value->jsonSerialize() : $value );
				if ( count( array_filter( array_keys( $value ), 'is_string' ) ) !== count( $value ) ) {
					return $this->get_type_error( $param_name, $full_type );
				}

				// validate object properties.
				foreach ( $value as $k => $v ) {
					if ( isset( $schema['properties'][ $k ] ) ) {
						$result = $this->validate_and_sanitize_value_from_schema( $v, $schema['properties'][ $k ], $param_name . '[' . $k . ']' );
						if ( is_wp_error( $result ) ) {
								return $result;
						}
						continue;
					}

					$pattern_property_schema = rest_find_matching_pattern_property_schema( $k, $schema );
					if ( $pattern_property_schema ) {
						$result = $this->validate_and_sanitize_value_from_schema( $v, $pattern_property_schema, $param_name . '[' . $k . ']' );
						if ( is_wp_error( $result ) ) {
							return $result;
						}
						continue;
					}

					if ( isset( $schema['additionalProperties'] ) && is_array( $schema['additionalProperties'] ) ) {
						$result = $this->validate_and_sanitize_value_from_schema( $v, $schema['additionalProperties'], $param_name . '[' . $k . ']' );
						if ( is_wp_error( $result ) ) {
							return $result;
						}
					}
				}
				break;
		}

		$result = rest_validate_value_from_schema( $value, $schema, $param_name );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return rest_sanitize_value_from_schema( $value, $schema, $param_name );
	}

	/**
	 * Mirrors rest_find_any_matching_schema().
	 *
	 * @param mixed  $value The value to validate.
	 * @param array  $any_of_schema The schema to validate against.
	 * @param string $param_name The parameter name.
	 * @return mixed|WP_Error
	 */
	private function validate_and_sanitize_any_of( $value, array $any_of_schema, string $param_name ) {
		$errors = array();
		foreach ( $any_of_schema['anyOf'] as $index => $schema ) {
			$result = $this->validate_and_sanitize_value_from_schema( $value, $schema, $param_name );
			if ( ! is_wp_error( $result ) ) {
				return $result;
			}
			$errors[] = array(
				'error_object' => $result,
				'schema'       => $schema,
				'index'        => $index,
			);
		}
		/* @phpstan-ignore-next-line Wrong annotation for parameter in WP. */
		return rest_get_combining_operation_error( $value, $param_name, $errors );
	}

	/**
	 * Mirrors rest_find_one_matching_schema().
	 *
	 * @param mixed  $value The value to validate.
	 * @param array  $one_of_schema The schema to validate against.
	 * @param string $param_name The parameter name.
	 * @return mixed|WP_Error
	 */
	private function validate_and_sanitize_one_of( $value, array $one_of_schema, string $param_name ) {
		$matching_schemas = array();
		$errors           = array();
		$data             = null;
		foreach ( $one_of_schema['oneOf'] as $index => $schema ) {
			$result = $this->validate_and_sanitize_value_from_schema( $value, $schema, $param_name );
			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'error_object' => $result,
					'schema'       => $schema,
					'index'        => $index,
				);
			} else {
				$data                       = $result;
				$matching_schemas[ $index ] = $schema;
			}
		}

		if ( ! $matching_schemas ) {
			/* @phpstan-ignore-next-line Wrong annotation for parameter in WP. */
			return rest_get_combining_operation_error( $value, $param_name, $errors );
		}

		if ( count( $matching_schemas ) > 1 ) {
			// reuse WP method to generate detailed error.
			$invalid_schema = array( 'type' => array() );
			$one_of         = array_replace( array_fill( 0, count( $one_of_schema['oneOf'] ), $invalid_schema ), $matching_schemas );
			return rest_find_one_matching_schema( $value, array( 'oneOf' => $one_of ), $param_name );
		}
		return $data;
	}

	/**
	 * Returns a WP_Error for a type mismatch.
	 *
	 * @param string          $param The parameter name.
	 * @param string|string[] $type The expected type.
	 */
	private function get_type_error( string $param, $type ): WP_Error {
		$type = is_array( $type ) ? $type : array( $type );
		return new WP_Error(
			'rest_invalid_type',
			// translators: %1$s is the current parameter and %2$s a comma-separated list of the allowed types.
			sprintf( __( '%1$s is not of type %2$s.', 'woocommerce' ), $param, implode( ',', $type ) ),
			array( 'param' => $param )
		);
	}
}
