<?php
/**
 * Extensible abilities class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Internal\Abilities\RegistrationChecks;
use Automattic\WooCommerce\Internal\Abilities\ResourceFields;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

/**
 * The entry point for extensions in WooCommerce's abilities, on top of the resources of AbilityResources.
 *
 * An extension registers fields, query filters and validators inside the `woocommerce_extensible_abilities_init`
 * action. An ability that formats an object of the resource returns the field values under
 * `extensions.<namespace>.<field>`, an ActionableAbility writes the same `extensions` input and runs the
 * validators, and the list ability of the resource accepts the filters under `filters.extensions`.
 *
 * The API is experimental while the `extensible_abilities` feature exists, and does nothing while it is off.
 *
 * @since 11.3.0
 */
class ExtensibleAbilities {

	public const FEATURE_ID = 'extensible_abilities';

	/**
	 * Query arguments that a filter callback can return.
	 */
	private const QUERY_ARG_KEYS = array( 'meta_query', 'tax_query', 'include_ids', 'exclude_ids' );

	/**
	 * Extension fields, keyed by resource, namespace, then field.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $fields = array();

	/**
	 * Query filters keyed by resource, then namespace, then filter name.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $filters = array();

	/**
	 * Validators keyed by resource.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $validators = array();

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
	 * Register a field that an extension adds to a resource, returned under `extensions.<namespace>.<field>`.
	 * Call it inside the `woocommerce_extensible_abilities_init` action.
	 *
	 * @since 11.4.0
	 *
	 * @param string $resource_name       Resource, such as `product`, `order` or `order_item`.
	 * @param string $extension_namespace Slug of the extension that owns the field, such as `subscriptions`.
	 * @param string $field               Field name, such as `trial_length`.
	 * @param array  $args {
	 *     Field arguments.
	 *
	 *     @type array    $schema          JSON schema of the value, with a `type` and no `$ref`. Its `title` is the label
	 *                                     clients show. No `required`: a value that fails its schema is left out.
	 *     @type callable $get_callback    Receives the object and returns the value. Read the given object, not the
	 *                                     database: an ability can format an object before it is saved.
	 *     @type bool     $personal_data   Optional. Whether the value is personal data, such as a phone number. It is left
	 *                                     out of the output and the output schema while the store withholds personal data.
	 *     @type callable $update_callback Optional. Receives the object and the value cast to the schema, or null to delete
	 *                                     it, and changes the object in memory. Return a WP_Error to reject the value, and
	 *                                     nothing is saved. Without it, the field is read-only.
	 * }
	 */
	public static function register_field( string $resource_name, string $extension_namespace, string $field, array $args ): void {
		$slugs = array(
			'resource_name' => $resource_name,
			'field'         => $field,
			'namespace'     => $extension_namespace,
		);
		if ( ! self::can_register( __METHOD__, 'field', $slugs ) ) {
			return;
		}
		if ( ! RegistrationChecks::has_field_args( __METHOD__, $args, true ) ) {
			return;
		}
		if ( RegistrationChecks::contains_ref( $args['schema'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'A "$ref" is only allowed in a field that AbilityResources::register_field() adds at the root of a resource.', '11.4.0' );
			return;
		}
		if ( isset( $args['update_callback'] ) && ! is_callable( $args['update_callback'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "update_callback" argument must be callable.', '11.4.0' );
			return;
		}
		if ( isset( self::$fields[ $resource_name ][ $extension_namespace ][ $field ] ) ) {
			RegistrationChecks::report_duplicate( __METHOD__, $extension_namespace . '.' . $field, $resource_name );
			return;
		}

		$record = array(
			'resource'      => $resource_name,
			'field'         => $field,
			'schema'        => $args['schema'],
			'get_callback'  => $args['get_callback'],
			'personal_data' => $args['personal_data'] ?? false,
		);
		if ( isset( $args['update_callback'] ) ) {
			$record['update_callback'] = $args['update_callback'];
		}
		self::$fields[ $resource_name ][ $extension_namespace ][ $field ] = array_merge( $record, array( 'namespace' => $extension_namespace ) );
		ResourceFields::forget();
	}

	/**
	 * Register a filter that the query ability of a resource accepts under `filters.extensions.<namespace>.<filter>`.
	 * Call it inside the `woocommerce_extensible_abilities_init` action.
	 *
	 * @since 11.4.0
	 *
	 * @param string   $resource_name       Resource, such as `product` or `order`.
	 * @param string   $extension_namespace Slug of the extension that owns the filter.
	 * @param string   $filter              Filter name, such as `subscribable`.
	 * @param array    $schema              JSON schema of the value, with a `type`. Its `description` tells agents how to use the filter.
	 * @param callable $callback            Receives the value, sanitized with the schema, and returns query arguments, or a
	 *                                      WP_Error to refuse the value. Allowed: `meta_query`, for products also `tax_query`,
	 *                                      and `include_ids` / `exclude_ids` for data that is not meta. All filters combine
	 *                                      with AND: an ID must be in every `include_ids` and in no `exclude_ids`. An empty
	 *                                      `include_ids` matches nothing.
	 */
	public static function register_query_filter( string $resource_name, string $extension_namespace, string $filter, array $schema, callable $callback ): void {
		$slugs = array(
			'resource_name' => $resource_name,
			'namespace'     => $extension_namespace,
			'filter'        => $filter,
		);
		if ( ! self::can_register( __METHOD__, 'filter', $slugs ) ) {
			return;
		}
		if ( ! isset( $schema['type'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "schema" argument must have a "type".', '11.4.0' );
			return;
		}
		if ( isset( self::$filters[ $resource_name ][ $extension_namespace ][ $filter ] ) ) {
			wc_doing_it_wrong(
				__METHOD__,
				sprintf( 'The filter "%1$s.%2$s" of "%3$s" is already registered.', $extension_namespace, $filter, $resource_name ),
				'11.4.0'
			);
			return;
		}

		self::$filters[ $resource_name ][ $extension_namespace ][ $filter ] = array(
			'schema'   => $schema,
			'callback' => $callback,
		);
	}

	/**
	 * Register a validator that can refuse a change to an object of a resource.
	 *
	 * An `update_callback` checks one field; a validator checks the whole change. It runs on every change that
	 * an ActionableAbility of the resource makes, also without extension fields in the input, and only while
	 * the `extensible_abilities` feature is on. Call it inside the `woocommerce_extensible_abilities_init` action.
	 *
	 * @since 11.4.0
	 *
	 * @param string   $resource_name       Resource, such as `product`.
	 * @param string   $extension_namespace Slug of the extension that owns the validator.
	 * @param callable $callback            Receives the changed object and the ability, before the save. Return a
	 *                                      WP_Error to refuse the change, and nothing is saved. Do not change the object.
	 */
	public static function register_validator( string $resource_name, string $extension_namespace, callable $callback ): void {
		$slugs = array(
			'resource_name' => $resource_name,
			'namespace'     => $extension_namespace,
		);
		if ( ! self::can_register( __METHOD__, 'validator', $slugs ) ) {
			return;
		}

		self::$validators[ $resource_name ][] = array(
			'resource'  => $resource_name,
			'namespace' => $extension_namespace,
			'callback'  => $callback,
		);
	}

	/**
	 * Extension fields of a resource, keyed by namespace, then field.
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
	 * @since 11.3.0
	 *
	 * @param string $resource_name Resource.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_validators( string $resource_name ): array {
		return self::$validators[ $resource_name ] ?? array();
	}

	/**
	 * Add `filters.extensions` to the input schema when the feature is on and the resource has filters.
	 * Unknown filters are refused, because the query would ignore them and return wrong results.
	 *
	 * @since 11.3.0
	 *
	 * @param array<string, mixed> $schema        Input schema.
	 * @param string               $resource_name Resource.
	 * @return array<string, mixed>
	 */
	public static function add_query_filters_schema( array $schema, string $resource_name ): array {
		$filters = self::get_query_filters( $resource_name );
		if ( empty( $filters ) || ! self::is_enabled() ) {
			return $schema;
		}

		$extensions = array(
			'type'                 => 'object',
			'description'          => __( 'Filters that extensions add, keyed by extension namespace, then filter.', 'woocommerce' ),
			'properties'           => array(),
			'additionalProperties' => false,
		);
		foreach ( $filters as $extension_namespace => $namespace_filters ) {
			$extensions['properties'][ $extension_namespace ] = array(
				'type'                 => 'object',
				'properties'           => array_map( static fn( array $args ) => $args['schema'], $namespace_filters ),
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
	 * Query arguments from the extension filters in the input of a list ability. Each key holds one clause per filter,
	 * so `array_merge_recursive()` adds them to WP_Query or wc_get_orders() arguments with AND.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_query_args( string $resource_name, array $input ) {
		$query_args = array();
		$include    = null;
		$exclude    = array();
		if ( ! self::is_enabled() ) {
			return $query_args;
		}

		foreach ( self::get_query_filters( $resource_name ) as $extension_namespace => $filters ) {
			foreach ( $filters as $filter => $args ) {
				if ( ! is_array( $input['filters']['extensions'][ $extension_namespace ] ?? null ) || ! array_key_exists( $filter, $input['filters']['extensions'][ $extension_namespace ] ) ) {
					continue;
				}

				$value = rest_sanitize_value_from_schema( $input['filters']['extensions'][ $extension_namespace ][ $filter ], $args['schema'], $filter );
				if ( is_wp_error( $value ) ) {
					$value->add_data( array( 'status' => 400 ) );
					return $value;
				}
				try {
					$clauses = call_user_func( $args['callback'], $value );
				} catch ( \Throwable $e ) {
					wc_get_logger()->error(
						sprintf( 'Ability filter "%s.%s" of "%s" failed: %s', $extension_namespace, $filter, $resource_name, $e->getMessage() ),
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
						sprintf( __( 'The "%s" filter failed.', 'woocommerce' ), $extension_namespace . '.' . $filter ),
						array( 'status' => 500 )
					);
				}
				if ( isset( $clauses['include_ids'] ) ) {
					$filter_ids = self::get_filter_ids( $clauses['include_ids'], $extension_namespace . '.' . $filter );
					if ( is_wp_error( $filter_ids ) ) {
						return $filter_ids;
					}
					$include = null === $include ? $filter_ids : array_values( array_intersect( $include, $filter_ids ) );
					unset( $clauses['include_ids'] );
				}
				if ( isset( $clauses['exclude_ids'] ) ) {
					$filter_ids = self::get_filter_ids( $clauses['exclude_ids'], $extension_namespace . '.' . $filter );
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
	 * Whether a registration runs inside the init action, with slugs, for a registered resource. Reports the first problem.
	 *
	 * @param string                $method Method that was called.
	 * @param string                $thing  What is registered, such as `filter`.
	 * @param array<string, string> $slugs  Slug arguments keyed by parameter name, with `resource_name`.
	 * @return bool
	 */
	private static function can_register( string $method, string $thing, array $slugs ): bool {
		if ( did_action( 'woocommerce_extensible_abilities_init' ) && ! doing_action( 'woocommerce_extensible_abilities_init' ) ) {
			wc_doing_it_wrong( $method, sprintf( 'Register a %s inside the woocommerce_extensible_abilities_init action, before the abilities build their schemas.', $thing ), '11.4.0' );
			return false;
		}
		if ( ! RegistrationChecks::has_slugs( $method, $slugs ) ) {
			return false;
		}
		if ( ! AbilityResources::is_registered( $slugs['resource_name'] ) && ! did_action( 'woocommerce_extensible_abilities_resources_init' ) ) {
			wc_doing_it_wrong( $method, sprintf( 'Register a %s inside the woocommerce_extensible_abilities_init action. The resources are not registered yet.', $thing ), '11.4.0' );
			return false;
		}
		if ( ! AbilityResources::is_registered( $slugs['resource_name'] ) ) {
			RegistrationChecks::report_unregistered( $method, $slugs['resource_name'] );
			return false;
		}
		return true;
	}

	/**
	 * Query filters of a resource, keyed by namespace, then filter.
	 *
	 * @param string $resource_name Resource.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	private static function get_query_filters( string $resource_name ): array {
		return self::$filters[ $resource_name ] ?? array();
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
}
