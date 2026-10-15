<?php
/**
 * Ability extensions class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The entry point for extensions in WooCommerce's abilities.
 *
 * An extension registers a resource inside the `woocommerce_ability_resources_init`
 * action. It registers a field on a resource, such as `product`, inside the
 * `woocommerce_ability_extensions_init` action, which fires after. An ability that formats an
 * object of that resource returns the field values under
 * `extensions.<namespace>.<field>`, and lists the fields in its output schema.
 * An ActionableAbility writes the same `extensions` input to the fields that
 * have an `update_callback`, runs the validators of the resource, and saves
 * the object one time.
 * An extension also registers a query filter, which the list ability of the
 * resource accepts under `filters.extensions.<namespace>.<filter>`.
 *
 * The API is experimental while the `ability_extensions` feature exists.
 *
 * @since 11.3.0
 */
class AbilityExtensions {

	public const FEATURE_ID = 'ability_extensions';

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
	 * Fields already reported for a value that does not match the schema, keyed by resource, namespace and field.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Whether the store enabled the experimental feature.
	 *
	 * @since 11.3.0
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return FeaturesUtil::feature_is_enabled( self::FEATURE_ID );
	}

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
	 * Register a validator that can refuse a change to an object of a resource.
	 *
	 * Call it inside the `woocommerce_ability_extensions_init` action. Validators
	 * run only when the `ability_contracts` feature is on.
	 *
	 * Use a field `update_callback` to refuse a bad value of one field. It runs
	 * only when the input sends that field. Use a validator to refuse a change as
	 * a whole. It runs on every change of the resource, also when the input sends
	 * no extension fields. For example, a validator refuses a status change of an
	 * order that is a subscription.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Validator arguments.
	 *
	 *     @type string   $resource  Resource that an ActionableAbility changes, `product` or `order`.
	 *     @type string   $namespace Slug of the extension that owns the validator, such as `subscriptions`.
	 *     @type callable $callback  Receives the changed object and the ability, after Core's changes and the
	 *                               field callbacks, before the one save. It returns a WP_Error to refuse the
	 *                               change, and then nothing is saved. The error gets the namespace in its
	 *                               data, and the 400 status when it has no status. Its message can name the
	 *                               ability or screen to use instead. Rules:
	 *                               1. It only reads. It does not change the object, save, send email or
	 *                                  make HTTP requests.
	 *                               2. It checks the ability name when the extension's own abilities
	 *                                  change the same resource.
	 * }
	 */
	public static function register_validator( array $args ): void {
		foreach ( array( 'resource', 'namespace' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] ) {
				wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores.', $key ), '11.3.0' );
				return;
			}
		}
		if ( ! is_callable( $args['callback'] ?? null ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "callback" argument must be callable.', '11.3.0' );
			return;
		}

		self::$validators[ $args['resource'] ][] = $args;
	}

	/**
	 * Register a filter that the list ability of a resource accepts under `filters.extensions.<namespace>.<filter>`.
	 *
	 * Call it inside the `woocommerce_ability_extensions_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()`.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Filter arguments.
	 *
	 *     @type string   $resource  Resource whose list ability applies the filters. Core's `products-query` and
	 *                               `orders-query` apply the filters of `product` and `order`. The list ability
	 *                               of another resource must call `get_query_args()` and `add_query_filters_schema()` itself.
	 *     @type string   $namespace Slug of the extension that owns the filter, such as `subscriptions`.
	 *     @type string   $filter    Filter name inside the namespace, such as `subscribable`.
	 *     @type array    $schema    JSON schema of the value. Its `description` tells agents how to use the filter.
	 *     @type callable $callback  Receives the value, sanitized with the schema, and returns query arguments:
	 *                               a `meta_query`, for products also a `tax_query`, `include_ids` or
	 *                               `exclude_ids`. Core adds each one to the query with AND. Return a
	 *                               WP_Error to refuse the value. A `tax_query` is accepted only for
	 *                               `product`. Any other key, or a `tax_query` for another resource, fails the call.
	 *                               `include_ids` and `exclude_ids` are lists of object IDs. Use them for
	 *                               data that is not meta, such as order items or a table of the extension:
	 *                               query that data and return the IDs that match. Core keeps the IDs that
	 *                               every `include_ids` filter returns, then drops the IDs that any
	 *                               `exclude_ids` filter returns. An empty `include_ids` matches nothing.
	 * }
	 */
	public static function register_query_filter( array $args ): void {
		if ( did_action( 'woocommerce_ability_extensions_init' ) && ! doing_action( 'woocommerce_ability_extensions_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a filter inside the woocommerce_ability_extensions_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'filter' ) as $key ) {
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
		if ( ! is_callable( $args['callback'] ?? null ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "callback" argument must be callable.', '11.3.0' );
			return;
		}
		if ( isset( self::$filters[ $args['resource'] ][ $args['namespace'] ][ $args['filter'] ] ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The filter "%s.%s" of "%s" is already registered.', $args['namespace'], $args['filter'], $args['resource'] ), '11.3.0' );
			return;
		}

		self::$filters[ $args['resource'] ][ $args['namespace'] ][ $args['filter'] ] = $args;
	}

	/**
	 * Add the `filters.extensions` property to the input schema of a list
	 * ability, when the feature is on and the resource has filters. It does
	 * not allow other filters, because a filter that the query ignores
	 * returns wrong results.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema        Input schema.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function add_query_filters_schema( array $schema, string $resource_name ): array {
		if ( empty( self::$filters[ $resource_name ] ) || ! self::is_enabled() ) {
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
	 * Get the query arguments of the filters in the input of a list ability.
	 * Each key holds one clause for each filter, so `array_merge_recursive()`
	 * adds them to WP_Query or wc_get_orders() arguments with AND. `post__in`
	 * holds the IDs that every `include_ids` filter returned, without the IDs
	 * that any `exclude_ids` filter returned, or `0` when none remain, because
	 * an empty `post__in` does not filter. Without `include_ids`, the excluded
	 * IDs go to `post__not_in`, which WP_Query ignores next to `post__in`.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, array>|\WP_Error The error of a filter that refused its value or failed.
	 */
	public static function get_query_args( string $resource_name, array $input ) {
		$query_args = array();
		$include    = null;
		$exclude    = array();
		if ( ! self::is_enabled() ) {
			return $query_args;
		}

		foreach ( self::$filters[ $resource_name ] ?? array() as $namespace => $filters ) {
			foreach ( $filters as $filter => $args ) {
				if ( ! is_array( $input['filters']['extensions'][ $namespace ] ?? null ) || ! array_key_exists( $filter, $input['filters']['extensions'][ $namespace ] ) ) {
					continue;
				}

				$value = rest_sanitize_value_from_schema( $input['filters']['extensions'][ $namespace ][ $filter ], $args['schema'], $filter );
				try {
					$clauses = call_user_func( $args['callback'], $value );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability filter "%s.%s" of "%s" failed: %s', $namespace, $filter, $resource_name, $e->getMessage() ),
						array( 'source' => 'ability-extensions' )
					);
					$clauses = null;
				}
				if ( is_wp_error( $clauses ) ) {
					return $clauses;
				}
				if (
					! is_array( $clauses )
					|| ! empty( array_diff_key( $clauses, array_flip( self::QUERY_ARG_KEYS ) ) )
					|| ( 'product' !== $resource_name && isset( $clauses['tax_query'] ) )
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
		if ( ! self::is_enabled() ) {
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
		if ( ! self::is_enabled() || empty( self::$fields[ $resource_name ] ) ) {
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
	 * Validators of a resource.
	 *
	 * @internal
	 *
	 * @param string $resource_name Resource.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_validators( string $resource_name ): array {
		return self::$validators[ $resource_name ] ?? array();
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
