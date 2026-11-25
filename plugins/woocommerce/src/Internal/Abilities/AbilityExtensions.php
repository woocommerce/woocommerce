<?php
/**
 * AbilityExtensions class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Abilities;

use Automattic\WooCommerce\Abilities\ExtensibleAbilities;

defined( 'ABSPATH' ) || exit;

/**
 * The registrations behind ExtensibleAbilities, and the helpers that only Core's abilities use.
 * Extensions call ExtensibleAbilities, not this class.
 *
 * @internal
 *
 * @since 11.4.0
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
	 * Validators keyed by resource.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $validators = array();

	/**
	 * Syncs keyed by resource.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $syncs = array();

	/**
	 * Prime callbacks keyed by resource.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $primes = array();

	/**
	 * Query filters keyed by resource, then namespace, then filter name.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $filters = array();

	/**
	 * Query arguments that a filter callback can return.
	 */
	private const QUERY_ARG_KEYS = array( 'meta_query', 'tax_query', 'include_ids', 'exclude_ids' );

	/**
	 * Type names that the REST schema check knows. It accepts any value for another name.
	 */
	private const JSON_TYPES = array( 'array', 'boolean', 'integer', 'null', 'number', 'object', 'string' );

	/**
	 * Implements ExtensibleAbilities::register_resource().
	 *
	 * @param string $resource_name Slug of the resource, such as `ticket`.
	 */
	public static function register_resource( string $resource_name ): void {
		if ( did_action( 'woocommerce_extensible_abilities_resources_init' ) && ! doing_action( 'woocommerce_extensible_abilities_resources_init' ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'Register a resource inside the woocommerce_extensible_abilities_resources_init action, before the fields are registered.', '11.3.0' );
			return;
		}
		if ( '' === $resource_name || sanitize_key( $resource_name ) !== $resource_name || ctype_digit( $resource_name ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The resource name must be a slug of lowercase letters, numbers, dashes and underscores, not only numbers.', '11.3.0' );
			return;
		}

		self::$resources[ $resource_name ] = true;
	}

	/**
	 * Implements ExtensibleAbilities::register_field().
	 *
	 * @param array $args See ExtensibleAbilities::register_field().
	 */
	public static function register_field( array $args ): void {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'Register a field inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'field' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] || ctype_digit( $args[ $key ] ) ) {
				wc_doing_it_wrong(
					ExtensibleAbilities::class . '::' . __FUNCTION__,
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
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'Register a field inside the woocommerce_extensible_abilities_init action. The resources are not registered yet.', '11.3.0' );
			return;
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::' . __FUNCTION__,
				sprintf(
					'The "%s" resource is not registered. Call ExtensibleAbilities::register_resource() first.',
					$args['resource']
				),
				'11.3.0'
			);
			return;
		}
		$readable = isset( $args['get_callback'] ) || isset( $args['output_schema'] );
		$writable = isset( $args['set_callback'] ) || isset( $args['input_schema'] );
		if ( ! $readable && ! $writable ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'A field needs "get_callback" and "output_schema", "set_callback" and "input_schema", or both pairs.', '11.4.0' );
			return;
		}
		if ( $readable ) {
			if ( ! is_callable( $args['get_callback'] ?? null ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "get_callback" argument must be callable, and a field with an "output_schema" needs it.', '11.3.0' );
				return;
			}
			if ( ! is_array( $args['output_schema'] ?? null ) || ! isset( $args['output_schema']['type'] ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "output_schema" argument must be an array with a "type".', '11.4.0' );
				return;
			}
			if ( ! is_string( $args['output_schema']['type'] ) || 'null' === $args['output_schema']['type'] ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "type" of the "output_schema" argument must be one type name other than "null". Return null to leave the field out.', '11.4.0' );
				return;
			}
		}
		if ( $writable ) {
			if ( ! is_callable( $args['set_callback'] ?? null ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "set_callback" argument must be callable, and a field with an "input_schema" needs it.', '11.4.0' );
				return;
			}
			if ( ! is_array( $args['input_schema'] ?? null ) || ! self::input_schema_has_type( $args['input_schema'] ) ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "input_schema" argument must be an array with one "type" name, or with an "anyOf" where each branch has one "type" name. For a nullable field, use an "anyOf" with a {"type":"null"} branch instead of a list of types.', '11.4.0' );
				return;
			}
		}
		foreach ( array( 'output_schema', 'input_schema' ) as $key ) {
			$path = isset( $args[ $key ] ) ? self::find_nested_type_list( $args[ $key ] ) : null;
			if ( null !== $path ) {
				wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, sprintf( 'The "%1$s" argument has a list of types at "%2$s". Use an "anyOf" with a {"type":"null"} branch instead. In an output, prefer to leave the property out when it has no value.', $key, $path ), '11.4.0' );
				return;
			}
		}
		if ( isset( $args['object_types'] ) && ( ! is_array( $args['object_types'] ) || empty( $args['object_types'] ) || array_filter( $args['object_types'], static fn( $type ) => ! is_string( $type ) ) ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "object_types" argument must be a list of object types.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::' . __FUNCTION__,
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

		self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] = $args;
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

	/**
	 * Find the first nested schema under `properties`, `patternProperties`, `additionalProperties`, `items`,
	 * `anyOf`, `oneOf` or `allOf` whose `type` is a list.
	 *
	 * @param array<string, mixed> $schema Field schema.
	 * @param string               $path   Path of the schema, such as `properties.size`.
	 * @return string|null Path of the first schema with a list of types, or null when there is none.
	 */
	private static function find_nested_type_list( array $schema, string $path = '' ): ?string {
		foreach ( array( 'properties', 'patternProperties', 'additionalProperties', 'items', 'anyOf', 'oneOf', 'allOf' ) as $keyword ) {
			$children = $schema[ $keyword ] ?? null;
			if ( ! is_array( $children ) ) {
				continue;
			}
			if ( 'additionalProperties' === $keyword || ( 'items' === $keyword && ! wp_is_numeric_array( $children ) ) ) {
				$children = array( '' => $children );
			}
			foreach ( $children as $key => $child ) {
				if ( ! is_array( $child ) ) {
					continue;
				}
				$child_path = trim( "$path.$keyword.$key", '.' );
				if ( is_array( $child['type'] ?? null ) ) {
					return $child_path;
				}
				$found = self::find_nested_type_list( $child, $child_path );
				if ( null !== $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * Implements ExtensibleAbilities::register_query_filter().
	 *
	 * @param array $args See ExtensibleAbilities::register_query_filter().
	 */
	public static function register_query_filter( array $args ): void {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'Register a filter inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'filter' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] || ctype_digit( $args[ $key ] ) ) {
				wc_doing_it_wrong(
					ExtensibleAbilities::class . '::' . __FUNCTION__,
					sprintf(
						'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores, not only numbers.',
						$key
					),
					'11.3.0'
				);
				return;
			}
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::' . __FUNCTION__,
				sprintf(
					'The "%s" resource is not registered. Call ExtensibleAbilities::register_resource() first.',
					$args['resource']
				),
				'11.3.0'
			);
			return;
		}
		if ( ! is_array( $args['schema'] ?? null ) || ! isset( $args['schema']['type'] ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "schema" argument must be an array with a "type".', '11.3.0' );
			return;
		}
		if ( ! is_callable( $args['callback'] ?? null ) ) {
			wc_doing_it_wrong( ExtensibleAbilities::class . '::' . __FUNCTION__, 'The "callback" argument must be callable.', '11.3.0' );
			return;
		}
		if ( isset( self::$filters[ $args['resource'] ][ $args['namespace'] ][ $args['filter'] ] ) ) {
			wc_doing_it_wrong(
				ExtensibleAbilities::class . '::' . __FUNCTION__,
				sprintf(
					'The filter "%1$s.%2$s" of "%3$s" is already registered.',
					$args['namespace'],
					$args['filter'],
					$args['resource']
				),
				'11.3.0'
			);
			return;
		}

		self::$filters[ $args['resource'] ][ $args['namespace'] ][ $args['filter'] ] = $args;
	}

	/**
	 * Implements ExtensibleAbilities::add_query_filters_schema().
	 *
	 * @param array<string, mixed> $schema        Input schema.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function add_query_filters_schema( array $schema, string $resource_name ): array {
		if ( empty( self::$filters[ $resource_name ] ) || ! ExtensibleAbilities::is_enabled() ) {
			return $schema;
		}

		$extensions = array(
			'type'                 => 'object',
			'description'          => __( 'Filters that extensions add, keyed by extension namespace, then filter.', 'woocommerce' ),
			'properties'           => array(),
			'additionalProperties' => false,
		);
		foreach ( self::$filters[ $resource_name ] as $namespace => $filters ) {
			$extensions['properties'][ $namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map(
					static function ( array $args ): array {
						return $args['schema'];
					},
					$filters
				),
				'additionalProperties' => false,
			);
		}
		$schema['properties']['filters'] = array(
			'type'                 => 'object',
			'properties'           => array( 'extensions' => $extensions ),
			'additionalProperties' => false,
		);
		return $schema;
	}

	/**
	 * Implements ExtensibleAbilities::get_query_args().
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, array>|\WP_Error The error of a value that fails its schema, or of a filter that refused its value or failed.
	 */
	public static function get_query_args( string $resource_name, array $input ) {
		$query_args = array();
		$include    = null;
		$exclude    = array();
		if ( ! ExtensibleAbilities::is_enabled() ) {
			return $query_args;
		}

		foreach ( self::$filters[ $resource_name ] ?? array() as $namespace => $filters ) {
			foreach ( $filters as $filter => $args ) {
				if ( ! is_array( $input['filters']['extensions'][ $namespace ] ?? null ) || ! array_key_exists( $filter, $input['filters']['extensions'][ $namespace ] ) ) {
					continue;
				}

				$value = rest_sanitize_value_from_schema( $input['filters']['extensions'][ $namespace ][ $filter ], $args['schema'], $filter );
				if ( is_wp_error( $value ) ) {
					$value->add_data( array( 'status' => 400 ) );
					return $value;
				}
				try {
					$clauses = call_user_func( $args['callback'], $value );
				} catch ( \Throwable $e ) {
					self::log_error( sprintf( 'Ability filter "%s.%s" of "%s" failed: %s', $namespace, $filter, $resource_name, $e->getMessage() ) );
					$clauses = null;
				}
				if ( is_wp_error( $clauses ) ) {
					return $clauses;
				}
				if (
					! is_array( $clauses )
					|| ! empty( array_diff_key( $clauses, array_flip( self::QUERY_ARG_KEYS ) ) )
					|| ( 'product' !== $resource_name && isset( $clauses['tax_query'] ) )
					|| ( 'customer' === $resource_name && ! empty( array_diff_key( $clauses, array( 'include_ids' => true ) ) ) )
					|| in_array( false, array_map( 'is_array', $clauses ), true )
				) {
					return new \WP_Error(
						'woocommerce_ability_extension_filter_failed',
						/* translators: %s: filter name, such as subscriptions.subscribable. */
						sprintf( __( 'The "%s" filter failed.', 'woocommerce' ), $namespace . '.' . $filter ),
						array( 'status' => 500 )
					);
				}
				if ( isset( $clauses['include_ids'] ) ) {
					$filter_ids = self::get_filter_ids( $clauses['include_ids'], $namespace . '.' . $filter );
					if ( is_wp_error( $filter_ids ) ) {
						return $filter_ids;
					}
					$include = null === $include ? $filter_ids : array_values( array_intersect( $include, $filter_ids ) );
					unset( $clauses['include_ids'] );
				}
				if ( isset( $clauses['exclude_ids'] ) ) {
					$filter_ids = self::get_filter_ids( $clauses['exclude_ids'], $namespace . '.' . $filter );
					if ( is_wp_error( $filter_ids ) ) {
						return $filter_ids;
					}
					$exclude = array_merge( $exclude, $filter_ids );
					unset( $clauses['exclude_ids'] );
				}
				foreach ( $clauses as $key => $clause ) {
					if ( empty( $clause ) ) {
						continue;
					}
					$query_args[ $key ][] = $clause;
				}
			}
		}
		if ( null !== $include ) {
			$include                = array_values( array_diff( $include, $exclude ) );
			$query_args['post__in'] = empty( $include ) ? array( 0 ) : $include;
		} elseif ( ! empty( $exclude ) ) {
			$query_args['post__not_in'] = array_values( array_unique( $exclude ) );
		}
		return $query_args;
	}

	/**
	 * The IDs that a filter returned, as integers.
	 *
	 * @param array  $ids    IDs from the filter callback.
	 * @param string $filter Filter name, such as `subscriptions.related_to`.
	 * @return int[]|\WP_Error An error when an ID is not a positive integer.
	 */
	private static function get_filter_ids( array $ids, string $filter ) {
		$valid = array_filter(
			array_map(
				static fn( $id ) => filter_var( $id, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ),
				$ids
			)
		);
		if ( count( $valid ) !== count( $ids ) ) {
			return new \WP_Error(
				'woocommerce_ability_extension_filter_failed',
				/* translators: %s: filter name, such as subscriptions.subscribable. */
				sprintf( __( 'The "%s" filter failed.', 'woocommerce' ), $filter ),
				array( 'status' => 500 )
			);
		}
		return array_values( array_unique( $valid ) );
	}

	/**
	 * Implements ExtensibleAbilities::register_validator().
	 *
	 * @param array $args See ExtensibleAbilities::register_validator().
	 */
	public static function register_validator( array $args ): void {
		if ( ! self::check_callback_args( $args, ExtensibleAbilities::class . '::' . __FUNCTION__, 'validator', '11.3.0' ) ) {
			return;
		}

		self::$validators[ $args['resource'] ][] = $args;
	}

	/**
	 * Implements ExtensibleAbilities::register_sync().
	 *
	 * @param array $args See ExtensibleAbilities::register_sync().
	 */
	public static function register_sync( array $args ): void {
		if ( ! self::check_callback_args( $args, ExtensibleAbilities::class . '::' . __FUNCTION__, 'sync', '11.4.0' ) ) {
			return;
		}

		self::$syncs[ $args['resource'] ][] = $args;
	}

	/**
	 * Implements ExtensibleAbilities::register_prime().
	 *
	 * @param array $args See ExtensibleAbilities::register_prime().
	 */
	public static function register_prime( array $args ): void {
		if ( ! self::check_callback_args( $args, ExtensibleAbilities::class . '::' . __FUNCTION__, 'prime callback', '11.4.0' ) ) {
			return;
		}

		self::$primes[ $args['resource'] ][] = $args;
	}

	/**
	 * Implements ExtensibleAbilities::prime().
	 *
	 * @param string   $resource_name Resource.
	 * @param object[] $objects       Objects to format.
	 */
	public static function prime( string $resource_name, array $objects ): void {
		if ( empty( self::$primes[ $resource_name ] ) || empty( $objects ) || ! ExtensibleAbilities::is_enabled() ) {
			return;
		}

		$objects = array_values( $objects );
		foreach ( self::$primes[ $resource_name ] as $args ) {
			try {
				call_user_func( $args['callback'], $objects );
			} catch ( \Throwable $e ) {
				self::log_error( sprintf( 'Ability prime callback of "%s" for "%s" failed: %s', $args['namespace'], $resource_name, $e->getMessage() ) );
			}
		}
	}

	/**
	 * Log an error from an extension callback. A logger that throws does not
	 * stop the ability.
	 *
	 * @param string $message Message to log.
	 */
	private static function log_error( string $message ): void {
		try {
			wc_get_logger()->error( $message, array( 'source' => 'extensible-abilities' ) );
		} catch ( \Throwable $e ) {
			unset( $e );
		}
	}

	/**
	 * Check the arguments of a validator, a sync or a prime callback, and report
	 * the first problem with wc_doing_it_wrong().
	 *
	 * @param array  $args    Validator, sync or prime arguments.
	 * @param string $method  Method that registers the callback.
	 * @param string $kind    `validator`, `sync` or `prime callback`, for the message.
	 * @param string $version Version that added the method.
	 * @return bool Whether the arguments are valid.
	 */
	private static function check_callback_args( array $args, string $method, string $kind, string $version ): bool {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( $method, sprintf( 'Register a %s inside the woocommerce_extensible_abilities_init action.', $kind ), $version );
			return false;
		}
		foreach ( array( 'resource', 'namespace' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] ) {
				wc_doing_it_wrong( $method, sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores.', $key ), $version );
				return false;
			}
		}
		if ( ! is_callable( $args['callback'] ?? null ) ) {
			wc_doing_it_wrong( $method, 'The "callback" argument must be callable.', $version );
			return false;
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong( $method, sprintf( 'The "%s" resource is not registered. Call ExtensibleAbilities::register_resource() first.', $args['resource'] ), $version );
			return false;
		}
		return true;
	}

	/**
	 * Implements ExtensibleAbilities::add_fields_to_object().
	 *
	 * @param array<string, mixed> $output   Formatted object.
	 * @param string               $resource_name Resource.
	 * @param object               $subject  Object to read.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function add_fields_to_object( array $output, string $resource_name, $subject ) {
		if ( empty( self::$fields[ $resource_name ] ) || ! ExtensibleAbilities::is_enabled() ) {
			return $output;
		}

		$extensions = array();
		foreach ( self::$fields[ $resource_name ] as $namespace => $fields ) {
			foreach ( $fields as $field => $args ) {
				if ( ! isset( $args['get_callback'] ) || ! self::field_applies_to( $args, $subject ) ) {
					continue;
				}
				try {
					$value = call_user_func( $args['get_callback'], $subject );
				} catch ( \Throwable $e ) {
					return self::field_callback_failed_error( $args, $e );
				}
				if ( null === $value ) {
					continue;
				}
				$valid = rest_validate_value_from_schema( $value, $args['output_schema'], $field );
				if ( true === $valid ) {
					$valid = rest_sanitize_value_from_schema( $value, $args['output_schema'], $field );
				}
				if ( is_wp_error( $valid ) ) {
					return self::field_output_invalid_error( $args, $valid );
				}
				$extensions[ $namespace ][ $field ] = self::prepare_json_value( $valid, $args['output_schema'] );
			}
		}
		if ( ! empty( $extensions ) ) {
			$output['extensions'] = $extensions;
		}
		return $output;
	}

	/**
	 * Whether a field applies to an object, from the `object_types` of the field.
	 *
	 * @param array<string, mixed> $args    Field arguments from `register_field()`.
	 * @param mixed                $subject The object. It must have a `get_type()` method.
	 * @return bool
	 */
	public static function field_applies_to( array $args, $subject ): bool {
		return empty( $args['object_types'] ) || in_array( $subject->get_type(), $args['object_types'], true );
	}

	/**
	 * The type of an object, from its `get_type()` method.
	 *
	 * @param mixed $subject The object. It must have a `get_type()` method.
	 * @return string
	 */
	public static function get_object_type( $subject ): string {
		return $subject->get_type();
	}

	/**
	 * Implements ExtensibleAbilities::add_fields_schema().
	 *
	 * @param array<string, mixed> $schema        Object schema.
	 * @param string               $resource_name Resource.
	 * @param callable|null        $field_filter  Optional. Gets the arguments of a field and returns whether the schema lists it.
	 * @return array<string, mixed>
	 */
	public static function add_fields_schema( array $schema, string $resource_name, ?callable $field_filter = null ): array {
		if ( ! ExtensibleAbilities::is_enabled() ) {
			return $schema;
		}

		$extensions = array(
			'type'                 => 'object',
			'description'          => __( 'Values that extensions add, keyed by extension namespace, then field.', 'woocommerce' ),
			'additionalProperties' => false,
		);
		foreach ( self::$fields[ $resource_name ] ?? array() as $namespace => $fields ) {
			$listed = array_filter(
				$fields,
				static function ( array $args ): bool {
					return isset( $args['get_callback'] );
				}
			);
			if ( null !== $field_filter ) {
				$listed = array_filter( $listed, $field_filter );
			}
			if ( empty( $listed ) ) {
				continue;
			}
			$extensions['properties'][ $namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map(
					static function ( array $args ): array {
						return $args['output_schema'];
					},
					$listed
				),
				'additionalProperties' => false,
			);
		}
		if ( empty( $extensions['properties'] ) ) {
			return $schema;
		}
		$schema['properties']['extensions'] = $extensions;
		return $schema;
	}

	/**
	 * Implements ExtensibleAbilities::get_input_schema().
	 *
	 * @param string        $resource_name Resource.
	 * @param callable|null $field_filter  Optional. Gets the arguments of a field and returns whether the schema lists it.
	 * @return array<string, mixed>
	 */
	public static function get_input_schema( string $resource_name, ?callable $field_filter = null ): array {
		$extensions = array(
			'type'                 => 'object',
			'description'          => __( 'Values to write to the fields that extensions add, keyed by extension namespace, then field.', 'woocommerce' ),
			'additionalProperties' => false,
		);
		foreach ( self::get_fields( $resource_name ) as $namespace => $fields ) {
			$writable = array_filter(
				null === $field_filter ? $fields : array_filter( $fields, $field_filter ),
				static function ( array $args ): bool {
					return isset( $args['set_callback'] );
				}
			);
			if ( empty( $writable ) ) {
				continue;
			}
			$extensions['properties'][ $namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map(
					static function ( array $args ): array {
						$field_schema = $args['input_schema'];
						if ( ! empty( $args['object_types'] ) ) {
							/* translators: %s: Comma-separated list of object types. */
							$applies_to                  = sprintf( __( 'Applies to: %s.', 'woocommerce' ), implode( ', ', $args['object_types'] ) );
							$field_schema['description'] = trim( ( $field_schema['description'] ?? '' ) . ' ' . $applies_to );
						}
						return $field_schema;
					},
					$writable
				),
				'additionalProperties' => false,
			);
		}
		return $extensions;
	}

	/**
	 * Fields of a resource, keyed by namespace, then field.
	 *
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function get_fields( string $resource_name ): array {
		return self::$fields[ $resource_name ] ?? array();
	}

	/**
	 * Validators of a resource.
	 *
	 * @param string $resource_name Resource.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_validators( string $resource_name ): array {
		return self::$validators[ $resource_name ] ?? array();
	}

	/**
	 * Whether a resource is registered.
	 *
	 * @param string $resource_name Resource.
	 * @return bool
	 */
	public static function is_resource_registered( string $resource_name ): bool {
		return isset( self::$resources[ $resource_name ] );
	}

	/**
	 * Syncs of a resource.
	 *
	 * @param string $resource_name Resource.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_syncs( string $resource_name ): array {
		return self::$syncs[ $resource_name ] ?? array();
	}

	/**
	 * Mark an error from a write that saved before it failed. The message names
	 * the saved object, because MCP clients get the message and not the data.
	 * The data gets `saved` set to true and the `id` of the object.
	 *
	 * @param \WP_Error            $error         Error after the save.
	 * @param string               $resource_name Resource, such as product.
	 * @param int                  $id            ID of the saved object.
	 * @param array<string, mixed> $data          More data to add.
	 * @return \WP_Error
	 */
	public static function saved_write_error( \WP_Error $error, string $resource_name, int $id, array $data = array() ): \WP_Error {
		$current = $error->get_error_data();
		return new \WP_Error(
			$error->get_error_code(),
			sprintf(
				/* translators: 1: Error message. 2: Resource, such as product. 3: Object ID. */
				__( '%1$s The change is saved on the %2$s %3$d, so do not send it again.', 'woocommerce' ),
				$error->get_error_message(),
				$resource_name,
				$id
			),
			array_merge(
				array( 'status' => 400 ),
				is_array( $current ) ? $current : array(),
				$data,
				array(
					'saved' => true,
					'id'    => $id,
				)
			)
		);
	}

	/**
	 * The error for a field value that does not match its schema. It is also
	 * reported with wc_doing_it_wrong(), so the extension developer sees it in the log.
	 *
	 * @param array<string, mixed> $args  Field arguments.
	 * @param \WP_Error            $error Error of the schema check.
	 * @return \WP_Error
	 */
	private static function field_output_invalid_error( array $args, \WP_Error $error ): \WP_Error {
		$message = sprintf(
			/* translators: 1: Extension namespace. 2: Field name. 3: Resource, such as product. 4: What the field schema expected. */
			__( 'The "%1$s" extension returned a value for the "%2$s" field of the %3$s that does not match the field schema: %4$s', 'woocommerce' ),
			$args['namespace'],
			$args['field'],
			$args['resource'],
			$error->get_error_message()
		);
		wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', $message, '11.4.0' );
		return new \WP_Error(
			'woocommerce_ability_field_output_invalid',
			$message,
			array(
				'status'    => 500,
				'namespace' => $args['namespace'],
				'field'     => $args['field'],
				'details'   => $error->get_error_message(),
			)
		);
	}

	/**
	 * The error for a get_callback that throws. The exception text goes to the
	 * log through wc_doing_it_wrong(), and stays out of the response.
	 *
	 * @param array<string, mixed> $args      Field arguments.
	 * @param \Throwable           $exception Exception that the get_callback threw.
	 * @return \WP_Error
	 */
	private static function field_callback_failed_error( array $args, \Throwable $exception ): \WP_Error {
		$message = sprintf(
			/* translators: 1: Extension namespace. 2: Field name. 3: Resource, such as product. */
			__( 'The "%1$s" extension failed while reading the "%2$s" field of the %3$s.', 'woocommerce' ),
			$args['namespace'],
			$args['field'],
			$args['resource']
		);
		wc_doing_it_wrong( ExtensibleAbilities::class . '::register_field', $message . ' ' . $exception->getMessage(), '11.3.0' );
		return new \WP_Error(
			'woocommerce_ability_extension_failed',
			$message,
			array(
				'status'    => 500,
				'namespace' => $args['namespace'],
				'field'     => $args['field'],
			)
		);
	}

	/**
	 * Send an empty array for an `object` as an empty object, at any depth.
	 * rest_sanitize_value_from_schema() turns every object into an array, so a
	 * callback cannot send `{}` by itself. A part with no schema stays as it is.
	 * The nested schemas of every `allOf` branch, and of the `anyOf` and `oneOf`
	 * branch that the sanitized value matches, apply too, after the schema's own.
	 * A value that matches no branch stays as it is. An empty array fits both an
	 * `object` and an `array` branch, so it takes the first `anyOf` branch, and
	 * under a `oneOf` it stays as it is. Only one level of `allOf`, `anyOf` and
	 * `oneOf` branches is read, so a combinator inside a branch is not followed.
	 * When more than one branch declares the same property, the first one is used.
	 *
	 * @param mixed                $value  Sanitized value.
	 * @param array<string, mixed> $schema Schema of the value.
	 * @return mixed The value for the JSON output.
	 */
	private static function prepare_json_value( $value, array $schema ) {
		$branches = (array) ( $schema['allOf'] ?? array() );
		$finders  = array(
			'anyOf' => 'rest_find_any_matching_schema',
			'oneOf' => 'rest_find_one_matching_schema',
		);
		foreach ( $finders as $keyword => $find_branch ) {
			if ( isset( $schema[ $keyword ] ) ) {
				$branch = $find_branch( $value, $schema, '' );
				if ( is_wp_error( $branch ) ) {
					return $value;
				}
				$branches[] = $branch;
			}
		}
		foreach ( array_filter( $branches, 'is_array' ) as $branch ) {
			$schema['type']                 = $schema['type'] ?? $branch['type'] ?? null;
			$schema['properties']           = (array) ( $schema['properties'] ?? array() ) + (array) ( $branch['properties'] ?? array() );
			$schema['patternProperties']    = (array) ( $schema['patternProperties'] ?? array() ) + (array) ( $branch['patternProperties'] ?? array() );
			$schema['items']                = $schema['items'] ?? $branch['items'] ?? null;
			$schema['additionalProperties'] = $schema['additionalProperties'] ?? $branch['additionalProperties'] ?? null;
		}
		$type = rest_get_best_type_for_value( $value, (array) ( $schema['type'] ?? array() ) );
		if ( 'array' === $type ) {
			$children = array_fill_keys( array_keys( $value ), $schema['items'] ?? null );
		} elseif ( 'object' === $type ) {
			if ( empty( $value ) ) {
				return new \stdClass();
			}
			$children = array();
			foreach ( array_keys( $value ) as $key ) {
				$children[ $key ] = $schema['properties'][ $key ] ?? rest_find_matching_pattern_property_schema( (string) $key, $schema ) ?? ( $schema['additionalProperties'] ?? null );
			}
		} else {
			return $value;
		}

		foreach ( $children as $key => $child_schema ) {
			if ( is_array( $child_schema ) ) {
				$value[ $key ] = self::prepare_json_value( $value[ $key ], $child_schema );
			}
		}
		return $value;
	}
}
