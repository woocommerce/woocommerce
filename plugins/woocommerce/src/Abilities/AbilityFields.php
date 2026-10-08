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
 * its output schema. An ActionableAbility writes the `extensions` input to the
 * fields and runs the validators that register_validator() adds.
 *
 * The public contract is register() and register_validator(). The
 * methods that Core's abilities call are internal and may change.
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
	 * Validators keyed by object type.
	 *
	 * @var array<string, array<int, callable>>
	 */
	private static array $validators = array();

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
	 *     @type callable $update_callback Optional. Receives the value and the object, and changes the object
	 *                                     in memory. A null value deletes the value of the field, as in an
	 *                                     undo of a field that had no value. It returns a WP_Error to reject
	 *                                     the value, and then nothing is saved. It must not save, send email
	 *                                     or make HTTP requests. A field without it cannot be written.
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
		if ( isset( $args['update_callback'] ) && ! is_callable( $args['update_callback'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "update_callback" argument must be callable.', '11.3.0' );
			return;
		}

		self::$fields[ $object_type ][ $attribute ] = $args;
	}

	/**
	 * Register a validator that runs on every change of an object type, after
	 * the ability and the fields change the object and before it is saved.
	 *
	 * @since 11.3.0
	 *
	 * @param string   $object_type Object type that an ActionableAbility changes, for example `product` or `order`.
	 * @param callable $callback    Receives the changed object, which is not saved yet. It returns a WP_Error
	 *                              to reject the change, and then nothing is saved. The error message can
	 *                              name the ability to use instead. It must not change or save the object.
	 */
	public static function register_validator( string $object_type, callable $callback ): void {
		self::$validators[ $object_type ][] = $callback;
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
	 * `extensions`. The key is left out when no field has a value or the
	 * feature is off.
	 *
	 * @internal
	 *
	 * @param array<string, mixed> $output      Formatted object.
	 * @param string               $object_type Object type.
	 * @param object               $subject     Object to read.
	 * @return array<string, mixed>
	 */
	public static function add_to_output( array $output, string $object_type, $subject ): array {
		$values = self::get_values( $object_type, $subject );
		if ( ! empty( $values ) ) {
			$output['extensions'] = $values;
		}
		return $output;
	}

	/**
	 * The field values of an object, keyed by attribute. A field that throws or
	 * returns a value that its schema does not allow is left out. Nothing is
	 * returned when the feature is off.
	 *
	 * @internal
	 *
	 * @param string $object_type Object type.
	 * @param object $subject     Object to read.
	 * @return array<string, mixed>
	 */
	public static function get_values( string $object_type, $subject ): array {
		if ( ! AbilityContracts::is_enabled() ) {
			return array();
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
				$values[ $attribute ] = $value;
			}
		}
		return $values;
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
	 * Write extension values to an object in memory with the update_callback
	 * of each field. Nothing happens when the feature is off.
	 *
	 * @internal
	 *
	 * @param string               $object_type Object type.
	 * @param object               $subject     Object to change.
	 * @param array<string, mixed> $values      Values keyed by attribute.
	 * @return \WP_Error|null A WP_Error when a value is rejected.
	 */
	public static function update( string $object_type, $subject, array $values ): ?\WP_Error {
		if ( ! AbilityContracts::is_enabled() ) {
			return null;
		}

		$fields = self::get( $object_type );
		foreach ( $values as $attribute => $value ) {
			if ( ! isset( $fields[ $attribute ]['update_callback'] ) ) {
				return new \WP_Error(
					'woocommerce_ability_field_invalid',
					/* translators: %s: Attribute under extensions. */
					sprintf( __( 'The extension field "%s" cannot be written.', 'woocommerce' ), $attribute ),
					array( 'status' => 400 )
				);
			}

			$valid = null === $value ? true : rest_validate_value_from_schema( $value, $fields[ $attribute ]['schema'], 'extensions.' . $attribute );
			if ( is_wp_error( $valid ) ) {
				return self::with_status( $valid );
			}

			$updated = call_user_func( $fields[ $attribute ]['update_callback'], $value, $subject );
			if ( is_wp_error( $updated ) ) {
				return self::with_status( $updated );
			}
		}
		return null;
	}

	/**
	 * Run the validators of an object type on a changed object. Nothing
	 * happens when the feature is off.
	 *
	 * @internal
	 *
	 * @param string $object_type Object type.
	 * @param object $subject     Changed object.
	 * @return \WP_Error|null A WP_Error when a validator rejects the change.
	 */
	public static function validate( string $object_type, $subject ): ?\WP_Error {
		if ( ! AbilityContracts::is_enabled() ) {
			return null;
		}

		foreach ( self::$validators[ $object_type ] ?? array() as $validator ) {
			$valid = call_user_func( $validator, $subject );
			if ( is_wp_error( $valid ) ) {
				return self::with_status( $valid );
			}
		}
		return null;
	}

	/**
	 * Add the `extensions` property to an input schema when the feature is on.
	 * It lists the fields that can be written, in each `oneOf` branch when the
	 * schema has them. Each field also accepts null, which deletes its value.
	 *
	 * @internal
	 *
	 * @param array<string, mixed> $schema      Input schema.
	 * @param string               $object_type Object type.
	 * @return array<string, mixed>
	 */
	public static function add_to_input_schema( array $schema, string $object_type ): array {
		if ( ! AbilityContracts::is_enabled() ) {
			return $schema;
		}

		$extensions = array(
			'type'        => 'object',
			'description' => __( 'Values to write to the fields that extensions add, keyed by attribute.', 'woocommerce' ),
		);
		$fields     = array_filter(
			self::get( $object_type ),
			static function ( array $field ): bool {
				return isset( $field['update_callback'] );
			}
		);
		if ( ! empty( $fields ) ) {
			$extensions['properties'] = array_map(
				static function ( array $field ): array {
					$field_schema = $field['schema'];
					if ( ! isset( $field_schema['type'] ) ) {
						return array( 'anyOf' => array( $field_schema, array( 'type' => 'null' ) ) );
					}
					$field_schema['type'] = array_values( array_unique( array_merge( (array) $field_schema['type'], array( 'null' ) ) ) );
					if ( isset( $field_schema['enum'] ) && ! in_array( null, $field_schema['enum'], true ) ) {
						$field_schema['enum'][] = null;
					}
					return $field_schema;
				},
				$fields
			);
		}

		if ( isset( $schema['oneOf'] ) ) {
			foreach ( $schema['oneOf'] as $index => $branch ) {
				$schema['oneOf'][ $index ]['properties']['extensions'] = $extensions;
			}
		} else {
			$schema['properties']['extensions'] = $extensions;
		}
		return $schema;
	}

	/**
	 * Give an error the 400 status when it has none.
	 *
	 * @param \WP_Error $error Error.
	 * @return \WP_Error
	 */
	private static function with_status( \WP_Error $error ): \WP_Error {
		$data = $error->get_error_data();
		if ( ! isset( $data['status'] ) ) {
			$error->add_data( array_merge( is_array( $data ) ? $data : array(), array( 'status' => 400 ) ) );
		}
		return $error;
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
