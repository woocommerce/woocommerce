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
 * An extension registers a field on a resource, such as `product`, inside the
 * `woocommerce_ability_extensions_init` action. An ability that formats an
 * object of that resource returns the field values under
 * `extensions.<namespace>.<field>`, and lists the fields in its output schema.
 * An extension also registers a query filter, which the list ability of the
 * resource accepts under `filters.extensions.<namespace>.<filter>`.
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
	 * Fields already reported for a value that does not match the schema, keyed by resource, namespace and field.
	 *
	 * @var array<string, bool>
	 */
	private static array $reported = array();

	/**
	 * Query filters keyed by resource, then namespace, then filter name.
	 *
	 * @var array<string, array<string, array<string, array<string, mixed>>>>
	 */
	private static array $filters = array();

	/**
	 * Query arguments that a filter callback can return.
	 */
	private const QUERY_ARG_KEYS = array( 'meta_query', 'tax_query' );

	/**
	 * Register a field that the abilities of a resource return under `extensions.<namespace>.<field>`.
	 *
	 * Call it inside the `woocommerce_ability_extensions_init` action.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Field arguments.
	 *
	 *     @type string   $resource     Resource that an ability formats, for example `product`, `order` or `order_item`.
	 *     @type string   $namespace    Slug of the extension that owns the field, such as `subscriptions`.
	 *     @type string   $field        Field name inside the namespace, such as `trial_length`.
	 *     @type array    $schema       JSON schema of the value. Its `title` is the label clients show.
	 *     @type callable $get_callback Receives the object and returns the value. A value that the schema
	 *                                  does not allow is left out, so return null when the object has no
	 *                                  value. A schema that allows null, such as `array( 'integer', 'null' )`,
	 *                                  keeps it. It reads the object it is given, not the database,
	 *                                  because an ability can format an object before it is saved.
	 * }
	 */
	public static function register_field( array $args ): void {
		if ( ! self::has_valid_args( __METHOD__, $args, 'field', 'get_callback' ) ) {
			return;
		}
		if ( isset( self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The field "%s.%s" of "%s" is already registered.', $args['namespace'], $args['field'], $args['resource'] ), '11.3.0' );
			return;
		}

		self::$fields[ $args['resource'] ][ $args['namespace'] ][ $args['field'] ] = $args;
	}

	/**
	 * Register a filter that the list ability of a resource accepts under `filters.extensions.<namespace>.<filter>`.
	 *
	 * Call it inside the `woocommerce_ability_extensions_init` action.
	 *
	 * @since 11.3.0
	 *
	 * @param array $args {
	 *     Filter arguments.
	 *
	 *     @type string   $resource  Resource that the list ability returns: `product` or `order`.
	 *     @type string   $namespace Slug of the extension that owns the filter, such as `subscriptions`.
	 *     @type string   $filter    Filter name inside the namespace, such as `subscribable`.
	 *     @type array    $schema    JSON schema of the value. Its `description` tells agents how to use the filter.
	 *     @type callable $callback  Receives the value, sanitized with the schema, and returns query arguments:
	 *                               a `meta_query`, and for products also a `tax_query`. Core adds each one
	 *                               to the query with AND. Return a WP_Error to refuse the value.
	 * }
	 */
	public static function register_query_filter( array $args ): void {
		if ( ! in_array( $args['resource'] ?? null, array( 'product', 'order' ), true ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "resource" argument must be "product" or "order".', '11.3.0' );
			return;
		}
		if ( ! self::has_valid_args( __METHOD__, $args, 'filter', 'callback' ) ) {
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
		if ( empty( self::$filters[ $resource_name ] ) || ! AbilityContracts::is_enabled() ) {
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
	 * adds them to WP_Query or wc_get_orders() arguments with AND.
	 *
	 * @since 11.3.0
	 *
	 * @param string               $resource_name Resource.
	 * @param array<string, mixed> $input         Ability input.
	 * @return array<string, array>|\WP_Error The error of a filter that refused its value or failed.
	 */
	public static function get_query_args( string $resource_name, array $input ) {
		$query_args = array();
		if ( ! AbilityContracts::is_enabled() ) {
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
				if ( ! is_array( $clauses ) || ! empty( array_diff_key( $clauses, array_flip( self::QUERY_ARG_KEYS ) ) ) ) {
					return new \WP_Error(
						'woocommerce_ability_extension_filter_failed',
						/* translators: %s: filter name, such as subscriptions.subscribable. */
						sprintf( __( 'The "%s" filter failed.', 'woocommerce' ), $namespace . '.' . $filter ),
						array( 'status' => 500 )
					);
				}
				foreach ( $clauses as $key => $clause ) {
					$query_args[ $key ][] = $clause;
				}
			}
		}
		return $query_args;
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
				if ( self::matches_schema( $args, $value ) ) {
					$extensions[ $namespace ][ $field ] = rest_sanitize_value_from_schema( $value, $args['schema'], $field );
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
	 * It lists each namespace with its fields, and allows others, because a
	 * field can be registered after the ability. The schema stays unchanged when
	 * no field is registered for the resource.
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

	/**
	 * Whether the arguments of a registration are valid. Reports the first argument that is not.
	 *
	 * @param string $method       Method that registers.
	 * @param array  $args         Registration arguments.
	 * @param string $name_key     Argument that names the field or filter.
	 * @param string $callback_key Argument that holds the callback.
	 * @return bool
	 */
	private static function has_valid_args( string $method, array $args, string $name_key, string $callback_key ): bool {
		foreach ( array( 'resource', 'namespace', $name_key ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] ) {
				wc_doing_it_wrong( $method, sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores.', $key ), '11.3.0' );
				return false;
			}
		}
		if ( ! is_array( $args['schema'] ?? null ) ) {
			wc_doing_it_wrong( $method, 'The "schema" argument must be an array.', '11.3.0' );
			return false;
		}
		if ( ! is_callable( $args[ $callback_key ] ?? null ) ) {
			wc_doing_it_wrong( $method, sprintf( 'The "%s" argument must be callable.', $callback_key ), '11.3.0' );
			return false;
		}
		return true;
	}
}
