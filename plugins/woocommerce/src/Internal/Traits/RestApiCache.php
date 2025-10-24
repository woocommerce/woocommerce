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
 * - Response headers are cached together with the response data, excluding certain fixed
 *   headers (like Set-Cookie) and optionally others specified via configuration
 *   (per-controller or per-endpoint).
 * - For the purposes of caching, a request is uniquely identified by its route
 *   and query string. Optionally, the user ID can also be included in the cache key
 *   when 'vary_by_user' is enabled, making responses user-specific.
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
 * (configurable via the 'woocommerce_enable_entity_versions_cache' hook).
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
 *                         // Optional bool, defaults to the controller's response_cache_vary_by_user().
 *                         'vary_by_user'       => true,
 *                         // Optional array, defaults to the controller's get_response_headers_to_exclude_from_caching().
 *                         'exclude_headers'    => array( 'X-Custom-Header' ),
 *                         // Optional, this will be passed to all the caching-related methods.
 *                         'endpoint_id'        => 'get_product'
 *                     )
 *                 ),
 *             )
 *         );
 *     }
 * }
 *
 * Override these methods in your controller as needed:
 * - get_default_entity_type(): Default entity type for endpoints without explicit config.
 * - response_cache_vary_by_user(): Whether cache should be user-specific.
 * - get_hooks_relevant_to_caching(): Hook names to track for cache invalidation.
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
	 *                           - endpoint_id: string (optional friendly identifier for the endpoint).
	 *                           - entity_type: string (falls back to get_default_entity_type()).
	 *                           - cache_ttl: int (defaults to HOUR_IN_SECONDS).
	 *                           - extract_entity_ids: callable (defaults to $this->extract_entity_ids).
	 *                           - must_cache: callable (defaults to $this->must_cache).
	 *                           - relevant_hooks: array (defaults to $this->get_hooks_relevant_to_caching()).
	 *                           - vary_by_user: bool (defaults to $this->response_cache_vary_by_user()).
	 *                           - exclude_headers: array (defaults to $this->get_response_headers_to_exclude_from_caching()).
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
	private function handle_cacheable_request( WP_REST_Request $request, callable $callback, array $config ) {
		if ( is_null( $this->entity_versions_cache ) ) {
			return call_user_func( $callback, $request );
		}

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

		$cached_response = $this->get_cached_response( $request, $cached_config );

		if ( $cached_response ) {
			$cached_response->header( 'X-WC-Cache', 'HIT' );
			return $cached_response;
		}

		$authoritative_response = call_user_func( $callback, $request );

		return $this->maybe_cache_response( $request, $authoritative_response, $cached_config );
	}

	/**
	 * Check if caching should be used for a particular incoming request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Caching configuration.
	 * @return bool True if caching should be used, false otherwise.
	 */
	private function should_use_cache_for_request( WP_REST_Request $request, array $config ): bool {
		$endpoint_id = $config['endpoint_id'] ?? null;

		// Check for explicit skip parameter.
		$skip_cache   = $request->get_param( '_skip_cache' );
		$should_cache = ! ( 'true' === $skip_cache || '1' === $skip_cache );

		// Check custom must_cache callback.
		if ( $should_cache ) {
			$must_cache_fn = $config['must_cache'] ?? array( $this, 'must_cache' );
			$should_cache  = call_user_func( $must_cache_fn, $request, $endpoint_id );
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
		 * @param string|null     $endpoint_id    Optional friendly identifier for the endpoint.
		 * @return bool True to enable response caching, false to disable.
		 */
		return apply_filters(
			'woocommerce_rest_api_enable_response_caching',
			$should_cache,
			$this,
			$request,
			$endpoint_id
		);
	}

	/**
	 * Build the output cache entry configuration from the request and per-endpoint config.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Raw configuration array passed to with_cache.
	 * @return array|null Normalized cache config with keys: endpoint_id, entity_type, cache_ttl, extract_entity_ids, relevant_hooks, vary_by_user, cache_key. Returns null if entity type is not available.
	 */
	private function build_cache_config( WP_REST_Request $request, array $config ): ?array {
		$endpoint_id  = $config['endpoint_id'] ?? null;
		$entity_type  = $config['entity_type'] ?? $this->get_default_entity_type();
		$vary_by_user = $config['vary_by_user'] ?? $this->response_cache_vary_by_user( $request, $endpoint_id );

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
			'endpoint_id'        => $endpoint_id,
			'entity_type'        => $entity_type,
			'cache_ttl'          => $config['cache_ttl'] ?? $this->get_cache_ttl( $request, $endpoint_id ),
			'extract_entity_ids' => $config['extract_entity_ids'] ?? array( $this, 'extract_entity_ids' ),
			'relevant_hooks'     => $config['relevant_hooks'] ?? $this->get_hooks_relevant_to_caching( $request, $endpoint_id ),
			'vary_by_user'       => $vary_by_user,
			'exclude_headers'    => $config['exclude_headers'] ?? $this->get_response_headers_to_exclude_from_caching( $request, $endpoint_id ),
			'cache_key'          => $this->get_cache_key( $request, $entity_type, $vary_by_user, $endpoint_id ),
		);
	}

	/**
	 * Cache the response if it's successful and return it with appropriate headers.
	 *
	 * Only caches responses with 2xx status codes. Always adds the X-WC-Cache header
	 * with value MISS if the response was cached, or SKIP if it was not cached.
	 *
	 * @param WP_REST_Request            $request       The request object.
	 * @param WP_REST_Response|\WP_Error $response      The response to potentially cache.
	 * @param array                      $cached_config Built caching configuration from build_cache_config().
	 * @return WP_REST_Response|\WP_Error The response with appropriate cache headers.
	 */
	private function maybe_cache_response( WP_REST_Request $request, $response, array $cached_config ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$cached = false;

		$status = $response->get_status();
		if ( $status >= 200 && $status <= 299 ) {
			$data       = $response->get_data();
			$entity_ids = call_user_func( $cached_config['extract_entity_ids'], $data, $request, $cached_config['endpoint_id'] );

			$response_headers  = $response->get_headers();
			$cacheable_headers = $this->filter_cacheable_headers( $response_headers, $cached_config['exclude_headers'] );

			$this->store_cached_response(
				$cached_config['cache_key'],
				$data,
				$status,
				$cached_config['entity_type'],
				$entity_ids,
				$cached_config['cache_ttl'],
				$cached_config['relevant_hooks'],
				$cacheable_headers
			);

			$cached = true;
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
	 * Determine whether the response cache should vary by user.
	 *
	 * When true, the user ID is included in the cache key, making responses
	 * user-specific. This is useful for endpoints that return user-specific data.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('vary_by_user' key).
	 *
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return bool True to make cache user-specific, false otherwise.
	 */
	protected function response_cache_vary_by_user( WP_REST_Request $request, ?string $endpoint_id = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return true;
	}

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
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return array Array of filter names to track.
	 */
	protected function get_hooks_relevant_to_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array();
	}

	/**
	 * Get the TTL (Time To Live) for a cached response in seconds.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('cache_ttl' key).
	 *
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return int Cache TTL in seconds.
	 */
	protected function get_cache_ttl( WP_REST_Request $request, ?string $endpoint_id = null ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return HOUR_IN_SECONDS;
	}

	/**
	 * Determine whether a given request should be cached.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('must_cache' key).
	 *
	 * Note: Additionally, the _skip_cache query parameter and the woocommerce_rest_api_enable_response_caching filter
	 * can be used to control the enabling of caching for a given request.
	 *
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return bool True to cache the request, false to skip caching.
	 */
	protected function must_cache( WP_REST_Request $request, ?string $endpoint_id = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return true;
	}

	/**
	 * Get response headers to exclude from caching.
	 *
	 * These headers will not be stored in the cache. Certain headers
	 * are always excluded (X-WC-Cache, Set-Cookie, Date, Expires, Last-Modified,
	 * Age, ETag, Cache-Control, Pragma) as they don't make sense to cache.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('exclude_headers' key).
	 *
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return array Array of header names to exclude (case-insensitive).
	 */
	protected function get_response_headers_to_exclude_from_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array();
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
	 * @param array           $data        Response data.
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids( array $data, WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
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
	 * Filter response headers to get only those that should be cached.
	 *
	 * Always excludes certain headers that don't make sense to cache:
	 * X-WC-Cache, Set-Cookie, Date, Expires, Last-Modified, Age, ETag, Cache-Control, Pragma.
	 *
	 * @param array $headers         Response headers.
	 * @param array $exclude_headers Additional header names to exclude (case-insensitive).
	 * @return array Filtered headers array.
	 */
	private function filter_cacheable_headers( array $headers, array $exclude_headers ): array {
		$always_exclude = array(
			'X-WC-Cache',
			'Set-Cookie',
			'Date',
			'Expires',
			'Last-Modified',
			'Age',
			'ETag',
			'Cache-Control',
			'Pragma',
		);

		$all_exclude_lowercase = array_map( 'strtolower', array_merge( $always_exclude, $exclude_headers ) );

		return array_filter(
			$headers,
			function ( $name ) use ( $all_exclude_lowercase ) {
				return ! in_array( strtolower( $name ), $all_exclude_lowercase, true );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get cache key information that uniquely identifies a request.
	 *
	 * Controllers can override this method to customize what information
	 * uniquely identifies a request for caching purposes.
	 *
	 * @param WP_REST_Request $request      The request object.
	 * @param bool            $vary_by_user Whether to include user ID in cache key.
	 * @param string|null     $endpoint_id  Optional friendly identifier for the endpoint.
	 * @return array Array of cache key information parts.
	 */
	protected function get_cache_key_info( WP_REST_Request $request, bool $vary_by_user = false, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$request_query_params = $request->get_query_params();
		if ( is_array( $request_query_params ) ) {
			ksort( $request_query_params );
		}
		$cache_key_parts = array(
			$request->get_route(),
			wp_json_encode( $request_query_params ),
		);

		if ( $vary_by_user ) {
			$user_id           = wc_get_container()->get( LegacyProxy::class )->call_function( 'get_current_user_id' );
			$cache_key_parts[] = "user_{$user_id}";
		}

		return $cache_key_parts;
	}

	/**
	 * Generate a cache key for a given request.
	 *
	 * @param WP_REST_Request $request      The request object.
	 * @param string          $entity_type  The entity type.
	 * @param bool            $vary_by_user Whether to include user ID in cache key.
	 * @param string|null     $endpoint_id  Optional friendly identifier for the endpoint.
	 * @return string Cache key.
	 */
	private function get_cache_key( WP_REST_Request $request, string $entity_type, bool $vary_by_user = false, ?string $endpoint_id = null ): string {
		$cache_key_parts = $this->get_cache_key_info( $request, $vary_by_user, $endpoint_id );

		/**
		 * Filter the information used to generate the cache key for a REST API request.
		 *
		 * Allows customization of what uniquely identifies a request for caching purposes.
		 *
		 * @since 10.4.0
		 *
		 * @param array           $cache_key_parts Array of cache key information parts.
		 * @param WP_REST_Request $request         The request object.
		 * @param bool            $vary_by_user    Whether user ID is included in cache key.
		 * @param string|null     $endpoint_id     Optional friendly identifier for the endpoint.
		 * @param object          $controller      The controller instance.
		 * @return array Filtered cache key information parts.
		 */
		$cache_key_parts = apply_filters(
			'woocommerce_rest_api_cache_key_info',
			$cache_key_parts,
			$request,
			$vary_by_user,
			$endpoint_id,
			$this
		);

		$request_hash = md5( implode( '-', $cache_key_parts ) );
		return "wc_rest_api_cache_{$entity_type}-{$request_hash}";
	}

	/**
	 * Generate a hash based on the actual usages of the hooks that affect the response.
	 *
	 * @param array $filter_names Array of hook names to track.
	 * @return string Hooks hash.
	 */
	private function generate_hooks_hash( array $filter_names ): string {
		if ( empty( $filter_names ) ) {
			return '';
		}

		global $wp_filter;

		$cache_hash_data = array();

		foreach ( $filter_names as $filter_name ) {
			if ( empty( $wp_filter[ $filter_name ] ) ) {
				continue;
			}

			$cache_hash_data[ $filter_name ] = array();
			$callbacks_by_priority           = $wp_filter[ $filter_name ]->callbacks ?? array();

			foreach ( $callbacks_by_priority as $priority => $callbacks ) {
				$normalized = array();
				foreach ( $callbacks as $cb ) {
					if ( ! isset( $cb['function'] ) ) {
						continue;
					}
					$normalized[] = $this->normalize_callback_for_hash( $cb['function'] );
				}
				if ( $normalized ) {
					$cache_hash_data[ $filter_name ][ $priority ] = array_values( array_unique( $normalized ) );
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
	 * Normalize a hook callback into a stable string for hashing.
	 *
	 * Converts various callback types to stable string representations to ensure
	 * consistent hash generation across requests. This prevents spurious cache
	 * invalidations caused by callback serialization inconsistencies.
	 *
	 * @param mixed $callback A WordPress hook callback.
	 * @return string Normalized callback representation.
	 */
	private function normalize_callback_for_hash( $callback ): string {
		if ( is_string( $callback ) ) {
			return $callback;
		}

		if ( is_array( $callback ) && 2 === count( $callback ) ) {
			$class = is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0];
			return "{$class}::{$callback[1]}";
		}

		if ( $callback instanceof \Closure ) {
			return 'Closure@' . spl_object_hash( $callback );
		}

		if ( is_object( $callback ) ) {
			return get_class( $callback ) . '::__invoke';
		}

		return 'unknown_callback';
	}

	/**
	 * Get a cached response, but only if it's valid
	 * (otherwise the cached response will be invalidated).
	 *
	 * @param WP_REST_Request $request       The request object.
	 * @param array           $cached_config Built caching configuration from build_cache_config().
	 * @return WP_REST_Response|null Cached response, or null if not available or has been invalidated.
	 */
	private function get_cached_response( WP_REST_Request $request, array $cached_config ): ?WP_REST_Response {
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
		$response    = new WP_REST_Response( $cached['data'], $status_code );

		if ( ! empty( $cached['headers'] ) ) {
			foreach ( $cached['headers'] as $name => $value ) {
				$response->header( $name, $value );
			}
		}

		return $response;
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
	 * @param array  $headers          Response headers to cache.
	 */
	private function store_cached_response( string $cache_key, array $data, int $status_code, string $entity_type, array $entity_ids, int $cache_ttl, array $relevant_hooks, array $headers = array() ): void {
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

		if ( ! empty( $headers ) ) {
			$cache_data['headers'] = $headers;
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
