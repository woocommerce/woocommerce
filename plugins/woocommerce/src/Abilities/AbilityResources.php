<?php
/**
 * Ability resources class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Abilities\RegistrationChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the resources that abilities return, such as `order`, and of their fields.
 * Abilities build their output and output schema from it. PersonalData leaves out the fields
 * the store withholds. Extension fields appear under `extensions`.
 *
 * Extensions register resources in `woocommerce_extensible_abilities_resources_init`, which fires
 * only while the experimental `extensible_abilities` feature is on.
 *
 * @since 11.4.0
 */
class AbilityResources {

	/**
	 * Whether each resource accepts root fields. Core closes its root fields before extension registration.
	 *
	 * @var array<string, bool>
	 */
	private static array $resources = array();

	/**
	 * True while Core registers its resources, the only time a resource is accepted outside the resources action.
	 *
	 * @var bool
	 */
	private static bool $registering_core = false;

	/**
	 * Fields keyed by resource, namespace, then field. The empty namespace holds root fields.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $fields = array();

	/**
	 * Output fields keyed by resource and feature state. Personal data is decided once per request, so a list
	 * of objects reuses the fields of the first one.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $output_fields = array();

	/**
	 * Missing `$ref` targets and invalid extension values, reported once each.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Register a resource, such as `ticket`, so fields can be added to it.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_resources_init` action. Other calls are refused,
	 * so no code can claim a Core resource before Core. Registering a resource again does nothing.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource slug.
	 */
	public static function register_resource( string $resource_name ): void {
		if ( ! self::$registering_core && ! doing_action( 'woocommerce_extensible_abilities_resources_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a resource inside the woocommerce_extensible_abilities_resources_init action, before the fields are registered.', '11.4.0' );
			return;
		}
		if ( ! RegistrationChecks::has_slugs( __METHOD__, array( 'resource_name' => $resource_name ) ) ) {
			return;
		}
		if ( self::is_registered( $resource_name ) ) {
			return;
		}

		self::$resources[ $resource_name ] = true;
	}

	/**
	 * Register a root or namespaced field. Root fields close after the resource registration phase.
	 * ExtensibleAbilities delegates extension fields here with a non-empty namespace.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource, such as `product`, `order` or `order_item`.
	 * @param string $field         Field name, such as `total`.
	 * @param array  $args {
	 *     Field arguments.
	 *
	 *     @type array    $schema        JSON schema of the value, with a `type`. Its `title` is the label clients show.
	 *                                   It can nest another resource with `$ref`: at the top, under `items` or under
	 *                                   `properties`. Add `fields` or `exclude` next to it to nest only a part. Other
	 *                                   keys win over the nested schema, so `'type' => array( 'object', 'null' )` allows
	 *                                   null. No `required`.
	 *     @type callable $get_callback  Receives the object and returns the value. Read the given object, not the
	 *                                   database: an ability can format an object before it is saved.
	 *     @type bool     $personal_data Optional. Whether the value is personal data, such as an email address. It is left
	 *                                   out of the output and the output schema while the store withholds personal data. Defaults to false.
	 *     @type callable $update_callback Optional. Namespaced fields only. Changes the object in memory.
	 * }
	 * @param string $extension_namespace     Optional. Extension namespace, or empty for a root field.
	 */
	public static function register_field( string $resource_name, string $field, array $args, string $extension_namespace = '' ): void {
		$slugs = array(
			'resource_name' => $resource_name,
			'field'         => $field,
		);
		if ( '' !== $extension_namespace ) {
			$slugs['namespace'] = $extension_namespace;
		}
		if ( ! RegistrationChecks::has_slugs( __METHOD__, $slugs ) ) {
			return;
		}
		if ( isset( $args['namespace'] ) || ( '' === $extension_namespace && isset( $args['update_callback'] ) ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a field with a namespace or an update_callback with ExtensibleAbilities::register_field().', '11.4.0' );
			return;
		}
		if ( ! self::is_registered( $resource_name ) ) {
			RegistrationChecks::report_unregistered( __METHOD__, $resource_name );
			return;
		}
		if ( '' === $extension_namespace && ! self::$resources[ $resource_name ] ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf( 'Root fields of the "%s" resource are closed. Extensions add namespaced fields with ExtensibleAbilities::register_field().', $resource_name ),
				'11.4.0'
			);
			return;
		}
		if ( '' !== $extension_namespace ) {
			if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
				wc_doing_it_wrong( __METHOD__, 'Register extension fields before the abilities build their schemas.', '11.4.0' );
				return;
			}
			if ( is_array( $args['schema'] ?? null ) && RegistrationChecks::contains_ref( $args['schema'] ) ) {
				wc_doing_it_wrong( __METHOD__, 'A "$ref" is only allowed in a root field.', '11.4.0' );
				return;
			}
			if ( isset( $args['update_callback'] ) && ! is_callable( $args['update_callback'] ) ) {
				wc_doing_it_wrong( __METHOD__, 'The "update_callback" argument must be callable.', '11.4.0' );
				return;
			}
		}
		if ( ! RegistrationChecks::has_field_args( __METHOD__, $args, ! ( is_array( $args['schema'] ?? null ) && self::has_ref( $args['schema'] ) ) ) ) {
			return;
		}
		if ( self::has_unresolved_ref( $args['schema'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'A "$ref" is only allowed at the top of a field schema, under "items" or under "properties".', '11.4.0' );
			return;
		}
		if ( '' === $extension_namespace && 'extensions' === $field ) {
			wc_doing_it_wrong( __METHOD__, 'The field name "extensions" is reserved for the fields that extensions add.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $resource_name ][ $extension_namespace ][ $field ] ) ) {
			RegistrationChecks::report_duplicate( __METHOD__, '' === $extension_namespace ? $field : $extension_namespace . '.' . $field, $resource_name );
			return;
		}

		self::$fields[ $resource_name ][ $extension_namespace ][ $field ] = array(
			'resource'      => $resource_name,
			'field'         => $field,
			'schema'        => $args['schema'],
			'get_callback'  => $args['get_callback'],
			'personal_data' => $args['personal_data'] ?? false,
		);
		if ( '' !== $extension_namespace ) {
			self::$fields[ $resource_name ][ $extension_namespace ][ $field ]['namespace'] = $extension_namespace;
			if ( isset( $args['update_callback'] ) ) {
				self::$fields[ $resource_name ][ $extension_namespace ][ $field ]['update_callback'] = $args['update_callback'];
			}
		}
		self::forget_output_fields();
	}

	/**
	 * Whether a resource is registered.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name Resource.
	 * @return bool
	 */
	public static function is_registered( string $resource_name ): bool {
		return isset( self::$resources[ $resource_name ] );
	}

	/**
	 * Run Core's resource registrations, then close their root fields.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 *
	 * @param callable ...$registrations Callbacks that each register a Core resource through the public methods.
	 */
	public static function register_core( callable ...$registrations ): void {
		self::$registering_core = true;
		try {
			foreach ( $registrations as $register ) {
				$register();
			}
		} finally {
			self::$registering_core = false;
		}
		self::close_root_fields();
	}

	/**
	 * Stop all registered resources from accepting more root fields.
	 *
	 * @internal
	 *
	 * @since 11.4.0
	 */
	public static function close_root_fields(): void {
		self::$resources = array_fill_keys( array_keys( self::$resources ), false );
	}

	/**
	 * JSON schema of a resource, with extension fields under `extensions`.
	 *
	 * @since 11.4.0
	 *
	 * @param string        $resource_name Resource.
	 * @param string[]|null $fields        Optional. Only these fields. Extension fields come along only when `extensions` is listed.
	 * @param string[]      $exclude       Optional. Leave these fields out, or `extensions` for the extension fields.
	 * @return array<string, mixed>
	 */
	public static function output_schema( string $resource_name, ?array $fields = null, array $exclude = array() ): array {
		return self::build_schema( self::get_format_args( $resource_name, $fields, $exclude ), array() );
	}

	/**
	 * Replace each `$ref` in a schema with the schema of that resource. A `$ref` is resolved at the top,
	 * under `items` and under `properties`.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, mixed> $schema JSON schema with `$ref`s, such as the output shape of an ability.
	 * @return array<string, mixed>
	 */
	public static function resolve_schema( array $schema ): array {
		return self::resolve_ref( $schema, array() );
	}

	/**
	 * Format each object at a `$ref` of the schema as that resource. Other values are returned unchanged.
	 *
	 * @since 11.4.0
	 *
	 * @param array<string, mixed> $schema JSON schema with `$ref`s.
	 * @param mixed                $value  Value, such as `array( 'order' => $order, 'total' => 3 )` for an output shape.
	 * @return mixed
	 */
	public static function resolve_output( array $schema, $value ) {
		return self::format_value( $schema, $value, array() );
	}

	/**
	 * Output of an object, read from the fields of the resource.
	 *
	 * @since 11.4.0
	 *
	 * @param string        $resource_name Resource.
	 * @param mixed         $subject       Object to read, such as a WC_Order.
	 * @param string[]|null $fields        Optional. Only these fields. Extension fields come along only when `extensions` is listed.
	 * @param string[]      $exclude       Optional. Leave these fields out, or `extensions` for the extension fields.
	 * @return array<string, mixed>
	 */
	public static function output_data( string $resource_name, $subject, ?array $fields = null, array $exclude = array() ): array {
		return self::format_object( self::get_format_args( $resource_name, $fields, $exclude ), $subject, array() );
	}

	/**
	 * Same as resolve_output(), but mirrors resolve_ref(): a reference back to a resource being formatted, or to
	 * an unregistered one, is an empty object, so a cycle in the data stops. An empty resource is `{}`, not `[]`.
	 *
	 * @param array<string, mixed> $schema    JSON schema with `$ref`s.
	 * @param mixed                $value     Value.
	 * @param string[]             $resolving Resources already being formatted.
	 * @return mixed
	 */
	private static function format_value( array $schema, $value, array $resolving ) {
		if ( isset( $schema['$ref'] ) ) {
			if ( null === $value ) {
				return null;
			}
			$ref_args = self::get_ref_args( $schema );
			if ( in_array( $ref_args['resource'], $resolving, true ) || ! self::is_registered( $ref_args['resource'] ) ) {
				return new \stdClass();
			}
			$output = self::format_object( $ref_args, $value, $resolving );
			return array() === $output ? new \stdClass() : $output;
		}
		if ( ! is_array( $value ) || ! self::has_ref( $schema ) ) {
			return $value;
		}
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			return array_map( static fn( $item ) => self::format_value( $schema['items'], $item, $resolving ), array_values( $value ) );
		}
		foreach ( $value as $key => $property ) {
			if ( isset( $schema['properties'][ $key ] ) && is_array( $schema['properties'][ $key ] ) ) {
				$value[ $key ] = self::format_value( $schema['properties'][ $key ], $property, $resolving );
			}
		}
		return $value;
	}

	/**
	 * Same as output_data(), with the resources already being formatted.
	 *
	 * @param array    $args      Arguments of output_data().
	 * @param mixed    $subject   Object to read.
	 * @param string[] $resolving Resources already being formatted.
	 * @return array<string, mixed>
	 */
	private static function format_object( array $args, $subject, array $resolving ): array {
		$resource_name = (string) $args['resource'];
		$resolving[]   = $resource_name;
		$output        = array();
		foreach ( self::get_output_fields( $args ) as $field => $field_args ) {
			$value = self::format_value( $field_args['schema'], call_user_func( $field_args['get_callback'], $subject ), $resolving );
			if ( array() === $value && ! empty( $field_args['omit_if_empty'] ) ) {
				continue;
			}
			$output[ $field ] = $value;
		}
		return $output;
	}

	/**
	 * Whether a schema has a `$ref` at any depth, so its value needs formatting.
	 *
	 * @param array<string, mixed> $schema JSON schema.
	 * @return bool
	 */
	private static function has_ref( array $schema ): bool {
		if ( isset( $schema['$ref'] ) ) {
			return true;
		}
		$children = array_merge(
			isset( $schema['items'] ) && is_array( $schema['items'] ) ? array( $schema['items'] ) : array(),
			is_array( $schema['properties'] ?? null ) ? array_filter( $schema['properties'], 'is_array' ) : array()
		);
		foreach ( $children as $child ) {
			if ( self::has_ref( $child ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether a schema has a `$ref` that resolve_schema() would not replace, such as one under `oneOf`.
	 *
	 * @param array<string, mixed> $schema JSON schema.
	 * @return bool
	 */
	private static function has_unresolved_ref( array $schema ): bool {
		foreach ( $schema as $key => $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}
			if ( 'items' === $key ) {
				$unresolved = self::has_unresolved_ref( $child );
			} elseif ( 'properties' === $key ) {
				$unresolved = in_array( true, array_map( static fn( $property ) => is_array( $property ) && self::has_unresolved_ref( $property ), $child ), true );
			} else {
				$unresolved = RegistrationChecks::contains_ref( $child );
			}
			if ( $unresolved ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Arguments for the resource that a `$ref` field schema nests.
	 *
	 * @param array<string, mixed> $schema Field schema with the `$ref`, and optional `fields` or `exclude`.
	 * @return array{resource: string, fields: string[]|null, exclude: string[]}
	 */
	private static function get_ref_args( array $schema ): array {
		return self::get_format_args(
			(string) $schema['$ref'],
			isset( $schema['fields'] ) ? (array) $schema['fields'] : null,
			(array) ( $schema['exclude'] ?? array() )
		);
	}

	/**
	 * Arguments that pick the part of a resource to format.
	 *
	 * @param string        $resource_name Resource.
	 * @param string[]|null $fields        Only these fields, or null for all.
	 * @param string[]      $exclude       Fields to leave out.
	 * @return array{resource: string, fields: string[]|null, exclude: string[]}
	 */
	private static function get_format_args( string $resource_name, ?array $fields, array $exclude ): array {
		return array(
			'resource' => $resource_name,
			'fields'   => $fields,
			'exclude'  => $exclude,
		);
	}

	/**
	 * Build the schema of a resource, and of the resources it refers to.
	 *
	 * @param array    $args      Arguments of output_schema().
	 * @param string[] $resolving Resources already being built, to stop at a reference back to one of them.
	 * @return array<string, mixed>
	 */
	private static function build_schema( array $args, array $resolving ): array {
		$resolving[] = (string) $args['resource'];
		$schema      = array(
			'type'                 => 'object',
			'properties'           => array(),
			'additionalProperties' => false,
		);
		foreach ( self::get_output_fields( $args ) as $field => $field_args ) {
			$schema['properties'][ $field ] = self::resolve_ref( $field_args['schema'], $resolving );
		}
		return $schema;
	}

	/**
	 * Same as resolve_schema(), but a reference back to a resource being built becomes a plain object.
	 *
	 * @param array<string, mixed> $schema    Field schema.
	 * @param string[]             $resolving Resources already being built.
	 * @return array<string, mixed>
	 */
	private static function resolve_ref( array $schema, array $resolving ): array {
		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$schema['items'] = self::resolve_ref( $schema['items'], $resolving );
		}
		if ( is_array( $schema['properties'] ?? null ) ) {
			foreach ( $schema['properties'] as $key => $property ) {
				if ( is_array( $property ) ) {
					$schema['properties'][ $key ] = self::resolve_ref( $property, $resolving );
				}
			}
		}
		if ( ! isset( $schema['$ref'] ) ) {
			return $schema;
		}

		$ref_args = self::get_ref_args( $schema );
		$schema   = array_diff_key( $schema, array_flip( array( '$ref', 'fields', 'exclude' ) ) );
		if ( ! self::is_registered( $ref_args['resource'] ) ) {
			self::report_unregistered_ref( $ref_args['resource'] );
			return array_merge( array( 'type' => 'object' ), $schema );
		}
		if ( in_array( $ref_args['resource'], $resolving, true ) ) {
			return array_merge( array( 'type' => 'object' ), $schema );
		}
		return array_merge( self::build_schema( $ref_args, $resolving ), $schema );
	}

	/**
	 * Fields to output: the root fields without withheld personal data, then `extensions`, then `fields` and `exclude`.
	 *
	 * @param array $args Arguments of output_schema() or output_data().
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_output_fields( array $args ): array {
		$resource_name = (string) $args['resource'];
		$fields        = self::fields_for_output( $resource_name );
		if ( isset( $args['fields'] ) ) {
			$fields = array_intersect_key( $fields, array_flip( (array) $args['fields'] ) );
		}
		return array_diff_key( $fields, array_flip( (array) ( $args['exclude'] ?? array() ) ) );
	}

	/**
	 * Report a `$ref` to a resource that is not registered, once per resource. Its schema falls back to a plain object.
	 *
	 * @param string $resource_name Resource.
	 */
	private static function report_unregistered_ref( string $resource_name ): void {
		if ( isset( self::$reported[ '$ref.' . $resource_name ] ) ) {
			return;
		}
		self::$reported[ '$ref.' . $resource_name ] = true;
		wc_doing_it_wrong(
			__CLASS__ . '::resolve_schema',
			sprintf( 'A "$ref" points to the "%s" resource, which is not registered.', $resource_name ),
			'11.4.0'
		);
	}

	/**
	 * Extension fields, including writable fields withheld from output.
	 *
	 * @internal
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function get_extension_fields( string $resource_name ): array {
		$fields = self::$fields[ $resource_name ] ?? array();
		unset( $fields[''] );
		return $fields;
	}

	/**
	 * Fields to output for a resource. Cached until a field is registered.
	 *
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, mixed>>
	 */
	private static function fields_for_output( string $resource_name ): array {
		$enabled = ExtensibleAbilities::is_enabled();
		$key     = $resource_name . ( $enabled ? '|extensions' : '' );
		if ( ! isset( self::$output_fields[ $key ] ) ) {
			$fields           = self::withhold( self::$fields[ $resource_name ][''] ?? array(), $resource_name );
			$extension_fields = $enabled ? self::get_output_extension_fields( $resource_name ) : array();
			if ( ! empty( $extension_fields ) ) {
				$fields['extensions'] = self::get_extensions_field( $resource_name, $extension_fields );
			}
			self::$output_fields[ $key ] = $fields;
		}
		return self::$output_fields[ $key ];
	}

	/**
	 * Drop the cached output fields after a field is registered.
	 */
	private static function forget_output_fields(): void {
		self::$output_fields = array();
	}

	/**
	 * The fields without those flagged as personal data, while the resource withholds them.
	 *
	 * @param array<string, array<string, mixed>> $fields        Field arguments keyed by field.
	 * @param string                              $resource_name Resource.
	 * @return array<string, array<string, mixed>>
	 */
	private static function withhold( array $fields, string $resource_name ): array {
		$personal = array_filter( $fields, static fn( array $field_args ) => ! empty( $field_args['personal_data'] ) );
		if ( empty( $personal ) || PersonalData::is_shared( $resource_name ) ) {
			return $fields;
		}
		return array_diff_key( $fields, $personal );
	}

	/**
	 * Extension fields to output, after withholding personal data. Empty namespaces are dropped.
	 *
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	private static function get_output_extension_fields( string $resource_name ): array {
		$extension_fields = array_map(
			static fn( array $fields ) => self::withhold( $fields, $resource_name ),
			self::get_extension_fields( $resource_name )
		);
		return array_filter( $extension_fields );
	}

	/**
	 * The `extensions` field, whose value holds the extension fields keyed by namespace, then field.
	 * It is left out of an output where no extension field has a value.
	 *
	 * @param string $resource_name    Resource.
	 * @param array  $extension_fields Output fields keyed by namespace.
	 * @return array<string, mixed>
	 */
	private static function get_extensions_field( string $resource_name, array $extension_fields ): array {
		$schema = array(
			'type'                 => 'object',
			'description'          => __( 'Values that extensions add, keyed by extension namespace, then field.', 'woocommerce' ),
			'additionalProperties' => false,
		);
		foreach ( $extension_fields as $extension_namespace => $fields ) {
			$schema['properties'][ $extension_namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map( static fn( array $field_args ) => $field_args['schema'], $fields ),
				'additionalProperties' => false,
			);
		}
		return array(
			'resource'      => $resource_name,
			'field'         => 'extensions',
			'schema'        => $schema,
			'get_callback'  => static fn( $subject ) => self::get_values( $resource_name, $extension_fields, $subject ),
			'omit_if_empty' => true,
		);
	}

	/**
	 * Extension field values of an object, keyed by namespace, then field. Values are cast to their schema.
	 * A field that throws or fails its schema is left out.
	 *
	 * @param string $resource_name    Resource.
	 * @param array  $extension_fields Output fields keyed by namespace.
	 * @param mixed  $subject          Object to read.
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_values( string $resource_name, array $extension_fields, $subject ): array {
		$values = array();
		foreach ( $extension_fields as $extension_namespace => $fields ) {
			foreach ( $fields as $field => $args ) {
				try {
					$value = call_user_func( $args['get_callback'], $subject );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability field "%s.%s" of "%s" failed: %s', $extension_namespace, $field, $resource_name, $e->getMessage() ),
						array( 'source' => 'extensible-abilities' )
					);
					continue;
				}
				if ( ! self::matches_schema( $args, $value ) ) {
					continue;
				}
				$sanitized = rest_sanitize_value_from_schema( $value, $args['schema'], $field );
				if ( ! is_wp_error( $sanitized ) ) {
					$values[ $extension_namespace ][ $field ] = $sanitized;
				}
			}
		}
		return $values;
	}

	/**
	 * Whether a value matches its field schema. A mismatch other than null is logged and reported once per field.
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

		$key = 'field.' . $args['resource'] . '.' . $args['namespace'] . '.' . $args['field'];
		if ( null === $value || isset( self::$reported[ $key ] ) ) {
			return false;
		}

		self::$reported[ $key ] = true;
		$message                = sprintf(
			'Ability field "%1$s.%2$s" of "%3$s" was left out because its value does not match its schema: %4$s',
			$args['namespace'],
			$args['field'],
			$args['resource'],
			$valid->get_error_message()
		);
		wc_get_logger()->error( $message, array( 'source' => 'extensible-abilities' ) );
		wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', $message, '11.4.0' );
		return false;
	}
}
