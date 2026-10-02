<?php
/**
 * Ability fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Fields other plugins add to an object type's abilities, the ability
 * counterpart of register_rest_field(). An ability that declares
 * `meta.woocommerce.extension_fields` returns them under `extensions`.
 *
 * @since 11.3.0
 */
final class AbilityFields {

	/**
	 * Fields keyed by object type, then attribute.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static $fields = array();

	/**
	 * Register a field. A later registration of the same attribute replaces it.
	 *
	 * @param string               $object_type Object type.
	 * @param string               $attribute   Attribute under `extensions`.
	 * @param array<string, mixed> $args        `schema`, `get_callback( $object )`, `update_callback( $value, $object )` (in memory, never saves) and optional `validate_callback( $value, $object )`.
	 */
	public static function register( string $object_type, string $attribute, array $args ): void {
		self::$fields[ $object_type ][ $attribute ] = $args;
	}

	/**
	 * Fields of an object type, keyed by attribute.
	 *
	 * @param string $object_type Object type.
	 * @return array<string, array<string, mixed>>
	 */
	public static function get( string $object_type ): array {
		/**
		 * Filters the fields registered for an object type's abilities.
		 *
		 * @since 11.3.0
		 *
		 * @param array<string, array<string, mixed>> $fields      Fields keyed by attribute.
		 * @param string                              $object_type Object type.
		 */
		return (array) apply_filters( 'woocommerce_ability_fields', self::$fields[ $object_type ] ?? array(), $object_type );
	}

	/**
	 * JSON schema for `extensions`, or null when there are no fields.
	 *
	 * @param array<string, array<string, mixed>> $fields Fields keyed by attribute.
	 * @return array<string, mixed>|null
	 */
	public static function schema( array $fields ): ?array {
		if ( empty( $fields ) ) {
			return null;
		}

		return array(
			'type'                 => 'object',
			'description'          => 'Fields added by extensions, grouped by extension.',
			'properties'           => array_map(
				static function ( array $field ): array {
					return $field['schema'];
				},
				$fields
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Validate every value, then update the object in memory. Nothing is
	 * updated when any value is refused.
	 *
	 * @param array<string, array<string, mixed>> $fields  Fields keyed by attribute.
	 * @param object                              $subject Object to change.
	 * @param mixed                               $values  `extensions` input.
	 * @return \WP_Error|null
	 */
	public static function update( array $fields, $subject, $values ): ?\WP_Error {
		if ( ! is_array( $values ) ) {
			return new \WP_Error( 'woocommerce_ability_field_invalid', __( 'extensions must be an object.', 'woocommerce' ) );
		}

		foreach ( $values as $attribute => $value ) {
			$field = $fields[ $attribute ] ?? null;
			if ( null === $field ) {
				/* translators: %s: extension field attribute. */
				return new \WP_Error( 'woocommerce_ability_field_invalid', sprintf( __( 'Unknown extension "%s".', 'woocommerce' ), $attribute ) );
			}
			$valid = isset( $field['validate_callback'] )
				? call_user_func( $field['validate_callback'], $value, $subject )
				: rest_validate_value_from_schema( $value, $field['schema'], "extensions.$attribute" );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		foreach ( $values as $attribute => $value ) {
			call_user_func( $fields[ $attribute ]['update_callback'], $value, $subject );
		}
		return null;
	}

	/**
	 * Values the fields read from the object, keyed by attribute. A field that
	 * reads null is left out.
	 *
	 * @param array<string, array<string, mixed>> $fields  Fields keyed by attribute.
	 * @param object                              $subject Object to read.
	 * @return array<string, mixed>
	 */
	public static function read( array $fields, $subject ): array {
		$values = array();
		foreach ( $fields as $attribute => $field ) {
			$value = isset( $field['get_callback'] ) ? call_user_func( $field['get_callback'], $subject ) : null;
			if ( null !== $value ) {
				$values[ $attribute ] = $value;
			}
		}
		return $values;
	}

	/**
	 * Add `extensions` to the object, or each object of a list, at the output
	 * key. Each object is loaded by the `id` its output carries.
	 *
	 * @param mixed                $output      Ability output.
	 * @param array<string, mixed> $declaration `object_type` and `output` key.
	 * @return mixed
	 */
	public static function fill_output( $output, array $declaration ) {
		$key         = $declaration['output'] ?? null;
		$object_type = (string) ( $declaration['object_type'] ?? '' );
		if ( ! is_string( $key ) || ! is_array( $output ) || ! isset( $output[ $key ] ) || ! is_array( $output[ $key ] ) ) {
			return $output;
		}

		$fields = self::get( $object_type );
		if ( empty( $fields ) ) {
			return $output;
		}

		$fill = static function ( $item ) use ( $fields, $object_type ) {
			if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
				return $item;
			}
			/**
			 * Filters the object of a type an ability output names by ID, so its extension fields can be read.
			 *
			 * @since 11.3.0
			 *
			 * @param object|null $subject     Object, or null when none is found.
			 * @param string      $object_type Object type.
			 * @param mixed       $id          Object ID.
			 */
			$subject = apply_filters( 'woocommerce_ability_object', null, $object_type, $item['id'] );
			$values  = is_object( $subject ) ? self::read( $fields, $subject ) : array();
			if ( ! empty( $values ) ) {
				$item['extensions'] = $values;
			}
			return $item;
		};

		$output[ $key ] = wp_is_numeric_array( $output[ $key ] ) ? array_map( $fill, $output[ $key ] ) : $fill( $output[ $key ] );
		return $output;
	}

	/**
	 * Add the `extensions` schema to the object, or the items of a list, at the output key.
	 *
	 * @param array<string, mixed> $schema     Output schema.
	 * @param string               $key        Output key.
	 * @param array<string, mixed> $extensions `extensions` schema.
	 * @return array<string, mixed>
	 */
	public static function add_to_output_schema( array $schema, string $key, array $extensions ): array {
		if ( ! isset( $schema['properties'][ $key ] ) ) {
			return $schema;
		}

		if ( 'array' === ( $schema['properties'][ $key ]['type'] ?? null ) ) {
			$schema['properties'][ $key ]['items']['properties']['extensions'] = $extensions;
		} else {
			$schema['properties'][ $key ]['properties']['extensions'] = $extensions;
		}
		return $schema;
	}
}
