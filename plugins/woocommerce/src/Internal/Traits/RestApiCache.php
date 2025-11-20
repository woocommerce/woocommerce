<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\CallbackUtil;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This trait provides caching capabilities for REST API endpoints using the WordPress cache.
 *
 * - The output of all the REST API endpoints whose callback declaration is wrapped
 *   in a call to 'with_cache' will be cached using wp_cache_* functions.
 * - For the purposes of caching, a request is uniquely identified by its route,
 *   HTTP method, query string, and user ID.
 * - The VersionStringGenerator class is used to track versions of entities included
 *   in the responses (an "entity" is any object that is uniquely identified by type and id
 *   and contributes with information to be included in the response),
 *   so that when those entities change, the relevant cached responses become invalid.
 *   Modification of entity versions must be done externally by the code that modifies
 *   those entities (via calls to VersionStringGenerator::generate_version).
 * - Various parameters (cached outputs TTL, entity type for a given response, hooks that affect
 *   the response) can be configured globally for the controller (via overriding protected methods)
 *   or per-endpoint (via arguments passed to with_cache).
 * - Caching can be disabled for a given request by adding a '_skip_cache=true|1'
 *   to the query string.
 * - A X-WC-Cache HTTP header is added to responses to indicate cache status:
 *   HIT, MISS, or SKIP.
 *
 * Usage: Wrap endpoint callbacks with the `with_cache()` method when registering routes.
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
 *     protected function get_default_response_entity_type(): ?string {
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
 *                         // String, optional if get_default_response_entity_type() is overridden.
 *                         'entity_type'    => 'product',
 *                         // Optional int, defaults to the controller's get_ttl_for_cached_response().
 *                         'cache_ttl'      => HOUR_IN_SECONDS,
 *                         // Optional array, defaults to the controller's get_hooks_relevant_to_caching().
 *                         'relevant_hooks' => array( 'filter_name_1', 'filter_name_2' ),
 *                         // Optional bool, defaults to the controller's response_cache_vary_by_user().
 *                         'vary_by_user'   => true,
 *                         // Optional, this will be passed to all the caching-related methods.
 *                         'endpoint_id'    => 'get_product'
 *                     )
 *                 ),
 *             )
 *         );
 *     }
 * }
 *
 * Override these methods in your controller as needed:
 * - get_default_response_entity_type(): Default entity type for endpoints without explicit config.
 * - response_cache_vary_by_user(): Whether cache should be user-specific.
 * - get_hooks_relevant_to_caching(): Hook names to track for cache invalidation.
 * - get_ttl_for_cached_response(): TTL for cached outputs in seconds.
 *
 * Cache invalidation happens when:
 * - Entity versions change (tracked via VersionStringGenerator).
 * - Hook callbacks change
 *   (if the `get_hooks_relevant_to_caching()` call result or the 'relevant_hooks' array isn't empty).
 * - Cached response TTL expires.
 *
 * NOTE: This caching mechanism uses the WordPress cache (wp_cache_* functions).
 * By default caching is only enabled when an external object cache is enabled
 * (checked via call to VersionStringGenerator::can_use()), so the cache is persistent
 * across requests and not just for the current request.
 *
 * @since   10.5.0
 */
trait RestApiCache {
	/**
	 * Cache group name for REST API responses.
	 *
	 * @var string
	 */
	private static string $cache_group = 'woocommerce_rest_api_cache';

	/**
	 * The instance of VersionStringGenerator to use, or null if caching is disabled.
	 *
	 * @var VersionStringGenerator|null
	 */
	private ?VersionStringGenerator $version_string_generator = null;

	/**
	 * Initialize the trait.
	 * This MUST be called from the controller's constructor.
	 */
	protected function initialize_rest_api_cache(): void {
		$generator                      = wc_get_container()->get( VersionStringGenerator::class );
		$this->version_string_generator = $generator->can_use() ? $generator : null;
	}

	/**
	 * Wrap an endpoint callback declaration with caching logic.
	 * Usage: `'callback' => $this->with_cache( array( $this, 'endpoint_callback_method' ) )`
	 *        `'callback' => $this->with_cache( array( $this, 'endpoint_callback_method' ), [ 'entity_type' => 'product' ] )`
	 *
	 * @param callable $callback The original endpoint callback.
	 * @param array    $config   Caching configuration:
	 *                           - entity_type: string (falls back to get_default_response_entity_type()).
	 *                           - vary_by_user: bool (defaults to response_cache_vary_by_user()).
	 *                           - endpoint_id: string|null (optional friendly identifier for the endpoint).
	 *                           - cache_ttl: int (defaults to get_ttl_for_cached_response()).
	 *                           - relevant_hooks: array (defaults to get_hooks_relevant_to_caching()).
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
		if ( is_null( $this->version_string_generator ) ) {
			return call_user_func( $callback, $request );
		}

		$should_skip_cache = ! $this->should_use_cache_for_request( $request );
		if ( ! $should_skip_cache ) {
			$cached_config     = $this->build_cache_config( $request, $config );
			$should_skip_cache = is_null( $cached_config );
		}

		if ( $should_skip_cache ) {
			$response = call_user_func( $callback, $request );
			if ( ! is_wp_error( $response ) ) {
				$response = rest_ensure_response( $response );
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		$cached_response = $this->get_cached_response( $cached_config );

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
	 * @return bool True if caching should be used, false otherwise.
	 */
	private function should_use_cache_for_request( WP_REST_Request $request ): bool {
		$skip_cache   = $request->get_param( '_skip_cache' );
		$should_cache = ! ( 'true' === $skip_cache || '1' === $skip_cache );

		/**
		 * Filter whether to enable response caching for a given REST API controller.
		 *
		 * @since 10.5.0
		 *
		 * @param bool            $enable_caching Whether to enable response caching (result of !_skip_cache evaluation).
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
	 * @return array|null Normalized cache config with keys: endpoint_id, entity_type, vary_by_user, cache_ttl, relevant_hooks, cache_key. Returns null if entity type is not available.
	 */
	private function build_cache_config( WP_REST_Request $request, array $config ): ?array {
		$endpoint_id  = $config['endpoint_id'] ?? null;
		$entity_type  = $config['entity_type'] ?? $this->get_default_response_entity_type();
		$vary_by_user = $config['vary_by_user'] ?? $this->response_cache_vary_by_user( $request, $endpoint_id );

		if ( ! $entity_type ) {
			wc_get_container()->get( LegacyProxy::class )->call_function(
				'wc_doing_it_wrong',
				__METHOD__,
				'No entity type provided and no default entity type available. Skipping cache.',
				'10.5.0'
			);
			return null;
		}

		return array(
			'endpoint_id'    => $endpoint_id,
			'entity_type'    => $entity_type,
			'vary_by_user'   => $vary_by_user,
			'cache_ttl'      => $config['cache_ttl'] ?? $this->get_ttl_for_cached_response( $request, $endpoint_id ),
			'relevant_hooks' => $config['relevant_hooks'] ?? $this->get_hooks_relevant_to_caching( $request, $endpoint_id ),
			'cache_key'      => $this->get_key_for_cached_response( $request, $entity_type, $vary_by_user, $endpoint_id ),
		);
	}

	/**
	 * Cache the response if it's successful and return it with appropriate headers.
	 *
	 * Only caches responses with 2xx status codes. Always adds the X-WC-Cache header
	 * with value MISS if the response was cached, or SKIP if it was not cached.
	 *
	 * Supports both WP_REST_Response objects and raw data (which will be wrapped in a response object).
	 * Error objects are returned as-is without caching.
	 *
	 * @param WP_REST_Request                         $request       The request object.
	 * @param WP_REST_Response|\WP_Error|array|object $response      The response to potentially cache.
	 * @param array                                   $cached_config Caching configuration from build_cache_config().
	 * @return WP_REST_Response|\WP_Error The response with appropriate cache headers.
	 */
	private function maybe_cache_response( WP_REST_Request $request, $response, array $cached_config ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );

		$cached = false;

		$status = $response->get_status();
		if ( $status >= 200 && $status <= 299 ) {
			$data       = $response->get_data();
			$entity_ids = is_array( $data ) ? $this->extract_entity_ids_from_response( $data, $request, $cached_config['endpoint_id'] ) : array();

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
	protected function get_default_response_entity_type(): ?string {
		return null;
	}

	/**
	 * Whether the response cache should vary by user.
	 *
	 * When true, each user gets their own cached version of the response.
	 * When false, the same cached response is shared across all users.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('vary_by_user' key).
	 *
	 * @param WP_REST_Request $request     The request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return bool True to make cache user-specific, false otherwise.
	 */
	protected function response_cache_vary_by_user( WP_REST_Request $request, ?string $endpoint_id = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return true;
	}

	/**
	 * Get the cache TTL (time to live) for cached responses.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('cache_ttl' key).
	 *
	 * @param WP_REST_Request $request     The request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return int Cache TTL in seconds.
	 */
	protected function get_ttl_for_cached_response( WP_REST_Request $request, ?string $endpoint_id = null ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return HOUR_IN_SECONDS;
	}

	/**
	 * Get the names of hooks (filters and actions) that can customize the response.
	 *
	 * All the existing instances of add_action/add_filter for these hooks
	 * will be included in the information that gets cached together with the response,
	 * and if any of these has changed when the cached response is retrieved,
	 * the cache entry will be invalidated.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('relevant_hooks' key).
	 *
	 * @param WP_REST_Request $request     Request object.
	 * @param string|null     $endpoint_id Optional friendly identifier for the endpoint.
	 * @return array Array of hook names to track.
	 */
	protected function get_hooks_relevant_to_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return array();
	}

	/**
	 * Extract entity IDs from response data.
	 *
	 * This implementation assumes the response is either:
	 * - An array with an 'id' field (single item)
	 * - An array of arrays each having an 'id' field (collection)
	 *
	 * Controllers can override this method to customize entity ID extraction.
	 *
	 * @param array           $response_data Response data.
	 * @param WP_REST_Request $request       The request object.
	 * @param string|null     $endpoint_id   Optional friendly identifier for the endpoint.
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids_from_response( array $response_data, WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$ids = array();

		if ( isset( $response_data[0] ) && is_array( $response_data[0] ) ) {
			foreach ( $response_data as $item ) {
				if ( isset( $item['id'] ) ) {
					$ids[] = $item['id'];
				}
			}
		} elseif ( isset( $response_data['id'] ) ) {
			$ids[] = $response_data['id'];
		}

		// Filter out null/false values but keep 0 and empty strings as they could be valid IDs.
		return array_unique(
			array_filter( $ids, fn ( $id ) => ! is_null( $id ) && false !== $id )
		);
	}

	/**
	 * Get cache key information that uniquely identifies a request.
	 *
	 * @param WP_REST_Request $request      The request object.
	 * @param bool            $vary_by_user Whether to include user ID in cache key.
	 * @param string|null     $endpoint_id  Optional friendly identifier for the endpoint.
	 * @return array Array of cache key information parts.
	 */
	protected function get_key_info_for_cached_response( WP_REST_Request $request, bool $vary_by_user = false, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$request_query_params = $request->get_query_params();
		if ( is_array( $request_query_params ) ) {
			ksort( $request_query_params );
		}

		$cache_key_parts = array(
			$request->get_route(),
			$request->get_method(),
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
	private function get_key_for_cached_response( WP_REST_Request $request, string $entity_type, bool $vary_by_user = false, ?string $endpoint_id = null ): string {
		$cache_key_parts = $this->get_key_info_for_cached_response( $request, $vary_by_user, $endpoint_id );

		/**
		 * Filter the information used to generate the cache key for a REST API request.
		 *
		 * Allows customization of what uniquely identifies a request for caching purposes.
		 *
		 * @since 10.5.0
		 *
		 * @param array           $cache_key_parts Array of cache key information parts.
		 * @param WP_REST_Request $request         The request object.
		 * @param bool            $vary_by_user    Whether user ID is included in cache key.
		 * @param string|null     $endpoint_id     Optional friendly identifier for the endpoint (passed to with_cache).
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
	 * @param array $hook_names Array of hook names to track.
	 * @return string Hooks hash.
	 */
	private function generate_hooks_hash( array $hook_names ): string {
		if ( empty( $hook_names ) ) {
			return '';
		}

		$cache_hash_data = array();

		foreach ( $hook_names as $hook_name ) {
			$signatures = CallbackUtil::get_hook_callback_signatures( $hook_name );
			if ( ! empty( $signatures ) ) {
				$cache_hash_data[ $hook_name ] = $signatures;
			}
		}

		/**
		 * Filter the data used to generate the hooks hash for REST API response caching.
		 *
		 * @since 10.5.0
		 *
		 * @param array  $cache_hash_data Hook callbacks data used for hash generation.
		 * @param array  $hook_names      Hook names being tracked.
		 * @param object $controller      Controller instance.
		 */
		$cache_hash_data = apply_filters(
			'woocommerce_rest_api_cache_hooks_hash_data',
			$cache_hash_data,
			$hook_names,
			$this
		);

		return md5( wp_json_encode( $cache_hash_data ) );
	}

	/**
	 * Get a cached response, but only if it's valid (otherwise the cached response will be invalidated).
	 *
	 * @param array $cached_config Built caching configuration from build_cache_config().
	 * @return WP_REST_Response|null Cached response, or null if not available or has been invalidated.
	 */
	private function get_cached_response( array $cached_config ): ?WP_REST_Response {
		$cache_key      = $cached_config['cache_key'];
		$entity_type    = $cached_config['entity_type'];
		$cache_ttl      = $cached_config['cache_ttl'];
		$relevant_hooks = $cached_config['relevant_hooks'];

		$found  = false;
		$cached = wp_cache_get( $cache_key, self::$cache_group, false, $found );

		if ( ! $found || ! array_key_exists( 'data', $cached ) || ! isset( $cached['entity_versions'], $cached['created_at'] ) ) {
			return null;
		}

		$current_time    = wc_get_container()->get( LegacyProxy::class )->call_function( 'time' );
		$expiration_time = $cached['created_at'] + $cache_ttl;
		if ( $current_time >= $expiration_time ) {
			wp_cache_delete( $cache_key, self::$cache_group );
			return null;
		}

		if ( ! empty( $relevant_hooks ) ) {
			$current_hooks_hash = $this->generate_hooks_hash( $relevant_hooks );
			$cached_hooks_hash  = $cached['hooks_hash'] ?? '';

			if ( $current_hooks_hash !== $cached_hooks_hash ) {
				wp_cache_delete( $cache_key, self::$cache_group );
				return null;
			}
		}

		foreach ( $cached['entity_versions'] as $entity_id => $cached_version ) {
			$version_id      = "{$entity_type}_{$entity_id}";
			$current_version = $this->version_string_generator->get_version( $version_id );
			if ( $current_version !== $cached_version ) {
				wp_cache_delete( $cache_key, self::$cache_group );
				return null;
			}
		}

		// At this point the cached response is valid.
		$response = new WP_REST_Response( $cached['data'], $cached['status_code'] ?? 200 );

		return $response;
	}

	/**
	 * Store a response in cache.
	 *
	 * @param string $cache_key      The cache key.
	 * @param mixed  $data           The response data to cache.
	 * @param int    $status_code    The HTTP status code of the response.
	 * @param string $entity_type    The entity type.
	 * @param array  $entity_ids     Array of entity IDs in the response.
	 * @param int    $cache_ttl      Cache TTL in seconds.
	 * @param array  $relevant_hooks Hook names to track for invalidation.
	 */
	private function store_cached_response( string $cache_key, $data, int $status_code, string $entity_type, array $entity_ids, int $cache_ttl, array $relevant_hooks ): void {
		$entity_versions = array();
		foreach ( $entity_ids as $entity_id ) {
			$version_id = "{$entity_type}_{$entity_id}";
			$version    = $this->version_string_generator->get_version( $version_id );
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

		wp_cache_set( $cache_key, $cache_data, self::$cache_group, $cache_ttl );
	}
}
