<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\EntityVersionsCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This trait provides transient-based caching capabilities for REST API endpoints.
 *
 * IMPORTANT: Caching only occurs when EntityVersionsCache is enabled. If disabled, endpoints will
 * execute normally without caching, regardless of configuration.
 *
 * REQUIRED SETUP:
 * 1. Call $this->initialize_rest_api_cache() in your controller's constructor.
 * 2. Either override get_default_entity_type() to return your entity type (e.g., 'product', 'order'),
 *    OR specify 'entity_type' in the config array for every with_cache() call.
 *
 * Usage: Wrap endpoint callbacks with the with_cache() method when registering routes:
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
 *                         'entity_type'        => 'product',  // Optional if get_default_entity_type() is overridden.
 *                         'cache_ttl'          => HOUR_IN_SECONDS,  // Optional, defaults to get_cache_ttl().
 *                         'extract_entity_ids' => array( $this, 'custom_extract_ids' ),  // Optional.
 *                         'must_cache'         => array( $this, 'should_cache' ),  // Optional.
 *                         'relevant_hooks'     => array( 'filter_name_1', 'filter_name_2' ),  // Optional.
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
 * - get_default_entity_type(): Default entity type for endpoints without explicit config (REQUIRED).
 * - get_relevant_filters(): Filter names to track for cache invalidation (default: empty array).
 * - get_cache_ttl(): Cache TTL in seconds (default: HOUR_IN_SECONDS).
 * - must_cache(): Whether to cache a request (default: true).
 * - extract_entity_ids(): Extract entity IDs from response data (default assumes 'id' field).
 *
 * Cache invalidation happens when:
 * - Entity versions change (tracked via EntityVersionsCache).
 * - Filter callbacks change (if relevant_filters is specified).
 * - Cache TTL expires.
 *
 * See WC_REST_Products_Controller for an example of trait usage and customization.
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
	 *
	 * Call this from the controller's constructor.
	 */
	protected function initialize_rest_api_cache(): void {
		$cache                       = wc_get_container()->get( EntityVersionsCache::class );
		$this->entity_versions_cache = $cache->is_enabled() ? $cache : null;
	}

	/**
	 * Wrap a callback with caching logic.
	 *
	 * @param callable $callback The original endpoint callback.
	 * @param array    $config   Caching configuration:
	 *                           - entity_type: string (optional, falls back to get_default_entity_type()).
	 *                           - cache_ttl: int (optional, defaults to HOUR_IN_SECONDS).
	 *                           - extract_entity_ids: callable (optional, defaults to $this->extract_entity_ids).
	 *                           - must_cache: callable (optional, receives WP_REST_Request, returns bool).
	 *                           - relevant_hooks: array (optional, falls back to get_relevant_hooks).
	 * @return callable Wrapped callback.
	 */
	protected function with_cache( $callback, $config = array() ) {
		return fn( $request ) => $this->handle_cacheable_request( $request, $callback, $config );
	}

	/**
	 * Handle a request with caching logic.
	 *
	 * Strategy: Try to use cached response if available and valid, otherwise execute the endpoint
	 * and cache the successful response for future requests.
	 *
	 * @param WP_REST_Request $request  The request object.
	 * @param callable        $callback The original endpoint callback.
	 * @param array           $config   Caching configuration.
	 * @return WP_REST_Response|WP_Error The response.
	 */
	protected function handle_cacheable_request( $request, $callback, $config ) {
		// If caching should not be used for this request, execute callback directly.
		if ( ! $this->should_use_cache_for_request( $request, $config ) ) {
			$response = call_user_func( $callback, $request );
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		// Build cache config - if it fails (e.g., no entity type), skip caching.
		$cached_config = $this->build_cache_config( $request, $config );
		if ( is_null( $cached_config ) ) {
			$response = call_user_func( $callback, $request );
			if ( $response instanceof WP_REST_Response ) {
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		// Try to get valid cached response.
		$cached_response = $this->get_cached_response( $request, $config );

		if ( $cached_response ) {
			$cached_response->header( 'X-WC-Cache', 'HIT' );
			return $cached_response;
		}

		// No valid cache - execute callback to get authoritative response and cache it.
		$authoritative_response = call_user_func( $callback, $request );

		return $this->maybe_cache_response( $request, $authoritative_response, $config );
	}

	/**
	 * Check if caching should be used for this request.
	 *
	 * Determines if the request is eligible for caching based on:
	 * - Entity versions cache availability
	 * - Global caching filter
	 * - Request-specific skip parameter
	 * - Custom must_cache callback
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Caching configuration.
	 * @return bool True if caching should be used, false otherwise.
	 */
	private function should_use_cache_for_request( $request, $config ) {
		// Check if entity versions cache is enabled.
		if ( is_null( $this->entity_versions_cache ) ) {
			return false;
		}

		/**
		 * Filter whether to enable response caching for a given REST API controller.
		 *
		 * @since 10.4.0
		 *
		 * @param bool   $enable_caching Whether to enable response caching for the controller.
		 * @param object $controller     The controller instance.
		 * @return bool True to enable response caching, false to disable.
		 */
		$enable_caching = apply_filters(
			'woocommerce_rest_api_enable_response_caching',
			true,
			$this
		);

		if ( ! $enable_caching ) {
			return false;
		}

		// Check for explicit skip parameter.
		$skip_cache = $request->get_param( '_skip_cache' );
		if ( 'true' === $skip_cache || '1' === $skip_cache ) {
			return false;
		}

		// Check custom must_cache callback.
		$must_cache_fn = $config['must_cache'] ?? array( $this, 'must_cache' );
		if ( ! call_user_func( $must_cache_fn, $request ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Build the cache configuration from request and config.
	 *
	 * Extracts and validates all configuration needed for caching operations.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Raw configuration array.
	 * @return array|null Normalized cache config with keys: entity_type, cache_ttl, extract_entity_ids, relevant_hooks, cache_key. Returns null if entity type is not available.
	 */
	private function build_cache_config( $request, $config ) {
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
	 * to indicate whether the response was cached (MISS).
	 *
	 * @param WP_REST_Request           $request  The request object.
	 * @param WP_REST_Response|WP_Error $response The response to potentially cache.
	 * @param array                     $config   Caching configuration.
	 * @return WP_REST_Response|WP_Error The response with appropriate cache headers.
	 */
	private function maybe_cache_response( $request, $response, $config ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		// Only cache successful responses (2xx status codes).
		$status = $response->get_status();
		if ( $status >= 200 && $status <= 299 ) {
			$cached_config = $this->build_cache_config( $request, $config );

			if ( ! is_null( $cached_config ) ) {
				$data       = $response->get_data();
				$entity_ids = call_user_func( $cached_config['extract_entity_ids'], $data, $request );

				$this->store_cached_response(
					$cached_config['cache_key'],
					$data,
					$cached_config['entity_type'],
					$entity_ids,
					$cached_config['cache_ttl'],
					$cached_config['relevant_hooks']
				);
			}
		}

		$response->header( 'X-WC-Cache', 'MISS' );
		return $response;
	}

	/**
	 * Get the default entity type for caching.
	 *
	 * Override this method in controllers that use the trait.
	 * Used as fallback when entity_type is not specified in with_cache config.
	 * Can be overridden per-endpoint by passing 'entity_type' in the with_cache() config array.
	 *
	 * @return string|null Entity type (e.g., 'product', 'order'), or null if no default.
	 */
	protected function get_default_entity_type(): ?string {
		return null;
	}

	/**
	 * Get the names of filters that can customize the response.
	 *
	 * Override this method in controllers to specify filters that affect the response.
	 * When these filters change (callbacks added/removed), cached responses will be invalidated.
	 * By default, returns an empty array (no filter tracking).
	 * Can be overridden per-endpoint by passing 'relevant_hooks' in the with_cache() config array.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of filter names to track.
	 */
	protected function get_hooks_relevant_to_caching( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array();
	}

	/**
	 * Get the cache TTL (Time To Live) in seconds.
	 *
	 * Override this method in controllers to customize cache lifetime.
	 * By default, returns one hour (3600 seconds).
	 * Can be overridden per-endpoint by passing 'cache_ttl' in the with_cache() config array.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return int Cache TTL in seconds.
	 */
	protected function get_cache_ttl( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return HOUR_IN_SECONDS;
	}

	/**
	 * Determine whether a request should be cached.
	 *
	 * Override this method in controllers to implement custom caching logic.
	 * By default, all requests are cached (returns true).
	 * Can be overridden per-endpoint by passing 'must_cache' in the with_cache() config array.
	 *
	 * Note: The _skip_cache=true query parameter always bypasses caching regardless of this method's return value.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool True to cache the request, false to skip caching.
	 */
	protected function must_cache( $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return true;
	}

	/**
	 * Extract entity IDs from response data.
	 *
	 * Default implementation assumes the response is either:
	 * - An array with an 'id' field (single item)
	 * - An array of arrays each having an 'id' field (collection)
	 *
	 * Override this method if your response structure differs.
	 * Can be overridden per-endpoint by passing 'extract_entity_ids' in the with_cache() config array.
	 *
	 * @param array           $data    Response data.
	 * @param WP_REST_Request $request Request object.
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids( $data, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$ids = array();

		// Check if it's a collection (array of items).
		if ( isset( $data[0] ) && is_array( $data[0] ) ) {
			foreach ( $data as $item ) {
				if ( isset( $item['id'] ) ) {
					$ids[] = $item['id'];
				}
			}
		} elseif ( isset( $data['id'] ) ) {
			// Single item.
			$ids[] = $data['id'];
		}

		return array_unique( array_filter( $ids ) );
	}

	/**
	 * Generate a cache key for the given request.
	 *
	 * @param WP_REST_Request $request     The request object.
	 * @param string          $entity_type The entity type.
	 * @return string Cache key.
	 */
	protected function get_cache_key( $request, $entity_type ) {
		$request_hash = md5(
			$request->get_route() . '-' . wp_json_encode( $request->get_query_params() )
		);
		return "wc_rest_api_cache_{$entity_type}-{$request_hash}";
	}

	/**
	 * Generate a hash based on the filters that affect the response.
	 *
	 * @param array $filter_names Array of filter names to track.
	 * @return string Hooks hash.
	 */
	protected function generate_hooks_hash( $filter_names ) {
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
	 * Get cached response if valid.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param array           $config  Caching configuration.
	 * @return WP_REST_Response|null Cached response or null.
	 */
	protected function get_cached_response( $request, $config ) {
		$cached_config = $this->build_cache_config( $request, $config );

		if ( is_null( $cached_config ) ) {
			return null;
		}

		$cache_key        = $cached_config['cache_key'];
		$entity_type      = $cached_config['entity_type'];
		$cache_ttl        = $cached_config['cache_ttl'];
		$relevant_filters = $cached_config['relevant_hooks'];

		$cached = $this->get_cached( $cache_key );

		if ( ! $cached || ! isset( $cached['data'], $cached['entity_versions'], $cached['created_at'] ) ) {
			return null;
		}

		// Check if cache has expired.
		$current_time    = wc_get_container()->get( LegacyProxy::class )->call_function( 'time' );
		$expiration_time = $cached['created_at'] + $cache_ttl;
		if ( $current_time >= $expiration_time ) {
			$this->delete_cached( $cache_key );
			return null;
		}

		// Validate hooks hash if filters are being tracked.
		if ( ! empty( $relevant_filters ) ) {
			$current_hooks_hash = $this->generate_hooks_hash( $relevant_filters );
			$cached_hooks_hash  = $cached['hooks_hash'] ?? '';

			if ( $current_hooks_hash !== $cached_hooks_hash ) {
				// Hooks have changed - invalidate cache entry.
				$this->delete_cached( $cache_key );
				return null;
			}
		}

		// Validate entity versions.
		foreach ( $cached['entity_versions'] as $entity_id => $cached_version ) {
			$current_version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( $current_version !== $cached_version ) {
				$this->delete_cached( $cache_key );
				return null;
			}
		}

		// Cache is valid.
		return new WP_REST_Response( $cached['data'], 200 );
	}

	/**
	 * Store response in cache.
	 *
	 * @param string $cache_key        The cache key.
	 * @param array  $data             The response data to cache.
	 * @param string $entity_type      The entity type.
	 * @param array  $entity_ids       Array of entity IDs in the response.
	 * @param int    $cache_ttl        Cache TTL in seconds.
	 * @param array  $relevant_filters Filter names to track for invalidation.
	 */
	protected function store_cached_response( $cache_key, $data, $entity_type, $entity_ids, $cache_ttl, $relevant_filters ) {

		// Get current versions for all entities.
		$entity_versions = array();
		foreach ( $entity_ids as $entity_id ) {
			$version = $this->entity_versions_cache->get_entity_version( $entity_type, $entity_id );
			if ( $version ) {
				$entity_versions[ $entity_id ] = $version;
			}
		}

		// Build cache data.
		$cache_data = array(
			'data'            => $data,
			'entity_versions' => $entity_versions,
			'created_at'      => wc_get_container()->get( LegacyProxy::class )->call_function( 'time' ),
		);

		// Add hooks hash if tracking filters.
		if ( ! empty( $relevant_filters ) ) {
			$cache_data['hooks_hash'] = $this->generate_hooks_hash( $relevant_filters );
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
