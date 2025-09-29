<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\StoreApi\RoutesController;
use Automattic\WooCommerce\StoreApi\SchemaController;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Service class that handles hydration of API data for blocks.
 */
class Hydration {
	/**
	 * Instance of the asset data registry.
	 *
	 * @var AssetDataRegistry
	 */
	protected $asset_data_registry;

	/**
	 * Cached notices to restore after hydrating the API.
	 *
	 * @var array
	 */
	protected $cached_store_notices = array();

	/**
	 * Constructor.
	 *
	 * @param AssetDataRegistry $asset_data_registry Instance of the asset data registry.
	 */
	public function __construct( AssetDataRegistry $asset_data_registry ) {
		$this->asset_data_registry = $asset_data_registry;
	}

	/**
	 * Hydrates the asset data registry with data from the API. Disables notices and nonces so requests contain valid
	 * data that is not polluted by the current session.
	 *
	 * @param array $path API paths to hydrate e.g. '/wc/store/v1/cart'.
	 * @return array Response data.
	 */
	public function get_rest_api_response_data( $path = '' ) {
		if ( ! str_starts_with( $path, '/wc/store' ) ) {
			return array();
		}

		// Allow-list only store API routes. No other request can be hydrated for safety.
		$available_routes = StoreApi::container()->get( RoutesController::class )->get_all_routes( 'v1', true );
		$controller_class = $this->match_route_to_handler( $path, $available_routes );

		/**
		 * We disable nonce check to support endpoints such as checkout. The caveat here is that we need to be careful to only support GET requests. No other request type should be processed without nonce check. Additionally, no GET request can modify data as part of hydration request, for example adding items to cart.
		 *
		 * Long term, we should consider validating nonce here, instead of disabling it temporarily.
		 */
		$this->disable_nonce_check();

		$this->cache_store_notices();

		$preloaded_data = array();

		if ( null !== $controller_class ) {
			try {
				$response = $this->get_response_from_controller( $controller_class, $path );
				if ( $response ) {
					$preloaded_data = array(
						'body'    => $response->get_data(),
						'headers' => $response->get_headers(),
					);
				}
			} catch ( \Exception $e ) {
				// This is executing in frontend of the site, a failure in hydration should not stop the site from working.
				wc_get_logger()->warning(
					'Error in hydrating REST API request: ' . $e->getMessage(),
					array(
						'source'    => 'blocks-hydration',
						'data'      => array(
							'path'       => $path,
							'controller' => $controller_class,
						),
						'backtrace' => true,
					)
				);
			}
		} else {
			// Preload the request and add it to the array. It will be $preloaded_requests['path']  and contain 'body' and 'headers'.
			$preloaded_requests = rest_preload_api_request( array(), $path );
			$preloaded_data     = $preloaded_requests[ $path ] ?? array();
		}

		$this->restore_cached_store_notices();
		$this->restore_nonce_check();

		// Returns just the single preloaded request, or an empty array if it doesn't exist.
		return $preloaded_data;
	}

	/**
	 * Helper method to generate GET response from a controller. Also fires the `rest_request_after_callbacks` for backward compatibility.
	 *
	 * @param string $controller_class Controller class FQN that will respond to the request.
	 * @param string $path             Request path regex.
	 *
	 * @return false|mixed|null Response
	 */
	private function get_response_from_controller( $controller_class, $path ) {
		if ( null === $controller_class ) {
			return false;
		}

		$request           = new \WP_REST_Request( 'GET', $path );
		$schema_controller = StoreApi::container()->get( SchemaController::class );
		$controller        = new $controller_class(
			$schema_controller,
			$schema_controller->get( $controller_class::SCHEMA_TYPE, $controller_class::SCHEMA_VERSION )
		);

		$controller_args = is_callable( array( $controller, 'get_args' ) ) ? $controller->get_args() : array();

		if ( empty( $controller_args ) ) {
			return false;
		}

		// Get the handler that responds to read request.
		$handler = current(
			array_filter(
				$controller_args,
				function ( $method_handler ) {
					return is_array( $method_handler ) && isset( $method_handler['methods'] ) && \WP_REST_Server::READABLE === $method_handler['methods'];
				}
			)
		);

		if ( ! $handler ) {
			return false;
		}

		/**
		 * Similar to WP core's `rest_dispatch_request` filter, this allows plugin to override hydrating the request.
		 * Allows backward compatibility with the `rest_dispatch_request` filter by providing the same arguments.
		 *
		 * @since 8.9.0
		 *
		 * @param mixed            $hydration_result Result of the hydration. If not null, this will be used as the response.
		 * @param WP_REST_Request  $request          Request used to generate the response.
		 * @param string           $path             Request path matched for the request..
		 * @param array            $handler          Route handler used for the request.
		 */
		$hydration_result = apply_filters( 'woocommerce_hydration_dispatch_request', null, $request, $path, $handler );

		if ( null !== $hydration_result ) {
			$response = $hydration_result;
		} else {
			$response = call_user_func_array( $handler['callback'], array( $request ) );
		}

		/**
		 * Similar to WP core's `rest_request_after_callbacks` filter, this allows to modify the response after it has been generated.
		 * Allows backward compatibility with the `rest_request_after_callbacks` filter by providing the same arguments.
		 *
		 * @since 8.9.0
		 *
		 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Result to send to the client.
		 *                                                                   Usually a WP_REST_Response or WP_Error.
		 * @param array                                            $handler  Route handler used for the request.
		 * @param WP_REST_Request                                  $request  Request used to generate the response.
		 */
		$response = apply_filters( 'woocommerce_hydration_request_after_callbacks', $response, $handler, $request );

		return $response;
	}

	/**
	 * Inspired from WP core's `match_request_to_handler`, this matches a given path from available route regexes.
	 * However, unlike WP core, this does not check against query params, request method etc.
	 *
	 * @param string $path The path to match.
	 * @param array  $available_routes Available routes in { $regex1 => $contoller_class1, ... } format.
	 *
	 * @return string|null
	 */
	private function match_route_to_handler( $path, $available_routes ) {
		$matched_route = null;
		foreach ( $available_routes as $route_path => $controller ) {
			$match = preg_match( '@^' . $route_path . '$@i', $path );
			if ( $match ) {
				$matched_route = $controller;
				break;
			}
		}
		return $matched_route;
	}

	/**
	 * Disable the nonce check temporarily.
	 */
	protected function disable_nonce_check() {
		add_filter( 'woocommerce_store_api_disable_nonce_check', array( $this, 'disable_nonce_check_callback' ) );
	}

	/**
	 * Callback to disable the nonce check. While we could use `__return_true`, we use a custom named callback so that
	 * we can remove it later without affecting other filters.
	 */
	public function disable_nonce_check_callback() {
		return true;
	}

	/**
	 * Restore the nonce check.
	 */
	protected function restore_nonce_check() {
		remove_filter( 'woocommerce_store_api_disable_nonce_check', array( $this, 'disable_nonce_check_callback' ) );
	}

	/**
	 * Cache notices before hydrating the API if the customer has a session.
	 */
	protected function cache_store_notices() {
		if ( ! did_action( 'woocommerce_init' ) || null === WC()->session ) {
			return;
		}
		$this->cached_store_notices = wc_get_notices();
		wc_clear_notices();
	}

	/**
	 * Restore notices into current session from cache.
	 */
	protected function restore_cached_store_notices() {
		if ( ! did_action( 'woocommerce_init' ) || null === WC()->session ) {
			return;
		}

		wc_set_notices( $this->cached_store_notices );
		$this->cached_store_notices = array();
	}
}
