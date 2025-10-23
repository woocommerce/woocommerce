<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This trait provides transient-based caching capabilities for REST API endpoints
 * (see "why transients?" at the end of this comment).
 *
 * - The output of all the REST API endpoints whose callback declaration is wrapped
 *   in a call to 'with_cache' will be cached using transients.
 * - For the purposes of caching, a request is uniquely identified by its route
 *   and query string.
 * - The EntityVersionsCache class is used to track versions of entities included
 *   in the responses (an "entity" is any object that is uniquely identified by type and id
 *   and contributes with information to be included in the response),
 *   so that when those entities change, the relevant cached responses become invalid.
 *   Modification of entity versions must be done externally by the code that modifies
 *   those entities (via calls to EntityVersionsCache::modify_entity_version).
 * - Various parameters (cached outputs TTL, entity type for a given response, extraction
 *   of entity ids from the response data, filters that affect the response) can be configured
 *   globally for the controller (via overriding protected methods) or per-endpoint
 *   (via arguments passed to with_cache).
 * - Caching can be disabled for a given request by adding a '_skip_cache=true|1'
 *   to the query string.
 * - A X-WC-Cache HTTP header is added to responses to indicate cache status:
 *   HIT, MISS, or SKIP.
 *
 * IMPORTANT: Caching only occurs when EntityVersionsCache::is_enabled() returns true,
 * and by default this only happens when object caching is enabled site-wide
 * (configurable via the 'woocommerce_enable_object_cache' hook).
 *
 * Required setup:
 *
 * 1. `use RestApiCache;` in the controller class.
 * 1. Call `$this->initialize_rest_api_cache()` in the controller's constructor.
 * 2. Either override `get_default_entity_type()` to return the default entity type
 *    associated to all endpoints (e.g., 'product', 'order'),  OR specify 'entity_type'
 *    in the config array for every `with_cache()` call.
 *
 * Usage: Wrap endpoint callbacks with the `with_cache()` method when registering routes,
 * so `'callback' => $this->with_cache( array( $this, 'get_item' ), [ ...config... ] )`.
 *
 * Example:
 *
 * class WC_REST_Products_Controller extends WC_REST_Products_V2_Controller {
 *     use RestApiCache;
 *
 *     public function __construct() {
 *         parent::__construct();
 *         $this->initialize_rest_api_cache();  // REQUIRED
 *     }
 *
 *     protected function get_default_entity_type(): ?string {
 *         return 'product';  // REQUIRED (or specify entity_type in each with_cache call)
 *     }
 *
 *     public function register_routes() {
 *         register_rest_route(
 *             $this->namespace,
 *             '/' . $this->rest_base . '/(?P<id>[\d]+)',
 *             array(
 *                 'methods'  => WP_REST_Server::READABLE,
 *                 'callback' => $this->with_cache(
 *                     array( $this, 'get_item' ),
 *                     array(
 *                         // String, optional if get_default_entity_type() is overridden.
 *                         'entity_type'        => 'product',
 *                         // Optional callback, defaults to the controller's get_cache_ttl().
 *                         'cache_ttl'          => HOUR_IN_SECONDS,
 *                         // Optional callback, defaults to the controller's extract_entity_ids().
 *                         'extract_entity_ids' => array( $this, 'custom_extract_ids' ),
 *                         // Optional callback, defaults to the controller's must_cache().
 *                         'must_cache'         => array( $this, 'should_cache' ),
 *                         // Optional array, defaults to the controller's get_hooks_relevant_to_caching().
 *                         'relevant_hooks'     => array( 'filter_name_1', 'filter_name_2' ),
 *                     )
 *                 ),
 *             )
 *         );
 *     }
 * }
 *
 * Note: we define an "entity" as an object that is uniquely identified by an entity type
 *       and entity id pair, and provides information to be included in the response.
 *
 * Override these methods in your controller as needed:
 * - get_default_entity_type(): Default entity type for endpoints without explicit config.
 * - get_relevant_filters(): Filter names to track for cache invalidation.
 * - get_cache_ttl(): TTL for cached outputs in seconds.
 * - must_cache(): Whether to cache a specific request or not.
 * - extract_entity_ids(): Extract entity IDs from response data.
 *
 * Cache invalidation happens when:
 * - Entity versions change (tracked via EntityVersionsCache).
 * - Filter callbacks change
 *   (if the `get_hooks_relevant_to_caching()` call result or the 'relevant_hooks' array isn't empty).
 * - Cached response TTL expires.
 *
 * NOTE: Why transients and not the WordPress global cache?
 *
 * Using transients for this kind of cache implementation in a site without a proper persistent global cache
 * configured is a bad idea, as it can lead to performance issues since as transients are stored directly
 * in the database. However, if a global cache is in place, transients will be stored using it, making
 * the usage of transients functionally equivalent to using the WordPress global cache.
 *
 * By default this caching mechanism is only enabled when an external object cache is enabled
 * (checked via call to EntityVersionsCache::is_enabled()), so database-backed transients won't be used.
 * However it's still possible to force-enable it via the 'woocommerce_enable_entity_versions_cache' filter,
 * and this can be useful in scenarios without a global cache in place for testing, experimenting, troubleshooting,
 * staging, and development purposes.
 *
 * @since   10.4.0
 */
trait RestApiCache {
	/**
	 * The instance of EntityVersionsCache to use, or null if caching is disabled.
	 *
	 * @var EntityVersionsCache|null
	 */
	private ?EntityVersionsCache $entity_versions_cache;

	/**
	 * Initialize the entity versions cache.
	 * This MUST be called from the controller's constructor.
	 */
	protected function initialize_rest_api_cache(): void {
		$cache                       = wc_get_container()->get( EntityVersionsCache::class );
		$this->entity_versions_cache = $cache->is_enabled() ? $cache : null;
	}

	/**
	 * Wrap an endpoint callback declaration with caching logic.
	 * Usage: `'callback' => $this->with_cache( array( $this, 'endpoint_callback_method' ), [ ...config... ] )`
	 *
	 * @param callable $callback The original endpoint callback.
	 * @param array    $config   Caching configuration:
	 *                           - entity_type: string (falls back to get_default_entity_type()).
	 *                           - cache_ttl: int (defaults to HOUR_IN_SECONDS).
	 *                           - extract_entity_ids: callable (defaults to $this->extract_entity_ids).
	 *                           - must_cache: callable (defaults to $this->must_cache).
	 *                           - relevant_hooks: array (defaults to $this->get_relevant_hooks()).
	 * @return callable Wrapped callback.
	 */
	protected function with_cache( callable $callback, array $config = array() ): callable {
		return fn( $request ) => $this->handle_cacheable_request( $request, $callback, $config );
	}

	/**
	 * Handle a request with caching logic.
	 *
	 * Strategy: Try to use cached response if available and valid, otherwise execute the endpoint
	 * callback and cache the response (if successful) for future requests.
	 *
	 * @param WP_REST_Request $request  The request object.
	 * @param callable        $callback The original endpoint callback.
	 * @param array           $config   Caching configuration specified for the endpoint.
	 * @return WP_REST_Response|\WP_Error The response.
	 */
	protected function handle_cacheable_request( WP_REST_Request $request, callable $callback, array $config ) {
		if ( ! $this->should_use_cache_for_request( $request, $config ) ) {
			$response = call_user_func( $callback, $request );
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		$cached_config = $this->build_cache_config( $request, $config );
		if ( is_null( $cached_config ) ) {
			$response = call_user_func( $callback, $request );
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		$cached_response = $this->get_cached_response( $request, $config );

		if ( $cached_response ) {
			$cached_response->header( 'X-WC-Cache', 'HIT' );
			return $cached_response;
		}

		$authoritative_response = call_user_func( $callback, $request );

		return $this->maybe_cache_response( $request, $authoritative_response, $config );
	}

	/**
	 * Check if caching should be used for a particular incoming request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Caching configuration.
	 * @return bool True if caching should be used, false otherwise.
	 */
	private function should_use_cache_for_request( WP_REST_Request $request, array $config ): bool {
		if ( is_null( $this->entity_versions_cache ) ) {
			return false;
		}

		// Check for explicit skip parameter.
		$skip_cache   = $request->get_param( '_skip_cache' );
		$should_cache = ! ( 'true' === $skip_cache || '1' === $skip_cache );

		// Check custom must_cache callback.
		if ( $should_cache ) {
			$must_cache_fn = $config['must_cache'] ?? array( $this, 'must_cache' );
			$should_cache  = call_user_func( $must_cache_fn, $request );
		}

		/**
		 * Filter whether to enable response caching for a given REST API controller.
		 *
		 * Based on the _skip_cache parameter and must_cache callback evaluation,
		 * the first parameter indicates whether caching should be enabled.
		 * This filter can override that decision.
		 *
		 * @since 10.4.0
		 *
		 * @param bool            $enable_caching Whether to enable response caching (result of !_skip_cache && must_cache evaluation).
		 * @param object          $controller     The controller instance.
		 * @param WP_REST_Request $request        The request object.
		 * @return bool True to enable response caching, false to disable.
		 */
		return apply_filters(
			'woocommerce_rest_api_enable_response_caching',
			$should_cache,
			$this,
			$request
		);
	}

	/**
	 * Build the output cache entry configuration from the request and per-endpoint config.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Raw configuration array passed to with_cache.
	 * @return array|null Normalized cache config with keys: entity_type, cache_ttl, extract_entity_ids, relevant_hooks, cache_key. Returns null if entity type is not available.
	 */
	private function build_cache_config( WP_REST_Request $request, array $config ): ?array {
		$entity_type = $config['entity_type'] ?? $this->get_default_entity_type();

		if ( ! $entity_type ) {
			wc_get_container()->get( LegacyProxy::class )->call_function(
				'wc_doing_it_wrong',
				__METHOD__,
				'No entity type provided and no default entity type available. Skipping cache.',
				'10.4.0'
			);
			return null;
		}

		return array(
			'entity_type'        => $entity_type,
			'cache_ttl'          => $config['cache_ttl'] ?? $this->get_cache_ttl( $request ),
			'extract_entity_ids' => $config['extract_entity_ids'] ?? array( $this, 'extract_entity_ids' ),
			'relevant_hooks'     => $config['relevant_hooks'] ?? $this->get_hooks_relevant_to_caching( $request ),
			'cache_key'          => $this->get_cache_key( $request, $entity_type ),
		);
	}

	/**
	 * Cache the response if it's successful and return it with appropriate headers.
	 *
	 * Only caches responses with 2xx status codes. Always adds the X-WC-Cache header
	 * with value MISS if the response was cached, or SKIP if it was not cached.
	 *
	 * @param WP_REST_Request            $request  The request object.
	 * @param WP_REST_Response|\WP_Error $response The response to potentially cache.
	 * @param array                      $config   Caching configuration.
	 * @return WP_REST_Response|\WP_Error The response with appropriate cache headers.
	 */
	private function maybe_cache_response( WP_REST_Request $request, $response, array $config ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$cached = false;

		$status = $response->get_status();
		if ( $status >= 200 && $status <= 299 ) {
			$cached_config = $this->build_cache_config( $request, $config );

			if ( ! is_null( $cached_config ) ) {
				$data       = $response->get_data();
				$entity_ids = call_user_func( $cached_config['extract_entity_ids'], $data, $request );

				$this->store_cached_response(
					$cached_config['cache_key'],
					$data,
					$status,
					$cached_config['entity_type'],
					$entity_ids,
					$cached_config['cache_ttl'],
					$cached_config['relevant_hooks']
				);

				$cached = true;
			}
		}

		$response->header( 'X-WC-Cache', $cached ? 'MISS' : 'SKIP' );
		return $response;
	}

	/**
	 * Get the default type for entities included in responses.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('entity_type' key).
	 *
	 * @return string|null Entity type (e.g., 'product', 'order'), or null if no controller-wide default.
	 */
	protected function get_default_entity_type(): ?string {
		return null;
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Get the names of filters that can customize the response.
	 *
	 * All the existing instances of add_action/add_filter for these filters
	 * will be included in the information that gets cached together with the response,
	 * and if any of these has changed when the cached response is retrieved,
	 * the cache entry will be invalidated.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('relevant_hooks' key).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of filter names to track.
	 */
	protected function get_hooks_relevant_to_caching( WP_REST_Request $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array();
	}

	/**
	 * Get the TTL (Time To Live) for a cached response in seconds.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('cache_ttl' key).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return int Cache TTL in seconds.
	 */
	protected function get_cache_ttl( WP_REST_Request $request ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return HOUR_IN_SECONDS;
	}

	/**
	 * Determine whether a given request should be cached.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('must_cache' key).
	 *
	 * Note: Additionally, the _skip_cache query parameter and the woocommerce_rest_api_cache_must_cache filter
	 * can be used to control the enabling of caching for a given request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True to cache the request, false to skip caching.
	 */
	protected function must_cache( WP_REST_Request $request ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return true;
	}

	// phpcs:enable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Extract entity IDs from response data.
	 *
	 * The default implementation assumes the response is either:
	 * - An array with an 'id' field (single item)
	 * - An array of arrays each having an 'id' field (collection)
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('extract_entity_ids' key).
	 *
	 * @param array           $data    Response data.
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids( array $data, WP_REST_Request $request ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$ids = array();

		if ( isset( $data[0] ) && is_array( $data[0] ) ) {
			foreach ( $data as $item ) {
				if ( isset( $item['id'] ) ) {
					$ids[] = $item['id'];
				}
			}
		} elseif ( isset( $data['id'] ) ) {
			$ids[] = $data['id'];
		}

		// Filter out null/false values but keep 0 and empty strings as they could be valid IDs.
		return array_unique(
			array_filter(
				$ids,
				function ( $id ) {
					return ! is_null( $id ) && false !== $id;
				}
			)
		);
	}

	/**
	 * Generate a cache key for a given request.
	 *
	 * @param WP_REST_Request $request     The request object.
	 * @param string          $entity_type The entity type.
	 * @return string Cache key.
	 */
	protected function get_cache_key( WP_REST_Request $request, string $entity_type ): string {
		$request_hash = md5(
			$request->get_route() . '-' . wp_json_encode( $request->get_query_params() )
		);
		return "wc_rest_api_cache_{$entity_type}-{$request_hash}";
	}

	/**
	 * Generate a hash based on the actual usages of the hooks that affect the response.
	 *
	 * @param array $filter_names Array of hook names to track.
	 * @return string Hooks hash.
	 */
	protected function generate_hooks_hash( array $filter_names ): string {
		if ( empty( $filter_names ) ) {
			return '';
		}

		global $wp_filter;

		$cache_hash_data = array();

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
		 * Filter the data used to generate the hooks hash for REST API response caching.
		 *
		 * @since 10.4.0
		 *
		 * @param array  $cache_hash_data Hook callbacks data used for hash generation.
		 * @param array  $filter_names    Filter names being tracked.
		 * @param object $controller      Controller instance.
		 */
		$cache_hash_data = apply_filters(
			'woocommerce_rest_api_cache_hooks_hash_data',
			$cache_hash_data,
			$filter_names,
			$this
		);

		return md5( wp_json_encode( $cache_hash_data ) );
	}

	/**
	 * Get a cached response, but only if it's valid
	 * (otherwise the cached response will be invalidated).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Caching configuration.
	 * @return WP_REST_Response|null Cached response, or null if not available or has been invalidated.
	 */
	protected function get_cached_response( WP_REST_Request $request, array $config ): ?WP_REST_Response {
		$cached_config = $this->build_cache_config( $request, $config );

		if ( is_null( $cached_config ) ) {
			return null;
		}

		$cache_key      = $cached_config['cache_key'];
		$entity_type    = $cached_config['entity_type'];
		$cache_ttl      = $cached_config['cache_ttl'];
		$relevant_hooks = $cached_config['relevant_hooks'];

		$cached = $this->get_cached( $cache_key );

		if ( ! $cached || ! isset( $cached['data'], $cached['entity_versions'], $cached['created_at'] ) ) {
			return null;
		}

		$current_time    = wc_get_container()->get( LegacyProxy::class )->call_function( 'time' );
		$expiration_time = $cached['created_at'] + $cache_ttl;
		if ( $current_time >= $expiration_time ) {
			$this->delete_cached( $cache_key );
			return null;
		}

		if ( ! empty( $relevant_hooks ) ) {
			$current_hooks_hash = $this->generate_hooks_hash( $relevant_hooks );
			$cached_hooks_hash  = $cached['hooks_hash'] ?? '';

			if ( $current_hooks_hash !== $cached_hooks_hash ) {
				$this->delete_cached( $cache_key );
				return null;
			}
		}

		foreach ( $cached['entity_versions'] as $entity_id => $cached_version ) {
			$current_version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( $current_version !== $cached_version ) {
				$this->delete_cached( $cache_key );
				return null;
			}
		}

		// In this point the cached response is valid.
		$status_code = $cached['status_code'] ?? 200;
		return new WP_REST_Response( $cached['data'], $status_code );
	}

	/**
	 * Store a response in cache.
	 *
	 * @param string $cache_key        The cache key.
	 * @param array  $data             The response data to cache.
	 * @param int    $status_code      The HTTP status code of the response.
	 * @param string $entity_type      The entity type.
	 * @param array  $entity_ids       Array of entity IDs in the response.
	 * @param int    $cache_ttl        Cache TTL in seconds.
	 * @param array  $relevant_hooks   Hook names to track for invalidation.
	 */
	protected function store_cached_response( string $cache_key, array $data, int $status_code, string $entity_type, array $entity_ids, int $cache_ttl, array $relevant_hooks ): void {
		$entity_versions = array();
		foreach ( $entity_ids as $entity_id ) {
			$version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( $version ) {
				$entity_versions[ $entity_id ] = $version;
			}
		}

		$cache_data = array(
			'data'            => $data,
			'entity_versions' => $entity_versions,
			'created_at'      => wc_get_container()->get( LegacyProxy::class )->call_function( 'time' ),
		);

		if ( 200 !== $status_code ) {
			$cache_data['status_code'] = $status_code;
		}

		if ( ! empty( $relevant_hooks ) ) {
			$cache_data['hooks_hash'] = $this->generate_hooks_hash( $relevant_hooks );
		}

		$this->set_cached( $cache_key, $cache_data, $cache_ttl );
	}

	/**
	 * Get a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return mixed|false The cached value or false if not found.
	 */
	protected function get_cached( string $cache_key ) {
		return get_transient( $cache_key );
	}

	/**
	 * Set a value in the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @param mixed  $value     The value to cache.
	 * @param int    $ttl       Time to live in seconds.
	 * @return bool True on success, false on failure.
	 */
	protected function set_cached( string $cache_key, $value, int $ttl ): bool {
		return set_transient( $cache_key, $value, $ttl );
	}

	/**
	 * Delete a value from the cache.
	 *
	 * @param string $cache_key The cache key.
	 * @return bool True on success, false on failure.
	 */
	protected function delete_cached( string $cache_key ): bool {
		return delete_transient( $cache_key );
	}
}
