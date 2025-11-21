<?php
/**
 * RestApiCacheTest class file.
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Internal\Traits;

use Automattic\WooCommerce\Internal\Caches\VersionStringGenerator;
use Automattic\WooCommerce\Internal\Traits\RestApiCache;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use WC_REST_Unit_Test_Case;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

/**
 * Tests for the simplified RestApiCache trait.
 */
class RestApiCacheTest extends WC_REST_Unit_Test_Case {

	private const CACHE_GROUP = 'woocommerce_rest_api_cache';

	/**
	 * System under test.
	 *
	 * @var object
	 */
	private $sut;

	/**
	 * @var VersionStringGenerator
	 */
	private VersionStringGenerator $version_generator;

	/**
	 * @var int
	 */
	private int $fixed_time = 1234567890;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'time'                      => fn() => $this->fixed_time,
				'get_current_user_id'       => fn() => 1,
				'wp_using_ext_object_cache' => fn() => true,
			)
		);

		// Needed to ensure VersionStringGenerator uses the mocked wp_using_ext_object_cache.
		$this->reset_container_resolutions();

		$this->version_generator = wc_get_container()->get( VersionStringGenerator::class );
		$this->sut               = $this->create_test_controller();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );
		$this->sut->register_routes();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * @testdox First request returns MISS and caches response, second request returns HIT with cached data.
	 */
	public function test_caching_workflow_miss_then_hit() {
		$response1 = $this->query_endpoint( 'multiple_entities' );

		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertEquals( $this->sut->responses['multiple_entities'], $response1->get_data() );

		$cache_keys = $this->get_all_cache_keys();
		$this->assertCount( 1, $cache_keys );
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertIsArray( $cached_entry );
		$this->assertArrayHasKey( 'data', $cached_entry );
		$this->assertArrayHasKey( 'entity_versions', $cached_entry );
		$this->assertArrayHasKey( 'created_at', $cached_entry );
		$this->assertEquals( $this->sut->responses['multiple_entities'], $cached_entry['data'] );
		$this->assertEquals( $this->fixed_time, $cached_entry['created_at'] );
		$this->assertCount( 2, $cached_entry['entity_versions'] );

		$this->assertNotEmpty( $this->version_generator->get_version( 'product_2' ) );
		$this->assertNotEmpty( $this->version_generator->get_version( 'product_3' ) );

		$modified_data        = array(
			array(
				'id'   => 999,
				'name' => 'Modified Product',
			),
		);
		$cached_entry['data'] = $modified_data;
		wp_cache_set( $cache_key, $cached_entry, self::CACHE_GROUP, HOUR_IN_SECONDS );

		$response2 = $this->query_endpoint( 'multiple_entities' );
		$this->assertCacheHeader( $response2, 'HIT' );
		$this->assertEquals( $modified_data, $response2->get_data() );
	}

	/**
	 * @testdox Expired cache entries are rejected and deleted.
	 */
	public function test_expired_cache_entries_are_rejected() {
		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$this->fixed_time += HOUR_IN_SECONDS + 1;

		$response3 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response3, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );
	}

	/**
	 * @testdox Cache is invalidated when endpoint-level relevant hooks change.
	 */
	public function test_cache_invalidated_when_endpoint_hooks_change() {
		$response1 = $this->query_endpoint( 'with_endpoint_hooks' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'with_endpoint_hooks' );
		$this->assertCacheHeader( $response2, 'HIT' );

		add_filter( 'test_endpoint_hook_for_caching', '__return_true' );
		$this->fixed_time += 1;

		$response3 = $this->query_endpoint( 'with_endpoint_hooks' );
		$this->assertCacheHeader( $response3, 'MISS' );

		remove_filter( 'test_endpoint_hook_for_caching', '__return_true' );
	}

	/**
	 * @testdox Cache is invalidated when controller-level relevant hooks change.
	 */
	public function test_cache_invalidated_when_controller_hooks_change() {
		$this->sut->controller_hooks = array( 'test_controller_hook_for_caching' );

		$response1 = $this->query_endpoint( 'with_controller_hooks' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'with_controller_hooks' );
		$this->assertCacheHeader( $response2, 'HIT' );

		add_filter( 'test_controller_hook_for_caching', '__return_false' );
		$this->fixed_time += 1;

		$response3 = $this->query_endpoint( 'with_controller_hooks' );
		$this->assertCacheHeader( $response3, 'MISS' );

		remove_filter( 'test_controller_hook_for_caching', '__return_false' );
	}

	/**
	 * @testdox Cache is invalidated when entity versions change.
	 * @testWith [2, true]
	 *           [3, true]
	 *           [999, false]
	 *
	 * @param int  $entity_id                   Entity ID to modify.
	 * @param bool $cache_invalidation_expected Whether cache invalidation is expected.
	 */
	public function test_cache_invalidated_when_entity_version_changes( int $entity_id, bool $cache_invalidation_expected ) {
		$response1 = $this->query_endpoint( 'multiple_entities' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$response2 = $this->query_endpoint( 'multiple_entities' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$this->version_generator->generate_version( "product_{$entity_id}" );
		$this->fixed_time += 1;

		$response3 = $this->query_endpoint( 'multiple_entities' );
		$this->assertCacheHeader( $response3, $cache_invalidation_expected ? 'MISS' : 'HIT' );
	}

	/**
	 * @testdox Cache keys differ based on query string parameters.
	 */
	public function test_cache_key_depends_on_query_string() {
		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'single_entity', array( 'foo' => 'bar' ) );
		$this->assertCacheHeader( $response2, 'MISS' );
		$this->assertCount( 2, $this->get_all_cache_keys() );

		$response3 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response3, 'HIT' );
	}

	/**
	 * @testdox Cache keys differ based on HTTP method.
	 */
	public function test_cache_key_depends_on_http_method() {
		$response1 = $this->query_endpoint( 'multi_method', null, 'GET' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'multi_method', null, 'POST' );
		$this->assertCacheHeader( $response2, 'MISS' );
		$this->assertCount( 2, $this->get_all_cache_keys() );

		$response3 = $this->query_endpoint( 'multi_method', null, 'GET' );
		$this->assertCacheHeader( $response3, 'HIT' );

		$response4 = $this->query_endpoint( 'multi_method', null, 'POST' );
		$this->assertCacheHeader( $response4, 'HIT' );
	}

	/**
	 * @testdox Caching is skipped when _skip_cache parameter is set.
	 * @testWith ["1"]
	 *           ["true"]
	 *
	 * @param string $skip_cache_value Value for _skip_cache parameter.
	 */
	public function test_skip_cache_parameter_bypasses_caching( $skip_cache_value ) {
		$response = $this->query_endpoint( 'single_entity', array( '_skip_cache' => $skip_cache_value ) );
		$this->assertCacheHeader( $response, 'SKIP' );
		$this->assertCount( 0, $this->get_all_cache_keys() );
	}

	/**
	 * @testdox Caching is skipped when entity versions cache is disabled.
	 */
	public function test_caching_skipped_when_entity_cache_disabled() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'wp_using_ext_object_cache' => fn() => false )
		);
		$this->reset_container_resolutions();
		$this->sut->reinitialize_cache();

		$response = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response, null );
		$this->assertCount( 0, $this->get_all_cache_keys() );
	}

	/**
	 * @testdox Caching is skipped when filter returns false.
	 */
	public function test_caching_skipped_when_filter_returns_false() {
		add_filter( 'woocommerce_rest_api_enable_response_caching', '__return_false' );

		$response = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response, 'SKIP' );
		$this->assertCount( 0, $this->get_all_cache_keys() );

		remove_all_filters( 'woocommerce_rest_api_enable_response_caching' );
	}

	/**
	 * @testdox Custom entity_type can be specified per endpoint.
	 */
	public function test_custom_entity_type_per_endpoint() {
		$response1 = $this->query_endpoint( 'custom_entity_type' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );
		$this->assertNotEmpty( $this->version_generator->get_version( 'custom_thing_100' ) );

		$response2 = $this->query_endpoint( 'custom_entity_type' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$this->version_generator->generate_version( 'custom_thing_100' );
		$response3 = $this->query_endpoint( 'custom_entity_type' );
		$this->assertCacheHeader( $response3, 'MISS' );
	}

	/**
	 * @testdox Cache varies by user ID when vary_by_user is true (default).
	 */
	public function test_cache_varies_by_user() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 1 )
		);
		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 2 )
		);
		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response2, 'MISS' );
		$this->assertCount( 2, $this->get_all_cache_keys() );
	}

	/**
	 * @testdox Cache does not vary by user when vary_by_user is false.
	 */
	public function test_cache_does_not_vary_by_user_when_disabled() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 1 )
		);
		$response1 = $this->query_endpoint( 'no_vary_by_user' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 2 )
		);
		$response2 = $this->query_endpoint( 'no_vary_by_user' );
		$this->assertCacheHeader( $response2, 'HIT' );
		$this->assertCount( 1, $this->get_all_cache_keys() );
	}

	/**
	 * @testdox Endpoint with endpoint_id works correctly.
	 */
	public function test_endpoint_id_is_supported() {
		$response1 = $this->query_endpoint( 'with_endpoint_id' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertEquals( $this->sut->responses['with_endpoint_id'], $response1->get_data() );

		$response2 = $this->query_endpoint( 'with_endpoint_id' );
		$this->assertCacheHeader( $response2, 'HIT' );
		$this->assertEquals( $this->sut->responses['with_endpoint_id'], $response2->get_data() );
	}

	/**
	 * @testdox Filter woocommerce_rest_api_cache_key_info allows customizing cache key parts.
	 */
	public function test_cache_key_info_filter() {
		$response1 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$filter_called = false;
		add_filter(
			'woocommerce_rest_api_cache_key_info',
			function ( $cache_key_parts, $request, $vary_by_user, $endpoint_id, $controller ) use ( &$filter_called ) {
				$filter_called = true;
				$this->assertIsArray( $cache_key_parts );
				$this->assertInstanceOf( WP_REST_Request::class, $request );
				$this->assertIsBool( $vary_by_user );
				$this->assertIsObject( $controller );
				$cache_key_parts[] = 'custom_part';
				return $cache_key_parts;
			},
			10,
			5
		);

		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertTrue( $filter_called, 'Filter should have been called' );
		$this->assertCacheHeader( $response2, 'MISS' );
		$this->assertCount( 2, $this->get_all_cache_keys() );

		remove_all_filters( 'woocommerce_rest_api_cache_key_info' );
	}

	/**
	 * @testdox Custom cache TTL can be specified per endpoint.
	 */
	public function test_custom_cache_ttl() {
		$response1 = $this->query_endpoint( 'custom_ttl' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$response2 = $this->query_endpoint( 'custom_ttl' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$this->fixed_time += 11;

		$response3 = $this->query_endpoint( 'custom_ttl' );
		$this->assertCacheHeader( $response3, 'MISS' );
	}

	/**
	 * @testdox Custom extract_entity_ids_from_response method can be overridden.
	 */
	public function test_custom_extract_entity_ids_from_response_override() {
		$custom_controller = new class() extends WP_REST_Controller {
			// phpcs:disable Squiz.Commenting
			use RestApiCache;

			public $custom_extractor_called = false;

			public function __construct() {
				$this->namespace = 'wc/v3';
				$this->rest_base = 'custom_extraction_test';
				$this->initialize_rest_api_cache();
			}

			protected function get_default_response_entity_type(): ?string {
				return 'product';
			}

			protected function extract_entity_ids_from_response( array $response_data, WP_REST_Request $request, ?string $endpoint_id = null ): array {
				$this->custom_extractor_called = true;
				return isset( $response_data['product_id'] ) ? array( $response_data['product_id'] ) : array();
			}

			public function register_routes() {
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base,
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) {
								unset( $request ); // Avoid parameter not used PHPCS errors.
								return new WP_REST_Response(
									array(
										'product_id' => 999,
										'name'       => 'Custom Product',
									),
									200
								);
							}
						),
						'permission_callback' => '__return_true',
					)
				);
			}
			// phpcs:enable Squiz.Commenting
		};

		$custom_controller->register_routes();

		$request   = new WP_REST_Request( 'GET', '/wc/v3/custom_extraction_test' );
		$response1 = $this->server->dispatch( $request );

		$this->assertTrue( $custom_controller->custom_extractor_called, 'Custom extractor should have been called' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertNotEmpty( $this->version_generator->get_version( 'product_999' ) );

		$response2 = $this->server->dispatch( $request );
		$this->assertCacheHeader( $response2, 'HIT' );
	}

	/**
	 * @testdox Non-array responses (scalars, null, 204 No Content) are cached without crashing.
	 * @testWith ["scalar"]
	 *           ["null"]
	 *           ["no_content"]
	 *
	 * @param string $response_type Type of response to test.
	 */
	public function test_non_array_responses_are_handled_gracefully( string $response_type ) {
		$response1 = $this->query_endpoint( 'non_array_response', array( 'type' => $response_type ) );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'non_array_response', array( 'type' => $response_type ) );
		$this->assertCacheHeader( $response2, 'HIT' );
		$this->assertEquals( $response1->get_data(), $response2->get_data() );
	}

	/**
	 * Test that controllers can return raw arrays without wrapping in WP_REST_Response.
	 */
	public function test_raw_array_responses_are_cached() {
		$response1 = $this->query_endpoint( 'raw_array_response' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertInstanceOf( WP_REST_Response::class, $response1 );
		$this->assertSame( 200, $response1->get_status() );
		$this->assertIsArray( $response1->get_data() );
		$this->assertArrayHasKey( 'id', $response1->get_data() );
		$this->assertSame( 42, $response1->get_data()['id'] );

		$response2 = $this->query_endpoint( 'raw_array_response' );
		$this->assertCacheHeader( $response2, 'HIT' );
		$this->assertEquals( $response1->get_data(), $response2->get_data() );
	}

	/**
	 * @testdox SKIP header is added to raw array responses when cache is skipped.
	 */
	public function test_skip_header_added_for_raw_array_responses() {
		$response = $this->query_endpoint( 'raw_array_response', array( '_skip_cache' => 'true' ) );
		$this->assertCacheHeader( $response, 'SKIP' );
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertIsArray( $response->get_data() );
		$this->assertArrayHasKey( 'id', $response->get_data() );
		$this->assertSame( 42, $response->get_data()['id'] );
		$this->assertCount( 0, $this->get_all_cache_keys() );
	}

	/**
	 * Query an endpoint and return the response.
	 *
	 * @param string      $endpoint_name Endpoint name.
	 * @param array|null  $query_params  Optional query parameters.
	 * @param string|null $method        Optional HTTP method (default: GET).
	 */
	private function query_endpoint( $endpoint_name, $query_params = null, $method = null ) {
		$request = new WP_REST_Request( $method ?? 'GET', "/wc/v3/rest_api_cache_test/{$endpoint_name}" );
		if ( ! is_null( $query_params ) ) {
			$request->set_query_params( $query_params );
		}
		return $this->server->dispatch( $request );
	}

	/**
	 * Assert cache header value (HIT, MISS, SKIP, or null for no header).
	 *
	 * @param WP_REST_Response $response       The response to check.
	 * @param string|null      $expected_value Expected header value.
	 */
	private function assertCacheHeader( $response, ?string $expected_value ) {
		$this->assertInstanceOf( WP_REST_Response::class, $response );
		if ( is_null( $expected_value ) ) {
			$this->assertArrayNotHasKey( 'X-WC-Cache', $response->get_headers() );
		} else {
			$this->assertEquals( $expected_value, $response->get_headers()['X-WC-Cache'] );
		}
	}

	/**
	 * Get all cache keys (works with unit test memory cache only).
	 */
	private function get_all_cache_keys(): array {
		global $wp_object_cache;
		return isset( $wp_object_cache->cache[ self::CACHE_GROUP ] )
			? array_keys( $wp_object_cache->cache[ self::CACHE_GROUP ] )
			: array();
	}

	/**
	 * Create a test controller.
	 */
	private function create_test_controller() {
		return new class() extends WP_REST_Controller {
			// phpcs:disable Squiz.Commenting
			use RestApiCache;

			public $responses = array(
				'single_entity'         => array(
					'id'   => 1,
					'name' => 'Product 1',
				),
				'multiple_entities'     => array(
					array(
						'id'   => 2,
						'name' => 'Product 2',
					),
					array(
						'id'   => 3,
						'name' => 'Product 3',
					),
				),
				'custom_entity_type'    => array(
					'id'   => 100,
					'name' => 'Custom Thing 100',
				),
				'no_vary_by_user'       => array(
					'id'   => 50,
					'name' => 'Shared Product',
				),
				'with_endpoint_id'      => array(
					'id'   => 60,
					'name' => 'Product with Endpoint ID',
				),
				'custom_ttl'            => array(
					'id'   => 70,
					'name' => 'Product with Custom TTL',
				),
				'with_endpoint_hooks'   => array(
					'id'   => 80,
					'name' => 'Product with Endpoint Hooks',
				),
				'with_controller_hooks' => array(
					'id'   => 90,
					'name' => 'Product with Controller Hooks',
				),
			);

			public $default_entity_type   = 'product';
			public $default_vary_by_user  = true;
			public $endpoint_vary_by_user = array();
			public $endpoint_ids          = array();
			public $controller_hooks      = array();

			public function __construct() {
				$this->namespace = 'wc/v3';
				$this->rest_base = 'rest_api_cache_test';
				$this->initialize_rest_api_cache();
			}

			public function register_routes() {
				$this->register_cached_route( 'single_entity' );
				$this->register_cached_route( 'multiple_entities' );
				$this->register_cached_route( 'custom_entity_type', array( 'entity_type' => 'custom_thing' ) );
				$this->register_cached_route( 'non_array_response', array( 'entity_type' => 'custom_thing' ), true );
				$this->register_cached_route( 'raw_array_response', array(), false, true );
				$this->register_cached_route( 'no_vary_by_user', array( 'vary_by_user' => false ) );
				$this->register_cached_route( 'with_endpoint_id', array( 'endpoint_id' => 'test_endpoint' ) );
				$this->register_cached_route( 'custom_ttl', array( 'cache_ttl' => 10 ) );
				$this->register_multi_method_route();
				$this->register_cached_route( 'with_endpoint_hooks', array( 'relevant_hooks' => array( 'test_endpoint_hook_for_caching' ) ) );
				$this->register_cached_route( 'with_controller_hooks' );
			}

			private function register_cached_route( string $endpoint, array $cache_args = array(), bool $non_array_request = false, bool $raw_response = false ) {
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/' . $endpoint,
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) use ( $endpoint, $non_array_request, $raw_response ) {
								if ( $raw_response ) {
									return $this->handle_raw_array_request( $endpoint, $request );
								}
								return $non_array_request ?
									$this->handle_non_array_request( $request ) :
									$this->handle_request( $endpoint, $request );
							},
							$cache_args
						),
						'permission_callback' => '__return_true',
					)
				);
			}


			private function register_multi_method_route() {
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/multi_method',
					array(
						'methods'             => array( 'GET', 'POST' ),
						'callback'            => $this->with_cache(
							function ( $request ) {
								$method = $request->get_method();
								return new WP_REST_Response(
									array(
										'id'     => 'GET' === $method ? 10 : 20,
										'method' => $method,
									),
									200
								);
							}
						),
						'permission_callback' => '__return_true',
					)
				);
			}

			protected function get_default_response_entity_type(): ?string {
				return $this->default_entity_type;
			}


			protected function response_cache_vary_by_user( WP_REST_Request $request, ?string $endpoint_id = null ): bool {
				if ( ! is_null( $endpoint_id ) && isset( $this->endpoint_vary_by_user[ $endpoint_id ] ) ) {
					return $this->endpoint_vary_by_user[ $endpoint_id ];
				}
				return $this->default_vary_by_user;
			}

			protected function get_hooks_relevant_to_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array {
				return $this->controller_hooks;
			}

			private function handle_request( string $endpoint, WP_REST_Request $request ) {
				return new WP_REST_Response( $this->responses[ $endpoint ], 200 );
			}

			private function handle_raw_array_request( string $endpoint, WP_REST_Request $request ) {
				return array(
					'id'   => 42,
					'name' => 'Raw Array Item',
				);
			}

			private function handle_non_array_request( WP_REST_Request $request ) {
				$type = $request->get_param( 'type' );
				switch ( $type ) {
					case 'scalar':
						return new WP_REST_Response( 'string_value', 200 );
					case 'no_content':
						return new WP_REST_Response( null, 204 );
					default:
						return new WP_REST_Response( null, 200 );
				}
			}

			public function reinitialize_cache() {
				$this->initialize_rest_api_cache();
			}

			// phpcs:enable Squiz.Commenting
		};
	}
}
