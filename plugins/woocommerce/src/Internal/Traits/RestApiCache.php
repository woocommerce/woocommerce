<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\CallbackUtil;
use WP_REST_Request;
use WP_REST_Response;

/**
 * This trait provides caching capabilities for REST API endpoints using the WordPress cache.
 *
 * - The output of all the REST API endpoints whose callback declaration is wrapped
 *   in a call to 'with_cache' will be cached using wp_cache_* functions.
 * - Response headers are cached together with the response data, excluding certain fixed
 *   headers (like Set-Cookie) and optionally others specified via configuration
 *   (per-controller or per-endpoint).
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
 * Additionally to caching, this trait also handles the sending of appropriate
 * Cache-Control and ETag headers to instruct clients and proxies on how to cache responses.
 * The ETag is generated based on the cached response data and cache key, and a request
 * containing an If-None-Match header with a matching ETag will receive a 304 Not Modified response.
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
 *                         'relevant_hooks'  => array( 'filter_name_1', 'filter_name_2' ),
 *                         // Optional bool, defaults to the controller's response_cache_vary_by_user().
 *                         'vary_by_user'    => true,
 *                         // Optional array, defaults to the controller's get_response_headers_to_include_in_caching().
 *                         'include_headers' => array( 'X-Custom-Header' ),
 *                         // Optional array, defaults to the controller's get_response_headers_to_exclude_from_caching().
 *                         'exclude_headers' => array( 'X-Private-Header' ),
 *                         // Optional, this will be passed to all the caching-related methods.
 *                         'endpoint_id'     => 'get_product'
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
 * - get_response_headers_to_include_in_caching(): Headers to include in cache (false = use exclusion mode).
 * - get_response_headers_to_exclude_from_caching(): Headers to exclude from cache (when in exclusion mode).
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
 * @since 10.5.0
 */
trait RestApiCache {
	/**
	 * Cache group name for REST API responses.
	 *
	 * @var string
	 */
	private static string $cache_group = 'woocommerce_rest_api_cache';

	/**
	 * Response headers that are always excluded from caching.
	 *
	 * @var array
	 */
	private static array $always_excluded_headers = array(
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

	/**
	 * The instance of VersionStringGenerator to use, or null if caching is disabled.
	 *
	 * @var VersionStringGenerator|null
	 */
	private ?VersionStringGenerator $version_string_generator = null;

	/**
	 * Whether we are currently handling a cached endpoint.
	 *
	 * @var bool
	 */
	private $is_handling_cached_endpoint = false;

	/**
	 * Whether the REST API caching feature is enabled.
	 *
	 * @var bool
	 */
	private bool $rest_api_caching_feature_enabled = false;

	/**
	 * Initialize the trait.
	 * This MUST be called from the controller's constructor.
	 */
	protected function initialize_rest_api_cache(): void {
		// Guard against early instantiation before WooCommerce is fully initialized.
		// Some third-party plugins instantiate REST controllers during plugin loading,
		// before the WooCommerce container is available.
		if ( ! function_exists( 'wc_get_container' ) ) {
			return;
		}

		$features_controller = wc_get_container()->get( FeaturesController::class );

		$this->rest_api_caching_feature_enabled = $features_controller->feature_is_enabled( 'rest_api_caching' );
		if ( ! $this->rest_api_caching_feature_enabled ) {
			return;
		}

		$generator = wc_get_container()->get( VersionStringGenerator::class );

		$backend_caching_enabled        = 'yes' === get_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		$this->version_string_generator = ( $backend_caching_enabled && $generator->can_use() ) ? $generator : null;

		add_filter( 'rest_send_nocache_headers', array( $this, 'handle_rest_send_nocache_headers' ), 10, 1 );
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
	 *                           - include_headers: array|false (defaults to get_response_headers_to_include_in_caching()).
	 *                           - exclude_headers: array (defaults to get_response_headers_to_exclude_from_caching()).
	 * @return callable Wrapped callback.
	 */
	protected function with_cache( callable $callback, array $config = array() ): callable {
		return $this->rest_api_caching_feature_enabled
			? fn( $request ) => $this->handle_cacheable_request( $request, $callback, $config )
			: fn( $request ) => call_user_func( $callback, $request );
	}

	/**
	 * Handle a request with caching logic.
	 *
	 * Strategy:
	 * - If backend caching is enabled: Try to use cached response if available, otherwise execute
	 *   the callback and cache the response.
	 * - If only cache headers are enabled: Execute the callback, generate ETag, and return 304
	 *   if the client's ETag matches.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request  The request object.
	 * @param callable                              $callback The original endpoint callback.
	 * @param array                                 $config   Caching configuration specified for the endpoint.
	 *
	 * @return WP_REST_Response|\WP_Error The response.
	 */
	private function handle_cacheable_request( WP_REST_Request $request, callable $callback, array $config ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$backend_caching_enabled = ! is_null( $this->version_string_generator );
		$cache_headers_enabled   = 'yes' === get_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );

		if ( ! $backend_caching_enabled && ! $cache_headers_enabled ) {
			return call_user_func( $callback, $request );
		}

		$cached_config     = null;
		$should_skip_cache = ! $this->should_use_cache_for_request( $request );
		if ( ! $should_skip_cache ) {
			$cached_config     = $this->build_cache_config( $request, $config );
			$should_skip_cache = is_null( $cached_config );
		}

		if ( $should_skip_cache || is_null( $cached_config ) ) {
			$response = call_user_func( $callback, $request );
			if ( ! is_wp_error( $response ) ) {
				$response = rest_ensure_response( $response );
				$response->header( 'X-WC-Cache', 'SKIP' );
			}
			return $response;
		}

		$this->is_handling_cached_endpoint = true;

		if ( $backend_caching_enabled ) {
			$cached_response = $this->get_cached_response( $request, $cached_config, $cache_headers_enabled );

			if ( $cached_response ) {
				$cached_response->header( 'X-WC-Cache', 'HIT' );
				return $cached_response;
			}
		}

		$authoritative_response = call_user_func( $callback, $request );

		return $backend_caching_enabled
			? $this->maybe_cache_response( $request, $authoritative_response, $cached_config, $cache_headers_enabled )
			: $this->maybe_add_cache_headers( $request, $authoritative_response, $cached_config );
	}

	/**
	 * Check if caching should be used for a particular incoming request.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 *
	 * @return bool True if caching should be used, false otherwise.
	 */
	private function should_use_cache_for_request( WP_REST_Request $request ): bool { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$skip_cache   = $request->get_param( '_skip_cache' );
		$should_cache = ! ( 'true' === $skip_cache || '1' === $skip_cache );

		/**
		 * Filter whether to enable response caching for a given REST API controller.
		 *
		 * @since 10.5.0
		 *
		 * @param bool            $enable_caching Whether to enable response caching (result of !_skip_cache evaluation).
		 * @param object          $controller     The controller instance.
		 * @param WP_REST_Request<array<string, mixed>> $request        The request object.
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
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @param array                                 $config  Raw configuration array passed to with_cache.
	 *
	 * @return array|null Normalized cache config with keys: endpoint_id, entity_type, vary_by_user, cache_ttl, relevant_hooks, include_headers, exclude_headers, cache_key. Returns null if entity type is not available.
	 *
	 * @throws \InvalidArgumentException If include_headers is not false or an array.
	 */
	private function build_cache_config( WP_REST_Request $request, array $config ): ?array { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$endpoint_id  = $config['endpoint_id'] ?? null;
		$entity_type  = $config['entity_type'] ?? $this->get_default_response_entity_type();
		$vary_by_user = $config['vary_by_user'] ?? $this->response_cache_vary_by_user( $request, $endpoint_id );

		if ( ! $entity_type ) {
			$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
			$legacy_proxy->call_function(
				'wc_doing_it_wrong',
				__METHOD__,
				'No entity type provided and no default entity type available. Skipping cache.',
				'10.5.0'
			);
			return null;
		}

		$include_headers = $config['include_headers'] ?? $this->get_response_headers_to_include_in_caching( $request, $endpoint_id );
		if ( false !== $include_headers && ! is_array( $include_headers ) ) {
			throw new \InvalidArgumentException(
				'include_headers must be either false or an array, ' . gettype( $include_headers ) . ' given.' // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		return array(
			'endpoint_id'     => $endpoint_id,
			'entity_type'     => $entity_type,
			'vary_by_user'    => $vary_by_user,
			'cache_ttl'       => $config['cache_ttl'] ?? $this->get_ttl_for_cached_response( $request, $endpoint_id ),
			'relevant_hooks'  => $config['relevant_hooks'] ?? $this->get_hooks_relevant_to_caching( $request, $endpoint_id ),
			'include_headers' => $include_headers,
			'exclude_headers' => $config['exclude_headers'] ?? $this->get_response_headers_to_exclude_from_caching( $request, $endpoint_id ),
			'cache_key'       => $this->get_key_for_cached_response( $request, $entity_type, $vary_by_user, $endpoint_id ),
		);
	}

	/**
	 * Cache the response if it's successful and optionally add cache headers.
	 *
	 * Only caches responses with 2xx status codes. Always adds the X-WC-Cache header
	 * with value MISS if the response was cached, or SKIP if it was not cached.
	 *
	 * Supports both WP_REST_Response objects and raw data (which will be wrapped in a response object).
	 * Error objects are returned as-is without caching.
	 *
	 * @param WP_REST_Request<array<string, mixed>>   $request            The request object.
	 * @param WP_REST_Response|\WP_Error|array|object $response           The response to potentially cache.
	 * @param array                                   $cached_config      Caching configuration from build_cache_config().
	 * @param bool                                    $add_cache_headers  Whether to add cache control headers.
	 *
	 * @return WP_REST_Response|\WP_Error The response with appropriate cache headers.
	 */
	private function maybe_cache_response( WP_REST_Request $request, $response, array $cached_config, bool $add_cache_headers ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );

		$cached = false;

		$status = $response->get_status();
		if ( $status >= 200 && $status <= 299 ) {
			$data       = $response->get_data();
			$entity_ids = is_array( $data ) ? $this->extract_entity_ids_from_response( $data, $request, $cached_config['endpoint_id'] ) : array();

			$response_headers  = $response->get_headers();
			$cacheable_headers = $this->get_headers_to_cache(
				$response_headers,
				$cached_config['include_headers'],
				$cached_config['exclude_headers'],
				$request,
				$response,
				$cached_config['endpoint_id']
			);

			$etag_data = is_array( $data ) ? $this->get_data_for_etag( $data, $request, $cached_config['endpoint_id'] ) : $data;
			$etag      = '"' . md5( $cached_config['cache_key'] . wp_json_encode( $etag_data ) ) . '"';

			$this->store_cached_response(
				$cached_config['cache_key'],
				$data,
				$status,
				$cached_config['entity_type'],
				$entity_ids,
				$cached_config['cache_ttl'],
				$cached_config['relevant_hooks'],
				$cacheable_headers,
				$etag
			);

			$cached = true;
		}

		$response->header( 'X-WC-Cache', $cached ? 'MISS' : 'SKIP' );

		return $add_cache_headers ?
			$this->maybe_add_cache_headers( $request, $response, $cached_config ) :
			$response;
	}

	/**
	 * Add cache control headers to a response.
	 *
	 * This method generates an ETag from the response data and returns a 304 Not Modified
	 * if the client's If-None-Match header matches. It can be used both with and without
	 * backend caching.
	 *
	 * @param WP_REST_Request<array<string, mixed>>   $request       The request object.
	 * @param WP_REST_Response|\WP_Error|array|object $response      The response to add headers to.
	 * @param array                                   $cached_config Caching configuration from build_cache_config().
	 *
	 * @return WP_REST_Response|\WP_Error The response with cache headers.
	 */
	private function maybe_add_cache_headers( WP_REST_Request $request, $response, array $cached_config ) { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );

		$status = $response->get_status();
		if ( $status < 200 || $status > 299 ) {
			return $response;
		}

		$response_data      = $response->get_data();
		$response_etag_data = is_array( $response_data ) ? $this->get_data_for_etag( $response_data, $request, $cached_config['endpoint_id'] ) : $response_data;
		$response_etag      = '"' . md5( $cached_config['cache_key'] . wp_json_encode( $response_etag_data ) ) . '"';

		$request_etag = $request->get_header( 'if-none-match' );

		$legacy_proxy        = wc_get_container()->get( LegacyProxy::class );
		$is_user_logged_in   = $legacy_proxy->call_function( 'is_user_logged_in' );
		$cache_visibility    = $cached_config['vary_by_user'] && $is_user_logged_in ? 'private' : 'public';
		$cache_control_value = $cache_visibility . ', must-revalidate, max-age=' . $cached_config['cache_ttl'];

		if ( $request_etag === $response_etag ) {
			$not_modified_response = $this->create_not_modified_response( $response_etag, $cache_control_value, $request, $cached_config['endpoint_id'] );
			if ( $not_modified_response ) {
				return $not_modified_response;
			}
		}

		$response->header( 'ETag', $response_etag );
		$response->header( 'Cache-Control', $cache_control_value );

		if ( ! array_key_exists( 'X-WC-Cache', $response->get_headers() ) ) {
			$response->header( 'X-WC-Cache', 'HEADERS' );
		}

		return $response;
	}

	/**
	 * Create a 304 Not Modified response if allowed by filters.
	 *
	 * @param string                                $etag                The ETag value.
	 * @param string                                $cache_control_value The Cache-Control header value.
	 * @param WP_REST_Request<array<string, mixed>> $request             The request object.
	 * @param string|null                           $endpoint_id         The endpoint identifier.
	 *
	 * @return WP_REST_Response|null 304 response if allowed, null otherwise.
	 */
	private function create_not_modified_response( string $etag, string $cache_control_value, WP_REST_Request $request, ?string $endpoint_id ): ?WP_REST_Response { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$response = new WP_REST_Response( null, 304 );
		$response->header( 'ETag', $etag );
		$response->header( 'Cache-Control', $cache_control_value );
		$response->header( 'X-WC-Cache', 'MATCH' );

		/**
		 * Filter the 304 Not Modified response before sending.
		 *
		 * @since 10.5.0
		 *
		 * @param WP_REST_Response|false $response    The 304 response object, or false to prevent sending it.
		 * @param WP_REST_Request        $request     The request object.
		 * @param string|null            $endpoint_id The endpoint identifier.
		 */
		$filtered_response = apply_filters( 'woocommerce_rest_api_not_modified_response', $response, $request, $endpoint_id );

		return false === $filtered_response ? null : rest_ensure_response( $filtered_response );
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
	 * Get data for ETag generation.
	 *
	 * Override in classes to exclude fields that change on each request
	 * (e.g., random recommendations, timestamps).
	 *
	 * @param array                                 $data        Response data.
	 * @param WP_REST_Request<array<string, mixed>> $request     The request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return array Cleaned data for ETag generation.
	 */
	protected function get_data_for_etag( array $data, WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		return $data;
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
	 * @param WP_REST_Request<array<string, mixed>> $request     The request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return bool True to make cache user-specific, false otherwise.
	 */
	protected function response_cache_vary_by_user( WP_REST_Request $request, ?string $endpoint_id = null ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		return true;
	}

	/**
	 * Get the cache TTL (time to live) for cached responses.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('cache_ttl' key).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request     The request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return int Cache TTL in seconds.
	 */
	protected function get_ttl_for_cached_response( WP_REST_Request $request, ?string $endpoint_id = null ): int { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
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
	 * @param WP_REST_Request<array<string, mixed>> $request     Request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return array Array of hook names to track.
	 */
	protected function get_hooks_relevant_to_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		return array();
	}

	/**
	 * Get the names of response headers to include in caching.
	 *
	 * When this returns an array, ONLY the headers whose names are returned
	 * will be included in the cache (subject to always-excluded headers).
	 * When this returns false, all headers will be included except those returned
	 * by get_response_headers_to_exclude_from_caching().
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('include_headers' key).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request     Request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return array|false Array of header names to include (case-insensitive), or false to use exclusion logic.
	 */
	protected function get_response_headers_to_include_in_caching( WP_REST_Request $request, ?string $endpoint_id = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
		return false;
	}

	/**
	 * Get the names of response headers to exclude from caching.
	 *
	 * These headers will not be stored in the cache, in addition to the
	 * always-excluded headers (X-WC-Cache, Set-Cookie, Date, Expires, Last-Modified,
	 * Age, ETag, Cache-Control, Pragma).
	 *
	 * This is only used when get_response_headers_to_include_in_caching() returns false.
	 *
	 * This can be customized per-endpoint via the config array
	 * passed to with_cache() ('exclude_headers' key).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request     Request object.
	 * @param string|null                           $endpoint_id Optional friendly identifier for the endpoint.
	 *
	 * @return array Array of header names to exclude (case-insensitive).
	 */
	protected function get_response_headers_to_exclude_from_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
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
	 * @param array                                 $response_data Response data.
	 * @param WP_REST_Request<array<string, mixed>> $request       The request object.
	 * @param string|null                           $endpoint_id   Optional friendly identifier for the endpoint.
	 *
	 * @return array Array of entity IDs.
	 */
	protected function extract_entity_ids_from_response( array $response_data, WP_REST_Request $request, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
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

		// Filter out false values but keep 0 and empty strings as they could be valid IDs.
		// Note: null values can't exist here because isset() checks above exclude them.
		return array_unique(
			array_filter( $ids, fn ( $id ) => false !== $id )
		);
	}

	/**
	 * Filter response headers to get only those that should be cached.
	 *
	 * The filtering process follows these steps:
	 * 1. If $include_headers is an array, only those headers are included (case-insensitive).
	 *    If $include_headers is false, all headers are included except those in $exclude_headers.
	 * 2. Always-excluded headers (X-WC-Cache, Set-Cookie, Date, etc.) are removed.
	 * 3. The woocommerce_rest_api_cached_headers filter is applied, receiving both the candidate
	 *    headers list and all available headers. This allows filters to both add and remove
	 *    headers from the caching list.
	 * 4. Always-excluded headers are enforced again post-filter to prevent filters from
	 *    re-introducing dangerous headers like Set-Cookie.
	 * 5. Only headers from the response that are in the filtered list are returned.
	 *
	 * @param array                                 $nominal_headers Response headers.
	 * @param array|false                           $include_headers Header names to include (false to use exclusion logic).
	 * @param array                                 $exclude_headers Header names to exclude (case-insensitive).
	 * @param WP_REST_Request<array<string, mixed>> $request The request object.
	 * @param WP_REST_Response                      $response        The response object.
	 * @param string|null                           $endpoint_id     Optional friendly identifier for the endpoint.
	 *
	 * @return array Filtered headers array.
	 */
	private function get_headers_to_cache( array $nominal_headers, $include_headers, array $exclude_headers, WP_REST_Request $request, WP_REST_Response $response, ?string $endpoint_id ): array { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		// Step 1: Determine which headers to consider based on include/exclude.
		if ( false !== $include_headers ) {
			$include_headers_lowercase = array_map( 'strtolower', $include_headers );
			$headers_to_cache          = array_filter(
				$nominal_headers,
				fn( $name ) => in_array( strtolower( $name ), $include_headers_lowercase, true ),
				ARRAY_FILTER_USE_KEY
			);
		} else {
			$exclude_headers_lowercase = array_map( 'strtolower', $exclude_headers );
			$headers_to_cache          = array_filter(
				$nominal_headers,
				fn( $name ) => ! in_array( strtolower( $name ), $exclude_headers_lowercase, true ),
				ARRAY_FILTER_USE_KEY
			);
		}

		// Step 2: Remove always-excluded headers.
		$always_exclude_lowercase = array_map( 'strtolower', self::$always_excluded_headers );
		$headers_to_cache         = array_filter(
			$headers_to_cache,
			fn( $name ) => ! in_array( strtolower( $name ), $always_exclude_lowercase, true ),
			ARRAY_FILTER_USE_KEY
		);

		// Step 3: Apply filter to header names.
		$cached_header_names = array_keys( $headers_to_cache );
		$all_header_names    = array_keys( $nominal_headers );

		/**
		 * Filter the list of response header names to cache.
		 *
		 * @since 10.5.0
		 *
		 * @param array            $cached_header_names Candidate list of header names to cache.
		 * @param array            $all_header_names    All header names available in the response.
		 * @param WP_REST_Request  $request             The request object.
		 * @param WP_REST_Response $response            The response object.
		 * @param string|null      $endpoint_id         Optional friendly identifier for the endpoint.
		 * @param object           $controller          The controller instance.
		 *
		 * @return array Filtered list of header names to cache.
		 */
		$filtered_header_names = apply_filters(
			'woocommerce_rest_api_cached_headers',
			$cached_header_names,
			$all_header_names,
			$request,
			$response,
			$endpoint_id,
			$this
		);

		// Step 4: Enforce always-excluded headers post-filter.
		$filtered_header_names_lowercase = array_map( 'strtolower', $filtered_header_names );
		$reintroduced_headers            = array_filter(
			$filtered_header_names,
			fn( $name ) => in_array( strtolower( $name ), $always_exclude_lowercase, true )
		);

		if ( ! empty( $reintroduced_headers ) ) {
			$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
			$legacy_proxy->call_function(
				'wc_doing_it_wrong',
				__METHOD__,
				sprintf(
					/* translators: %s: comma-separated list of header names */
					'The woocommerce_rest_api_cached_headers filter attempted to cache always-excluded headers: %s. These headers have been removed for security reasons.',
					implode( ', ', $reintroduced_headers )
				),
				'10.5.0'
			);

			$filtered_header_names_lowercase = array_filter(
				$filtered_header_names_lowercase,
				fn( $name ) => ! in_array( $name, $always_exclude_lowercase, true )
			);
		}

		// Step 5: Return only the headers that are in the filtered list.
		return array_filter(
			$nominal_headers,
			fn( $name ) => in_array( strtolower( $name ), $filtered_header_names_lowercase, true ),
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Get cache key information that uniquely identifies a request.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request      The request object.
	 * @param bool                                  $vary_by_user Whether to include user ID in cache key.
	 * @param string|null                           $endpoint_id  Optional friendly identifier for the endpoint.
	 *
	 * @return array Array of cache key information parts.
	 */
	protected function get_key_info_for_cached_response( WP_REST_Request $request, bool $vary_by_user = false, ?string $endpoint_id = null ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed, Squiz.Commenting.FunctionComment.IncorrectTypeHint
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
			$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
			// @phpstan-ignore-next-line argument.type -- get_current_user_id returns int at runtime.
			$user_id           = intval( $legacy_proxy->call_function( 'get_current_user_id' ) );
			$cache_key_parts[] = "user_{$user_id}";
		}

		return $cache_key_parts;
	}

	/**
	 * Generate a cache key for a given request.
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request      The request object.
	 * @param string                                $entity_type  The entity type.
	 * @param bool                                  $vary_by_user Whether to include user ID in cache key.
	 * @param string|null                           $endpoint_id  Optional friendly identifier for the endpoint.
	 *
	 * @return string Cache key.
	 */
	private function get_key_for_cached_response( WP_REST_Request $request, string $entity_type, bool $vary_by_user = false, ?string $endpoint_id = null ): string { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$cache_key_parts = $this->get_key_info_for_cached_response( $request, $vary_by_user, $endpoint_id );

		/**
		 * Filter the information used to generate the cache key for a REST API request.
		 *
		 * Allows customization of what uniquely identifies a request for caching purposes.
		 *
		 * @since 10.5.0
		 *
		 * @param array           $cache_key_parts Array of cache key information parts.
		 * @param WP_REST_Request<array<string, mixed>> $request         The request object.
		 * @param bool            $vary_by_user    Whether user ID is included in cache key.
		 * @param string|null     $endpoint_id     Optional friendly identifier for the endpoint (passed to with_cache).
		 * @param object          $controller      The controller instance.
		 *
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
	 *
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

		$json = wp_json_encode( $cache_hash_data );
		return md5( false === $json ? '' : $json );
	}

	/**
	 * Get a cached response, but only if it's valid (otherwise the cached response will be invalidated).
	 *
	 * @param WP_REST_Request<array<string, mixed>> $request              The request object.
	 * @param array                                 $cached_config        Built caching configuration from build_cache_config().
	 * @param bool                                  $cache_headers_enabled Whether to add cache control headers.
	 *
	 * @return WP_REST_Response|null Cached response, or null if not available or has been invalidated.
	 */
	private function get_cached_response( WP_REST_Request $request, array $cached_config, bool $cache_headers_enabled ): ?WP_REST_Response { // phpcs:ignore Squiz.Commenting.FunctionComment.IncorrectTypeHint
		$cache_key      = $cached_config['cache_key'];
		$entity_type    = $cached_config['entity_type'];
		$cache_ttl      = $cached_config['cache_ttl'];
		$relevant_hooks = $cached_config['relevant_hooks'];

		$found  = false;
		$cached = wp_cache_get( $cache_key, self::$cache_group, false, $found );

		if ( ! $found || ! is_array( $cached ) || ! array_key_exists( 'data', $cached ) || ! isset( $cached['entity_versions'], $cached['created_at'] ) ) {
			return null;
		}

		$legacy_proxy    = wc_get_container()->get( LegacyProxy::class );
		$current_time    = $legacy_proxy->call_function( 'time' );
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

		if ( ! is_null( $this->version_string_generator ) ) {
			foreach ( $cached['entity_versions'] as $entity_id => $cached_version ) {
				$version_id      = "{$entity_type}_{$entity_id}";
				$current_version = $this->version_string_generator->get_version( $version_id );
				if ( $current_version !== $cached_version ) {
					wp_cache_delete( $cache_key, self::$cache_group );
					return null;
				}
			}
		}

		// At this point the cached response is valid.

		// Check if client sent an ETag and it matches - if so, return 304 Not Modified.
		$cached_etag  = $cached['etag'] ?? '';
		$request_etag = $request->get_header( 'if-none-match' );

		$response_headers = array();

		if ( $cache_headers_enabled ) {
			$legacy_proxy      = wc_get_container()->get( LegacyProxy::class );
			$is_user_logged_in = $legacy_proxy->call_function( 'is_user_logged_in' );
			$cache_visibility  = $cached_config['vary_by_user'] && $is_user_logged_in ? 'private' : 'public';

			if ( ! empty( $cached_etag ) ) {
				$response_headers['ETag'] = $cached_etag;
			}
			$response_headers['Cache-Control'] = $cache_visibility . ', must-revalidate, max-age=' . $cache_ttl;

			// If the server adds a 'Date' header by itself there will be two such headers in the response.
			// To help disambiguate them, we add also an 'X-WC-Date' header with the proper value.
			// @phpstan-ignore-next-line argument.type -- created_at is int, stored by store_cached_response.
			$created_at                    = gmdate( 'D, d M Y H:i:s', intval( $cached['created_at'] ) ) . ' GMT';
			$response_headers['Date']      = $created_at;
			$response_headers['X-WC-Date'] = $created_at;

			if ( ! empty( $cached_etag ) && $request_etag === $cached_etag ) {
				$cache_control         = $response_headers['Cache-Control'];
				$not_modified_response = $this->create_not_modified_response( $cached_etag, $cache_control, $request, $cached_config['endpoint_id'] );
				if ( $not_modified_response ) {
					$not_modified_response->header( 'Date', $response_headers['Date'] );
					$not_modified_response->header( 'X-WC-Date', $response_headers['X-WC-Date'] );
					return $not_modified_response;
				}
			}
		}

		$response = new WP_REST_Response( $cached['data'], $cached['status_code'] ?? 200 );

		foreach ( $response_headers as $name => $value ) {
			$response->header( $name, $value );
		}

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
	 * @param string $cache_key      The cache key.
	 * @param mixed  $data           The response data to cache.
	 * @param int    $status_code    The HTTP status code of the response.
	 * @param string $entity_type    The entity type.
	 * @param array  $entity_ids     Array of entity IDs in the response.
	 * @param int    $cache_ttl      Cache TTL in seconds.
	 * @param array  $relevant_hooks Hook names to track for invalidation.
	 * @param array  $headers        Response headers to cache.
	 * @param string $etag           ETag for the response.
	 */
	private function store_cached_response( string $cache_key, $data, int $status_code, string $entity_type, array $entity_ids, int $cache_ttl, array $relevant_hooks, array $headers = array(), string $etag = '' ): void {
		$entity_versions = array();
		if ( ! is_null( $this->version_string_generator ) ) {
			foreach ( $entity_ids as $entity_id ) {
				$version_id = "{$entity_type}_{$entity_id}";
				$version    = $this->version_string_generator->get_version( $version_id );
				if ( $version ) {
					$entity_versions[ $entity_id ] = $version;
				}
			}
		}

		$legacy_proxy = wc_get_container()->get( LegacyProxy::class );
		$cache_data   = array(
			'data'            => $data,
			'entity_versions' => $entity_versions,
			'created_at'      => $legacy_proxy->call_function( 'time' ),
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

		if ( ! empty( $etag ) ) {
			$cache_data['etag'] = $etag;
		}

		wp_cache_set( $cache_key, $cache_data, self::$cache_group, $cache_ttl );
	}

	/**
	 * Handle rest_send_nocache_headers filter to prevent WordPress from overriding our cache headers.
	 *
	 * @internal
	 *
	 * @param bool $send_no_cache_headers Whether to send no-cache headers.
	 *
	 * @return bool False if we're handling caching for this request, original value otherwise.
	 */
	public function handle_rest_send_nocache_headers( bool $send_no_cache_headers ): bool {
		if ( ! $this->is_handling_cached_endpoint ) {
			return $send_no_cache_headers;
		}

		$this->is_handling_cached_endpoint = false;
		return false;
	}
}
