<?php
/**
 * Ability fields class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;

defined( 'ABSPATH' ) || exit;

/**
 * Fields that extensions add to the abilities of an object type, as
 * register_rest_field() does for the REST API. An extension registers a field
 * with register(). An ability that formats an object of that type adds the
 * field values under `extensions`, keyed by attribute, and lists the fields in
 * its output schema.
 *
 * The public contract is register(). The read side that Core's abilities call
 * is internal and may change.
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
	 * Fields already reported for a value that does not match the schema, keyed by object type and attribute.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Register a field that the abilities of an object type return under `extensions`.
	 *
	 * Several extensions can add fields to the same object type, so prefix the
	 * attribute with the extension's name, such as `subscription_trial_length`.
	 * A later registration of the same attribute replaces it.
	 *
	 * @since 11.3.0
	 *
	 * @param string $object_type Object type that an ability formats, for example `product`, `order` or `order_item`.
	 * @param string $attribute   Attribute under `extensions`.
	 * @param array  $args        {
	 *     Field arguments.
	 *
	 *     @type array    $schema       JSON schema of the value. Its `title` is the label clients show.
	 *     @type callable $get_callback Receives the object and returns the value. A value that the schema
	 *                                  does not allow is left out, so return null when the object has no
	 *                                  value. A schema that allows null, such as `array( 'integer', 'null' )`,
	 *                                  keeps it. It reads the object it is given, not the database,
	 *                                  because an ability can format an object before it is saved.
	 * }
	 */
	public static function register( string $object_type, string $attribute, array $args ): void {
		if ( ! is_array( $args['schema'] ?? null ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "schema" argument must be an array.', '11.3.0' );
			return;
		}
		if ( ! is_callable( $args['get_callback'] ?? null ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "get_callback" argument must be callable.', '11.3.0' );
			return;
		}

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
	 * `extensions`. A value is cast to its schema type, so `'12'` becomes `12`
	 * for an integer field. A field that throws or returns a value that its schema does not allow is left out, and the key is left out
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
			try {
				$value = call_user_func( $field['get_callback'], $subject );
			} catch ( \Throwable $e ) {
				wc_get_logger()->error(
					sprintf( 'Ability field "%s" of "%s" failed: %s', $attribute, $object_type, $e->getMessage() ),
					array( 'source' => 'ability-fields' )
				);
				continue;
			}
			if ( self::matches_schema( $object_type, $attribute, $value ) ) {
				$values[ $attribute ] = rest_sanitize_value_from_schema( $value, $field['schema'], $attribute );
			}
		}
		if ( ! empty( $values ) ) {
			$output['extensions'] = $values;
		}
		return $output;
	}

	/**
	 * Whether a value matches its field schema. A value other than null that
	 * does not match is logged and reported one time for each field.
	 *
	 * @param string $object_type Object type.
	 * @param string $attribute   Attribute.
	 * @param mixed  $value       Value that the get_callback returned.
	 * @return bool
	 */
	private static function matches_schema( string $object_type, string $attribute, $value ): bool {
		$valid = rest_validate_value_from_schema( $value, self::$fields[ $object_type ][ $attribute ]['schema'], $attribute );
		if ( true === $valid ) {
			return true;
		}
		if ( null === $value || isset( self::$reported[ $object_type . '.' . $attribute ] ) ) {
			return false;
		}

		self::$reported[ $object_type . '.' . $attribute ] = true;
		$message = sprintf( 'Ability field "%s" of "%s" was left out because its value does not match its schema: %s', $attribute, $object_type, $valid->get_error_message() );
		wc_get_logger()->error( $message, array( 'source' => 'ability-fields' ) );
		wc_doing_it_wrong( __CLASS__ . '::register', $message, '11.3.0' );
		return false;
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
