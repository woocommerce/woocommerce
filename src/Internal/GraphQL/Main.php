<?php

namespace Automattic\WooCommerce\Internal\GraphQL;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Debug;

defined( 'ABSPATH' ) || exit;

/**
 * The main entry point class for the GraphQL API.
 *
 * The GraphQL API is enabled when the "woocommerce_graphql_api_enabled" option has a value of "yes",
 * this option can be set via UI from Woo settings - Advanced - GraphQL API.
 *
 * Once "initialize" is called the API is available via POST at endpoint /wp-json/wc/graphql/api.
 * Authorization is done in the "RootQueryType" and "RootMutationType" classes.
 *
 * Request bodies must consist of a JSON object having a "query" key and optionally a "variables" key.
 *
 * By default unexpected exceptions (those that are not ApiException) will be returned as a generic
 * "Internal server error" message, see "get_debug_config" method for how to enable verbose errors mode.
 */
class Main {

	/**
	 * An instance of the dependency injection container.
	 *
	 * @var \Psr\Container\ContainerInterface
	 */
	private static $container;

	/**
	 * Namespaces (relative to the one of this class) that contain GraphQL related classes
	 * to be resolved via dependency injection container.
	 *
	 * @var string List of namespaces.
	 */
	private static $subnamespaces = array( 'EnumTypes', 'QueryTypes', 'MutationTypes', 'InputTypes' );

	/**
	 * Initialize the GraphQL API entry point.
	 */
	public static function initialize() {
		if ( 'yes' !== get_option( 'woocommerce_graphql_api_enabled' ) ) {
			return;
		}

		self::$container = wc_get_container();

		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'wc/graphql',
					'api',
					array(
						'methods'             => 'POST',
						'callback'            => function( $request ) {
							return call_user_func( self::class . '::handle_request', $request );
						},
						'permission_callback' => '__return_true',
					)
				);
			}
		);
	}

	/**
	 * Get an instance of a class given its non-namespaced name.
	 *
	 * @param string $name Non-namespaced ame of the class to get an instance from.
	 * @return mixed Instance of the requested class.
	 * @throws \Exception The type can't be resolved.
	 */
	public static function resolve_type( $name ) {
		foreach ( self::$subnamespaces as $namespace ) {
			$full_name = __NAMESPACE__ . '\\' . $namespace . '\\' . $name;
			if ( self::$container->has( $full_name ) ) {
				return self::$container->get( $full_name );
			}
		}
		throw new \Exception( "There's no way to resolve the type '" . $name . "'." );
	}

	/**
	 * Handle a HTTP request for the GraphQL API entry point.
	 *
	 * @param \WP_REST_Request $request Request to handle.
	 * @return array|\WP_REST_Response Response to return to the client.
	 * @throws ApiException Does not actually throw any exception, but phpcs needs this comment.
	 */
	private static function handle_request( \WP_REST_Request $request ) {
		try {
			$input = json_decode( $request->get_body(), true );
			if ( is_null( $input ) ) {
				// Should never happen since WP REST engine already catches invalid JSON errors, but it doesn't hurt to check it anyway.
				return self::error_response( 'Invalid input JSON: ' . json_last_error_msg(), 'rest_invalid_json', 400 );
			}
			if ( ! isset( $input['query'] ) ) {
				return self::error_response( "Invalid input JSON: no 'query' element", 'rest_invalid_json', 400 );
			}

			$query           = $input['query'];
			$variable_values = isset( $input['variables'] ) ? $input['variables'] : null;

			$schema = new Schema(
				array(
					'query'      => self::$container->get( RootQueryType::class ),
					'mutation'   => self::$container->get( RootMutationType::class ),
					'typeLoader' => function( $name ) {
						return self::resolve_type( $name );
					},
				)
			);

			$result = GraphQL::executeQuery( $schema, $query, null, null, $variable_values );
			return $result->toArray( self::get_debug_config() );
		} catch ( \Exception $e ) {
			return self::error_response( $e, 'internal_server_error', 500 );
		}
	}

	/**
	 * Generate an instance of WP_Error to return as the response.
	 *
	 * @param mixed  $error An error message string or an exception.
	 * @param string $code Error code for WP_Error.
	 * @param int    $status_code HTTP status code for the response.
	 * @return \WP_Error The generated error.
	 */
	private static function error_response( $error, $code, $status_code ) {
		if ( is_string( $error ) ) {
			return new \WP_Error( $code, $error, array( 'status' => $status_code ) );
		} elseif ( self::get_debug_config() ) {
			return new \WP_Error(
				$code,
				$error->getMessage(),
				array(
					'status' => $status_code,
					'trace'  => $error->getTrace(),
				)
			);
		} else {
			return new \WP_Error( $code, 'Internal server error', array( 'status' => $status_code ) );
		}
	}

	/**
	 * Get debug flags for GraphQL::executeQuery()->toArray.
	 *
	 * When debug flags are returned error output will always include the exception error message
	 * (even for exceptions that are not ApiException) and the output will also include
	 * a full stack trace.
	 *
	 * Debug flags are returned when "?verbose_errors" is added to the query string AND
	 * either the user is in the "administrator" role OR the WP_DEBUG constant is set.
	 *
	 * @return false|int Debug flags or false.
	 */
	private static function get_debug_config() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['verbose_errors'] ) ) {
			return false;
		}

		if ( wc_current_user_has_role( 'administrator' ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE;
		}

		return false;
	}
}
