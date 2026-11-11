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

	private const JSON_TYPES = array( 'array', 'object', 'string', 'number', 'integer', 'boolean', 'null' );

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
	 * Missing `$ref` targets, reported once each.
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
	 * Namespaced fields use output_schema/get_callback and input_schema/set_callback pairs.
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
	 *     @type bool $omit_if_null Optional. Omit unavailable root values. Defaults to false.
	 * }
	 * @param string $extension_namespace     Optional. Extension namespace, or empty for a root field.
	 */
	public static function register_field( string $resource_name, string $field, array $args, string $extension_namespace = '' ): void {
		if ( '' !== $extension_namespace ) {
			self::register_extension_field(
				array_merge(
					$args,
					array(
						'resource'  => $resource_name,
						'namespace' => $extension_namespace,
						'field'     => $field,
					)
				)
			);
			return;
		}

		$slugs = array(
			'resource_name' => $resource_name,
			'field'         => $field,
		);
		if ( ! RegistrationChecks::has_slugs( __METHOD__, $slugs ) ) {
			return;
		}
		if ( isset( $args['namespace'] ) || isset( $args['update_callback'] ) || isset( $args['set_callback'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register writable or namespaced fields with ExtensibleAbilities::register_field().', '11.4.0' );
			return;
		}
		if ( ! self::is_registered( $resource_name ) ) {
			RegistrationChecks::report_unregistered( __METHOD__, $resource_name );
			return;
		}
		if ( ! self::$resources[ $resource_name ] ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf( 'Root fields of the "%s" resource are closed. Extensions add namespaced fields with ExtensibleAbilities::register_field().', $resource_name ),
				'11.4.0'
			);
			return;
		}
		if ( ! RegistrationChecks::has_field_args( __METHOD__, $args, ! ( is_array( $args['schema'] ?? null ) && self::has_ref( $args['schema'] ) ) ) ) {
			return;
		}
		if ( self::has_unresolved_ref( $args['schema'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'A "$ref" is only allowed at the top of a field schema, under "items" or under "properties".', '11.4.0' );
			return;
		}
		if ( 'extensions' === $field ) {
			wc_doing_it_wrong( __METHOD__, 'The field name "extensions" is reserved for the fields that extensions add.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $resource_name ][ $extension_namespace ][ $field ] ) ) {
			RegistrationChecks::report_duplicate( __METHOD__, $field, $resource_name );
			return;
		}

		self::$fields[ $resource_name ][ $extension_namespace ][ $field ] = array(
			'resource'      => $resource_name,
			'field'         => $field,
			'schema'        => $args['schema'],
			'get_callback'  => $args['get_callback'],
			'personal_data' => $args['personal_data'] ?? false,
			'omit_if_null'  => $args['omit_if_null'] ?? false,
		);
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
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function output_data( string $resource_name, $subject, ?array $fields = null, array $exclude = array() ) {
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
		if ( is_wp_error( $value ) ) {
			return $value;
		}
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
		if ( ( ! is_array( $value ) && ! is_object( $value ) ) || ! self::has_ref( $schema ) ) {
			return $value;
		}
		if ( is_array( $value ) && isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			$output = array();
			foreach ( $value as $item ) {
				$formatted = self::format_value( $schema['items'], $item, $resolving );
				if ( is_wp_error( $formatted ) ) {
					return $formatted;
				}
				$output[] = $formatted;
			}
			return $output;
		}
		$is_object = is_object( $value );
		$value     = $is_object ? get_object_vars( $value ) : $value;
		foreach ( $value as $key => $property ) {
			if ( isset( $schema['properties'][ $key ] ) && is_array( $schema['properties'][ $key ] ) ) {
				$value[ $key ] = self::format_value( $schema['properties'][ $key ], $property, $resolving );
				if ( is_wp_error( $value[ $key ] ) ) {
					return $value[ $key ];
				}
			}
		}
		return $is_object ? (object) $value : $value;
	}

	/**
	 * Same as output_data(), with the resources already being formatted.
	 *
	 * @param array    $args      Arguments of output_data().
	 * @param mixed    $subject   Object to read.
	 * @param string[] $resolving Resources already being formatted.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function format_object( array $args, $subject, array $resolving ) {
		$resource_name = (string) $args['resource'];
		$resolving[]   = $resource_name;
		$output        = array();
		foreach ( self::get_output_fields( $args ) as $field => $field_args ) {
			try {
				$value = call_user_func( $field_args['get_callback'], $subject );
			} catch ( \Throwable $exception ) {
				$message = sprintf(
					/* translators: 1: Field name. 2: Resource, such as product. */
					__( 'Reading the "%1$s" field of the %2$s failed.', 'woocommerce' ),
					$field,
					$resource_name
				);
				wc_doing_it_wrong( __CLASS__ . '::register_field', $message . ' ' . $exception->getMessage(), '11.4.0' );
				return new \WP_Error(
					'woocommerce_ability_extension_failed',
					$message,
					array(
						'status'   => 500,
						'resource' => $resource_name,
						'field'    => $field,
					)
				);
			}
			$value = self::format_value( $field_args['schema'], $value, $resolving );
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			if ( null === $value && ! empty( $field_args['omit_if_null'] ) ) {
				continue;
			}
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
			$fields = self::withhold( self::$fields[ $resource_name ][''] ?? array(), $resource_name );
			$schema = ExtensibleAbilities::add_fields_schema( array(), $resource_name );
			if ( isset( $schema['properties']['extensions'] ) ) {
				$fields['extensions'] = array(
					'schema'        => $schema['properties']['extensions'],
					'get_callback'  => static function ( $subject ) use ( $resource_name ) {
						$output = ExtensibleAbilities::add_fields_to_object( array(), $resource_name, $subject );
						return is_wp_error( $output ) ? $output : ( $output['extensions'] ?? array() );
					},
					'omit_if_empty' => true,
				);
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
	 * @internal
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function get_output_extension_fields( string $resource_name ): array {
		$extension_fields = array_map(
			static fn( array $fields ) => self::withhold( $fields, $resource_name ),
			self::get_extension_fields( $resource_name )
		);
		return array_filter( $extension_fields );
	}

	/**
	 * Validate and store an extension field using the ExtensibleAbilities argument contract.
	 *
	 * @internal
	 * @since 11.4.0
	 * @param array<string, mixed> $args Field arguments, including optional personal_data (default false).
	 */
	public static function register_extension_field( array $args ): void {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'Register a field inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'field' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] || ctype_digit( $args[ $key ] ) ) {
				wc_doing_it_wrong(
					ExtensibleAbilities::class . '::register_field',
					sprintf(
						'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores, not only numbers.',
						$key
					),
					'11.3.0'
				);
				return;
			}
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) && ! did_action( 'woocommerce_extensible_abilities_resources_init' ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'Register a field inside the woocommerce_extensible_abilities_init action. The resources are not registered yet.', '11.3.0' );
			return;
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::register_field',
				sprintf(
					'The "%s" resource is not registered. Call ExtensibleAbilities::register_resource() first.',
					$args['resource']
				),
				'11.3.0'
			);
			return;
		}
		$readable = isset( $args['get_callback'] ) || isset( $args['output_schema'] );
		if ( is_array( $args['output_schema'] ?? null ) && RegistrationChecks::contains_ref( $args['output_schema'] ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'A resource reference is only allowed in a root field.', '11.4.0' );
			return;
		}
		$writable = isset( $args['set_callback'] ) || isset( $args['input_schema'] );
		if ( ! $readable && ! $writable ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'A field needs "get_callback" and "output_schema", "set_callback" and "input_schema", or both pairs.', '11.4.0' );
			return;
		}
		if ( $readable ) {
			if ( ! is_callable( $args['get_callback'] ?? null ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "get_callback" argument must be callable, and a field with an "output_schema" needs it.', '11.3.0' );
				return;
			}
			if ( ! is_array( $args['output_schema'] ?? null ) || ! isset( $args['output_schema']['type'] ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "output_schema" argument must be an array with a "type".', '11.4.0' );
				return;
			}
			if ( ! is_string( $args['output_schema']['type'] ) || 'null' === $args['output_schema']['type'] ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "type" of the "output_schema" argument must be one type name other than "null". Return null to leave the field out.', '11.4.0' );
				return;
			}
		}
		if ( $writable ) {
			if ( ! is_callable( $args['set_callback'] ?? null ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "set_callback" argument must be callable, and a field with an "input_schema" needs it.', '11.4.0' );
				return;
			}
			if ( ! is_array( $args['input_schema'] ?? null ) || ! self::input_schema_has_type( $args['input_schema'] ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "input_schema" argument must be an array with one "type" name, or with an "anyOf" where each branch has one "type" name. For a nullable field, use an "anyOf" with a {"type":"null"} branch instead of a list of types.', '11.4.0' );
				return;
			}
		}
		if ( isset( $args['object_types'] ) && ( ! is_array( $args['object_types'] ) || empty( $args['object_types'] ) || array_filter( $args['object_types'], static fn( $type ) => ! is_string( $type ) ) ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', 'The "object_types" argument must be a list of object types.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::register_field',
				sprintf(
					'The field "%1$s.%2$s" of "%3$s" is already registered.',
					$args['namespace'],
					$args['field'],
					$args['resource']
				),
				'11.3.0'
			);
			return;
		}

		self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] = $args + array( 'personal_data' => false );
		self::forget_output_fields();
	}

	/**
	 * Whether a field input schema has one top-level JSON type name, or a non-empty
	 * `anyOf` list where each branch has one JSON type name. The REST schema check
	 * needs a known type for every branch that a value can match.
	 *
	 * @param array<string, mixed> $schema Field input schema.
	 * @return bool
	 */
	private static function input_schema_has_type( array $schema ): bool {
		if ( isset( $schema['type'] ) ) {
			return in_array( $schema['type'], self::JSON_TYPES, true );
		}
		$branches = $schema['anyOf'] ?? null;
		if ( ! is_array( $branches ) || empty( $branches ) || array_values( $branches ) !== $branches ) {
			return false;
		}
		foreach ( $branches as $branch ) {
			if ( ! is_array( $branch ) || ! in_array( $branch['type'] ?? null, self::JSON_TYPES, true ) ) {
				return false;
			}
		}
		return true;
	}
}
