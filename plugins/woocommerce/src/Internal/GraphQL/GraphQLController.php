<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\Executor\ExecutionResult;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;

/**
 * Main class to handle GraphQL requests  and execute GraphQL queries.
 */
class GraphQLController {

	private static $container;

	/**
	 * Initialize the class, registering the API endpoints.
	 *
	 * @return void
	 */
	public function initialize() {
		self::$container = wc_get_container();

		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'wc/v4',
					'graphql',
					array(
						'methods'             => 'POST',
						'callback'            => function( $request ) {
							return self::handle_graphql_request( $request );
						},
						'permission_callback' => '__return_true',
					)
				);

				register_rest_route(
					'wc/v4',
					'orders/(?P<id>\d+)',
					array(
						'methods'             => 'GET',
						'callback'            => function( $request ) {
							return wc_get_container()->get( OrderRetriever::class )->retrieve_order( $request['id'] );
						},
						'permission_callback' => '__return_true',
					)
				);

				register_rest_route(
					'wc/v4',
					'orders/(?P<order_id>\d+)/address/(?P<type>\w+)',
					array(
						'methods'             => 'GET',
						'callback'            => function( $request ) {
							return wc_get_container()->get( OrderRetriever::class )->retrieve_address( $request['order_id'], $request['type'] );
						},
						'permission_callback' => '__return_true',
					)
				);
			}
		);
	}

	/**
	 * Handle a GraphQL HTTP request.
	 *
	 * @param \WP_REST_Request $request The request to handle.
	 * @return ExecutionResult|\WP_Error The request result or an error.
	 */
	private static function handle_graphql_request( \WP_REST_Request $request ) {
		$input = json_decode( $request->get_body(), true );

		$query           = $input['query'];
		$variable_values = $input['variables'] ?? null;

		$schema = new Schema(
			array(
				'query'      => self::$container->get( RootQueryType::class ),
				'typeLoader' => function( $name ) {
					return self::resolve_type( $name );
				},
			)
		);

		// The built-in field resolver takes in account both objects and arrays,
		// this is a simplified version that handles arrays only.
		$field_resolver = function( $objectValue, array $args, $context, ResolveInfo $info ) {
			return $objectValue[ $info->fieldName ];
		};

		return GraphQL::executeQuery( $schema, $query, null, null, $variable_values, null, $field_resolver );
	}

	/**
	 * Convert a GraphQL type into a full class name.
	 *
	 * @param string $name The GraphQL type name.
	 * @return string The resulting full class name.
	 * @throws \Exception Unknown type.
	 */
	public static function resolve_type( $name ) {
		$full_name = __NAMESPACE__ . '\\' . $name . 'Type';
		if ( self::$container->has( $full_name ) ) {
			return self::$container->get( $full_name );
		}

		throw new \Exception( "There's no way to resolve the type '" . $name . "'." );
	}

	/**
	 * Execute a GraphQL query displaying execution times.
	 *
	 * @param string $query The query to execute.
	 * @param bool   $use_query_cache If true, use query caching.
	 * @param bool   $force_query_caching If this and $use_query_cache are true, always assume the query isn't cached.
	 * @return ExecutionResult The result of executing the query.
	 * @throws \Exception Query execution failed.
	 */
	public static function execute_graphql_verbose( string $query, bool $use_query_cache, bool $force_query_caching ) {
		echo "\n";

		$schema = new Schema(
			array(
				'query'      => self::$container->get( RootQueryType::class ),
				'typeLoader' => function( $name ) {
					return self::resolve_type( $name );
				},
			)
		);

		// The built-in field resolver takes in account both objects and arrays,
		// this is a simplified version that handles arrays only.
		$field_resolver = function( $objectValue, array $args, $context, ResolveInfo $info ) {
			return $objectValue[ $info->fieldName ];
		};

		$query_was_cached = false;
		if ( $use_query_cache ) {
			$query_hash      = md5( $query );
			$query_cache_key = "graphql_query_$query_hash";

			if ( $force_query_caching ) {
				delete_transient( $query_cache_key );
			}
			$cached_query = self::run_measuring_time(
				function() use ( $query_cache_key ) {
					return get_transient( $query_cache_key );
				},
				'Get cached query'
			);

			if ( false === $cached_query ) {
				echo "The parsed query wasn't cached.\n";
				$parsed = self::run_measuring_time(
					function() use ( $schema, $query ) {
						$parsed            = Parser::parse( new Source( $query ?? '', 'GraphQL' ) );
						$validation_errors = DocumentValidator::validate( $schema, $parsed );
						if ( count( $validation_errors ) ) {
							return new ExecutionResult( null, $validation_errors );
						}
						return $parsed;},
					'Parse and validate query'
				);

				if ( $parsed instanceof ExecutionResult ) {
					return $parsed;
				}

				$query = $parsed;

				self::run_measuring_time(
					function() use ( $query_cache_key, $query ) {
						set_transient( $query_cache_key, $query, 60 );
					},
					'Set parsed query in cache'
				);
				echo "The parsed query has been cached.\n";
			} else {
				$query_was_cached = true;
				$query            = $cached_query;
			}
		}

		return self::run_measuring_time(
			function() use ( $schema, $query, $field_resolver, $query_was_cached ) {
				return GraphQL::executeQuery( $schema, $query, null, null, null, null, $field_resolver, $query_was_cached ? array() : null );
			},
			'Run query'
		);
	}

	/**
	 * Execute a GraphQL query and display the total execution time.
	 *
	 * @param string $query The query to execute.
	 * @param bool   $use_query_cache If true, use query caching.
	 * @param bool   $force_query_caching If this and $use_query_cache are true, always assume the query isn't cached.
	 * @return ExecutionResult The result of executing the query.
	 * @throws \Exception Query execution failed.
	 */
	public static function execute_graphql( string $query, bool $use_query_cache, bool $force_query_caching ) {
		return self::run_measuring_time(
			function() use ( $query, $use_query_cache, $force_query_caching ) {
				return self::_execute_graphql( $query, $use_query_cache, $force_query_caching ); },
			'Execution time'
		);
	}

	private static function _execute_graphql( string $query, bool $use_query_cache, bool $force_query_caching ) {
		$schema = new Schema(
			array(
				'query'      => self::$container->get( RootQueryType::class ),
				'typeLoader' => function( $name ) {
					return self::resolve_type( $name );
				},
			)
		);

		$query_was_cached = false;
		if ( $use_query_cache ) {
			$query_hash      = md5( $query );
			$query_cache_key = "graphql_query_$query_hash";

			if ( $force_query_caching ) {
				delete_transient( $query_cache_key );
			}
			$cached_query = get_transient( $query_cache_key );

			if ( false === $cached_query ) {
				$query             = Parser::parse( new Source( $query ?? '', 'GraphQL' ) );
				$validation_errors = DocumentValidator::validate( $schema, $query );
				if ( count( $validation_errors ) ) {
					return new ExecutionResult( null, $validation_errors );
				}

				set_transient( $query_cache_key, $query, 60 );
			} else {
				$query_was_cached = true;
				$query            = $cached_query;
			}
		}

		$field_resolver = function( $objectValue, array $args, $context, ResolveInfo $info ) {
			return $objectValue[ $info->fieldName ];
		};

		return GraphQL::executeQuery(
			$schema, $query,
			null, null, null, null, $field_resolver,
			// Validation rules to use, null means the default set:
			$query_was_cached ? array() : null );
	}

	/**
	 * Run some code and display the execution time.
	 *
	 * @param callable $callback The code to run.
	 * @param string   $action Text to display before the measured time.
	 * @return mixed The return value of the executed code.
	 */
	private static function run_measuring_time( callable $callback, string $action ) {
		$start   = hrtime( true );
		$result  = $callback();
		$end     = hrtime( true );
		$elapsed = ( $end - $start ) / 1e6;
		echo "$action: $elapsed ms\n";
		return $result;
	}

	/**
	 * Retrieve an order together with its billing and shipping addresses, and display the total execution time.
	 *
	 * @param int $order_id The id of the order to retrieve.
	 * @return mixed The retrieved order.
	 */
	public static function retrieve_order( int $order_id ) {
		return self::run_measuring_time(
			function() use ( $order_id ) {
				$retriever = wc_get_container()->get( OrderRetriever::class );
				return array(
					'order'    => $retriever->retrieve_order( $order_id ),
					'billing'  => $retriever->retrieve_address( $order_id, 'billing' ),
					'shipping' => $retriever->retrieve_address( $order_id, 'shipping' ),
				);
			},
			'Execution time'
		);
	}
}
