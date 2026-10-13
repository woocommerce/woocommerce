<?php
/**
 * Ability extensions class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityContracts;

defined( 'ABSPATH' ) || exit;

/**
 * The entry point for extensions in WooCommerce's abilities.
 *
 * An extension registers a resource inside the `woocommerce_ability_resources_init`
 * action. It registers a field on a resource, such as `product`, inside the
 * `woocommerce_ability_extensions_init` action, which fires after. An ability that formats an
 * object of that resource returns the field values under
 * `extensions.<namespace>.<field>`, and lists the fields in its output schema.
 *
 * The API is experimental while the `ability_contracts` feature exists.
 *
 * @since 11.3.0
 */
class AbilityExtensions {

	/**
	 * Fields keyed by resource, then namespace, then field name.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $fields = array();

	/**
	 * Registered resources, keyed by resource name.
	 *
	 * @var array<string, bool>
	 */
	private static array $resources = array();

	/**
	 * Fields already reported for a value that does not match the schema, keyed by resource, namespace and field.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Register a resource that abilities format, so extensions can register fields on it.
	 *
	 * Call it inside the `woocommerce_ability_resources_init` action. A call after that action is refused.
	 * Core registers `product`, `order` and `order_item` there. An
	 * extension registers a resource that its own abilities format, and then calls `add_fields_to_object()`
	 * and `add_fields_schema()` from those abilities, exactly as Core's abilities do.
	 * Registering the same resource again does nothing.
	 *
	 * @since 11.3.0
	 *
	 * @param string $resource_name Slug of the resource, such as `ticket`.
	 */
	public static function register_resource( string $resource_name ): void {
		if ( did_action( 'woocommerce_ability_resources_init' ) && ! doing_action( 'woocommerce_ability_resources_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a resource inside the woocommerce_ability_resources_init action, before the fields are registered.', '11.3.0' );
			return;
		}
		if ( '' === $resource_name || sanitize_key( $resource_name ) !== $resource_name ) {
			wc_doing_it_wrong( __METHOD__, 'The resource name must be a slug of lowercase letters, numbers, dashes and underscores.', '11.3.0' );
			return;
		}

		self::$resources[ $resource_name ] = true;
	}

	/**
	 * Register a field that the abilities of a resource return under `extensions.<namespace>.<field>`.
	 *
	 * Call it inside the `woocommerce_ability_extensions_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()` in the earlier `woocommerce_ability_resources_init` action.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Field arguments.
	 *
	 *     @type string   $resource     Resource that an ability formats, for example `product`, `order` or `order_item`.
	 *     @type string   $namespace    Slug of the extension that owns the field, such as `subscriptions`.
	 *     @type string   $field        Field name inside the namespace, such as `trial_length`.
	 *     @type array    $schema       JSON schema of the value. It needs a `type`. Its `title` is the label clients show.
	 *                                  A field with `required` must return a value that matches its schema
	 *                                  for every object, or the ability output fails validation.
	 *     @type callable $get_callback Receives the object and returns the value. A value that the schema
	 *                                  does not allow is left out, so return null when the object has no
	 *                                  value. A schema that allows null, such as `array( 'integer', 'null' )`,
	 *                                  keeps it. It reads the object it is given, not the database,
	 *                                  because an ability can format an object before it is saved.
	 * }
	 */
	public static function register_field( array $args ): void {
		if ( did_action( 'woocommerce_ability_extensions_init' ) && ! doing_action( 'woocommerce_ability_extensions_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a field inside the woocommerce_ability_extensions_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'field' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] ) {
				wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores.', $key ), '11.3.0' );
				return;
			}
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" resource is not registered. Call AbilityExtensions::register_resource() first.', $args['resource'] ), '11.3.0' );
			return;
		}
		if ( ! is_array( $args['schema'] ?? null ) || ! isset( $args['schema']['type'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "schema" argument must be an array with a "type".', '11.3.0' );
			return;
		}
		if ( ! is_callable( $args['get_callback'] ?? null ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "get_callback" argument must be callable.', '11.3.0' );
			return;
		}
		if ( isset( self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The field "%s.%s" of "%s" is already registered.', $args['namespace'], $args['field'], $args['resource'] ), '11.3.0' );
			return;
		}

		self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] = $args;
	}

	/**
	 * Add the field values of an object to its formatted output, under
	 * `extensions.<namespace>.<field>`. A value is cast to its schema type, so
	 * `'12'` becomes `12` for an integer field. A field that throws or returns a
	 * value that its schema does not allow is left out. A namespace with no
	 * values is left out, and `extensions` is left out when no namespace has
	 * values or the feature is off.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $output   Formatted object.
	 * @param string               $resource_name Resource.
	 * @param object               $subject  Object to read.
	 * @return array<string, mixed>
	 */
	public static function add_fields_to_object( array $output, string $resource_name, $subject ): array {
		if ( ! AbilityContracts::is_enabled() ) {
			return $output;
		}

		$extensions = array();
		foreach ( self::$fields[ $resource_name ] ?? array() as $namespace => $fields ) {
			foreach ( $fields as $field => $args ) {
				try {
					$value = call_user_func( $args['get_callback'], $subject );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability field "%s.%s" of "%s" failed: %s', $namespace, $field, $resource_name, $e->getMessage() ),
						array( 'source' => 'ability-extensions' )
					);
					continue;
				}
				if ( ! self::matches_schema( $args, $value ) ) {
					continue;
				}
				$sanitized = rest_sanitize_value_from_schema( $value, $args['schema'], $field );
				if ( ! is_wp_error( $sanitized ) ) {
					$extensions[ $namespace ][ $field ] = $sanitized;
				}
			}
		}
		if ( ! empty( $extensions ) ) {
			$output['extensions'] = $extensions;
		}
		return $output;
	}

	/**
	 * Add the `extensions` property to an object schema when the feature is on.
	 * It lists each namespace with its fields. Call it after the
	 * `woocommerce_ability_extensions_init` action, when the ability builds its
	 * schema. The schema stays unchanged when no field is registered for the resource.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema   Object schema.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function add_fields_schema( array $schema, string $resource_name ): array {
		if ( ! AbilityContracts::is_enabled() || empty( self::$fields[ $resource_name ] ) ) {
			return $schema;
		}

		$extensions = array(
			'type'        => 'object',
			'description' => __( 'Values that extensions add, keyed by extension namespace, then field.', 'woocommerce' ),
		);
		foreach ( self::$fields[ $resource_name ] ?? array() as $namespace => $fields ) {
			$extensions['properties'][ $namespace ] = array(
				'type'       => 'object',
				'properties' => array_map(
					static function ( array $args ): array {
						return $args['schema'];
					},
					$fields
				),
			);
		}
		$schema['properties']['extensions'] = $extensions;
		return $schema;
	}

	/**
	 * Whether a value matches its field schema. A value other than null that
	 * does not match is logged and reported one time for each field.
	 *
	 * @param array<string, mixed> $args  Field arguments.
	 * @param mixed                $value Value that the get_callback returned.
	 * @return bool
	 */
	private static function matches_schema( array $args, $value ): bool {
		$valid = rest_validate_value_from_schema( $value, $args['schema'], $args['field'] );
		if ( true === $valid ) {
			return true;
		}

		$key = $args['resource'] . '.' . $args['namespace'] . '.' . $args['field'];
		if ( null === $value || isset( self::$reported[ $key ] ) ) {
			return false;
		}

		self::$reported[ $key ] = true;
		$message                = sprintf( 'Ability field "%s.%s" of "%s" was left out because its value does not match its schema: %s', $args['namespace'], $args['field'], $args['resource'], $valid->get_error_message() );
		wc_get_logger()->error( $message, array( 'source' => 'ability-extensions' ) );
		wc_doing_it_wrong( __CLASS__ . '::register_field', $message, '11.3.0' );
		return false;
	}
}
