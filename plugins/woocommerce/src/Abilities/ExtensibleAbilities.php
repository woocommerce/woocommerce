<?php
/**
 * Extensible abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Utilities\StagedMethod;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The entry point for extensions in WooCommerce's abilities.
 *
 * An extension registers a resource inside the `woocommerce_extensible_abilities_resources_init`
 * action. It registers a field on a resource, such as `product`, inside the
 * `woocommerce_extensible_abilities_init` action, which fires after. An ability that formats an
 * object of that resource returns the field values under
 * `extensions.<namespace>.<field>`, and lists the fields in its output schema.
 * An ActionableAbility writes the same `extensions` input to the fields that
 * have a `set_callback`, runs the validators of the resource, and saves
 * the object one time.
 * An extension also registers a query filter, which the list ability of the
 * resource accepts under `filters.extensions.<namespace>.<filter>`.
 *
 * The API is experimental while the `extensible_abilities` feature exists.
 *
 * @since 11.3.0
 */
class ExtensibleAbilities {

	public const FEATURE_ID = 'extensible_abilities';

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
	 * Type names that the REST schema check knows. It accepts any value for another name.
	 */
	private const JSON_TYPES = array( 'array', 'boolean', 'integer', 'null', 'number', 'object', 'string' );

	/**
	 * Register a resource that abilities format, so extensions can register fields on it.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_resources_init` action. A call after that action is refused.
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
		if ( did_action( 'woocommerce_extensible_abilities_resources_init' ) && ! doing_action( 'woocommerce_extensible_abilities_resources_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a resource inside the woocommerce_extensible_abilities_resources_init action, before the fields are registered.', '11.3.0' );
			return;
		}
		if ( '' === $resource_name || sanitize_key( $resource_name ) !== $resource_name || ctype_digit( $resource_name ) ) {
			wc_doing_it_wrong( __METHOD__, 'The resource name must be a slug of lowercase letters, numbers, dashes and underscores, not only numbers.', '11.3.0' );
			return;
		}

		self::$resources[ $resource_name ] = true;
	}

	/**
	 * Register a field that the abilities of a resource return under `extensions.<namespace>.<field>`.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()` in the earlier `woocommerce_extensible_abilities_resources_init` action.
	 * A field declares `get_callback` and `output_schema`, `set_callback` and `input_schema`, or both pairs. The
	 * first pair makes it readable and the second makes it writable. An incomplete pair is refused.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Field arguments.
	 *
	 *     @type string   $resource     Resource that an ability formats, for example `product`, `order` or `order_item`.
	 *     @type string   $namespace    Slug of the extension that owns the field, such as `subscriptions`.
	 *     @type string   $field        Field name inside the namespace, such as `trial_length`.
	 *     @type array    $output_schema Optional. JSON schema of the value that `get_callback` returns. Required
	 *                                  with `get_callback`. It needs one `type` other than null, because clients
	 *                                  expect one type for each field. Core publishes it as declared. Its
	 *                                  `title` is the label clients show.
	 *     @type string[] $object_types Optional. The object types the field applies to, as `get_type()` of the
	 *                                  object returns them, such as `simple` and `subscription` for a product or
	 *                                  `line_item` for an order item. The object must have a `get_type()` method.
	 *                                  The abilities leave the field out of the other objects and refuse a write
	 *                                  to them. Omit it for every type.
	 *     @type callable $get_callback Optional. Receives the object and returns the value. A field without it
	 *                                  is not in the output. Required with `output_schema`. Return null when the
	 *                                  object has no value, and the field is left out with no error. Any other
	 *                                  value that `output_schema` does not allow fails the call with
	 *                                  `woocommerce_ability_field_output_invalid`. A callback that throws fails
	 *                                  the call with `woocommerce_ability_extension_failed`. It reads the object
	 *                                  it is given, not the database, because an ability can format an object
	 *                                  before it is saved. An empty array for an `object` is sent as `{}`. The
	 *                                  callback must not return a list for an `object` or a `date-time` without
	 *                                  a UTC offset: the schema check accepts them, and a strict client refuses
	 *                                  the whole result.
	 *     @type array    $input_schema Optional. JSON schema of the value that `set_callback` receives. It needs
	 *                                  one `type` name, or an `anyOf` where each branch has one `type` name. A
	 *                                  list of types is refused at the top level, because some clients refuse it:
	 *                                  a nullable field uses `[{"type":"string"},{"type":"null"}]` as its `anyOf`.
	 *                                  Core publishes it as declared. A value can be null only where it declares
	 *                                  null. Required with `set_callback`.
	 *     @type callable $set_callback Optional. Receives the object and the value, and changes the object
	 *                                  in memory, when an ability creates or updates the object. It returns a
	 *                                  WP_Error to reject the value, and then nothing is saved. It also runs
	 *                                  after Core refuses another value of the primary object. A field without
	 *                                  it cannot be written. Required with `input_schema`. Rules:
	 *                                  1. It only sets values in memory. It does not save, send email
	 *                                     or make HTTP requests.
	 *                                  2. It receives the value already cast to `input_schema`.
	 *                                  3. A null value, where `input_schema` declares null, deletes the value
	 *                                     of the field.
	 *                                  4. It writes one object: the one it receives. Data that a
	 *                                     write must keep on another object, such as a price range
	 *                                     that a variable parent derives from its variations, is
	 *                                     written by a sync that register_sync() adds. A sync runs
	 *                                     once per call after every object is saved and inside the
	 *                                     transaction of the ability.
	 * }
	 */
	public static function register_field( array $args ): void {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a field inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'field' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] || ctype_digit( $args[ $key ] ) ) {
				wc_doing_it_wrong(
					__METHOD__,
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
			wc_doing_it_wrong( __METHOD__, 'Register a field inside the woocommerce_extensible_abilities_init action. The resources are not registered yet.', '11.3.0' );
			return;
		}
		if ( ! isset( self::$resources[ $args['resource'] ] ) ) {
			wc_doing_it_wrong(
				__METHOD__,
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
			wc_doing_it_wrong( __METHOD__, 'A field needs "get_callback" and "output_schema", "set_callback" and "input_schema", or both pairs.', '11.4.0' );
			return;
		}
		if ( $readable ) {
			if ( ! is_callable( $args['get_callback'] ?? null ) ) {
				wc_doing_it_wrong( __METHOD__, 'The "get_callback" argument must be callable, and a field with an "output_schema" needs it.', '11.3.0' );
				return;
			}
			if ( ! is_array( $args['output_schema'] ?? null ) || ! isset( $args['output_schema']['type'] ) ) {
				wc_doing_it_wrong( __METHOD__, 'The "output_schema" argument must be an array with a "type".', '11.4.0' );
				return;
			}
			if ( ! is_string( $args['output_schema']['type'] ) || 'null' === $args['output_schema']['type'] ) {
				wc_doing_it_wrong( __METHOD__, 'The "type" of the "output_schema" argument must be one type name other than "null". Return null to leave the field out.', '11.4.0' );
				return;
			}
		}
		if ( $writable ) {
			if ( ! is_callable( $args['set_callback'] ?? null ) ) {
				wc_doing_it_wrong( __METHOD__, 'The "set_callback" argument must be callable, and a field with an "input_schema" needs it.', '11.4.0' );
				return;
			}
			if ( ! is_array( $args['input_schema'] ?? null ) || ! self::input_schema_has_type( $args['input_schema'] ) ) {
				wc_doing_it_wrong( __METHOD__, 'The "input_schema" argument must be an array with one "type" name, or with an "anyOf" where each branch has one "type" name. For a nullable field, use an "anyOf" with a {"type":"null"} branch instead of a list of types.', '11.4.0' );
				return;
			}
		}
		if ( isset( $args['object_types'] ) && ( ! is_array( $args['object_types'] ) || empty( $args['object_types'] ) || array_filter( $args['object_types'], static fn( $type ) => ! is_string( $type ) ) ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "object_types" argument must be a list of object types.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] ) ) {
			wc_doing_it_wrong(
				__METHOD__,
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
	 * Register a filter that the list ability of a resource accepts under `filters.extensions.<namespace>.<filter>`.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. A call after that action is refused.
	 * The resource must be registered with `register_resource()`.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Filter arguments.
	 *
	 *     @type string   $resource  Resource whose list ability applies the filters. Core's `products-query`,
	 *                               `orders-query` and `customers-query` apply the filters of `product`, `order`
	 *                               and `customer`. A `meta_query` for `customer` matches user meta. The list ability
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
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a filter inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', '11.3.0' );
			return;
		}
		foreach ( array( 'resource', 'namespace', 'filter' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] || ctype_digit( $args[ $key ] ) ) {
				wc_doing_it_wrong(
					__METHOD__,
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
				__METHOD__,
				sprintf(
					'The "%s" resource is not registered. Call ExtensibleAbilities::register_resource() first.',
					$args['resource']
				),
				'11.3.0'
			);
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
			wc_doing_it_wrong(
				__METHOD__,
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
	 * adds them to WP_Query, wc_get_orders() or WP_User_Query arguments with AND. `post__in`
	 * holds the IDs that every `include_ids` filter returned, without the IDs
	 * that any `exclude_ids` filter returned, or `0` when none remain, because
	 * an empty `post__in` does not filter. Without `include_ids`, the excluded
	 * IDs go to `post__not_in`, which WP_Query ignores next to `post__in`.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, array>|\WP_Error The error of a value that fails its schema, or of a filter that refused its value or failed.
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
				if ( is_wp_error( $value ) ) {
					$value->add_data( array( 'status' => 400 ) );
					return $value;
				}
				try {
					$clauses = call_user_func( $args['callback'], $value );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability filter "%s.%s" of "%s" failed: %s', $namespace, $filter, $resource_name, $e->getMessage() ),
						array( 'source' => 'extensible-abilities' )
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
	 * Refuse a direct call of a stage method when a class overrides its saving
	 * method without overriding the stage method too, because staging would skip
	 * the rules of that override. WC_Data classes use
	 * WC_Data::refuse_unstaged_override(). Another class calls this method at the
	 * start of its stage method, unless its saving method runs the stage method.
	 *
	 * @since 11.4.0
	 *
	 * @param object $subject      Object that gets the change.
	 * @param string $method       Saving method, for example add_tracking_item.
	 * @param string $stage_method Stage method, for example stage_tracking_item.
	 * @throws \WC_Data_Exception When a class has not adopted the stage method.
	 */
	public static function refuse_unstaged_override( object $subject, string $method, string $stage_method ): void {
		StagedMethod::refuse_unstaged( $subject, $method, $stage_method );
	}

	/**
	 * Register a validator that can refuse a change to an object of a resource.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. Validators
	 * run only when the `extensible_abilities` feature is on.
	 *
	 * Use a field `set_callback` to refuse a bad value of one field. It runs
	 * only when the input sends that field. Use a validator to refuse a change as
	 * a whole. It runs on every change of the resource, also when the input sends
	 * no extension fields. For example, a validator refuses a product change that
	 * would break a subscription plan.
	 *
	 * Core's product abilities and order-update-status run validators. An order
	 * validator sees the order with its new status staged, including the paid
	 * and completed dates that the status sets. A stage_*() method refuses a class
	 * that overrides its saving method without overriding the stage method too, so
	 * the ability refuses that class while the feature is on. See
	 * WC_Data::refuse_unstaged_override().
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Validator arguments.
	 *
	 *     @type string   $resource  Resource that an ActionableAbility changes, such as `product`.
	 *     @type string   $namespace Slug of the extension that owns the validator, such as `subscriptions`.
	 *     @type callable $callback  Receives the changed object, the ability and `array $context`, after Core's
	 *                               changes and the field callbacks, before the one save. A callback that only
	 *                               needs the object or the ability can take fewer arguments.
	 *                               `$context['primary']` is the primary object of the call. It differs from the
	 *                               changed object when the object is a variation that the call saves through
	 *                               its parent. On product-create that parent is not saved yet, so the variation
	 *                               has no parent ID and a rule reads the parent from here.
	 *                               `$context['deleting']` is true when the call deletes or trashes the object,
	 *                               such as a variation of a deleted parent, or an item with `delete` in the
	 *                               `variations` input. `$context['objects']` is every object the call saves,
	 *                               the primary object first, with the field writes of all of them applied, so
	 *                               a rule can compare a variation with the other variations of the same call.
	 *                               `$context['objects_to_delete']` is every object the call deletes or
	 *                               trashes. It returns a WP_Error to refuse the change, and then nothing is
	 *                               saved. The other validators still run, and the client gets every refusal in
	 *                               one error. The client sees the code prefixed with the namespace, as
	 *                               `<namespace>:<code>`. A code that another validator in the same namespace
	 *                               already used gets a numeric suffix, as `<namespace>:<code>:2`. The error
	 *                               gets the namespace in its data, and the 400 status when it has no status.
	 *                               Its message can name the ability or screen to use instead. Validators also
	 *                               run after a field callback or Core refuses a value, so the client gets every
	 *                               refusal together, with all messages in the first one. After a Core refusal
	 *                               they run on the primary object only, which keeps the values Core refused at
	 *                               their old values. Rules:
	 *                               1. It only reads. It does not change the object, save, send email or
	 *                                  make HTTP requests.
	 *                               2. It checks the ability name when the extension's own abilities
	 *                                  change the same resource.
	 * }
	 */
	public static function register_validator( array $args ): void {
		if ( ! self::check_callback_args( $args, __METHOD__, 'validator', '11.3.0' ) ) {
			return;
		}

		self::$validators[ $args['resource'] ][] = $args;
	}

	/**
	 * Register a sync on a resource. A sync runs once per ability call, after
	 * every object of the call is saved, and writes what the saved objects imply
	 * elsewhere, such as the price range a variable parent derives from its
	 * variations, or a row in a table of the extension.
	 *
	 * Call it inside the `woocommerce_extensible_abilities_init` action. Syncs
	 * run only when the `extensible_abilities` feature is on, inside the
	 * transaction of the ActionableAbility.
	 *
	 * A sync registered for a resource runs only when an object of that resource
	 * was saved, deleted or trashed in the call. It runs once per call, not once per
	 * object. The saved objects can belong to more than one resource, so a sync
	 * reads the objects it owns and ignores the rest.
	 *
	 * @since 11.4.0
	 *
	 * @param array $args {
	 *     Sync arguments.
	 *
	 *     @type string   $resource  Resource that an ActionableAbility saves, such as `product`.
	 *     @type string   $namespace Slug of the extension that owns the sync, such as `subscriptions`.
	 *     @type callable $callback  Receives `\WC_Data[] $saved`, the ActionableAbility and `array $context`.
	 *                               `$saved` is every saved object of the call, the primary object first
	 *                               unless the call deleted it.
	 *                               `$context['deleted']` is every object the call deleted for good, as it
	 *                               was before the delete, with its ID. `$context['trashed']` is every object
	 *                               the call moved to the trash; it can be restored, so a sync keeps what a
	 *                               restore needs. And
	 *                               `$context['input']` is the input of the call, so a sync can skip work
	 *                               when the call did not write its fields. It returns a WP_Error to
	 *                               refuse, and then every save of the call is undone. A thrown exception
	 *                               refuses it too. The error gets the namespace in its data, and the 400
	 *                               status when it has no status, as a validator's error does. A sync must not
	 *                               send email or make HTTP requests, because an undo does not reach outside
	 *                               the database.
	 * }
	 */
	public static function register_sync( array $args ): void {
		if ( ! self::check_callback_args( $args, __METHOD__, 'sync', '11.4.0' ) ) {
			return;
		}

		self::$syncs[ $args['resource'] ][] = $args;
	}

	/**
	 * Check the arguments of a validator or a sync, and report the first
	 * problem with wc_doing_it_wrong().
	 *
	 * @param array  $args    Validator or sync arguments.
	 * @param string $method  Method that registers the callback.
	 * @param string $kind    `validator` or `sync`, for the message.
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
	 * Add the field values of an object to its formatted output, under
	 * `extensions.<namespace>.<field>`. A value is cast to its schema type, so
	 * `'12'` becomes `12` for an integer field. A field that returns null is
	 * left out. Any other value that its output schema
	 * does not allow returns a `woocommerce_ability_field_output_invalid` error,
	 * and a get_callback that throws returns a `woocommerce_ability_extension_failed`
	 * error. The ability returns the error. A namespace with no
	 * values is left out, and `extensions` is left out when no namespace has
	 * values or the feature is off.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $output   Formatted object.
	 * @param string               $resource_name Resource.
	 * @param object               $subject  Object to read.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function add_fields_to_object( array $output, string $resource_name, $subject ) {
		if ( empty( self::$fields[ $resource_name ] ) || ! self::is_enabled() ) {
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
	 * @since 11.4.0
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
	 * @since 11.4.0
	 *
	 * @param mixed $subject The object. It must have a `get_type()` method.
	 * @return string
	 */
	public static function get_object_type( $subject ): string {
		return $subject->get_type();
	}

	/**
	 * Add the `extensions` property to an object schema when the feature is on.
	 * It lists each namespace with its fields. Call it after the
	 * `woocommerce_extensible_abilities_init` action, when the ability builds its
	 * schema. The schema stays unchanged when the resource has no readable field.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema        Object schema.
	 * @param string               $resource_name Resource.
	 * @param callable|null        $field_filter  Optional. Gets the arguments of a field and returns whether the schema lists it.
	 * @return array<string, mixed>
	 */
	public static function add_fields_schema( array $schema, string $resource_name, ?callable $field_filter = null ): array {
		if ( ! self::is_enabled() ) {
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
	 * The schema of the `extensions` input of a resource. It lists the fields
	 * that have a set_callback, each with its input schema as declared, and says
	 * which object types a field with `object_types` applies to. ActionableAbility adds it to the input schema of every
	 * ability. An ability that writes other objects of the resource in the same
	 * call, such as the variations of a product, adds it to their input too.
	 *
	 * @since 11.4.0
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
	 * @internal
	 *
	 * @since 11.3.0
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
	 * @internal
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
	 * @internal
	 *
	 * @since 11.4.0
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
	 * @internal
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
	 * @internal
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
		wc_doing_it_wrong( __CLASS__ . '::register_field', $message, '11.4.0' );
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
		wc_doing_it_wrong( __CLASS__ . '::register_field', $message . ' ' . $exception->getMessage(), '11.3.0' );
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
	 *
	 * @param mixed                $value  Sanitized value.
	 * @param array<string, mixed> $schema Schema of the value.
	 * @return mixed The value for the JSON output.
	 */
	private static function prepare_json_value( $value, array $schema ) {
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
