<?php
/**
 * Helper used to validate schema differences.
 */

namespace Automattic\WooCommerce\Blocks\Tests\Helpers;

/**
 * Validate schema.
 */
class ValidateSchema {
	/**
	 * The schema.
	 *
	 * @var array
	 */
	protected $schema = [];

	/**
	 * Constructor passed schema object.
	 *
	 * @param array $schema API schema representation.
	 */
	public function __construct( $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Validate properties and return diff.
	 *
	 * @param array|object $object Object to compare.
	 * @param array        $schema Schema to find nested properties under.
	 * @param string       $prefix Prefix to append to diff property names.
	 * @return array
	 */
	public function get_diff_from_object( $object, $schema = null, $prefix = '' ) {
		$missing      = [];
		$invalid_type = [];
		$no_schema    = [];

		if ( is_null( $schema ) ) {
			$schema = $this->schema['properties'];
		}

		if ( ! is_array( $object ) && ! is_object( $object ) ) {
			return [];
		}

		$object          = (array) $object;
		$no_schema_diffs = array_diff( array_keys( (array) $object ), array_keys( $schema ) );
		foreach ( $no_schema_diffs as $no_schema_diff ) {
			$no_schema[] = $prefix . $no_schema_diff;
		}

		foreach ( $schema as $property_name => $property_schema ) {
			// Validate property is set in object. Avoids isset in case value is NULL.
			if ( ( is_array( $object ) && ! array_key_exists( $property_name, $object ) ) || ( is_object( $object ) && ! property_exists( $object, $property_name ) ) ) {
				$missing[] = $prefix . $property_name;
				continue;
			}

			// Validate type.
			$type = gettype( $object[ $property_name ] );
			if ( $type && ! $this->validate_type( $type, $property_schema['type'] ) ) {
				$expected       = is_array( $property_schema['type'] ) ? implode( ', ', $property_schema['type'] ) : $property_schema['type'];
				$invalid_type[] = $prefix . $property_name . " ({$type}, expected {$expected})";
				continue;
			}

			// Validate nested props.
			if ( isset( $property_schema['items']['properties'] ) ) {
				$nested_value = is_array( $object[ $property_name ] ) ? current( $object[ $property_name ] ) : $object[ $property_name ];

				if ( $nested_value ) {
					$diff         = $this->get_diff_from_object(
						$nested_value,
						$property_schema['items']['properties'],
						$prefix . $property_name . ':'
					);
					$missing      = isset( $diff['missing'] ) ? array_merge( $missing, $diff['missing'] ) : $missing;
					$invalid_type = isset( $diff['invalid_type'] ) ? array_merge( $invalid_type, $diff['invalid_type'] ) : $invalid_type;
					$no_schema    = isset( $diff['no_schema'] ) ? array_merge( $no_schema, $diff['no_schema'] ) : $no_schema;
				}
			}
		}

		return array_filter(
			[
				'missing'      => array_values( array_filter( $missing ) ),
				'invalid_type' => array_values( array_filter( $invalid_type ) ),
				'no_schema'    => array_values( array_filter( $no_schema ) ),
			]
		);
	}

	/**
	 * Validate type and return if it matches.
	 *
	 * @param string       $type Type to check.
	 * @param string|array $expected Expected types.
	 * @return boolean
	 */
	protected function validate_type( $type, $expected ) {
		$type     = strtolower( $type );
		$expected = is_array( $expected ) ? $expected : [ $expected ];

		if ( in_array( 'number', $expected ) ) {
			$expected = array_merge( $expected, [ 'double', 'float', 'integer' ] );
		}

		return in_array( $type, $expected, true );
	}
}
