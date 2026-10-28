<?php
/**
 * Ability resources class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Abilities\RegistrationChecks;
use Automattic\WooCommerce\Internal\Abilities\ResourceFields;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of the resources that abilities return, such as `order`, and of their root fields.
 * Abilities build their output and output schema from it. PersonalData leaves out the fields
 * the store withholds, and ExtensibleAbilities adds the fields of extensions under `extensions`.
 *
 * Extensions register resources in `woocommerce_extensible_abilities_resources_init`, which fires
 * only while the experimental `extensible_abilities` feature is on.
 *
 * @since 11.4.0
 */
class AbilityResources {

	/**
	 * Whether each registered resource still accepts root fields. Only the code that registers a resource adds them, right after.
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
	 * Fields at the root of a resource, keyed by resource, then field.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $fields = array();

	/**
	 * `$ref` targets that were missing, so each is reported once.
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
	 * Register a field at the root of a resource. Only the code that registers the resource adds root fields,
	 * right after registering it. Extensions add their fields with ExtensibleAbilities::register_field().
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
	 *                                   out of the output and the output schema while the store withholds personal data.
	 * }
	 */
	public static function register_field( string $resource_name, string $field, array $args ): void {
		$slugs = array(
			'resource_name' => $resource_name,
			'field'         => $field,
		);
		if ( ! RegistrationChecks::has_slugs( __METHOD__, $slugs ) ) {
			return;
		}
		if ( isset( $args['namespace'] ) || isset( $args['update_callback'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a field with a namespace or an update_callback with ExtensibleAbilities::register_field().', '11.4.0' );
			return;
		}
		if ( ! self::is_registered( $resource_name ) ) {
			RegistrationChecks::report_unregistered( __METHOD__, $resource_name );
			return;
		}
		if ( ! self::$resources[ $resource_name ] ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf( 'Only the code that registers the "%s" resource adds fields at its root, right after registering it. Extensions add fields with ExtensibleAbilities::register_field().', $resource_name ),
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
		if ( isset( self::$fields[ $resource_name ][ $field ] ) ) {
			RegistrationChecks::report_duplicate( __METHOD__, $field, $resource_name );
			return;
		}

		self::$fields[ $resource_name ][ $field ] = array(
			'resource'      => $resource_name,
			'field'         => $field,
			'schema'        => $args['schema'],
			'get_callback'  => $args['get_callback'],
			'personal_data' => $args['personal_data'] ?? false,
		);
		ResourceFields::forget();
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
		$fields        = ResourceFields::for_output( $resource_name, self::$fields[ $resource_name ] ?? array() );
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
}
