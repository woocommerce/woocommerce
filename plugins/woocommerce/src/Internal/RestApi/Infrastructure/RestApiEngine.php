<?php
/**
 * WooCommerce REST API v4 engine class file.
 *
 * THIS IS AN AUTOMATICALLY GENERATED FILE. Do not make changes directly to this file.
 * Instead, make any necessary changes to bin/RestApiEngineTemplate.php.
 *
 * @package WooCommerce
 */

namespace Automattic\WooCommerce\Internal\RestApi\Infrastructure;

/**
 * WooCommerce REST API v4 engine class.
 */
class RestApiEngine {

	/**
	 * Initialize the REST API engine, needs to be invoked during WooCommerce initialization.
	 */
	public static function initialize() {
		add_action(
			'rest_api_init',
			function () {

				register_rest_route(
					'wc/v4',
					'/orders/(?<id>[\d]+)',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\OrdersController', 'get_order', $request, '', true ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/orders/(?<id>[\d]+)/notes',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\OrdersController', 'get_order_notes', $request, '', true ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/orders/(?<id>[\d]+)/notes',
					array(
						'methods'             => 'POST',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\OrdersController', 'add_order_note', $request, '', true ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/orders/hpos_is_active',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\OrdersController', 'get_hpos_is_active', $request, 'administrator', true ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/test/admins_only',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\TestController', 'administrator_only', $request, 'administrator', false ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/test/welcome',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\TestController', 'welcome', $request, '', false ),
						'permission_callback' => '__return_true',
					)
				);

				register_rest_route(
					'wc/v4',
					'/test/',
					array(
						'methods'             => 'GET',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\TestController', 'root', $request, '', false ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				register_rest_route(
					'wc/v4',
					'/test/echo',
					array(
						'methods'             => 'POST',
						'callback'            => fn( \WP_REST_Request $request ) =>
							static::safe_invoke( 'Automattic\WooCommerce\Internal\RestApi\Controller\TestController', 'echo', $request, '', false ),
						'permission_callback' => fn() => get_current_user_id() !== 0,
					)
				);

				/**
				 * Filter to add additional controller classes with endpoint methods to the REST API.
				 */
				$additional_endpoints = apply_filters( 'wc_rest_api_v4_additional_endpoints', array() );

				foreach ( $additional_endpoints as $additional_endpoint_info ) {
					$has_init = $additional_endpoint_info['has_init'] ?? false;
					foreach ( $additional_endpoint_info['endpoints'] as $endpoint ) {
						$allowed_roles          = $endpoint['allowed_roles'] ?? '';
						$allows_unauthenticated = $endpoint['allows_unauthenticated'] ?? false;

						register_rest_route(
							'wc/v4',
							$endpoint['route'],
							array(
								'methods'             => $endpoint['verbs'],
								'callback'            => fn ( \WP_REST_Request $request) =>
								static::safe_invoke( $additional_endpoint_info['class_name'], $endpoint['method_name'], $request, $allowed_roles, $has_init ),
								'permission_callback' => $allows_unauthenticated ? '__return_true' : fn() => get_current_user_id() !== 0,
							)
						);
					}
				}
			}
		);
	}

	/**
	 * Invoke a method in a REST API controller, converting any unhandled exception
	 * into a suitable HTTP response.
	 *
	 * @param string           $class Controller class name.
	 * @param string           $method Endpoint class method name.
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @param string           $allowed_roles Comma-separated list of user roles allowed to process the request.
	 * @param bool             $invoke_init True if the "init" method of the controller class must be invoked.
	 * @return \WP_Error|\WP_REST_Response
	 */
	private static function safe_invoke( $class, $method, $request, $allowed_roles, $invoke_init ) {
		try {
			$response = self::invoke( $class, $method, $request, $allowed_roles, $invoke_init );

			/**
			 * Filter fired before processing the REST API response.
			 *
			 * @param \WP_REST_Request $response The original response to send.
			 */
			$response = apply_filters( 'wc_rest_api_v4_response', $response, $request );
		} catch ( \Exception $ex ) {
			if ( property_exists( $ex, 'rest_response' ) ) {
				$response = $ex->rest_response;
			} else {
				$response = Responses::internal_server_error();
			}
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Invoke a method in a REST API controller, without handling any exception.
	 *
	 * @param string           $class Controller class name.
	 * @param string           $method Endpoint class method name.
	 * @param \WP_REST_Request $request Incoming REST request.
	 * @param string           $allowed_roles Comma-separated list of user roles allowed to process the request.
	 * @param bool             $invoke_init True if the "init" method of the controller class must be invoked.
	 * @return \WP_Error|\WP_REST_Response
	 */
	private static function invoke( $class, $method, &$request, $allowed_roles, $invoke_init ) {
		$user_id = get_current_user_id();
		$user    = $user_id ? get_user_by( 'id', $user_id ) : null;

		/**
		 * Action fired before processing the request.
		 *
		 * @param \WP_REST_Request $request The request to process.
		 */
		do_action( 'wc_rest_api_v4_request', $request, $user );

		if ( $user && $allowed_roles ) {
			$allowed_roles_array = explode( ',', $allowed_roles );
			if ( empty( array_intersect( $allowed_roles_array, $user->roles ) ) ) {
				return Responses::unauthorized();
			}
		}

		if ( $invoke_init ) {
			$class::init( $request, $user );
		}

		$result = $class::$method( $request, $user );
		return rest_ensure_response( $result );
	}
}
