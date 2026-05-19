<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Api;

/**
 * Deferred-registration helper for GraphQL endpoints declared via
 * {@see Main::register_graphql_endpoint()}.
 *
 * Each instance captures the arguments of a single registration request and
 * exposes {@see self::handle_rest_api_init()} as the callback target for the
 * rest_api_init action, so Main doesn't have to carry per-registration state
 * through a closure.
 */
class GraphQLEndpointRegistrar {
	/**
	 * Capture the arguments of a single register_graphql_endpoint() call.
	 *
	 * @param string   $controller_class_name Fully-qualified name of a concrete GraphQLController subclass.
	 * @param string   $route_namespace       REST namespace passed to register_rest_route().
	 * @param string   $route                 REST route path passed to register_rest_route().
	 * @param string[] $methods               HTTP methods accepted on the endpoint.
	 */
	public function __construct(
		private readonly string $controller_class_name,
		private readonly string $route_namespace,
		private readonly string $route,
		private readonly array $methods
	) {}

	/**
	 * Hook callback for rest_api_init. Instantiates the controller and
	 * registers the REST route.
	 *
	 * The caller-declared methods are narrowed by
	 * {@see Main::filter_methods_against_settings()} so plugin endpoints honour
	 * the same site-wide settings (e.g. the GET-endpoint toggle) as
	 * WooCommerce core's `/wc/graphql`. If the filter empties the list the
	 * endpoint is not registered.
	 */
	public function handle_rest_api_init(): void {
		$methods = Main::filter_methods_against_settings( $this->methods );
		if ( empty( $methods ) ) {
			return;
		}

		$controller = Main::instantiate_graphql_controller( $this->controller_class_name );
		if ( null === $controller ) {
			return;
		}

		register_rest_route(
			$this->route_namespace,
			$this->route,
			array(
				'methods'             => $methods,
				'callback'            => array( $controller, 'handle_request' ),
				// Auth is handled per-query/mutation.
				'permission_callback' => '__return_true',
			)
		);
	}
}
