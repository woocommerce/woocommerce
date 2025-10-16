<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * This trait allows adding transient-based caching capabilities
 * for outputs of REST API endpoints.
 *
 * The minimum setup required is 'use RestApiCache' in the corresponding
 * REST API controller, adding '$this->register_response_cache_hooks();' to __construct,
 * and overriding the get_default_entity_type method. This is enough if:
 *
 * - All the GET endpoints defined in the controller are cacheable
 *   (and none of the non-GET endpoints is cacheable).
 * - The default lifetime for cached entries (see get_cache_ttl)
 *   is acceptable for all the endpoints.
 * - All endpoints return deterministic information
 *   (the returned information is always the same if the request is the same
 *    and the involved entities are the same ones).
 *
 * Note: we define an "entity" as an object that is uniquely identified by an entity type
 *       and entity id pair, and provides information to be included in the response.
 *
 * Further customization will often be needed by overriding the following trait methods in the controller:
 *
 * - extract_entity_ids(): If mutable entity ids don't identify by an 'id' field,
 *   or not all the entities included in the response are mutable.
 * - get_cache_hash_filters(): If the output can be customized via filters before being sent.
 * - get_cache_ttl(): To customize the lifetime of the cache entries.
 * - get_request_uid_info(): To fine-tune which requests/endpoints are to be cached,
 *   and which is the type of entity being cached; or if the request needs to be
 *   uniquely identified by anything else than the route and the query string.
 *   The get_matched_route method can be useful when overriding this method.
 *
 * If entities are mutable and serving stale cached data is unacceptable, the entity
 * modification (or deletion) event must be captured and the cached version of the
 * corresponding entities must be modified. See the EntityVersionsCache class.
 *
 * See WC_REST_Products_Controller for an example of trait usage and customization.
 *
 * @since   10.4.0
 */
trait RestApiCache {
	/**
	 * The instance of EntityVersionsCache to use.
	 *
	 * @var EntityVersionsCache
	 */
	private EntityVersionsCache $entity_versions_cache;

	/**
	 * Register the appropriate hooks.
	 * Call this from the controller's constructor.
	 */
	protected function initialize_output_caching(): void {
		$this->entity_versions_cache = wc_get_container()->get( EntityVersionsCache::class );

		if ( ! $this->entity_versions_cache->is_enabled() ) {
			return;
		}

		/**
		 * Filter whether to enable response caching for a given REST API controller.
		 *
		 * @since 10.4.0
		 *
		 * @param bool   $enable_hooks Whether to enable response caching for the controller.
		 * @param object $controller   The controller instance.
		 * @return bool True to enable response caching, false to disable.
		 */
		$enable_caching = apply_filters(
			'woocommerce_rest_api_enable_response_caching',
			true,
			$this
		);
		if ( ! $enable_caching ) {
			return;
		}

		add_filter( 'rest_pre_dispatch', array( $this, 'handle_rest_pre_dispatch' ), 10, 3 );
		add_filter( 'rest_post_dispatch', array( $this, 'handle_rest_post_dispatch' ), 10, 3 );
	}

	/**
	 * Get the matched route pattern for the current request.
	 *
	 * This returns the route pattern that was matched (e.g., '/wc/v3/products/(?P<id>[\d]+)')
	 * rather than the actual route (e.g., '/wc/v3/products/123').
	 * Useful for determining which endpoint was matched without actually parsing the route
	 * of the current request.
	 *
	 * @param WP_REST_Request $request Current request object.
	 * @return string|null Matched route pattern or null if none of the routes match.
	 */
	protected function get_matched_route( WP_REST_Request $request ): ?string {
		$route  = $request->get_route();
		$routes = rest_get_server()->get_routes();

		foreach ( $routes as $pattern => $handlers ) {
			if ( preg_match( '@^' . $pattern . '$@i', $route ) ) {
				return $pattern;
			}
		}

		return null;
	}

	/**
	 * Get the default entity type for caching.
	 *
	 * It's mandatory to override this method in controllers that include the trait,
	 * except if get_request_uid_info is also overridden to always provide an entity type
	 *
	 * @return string|null Entity type (e.g., 'product', 'variation'), or null if no default.
	 */
	protected function get_default_entity_type(): ?string {
		return null;
	}

	/**
	 * Get the information that uniquely identifies a request for caching purposes.
	 *
	 * Override this method in controllers in these cases:
	 *
	 * - Not all GET requests are to be cached.
	 * - Non-GET requests are to be cached.
	 * - The requests needs to be uniquely identified by something other than
	 *   route and query string.
	 * - Entity types other than the default one are involved.
	 *
	 * Return null to skip caching for this request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|null Array with 'request_hash' (string) and 'entity_type' (string), or null to skip caching.
	 */
	protected function get_request_uid_info( WP_REST_Request $request ): ?array {
		if ( ! $this->route_belongs_to_this_controller( $request ) || $request->get_method() !== 'GET' ) {
			return null;
		}

		$uid_info = array(
			'request_hash' => md5( $request->get_route() . '-' . wp_json_encode( $request->get_query_params() ) ),
			'entity_type'  => $this->get_default_entity_type(),
		);

		/**
		 * Filter the request UID information for REST API caching.
		 *
		 * Allows customization of the cache key and entity type for a request.
		 * Return null to skip caching for the current request.
		 *
		 * @since 10.4.0
		 *
		 * @param array|null      $uid_info  Array with 'request_hash' and 'entity_type', or null to skip caching.
		 * @param WP_REST_Request $request   Request object.
		 * @param object          $controller Controller instance.
		 */
		return apply_filters( 'woocommerce_rest_api_request_uid_info', $uid_info, $request, $this );
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Check if the current request route belongs to this controller.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True if route belongs to this controller, false otherwise.
	 */
	protected function route_belongs_to_this_controller( WP_REST_Request $request ): bool {
		$matched_route = $this->get_matched_route( $request );
		if ( ! $matched_route ) {
			return false;
		}

		$routes = rest_get_server()->get_routes();
		if ( ! isset( $routes[ $matched_route ] ) ) {
			return false;
		}

		$handlers = $routes[ $matched_route ];

		foreach ( $handlers as $handler ) {
			$callback = $handler['callback'] ?? null;
			if ( is_array( $callback ) && ( $callback[0] ?? null ) === $this ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the names of the filters that can customize the response for a given request
	 * before it's sent (likely fired in the prepare_object_for_response method).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of filter names.
	 */
	protected function get_cache_hash_filters( WP_REST_Request $request ): array {
		return array();
	}

	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Extract entity IDs from response data.
	 *
	 * This implementation assumes that the response is either an array with an 'id' field,
	 * or an array of arrays each having an 'id' field; and that all of these ids
	 * correspond to mutable entities. If that's not the case this method needs to be overridden.
	 *
	 * @param array $data Response data.
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids( array $data ): array {
		$ids = array();

		if ( isset( $data[0] ) ) {
			foreach ( $data as $item ) {
				if ( isset( $item['id'] ) ) {
					$ids[] = $item['id'];
				}
			}
		} elseif ( isset( $data['id'] ) ) {
			$ids[] = $data['id'];
		}

		return array_unique( array_filter( $ids ) );
	}

	/**
	 * Get the TTL for cached responses in seconds.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return int Cache TTL in seconds.
	 */
	protected function get_cached_output_ttl( WP_REST_Request $request ): int {
		/**
		 * Filter the cache TTL for REST API responses.
		 *
		 * @since 10.4.0
		 *
		 * @param int             $ttl        Cache TTL in seconds. Default is one hour (3600 seconds).
		 * @param WP_REST_Request $request    Request object.
		 * @param object          $controller Controller instance.
		 */
		return apply_filters( 'woocommerce_rest_api_cached_output_ttl', HOUR_IN_SECONDS, $request, $this );
	}

	/**
	 * Generate a hash based on the filters that affect the response.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return string Hooks hash.
	 */
	protected function generate_hooks_hash( WP_REST_Request $request ): string {
		global $wp_filter;

		$cache_hash_data = array();
		$filter_names    = $this->get_cache_hash_filters( $request );

		foreach ( $filter_names as $filter_name ) {
			if ( ! empty( $wp_filter[ $filter_name ] ) ) {
				$cache_hash_data[ $filter_name ] = array();

				foreach ( $wp_filter[ $filter_name ] as $priority => $callbacks ) {
					$cache_hash_data[ $filter_name ][ $priority ] = array_values(
						wp_list_pluck( $callbacks, 'function' )
					);
				}
			}
		}

		/**
		 * Filter the data used to generate the hooks hash for the purposes of caching REST API responses.
		 *
		 * @since 10.4.0
		 *
		 * @param array           $cache_hash_data Hook callbacks data used for hash generation.
		 * @param WP_REST_Request $request         Request object.
		 * @param object          $controller      Controller instance.
		 */
		$cache_hash_data = apply_filters(
			'woocommerce_rest_api_cache_hooks_hash_data',
			$cache_hash_data,
			$request,
			$this
		);

		return md5( wp_json_encode( $cache_hash_data ) );
	}

	/**
	 * Handle the rest_pre_dispatch action to return a cached response if appropriate.
	 *
	 * @internal
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return WP_REST_Response|null Response, or null to use the original response.
	 */
	public function handle_rest_pre_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( $request->get_param( '_skip_cache' ) === 'true' ) {
			return null;
		}

		// Skip if another handler already returned a result.
		if ( ! is_null( $result ) ) {
			return $result;
		}

		// Skip if another controller already handled caching.
		if ( $request->get_param( '_cache_uid_info' ) ) {
			return $result;
		}

		$uid_info = $this->get_request_uid_info( $request );
		if ( ! $uid_info ) {
			return null;
		}

		$entity_type = $uid_info['entity_type'] ?? null;
		if ( ! $entity_type ) {
			wc_doing_it_wrong(
				__METHOD__,
				'Request is cacheable but no entity type is provided. Override either get_default_entity_type or get_request_uid_info in the controller.',
				'10.4.0'
			);
			return null;
		}

		// Store request UID info for post-dispatch.
		$uid_info['controller_class'] = get_class( $this );
		$request->set_param( '_cache_uid_info', $uid_info );

		// See if we have a usable cached response.
		$transient_key = "wc_rest_api_cache_{$entity_type}-{$uid_info['request_hash']}";
		$cached        = $this->get_cached_data( $transient_key );

		if ( ! $cached || ! isset( $cached['hooks_hash'], $cached['data'], $cached['created_at'], $cached['entity_versions'] ) ) {
			return null;
		}

		$current_time    = time();
		$expiration_time = $cached['created_at'] + $this->get_cached_output_ttl( $request );
		if ( $current_time >= $expiration_time ) {
			// Entry has expired (TTL is now lower than the lifetime of the transient).
			$this->delete_cached_data( $transient_key );
			return null;
		}

		$current_hooks_hash = $this->generate_hooks_hash( $request );
		if ( $cached['hooks_hash'] !== $current_hooks_hash ) {
			// Hooks have changed - invalidate cache entry.
			$this->delete_cached_data( $transient_key );
			return null;
		}

		// Validate versions for all the entities involved in the cached response:
		// if any has changed, the entire cached response is invalid.
		foreach ( $cached['entity_versions'] as $entity_id => $cached_version ) {
			$current_version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( is_null( $current_version ) || $current_version !== $cached_version ) {
				$this->delete_cached_data( $transient_key );
				return null;
			}
		}

		// If we reach this point the cached response is valid.
		return new WP_REST_Response( $cached['data'], 200, array( 'X-WC-Cache' => 'HIT' ) );
	}

	/**
	 * Handle the rest_post_dispatch filter to possibly cache the response
	 * after all the hooks that can modify it have run.
	 *
	 * @internal
	 *
	 * @param WP_REST_Response $response Result to send to the client.
	 * @param WP_REST_Server   $server   Server instance.
	 * @param WP_REST_Request  $request  Request used to generate the response.
	 * @return WP_REST_Response Response object.
	 */
	public function handle_rest_post_dispatch( WP_REST_Response $response, WP_REST_Server $server, WP_REST_Request $request ): WP_REST_Response {
		if ( $request->get_param( '_skip_cache' ) === 'true' ) {
			$response->header( 'X-WC-Cache', 'SKIP' );
			return $response;
		}

		// Only handle successful requests.
		$status = $response->get_status();
		if ( $status < 200 || $status > 299 ) {
			return $response;
		}

		$uid_info = $request->get_param( '_cache_uid_info' );
		if ( ! $uid_info ) {
			// Pre-dispatch didn't set UID info, so this request doesn't use caching.
			return $response;
		}

		if ( ! isset( $uid_info['controller_class'] ) || get_class( $this ) !== $uid_info['controller_class'] ) {
			// Pre-dispatch was handled by a different controller.
			return $response;
		}

		$headers = $response->get_headers();
		if ( isset( $headers['X-WC-Cache'] ) ) {
			// Pre-dispatch already set the response from cache.
			return $response;
		}

		$data = $response->get_data();

		// If we reach this point we're going to cache the response
		// and then send it.

		$entity_ids      = $this->extract_entity_ids( $data );
		$entity_type     = $uid_info['entity_type'];
		$entity_versions = array();
		foreach ( $entity_ids as $entity_id ) {
			$version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( ! is_null( $version ) ) {
				$entity_versions[ $entity_id ] = $version;
			}
		}

		$cache_data = array(
			'entity_type'     => $entity_type,
			'created_at'      => time(),
			'hooks_hash'      => $this->generate_hooks_hash( $request ),
			'data'            => $data,
			'entity_versions' => $entity_versions,
		);

		$transient_key = "wc_rest_api_cache_{$entity_type}-{$uid_info['request_hash']}";
		$this->set_cached_data( $transient_key, $cache_data, $this->get_cached_output_ttl( $request ) );

		$request->set_param( '_cache_uid_info', null );

		$response->header( 'X-WC-Cache', 'MISS' );
		return $response;
	}

	/**
	 * Get cached data from persistent storage.
	 *
	 * @param string $key Storage key.
	 * @return mixed Cached value or false if not found.
	 */
	protected function get_cached_data( string $key ) {
		return get_transient( $key );
	}

	/**
	 * Set cached data in persistent storage.
	 *
	 * @param string $key   Storage key.
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Time to live in seconds.
	 * @return bool True on success, false on failure.
	 */
	protected function set_cached_data( string $key, $value, int $ttl ): bool {
		return set_transient( $key, $value, $ttl );
	}

	/**
	 * Delete cached data from persistent storage.
	 *
	 * @param string $key Storage key.
	 * @return bool True on success, false on failure.
	 */
	protected function delete_cached_data( string $key ): bool {
		return delete_transient( $key );
	}
}
