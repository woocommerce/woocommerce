<?php
/**
 * Ability fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\AbilitiesApi;

defined( 'ABSPATH' ) || exit;

/**
 * Fields that extensions add to the abilities of an object type, as
 * register_rest_field() does for the REST API. An ability that formats an
 * object of that type adds the field values under `extensions`, keyed by
 * attribute, and lists the fields in its output schema.
 *
 * @internal Core's abilities call this class. The public contract is
 *           wc_register_ability_field(); the read side may change.
 *
 * @since 11.3.0
 */
class AbilityFields {

	/**
	 * Fields keyed by object type, then attribute.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $fields = array();

	/**
	 * Register a field. A later registration of the same attribute replaces it.
	 *
	 * @param string               $object_type Object type.
	 * @param string               $attribute   Attribute under `extensions`.
	 * @param array<string, mixed> $args        `schema` and `get_callback( $object )`.
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
		return self::$fields[ $object_type ] ?? array();
	}

	/**
	 * Add the field values of an object to its formatted output, under
	 * `extensions`. A field that reads null is left out, and the key is left out
	 * when no field has a value or the feature is off.
	 *
	 * @internal
	 *
	 * @param array<string, mixed> $output      Formatted object.
	 * @param string               $object_type Object type.
	 * @param object               $subject     Object to read.
	 * @return array<string, mixed>
	 */
	public static function add_to_output( array $output, string $object_type, $subject ): array {
		if ( ! AbilityContracts::is_enabled() ) {
			return $output;
		}

		$values = array();
		foreach ( self::get( $object_type ) as $attribute => $field ) {
			$value = isset( $field['get_callback'] ) ? call_user_func( $field['get_callback'], $subject ) : null;
			if ( null !== $value ) {
				$values[ $attribute ] = $value;
			}
		}
		if ( ! empty( $values ) ) {
			$output['extensions'] = $values;
		}
		return $output;
	}

	/**
	 * Add the `extensions` property to an object schema when the feature is on.
	 * It lists the fields registered so far and allows others, because a field
	 * can be registered after the ability.
	 *
	 * @internal
	 *
	 * @param array<string, mixed> $schema      Object schema.
	 * @param string               $object_type Object type.
	 * @return array<string, mixed>
	 */
	public static function add_to_schema( array $schema, string $object_type ): array {
		if ( ! AbilityContracts::is_enabled() ) {
			return $schema;
		}

		$extensions = array(
			'type'        => 'object',
			'description' => __( 'Values that extensions add, keyed by attribute.', 'woocommerce' ),
		);
		$fields     = self::get( $object_type );
		if ( ! empty( $fields ) ) {
			$extensions['properties'] = array_map(
				static function ( array $field ): array {
					return $field['schema'];
				},
				$fields
			);
		}
		$schema['properties']['extensions'] = $extensions;
		return $schema;
	}
}
