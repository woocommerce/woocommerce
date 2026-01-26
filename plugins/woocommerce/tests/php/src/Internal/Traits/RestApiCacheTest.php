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

		remove_all_filters( 'woocommerce_rest_api_not_modified_response' );

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'time'                      => fn() => $this->fixed_time,
				'get_current_user_id'       => fn() => 1,
				'is_user_logged_in'         => fn() => true,
				'wp_using_ext_object_cache' => fn() => true,
			)
		);

		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'yes' );
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );

		// Needed to ensure VersionStringGenerator uses the mocked wp_using_ext_object_cache.
		$this->reset_container_resolutions();

		$this->version_generator = wc_get_container()->get( VersionStringGenerator::class );
		$this->sut               = $this->create_test_controller();

		// Set default allowed directories to include WC_ABSPATH and temp dir for file tracking tests.
		$this->sut->allowed_directories = array( WC_ABSPATH, sys_get_temp_dir() );

		// Disable file check caching by default so tests can modify files and expect immediate invalidation.
		$this->sut->file_check_interval = 0;

		$this->reset_rest_server();
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		remove_all_filters( 'woocommerce_rest_api_not_modified_response' );

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
	 * @testdox Backend caching is skipped when object cache is disabled, but cache headers still work.
	 */
	public function test_caching_skipped_when_entity_cache_disabled() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'wp_using_ext_object_cache' => fn() => false )
		);
		$this->reset_container_resolutions();
		$this->sut->reinitialize_cache();

		$response = $this->query_endpoint( 'single_entity' );

		// Backend caching should be skipped but cache headers should still work.
		$this->assertCount( 0, $this->get_all_cache_keys() );

		$this->assertCacheHeader( $response, 'HEADERS' );
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertArrayHasKey( 'Cache-Control', $headers );
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
	 * @testdox Response headers are cached and restored on cache hit.
	 */
	public function test_response_headers_are_cached() {
		$this->sut->response_headers['standard'] = array(
			'X-Custom-Header'  => 'custom-value',
			'X-Another-Header' => 'another-value',
		);

		$response1 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayHasKey( 'X-Custom-Header', $cached_entry['headers'] );
		$this->assertEquals( 'custom-value', $cached_entry['headers']['X-Custom-Header'] );
		$this->assertArrayHasKey( 'X-Another-Header', $cached_entry['headers'] );
		$this->assertEquals( 'another-value', $cached_entry['headers']['X-Another-Header'] );

		$response2 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();
		$this->assertArrayHasKey( 'X-Custom-Header', $headers );
		$this->assertEquals( 'custom-value', $headers['X-Custom-Header'] );
		$this->assertArrayHasKey( 'X-Another-Header', $headers );
		$this->assertEquals( 'another-value', $headers['X-Another-Header'] );
	}

	/**
	 * @testdox Certain response headers are always excluded from caching.
	 */
	public function test_headers_always_excluded_from_caching() {
		$this->sut->response_headers['standard'] = array(
			'Set-Cookie'     => 'session=abc123',
			'Date'           => 'Mon, 01 Jan 2024 00:00:00 GMT',
			'X-Custom-Valid' => 'should-be-present',
		);

		$response1 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayNotHasKey( 'Set-Cookie', $cached_entry['headers'], 'Set-Cookie should be excluded from cache' );
		$this->assertArrayNotHasKey( 'Date', $cached_entry['headers'], 'Date should be excluded from cache' );
		$this->assertArrayHasKey( 'X-Custom-Valid', $cached_entry['headers'] );
		$this->assertEquals( 'should-be-present', $cached_entry['headers']['X-Custom-Valid'] );

		$response2 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();

		$this->assertArrayNotHasKey( 'Set-Cookie', $headers, 'Set-Cookie header should not be cached' );
		$this->assertArrayHasKey( 'Date', $headers, 'Date header should be present with cache timestamp' );
		$this->assertNotEquals( 'Mon, 01 Jan 2024 00:00:00 GMT', $headers['Date'], 'Date should be cache timestamp, not original' );
		$this->assertArrayHasKey( 'X-WC-Date', $headers, 'X-WC-Date header should be present' );
		$this->assertEquals( $headers['Date'], $headers['X-WC-Date'], 'X-WC-Date should match Date' );

		$this->assertArrayHasKey( 'X-Custom-Valid', $headers );
		$this->assertEquals( 'should-be-present', $headers['X-Custom-Valid'] );
	}

	/**
	 * @testdox Custom headers can be excluded from caching via controller method and endpoint config.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $use_with_cache_config Whether to use with_cache config (true) or controller method override (false).
	 */
	public function test_custom_headers_excluded_from_caching( bool $use_with_cache_config ) {
		if ( $use_with_cache_config ) {
			$endpoint = 'custom_endpoint_config';
			$this->sut->endpoint_cache_config[ $endpoint ]['config'] = array(
				'exclude_headers' => array( 'X-Custom-Exclude' ),
			);
			$this->sut->reinitialize_cache();

			$this->reset_rest_server();
		} else {
			$endpoint                          = 'standard';
			$this->sut->custom_exclude_headers = array( 'X-Custom-Exclude' );
		}

		$this->sut->response_headers[ $endpoint ] = array(
			'X-Custom-Exclude' => 'should-not-be-cached',
			'X-Custom-Include' => 'should-be-cached',
		);

		$response1 = $this->query_endpoint( $endpoint );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayNotHasKey( 'X-Custom-Exclude', $cached_entry['headers'], 'Custom excluded header should be excluded from cache' );
		$this->assertArrayHasKey( 'X-Custom-Include', $cached_entry['headers'] );
		$this->assertEquals( 'should-be-cached', $cached_entry['headers']['X-Custom-Include'] );

		$response2 = $this->query_endpoint( $endpoint );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();

		$this->assertArrayNotHasKey( 'X-Custom-Exclude', $headers, 'Custom excluded header should not be cached' );

		$this->assertArrayHasKey( 'X-Custom-Include', $headers );
		$this->assertEquals( 'should-be-cached', $headers['X-Custom-Include'] );

		if ( ! $use_with_cache_config ) {
			$this->sut->custom_exclude_headers = array();
		}
	}

	/**
	 * @testdox The woocommerce_rest_api_cached_headers filter can modify which headers are cached.
	 */
	public function test_filter_can_modify_cached_headers() {
		$this->sut->response_headers['standard'] = array(
			'X-Header-One'   => 'value-one',
			'X-Header-Two'   => 'value-two',
			'X-Header-Three' => 'value-three',
		);

		$filter_callback = function ( $cached_header_names, $all_header_names, $request, $response, $endpoint_id, $controller ) {
			unset( $all_header_names, $request, $response, $endpoint_id, $controller ); // Avoid parameter not used PHPCS errors.
			return array_values( array_filter( $cached_header_names, fn( $name ) => 'X-Header-Two' !== $name ) );
		};
		add_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10, 6 );

		$response1 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayHasKey( 'X-Header-One', $cached_entry['headers'] );
		$this->assertArrayNotHasKey( 'X-Header-Two', $cached_entry['headers'], 'Filter should have excluded X-Header-Two' );
		$this->assertArrayHasKey( 'X-Header-Three', $cached_entry['headers'] );

		$response2 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();

		$this->assertArrayHasKey( 'X-Header-One', $headers );
		$this->assertArrayNotHasKey( 'X-Header-Two', $headers, 'Filtered header should not be restored' );
		$this->assertArrayHasKey( 'X-Header-Three', $headers );

		remove_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10 );
	}

	/**
	 * @testdox The woocommerce_rest_api_cached_headers filter can add headers using all_header_names parameter.
	 */
	public function test_filter_can_add_headers_from_all_headers() {
		$this->sut->custom_include_headers = array( 'X-Header-One' );

		$this->sut->response_headers['standard'] = array(
			'X-Header-One'   => 'value-one',
			'X-Header-Two'   => 'value-two',
			'X-Header-Three' => 'value-three',
		);

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		$filter_callback = function ( $cached_header_names, $all_header_names, $request, $response, $endpoint_id, $controller ) {
			if ( in_array( 'X-Header-Two', $all_header_names, true ) ) {
				$cached_header_names[] = 'X-Header-Two';
			}
			if ( in_array( 'X-Header-Three', $all_header_names, true ) ) {
				$cached_header_names[] = 'X-Header-Three';
			}
			return $cached_header_names;
		};
		add_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10, 6 );

		$response1 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayHasKey( 'X-Header-One', $cached_entry['headers'], 'X-Header-One should be cached (original)' );
		$this->assertArrayHasKey( 'X-Header-Two', $cached_entry['headers'], 'X-Header-Two should be cached (added by filter)' );
		$this->assertArrayHasKey( 'X-Header-Three', $cached_entry['headers'], 'X-Header-Three should be cached (added by filter)' );

		$response2 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();

		$this->assertArrayHasKey( 'X-Header-One', $headers );
		$this->assertEquals( 'value-one', $headers['X-Header-One'] );
		$this->assertArrayHasKey( 'X-Header-Two', $headers );
		$this->assertEquals( 'value-two', $headers['X-Header-Two'] );
		$this->assertArrayHasKey( 'X-Header-Three', $headers );
		$this->assertEquals( 'value-three', $headers['X-Header-Three'] );

		remove_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10 );
		$this->sut->custom_include_headers = false;
	}

	/**
	 * @testdox The woocommerce_rest_api_cached_headers filter cannot re-introduce always-excluded headers.
	 */
	public function test_filter_cannot_reintroduce_always_excluded_headers() {
		$this->sut->response_headers['standard'] = array(
			'Set-Cookie'      => 'session=abc123',
			'Cache-Control'   => 'no-cache',
			'X-Custom-Header' => 'custom-value',
		);

		$filter_callback = fn( $cached_header_names, $all_header_names )  => $all_header_names;
		add_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10, 6 );

		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Internal\Traits\RestApiCache::get_headers_to_cache' );

		$response1 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayNotHasKey( 'Set-Cookie', $cached_entry['headers'], 'Set-Cookie should not be cached even when filter returns it' );
		$this->assertArrayNotHasKey( 'Cache-Control', $cached_entry['headers'], 'Cache-Control should not be cached even when filter returns it' );
		$this->assertArrayHasKey( 'X-Custom-Header', $cached_entry['headers'], 'Non-excluded headers should still be cached' );
		$this->assertEquals( 'custom-value', $cached_entry['headers']['X-Custom-Header'] );

		$response2 = $this->query_endpoint( 'standard' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();
		$this->assertArrayNotHasKey( 'Set-Cookie', $headers, 'Set-Cookie should not be in cached response' );
		// Cache-Control IS present because we add our own cache control headers, but it should NOT contain the original "no-cache" value.
		$this->assertArrayHasKey( 'Cache-Control', $headers, 'Cache-Control should be present with our generated cache headers' );
		$this->assertStringNotContainsString( 'no-cache', $headers['Cache-Control'], 'Original no-cache value should not be in Cache-Control' );
		$this->assertStringContainsString( 'max-age', $headers['Cache-Control'], 'Our generated Cache-Control should contain max-age' );
		$this->assertArrayHasKey( 'X-Custom-Header', $headers );

		remove_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10 );
	}

	/**
	 * @testdox A doing_it_wrong notice is emitted when filter tries to re-introduce always-excluded headers.
	 */
	public function test_doing_it_wrong_notice_when_filter_reintroduces_excluded_headers() {
		$this->sut->response_headers['standard'] = array(
			'Set-Cookie'      => 'session=abc123',
			'X-Custom-Header' => 'custom-value',
		);

		$filter_callback = fn( $cached_header_names, $all_header_names )  => $all_header_names;
		add_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10, 6 );

		$this->setExpectedIncorrectUsage( 'Automattic\WooCommerce\Internal\Traits\RestApiCache::get_headers_to_cache' );

		$this->query_endpoint( 'standard' );

		remove_filter( 'woocommerce_rest_api_cached_headers', $filter_callback, 10 );
	}

	/**
	 * @testdox Setting include_headers to false in endpoint config forces exclusion mode even when controller has default inclusion list.
	 */
	public function test_false_include_headers_forces_exclusion_mode() {
		$this->sut->custom_include_headers = array( 'X-Header-One', 'X-Header-Two' );

		$this->sut->endpoint_cache_config['test_false_override']['config'] = array(
			'include_headers' => false,
			'exclude_headers' => array( 'X-Header-Two' ),
		);
		$this->sut->reinitialize_cache();

		$this->reset_rest_server();

		$this->sut->response_headers['test_false_override'] = array(
			'X-Header-One'   => 'value-one',
			'X-Header-Two'   => 'value-two',
			'X-Header-Three' => 'value-three',
		);

		$response1 = $this->query_endpoint( 'test_false_override' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'headers', $cached_entry );
		$this->assertArrayHasKey( 'X-Header-One', $cached_entry['headers'], 'X-Header-One should be cached (not excluded)' );
		$this->assertArrayNotHasKey( 'X-Header-Two', $cached_entry['headers'], 'X-Header-Two should be excluded (exclusion mode used)' );
		$this->assertArrayHasKey( 'X-Header-Three', $cached_entry['headers'], 'X-Header-Three should be cached (not excluded)' );

		$response2 = $this->query_endpoint( 'test_false_override' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers = $response2->get_headers();

		$this->assertArrayHasKey( 'X-Header-One', $headers );
		$this->assertArrayNotHasKey( 'X-Header-Two', $headers );
		$this->assertArrayHasKey( 'X-Header-Three', $headers );

		$this->sut->custom_include_headers = false;
	}

	/**
	 * @testdox InvalidArgumentException is thrown when include_headers is not false or an array.
	 */
	public function test_invalid_include_headers_throws_exception() {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'include_headers must be either false or an array' );

		$this->sut->endpoint_cache_config['invalid_headers']['config'] = array(
			'include_headers' => 'invalid-string',
		);
		$this->sut->reinitialize_cache();

		$this->reset_rest_server();

		$this->query_endpoint( 'invalid_headers' );
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
	 * Reset the REST server and register routes.
	 *
	 * This is useful when you need to re-register routes after changing
	 * configuration, as WordPress doesn't allow re-registering routes
	 * on the same server instance.
	 */
	private function reset_rest_server() {
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		do_action( 'rest_api_init' );
		$this->sut->register_routes();
	}

	/**
	 * Create a test controller.
	 */
	private function create_test_controller() {
		return new class() extends WP_REST_Controller {
			// phpcs:disable Squiz.Commenting
			use RestApiCache;

			public $responses              = array(
				'single_entity'          => array(
					'id'   => 1,
					'name' => 'Product 1',
				),
				'multiple_entities'      => array(
					array(
						'id'   => 2,
						'name' => 'Product 2',
					),
					array(
						'id'   => 3,
						'name' => 'Product 3',
					),
				),
				'custom_entity_type'     => array(
					'id'   => 100,
					'name' => 'Custom Thing 100',
				),
				'no_vary_by_user'        => array(
					'id'   => 50,
					'name' => 'Shared Product',
				),
				'with_endpoint_id'       => array(
					'id'   => 60,
					'name' => 'Product with Endpoint ID',
				),
				'custom_ttl'             => array(
					'id'   => 70,
					'name' => 'Product with Custom TTL',
				),
				'with_endpoint_hooks'    => array(
					'id'   => 80,
					'name' => 'Product with Endpoint Hooks',
				),
				'with_controller_hooks'  => array(
					'id'   => 90,
					'name' => 'Product with Controller Hooks',
				),
				'with_controller_files'  => array(
					'id'   => 91,
					'name' => 'Product with Controller Files',
				),
				'standard'               => array(
					'id'   => 10,
					'name' => 'Standard Product',
				),
				'custom_endpoint_config' => array(
					'id'   => 20,
					'name' => 'Custom Config Product',
				),
				'test_false_override'    => array(
					'id'   => 30,
					'name' => 'Test False Override Product',
				),
				'invalid_headers'        => array(
					'id'   => 40,
					'name' => 'Invalid Headers Product',
				),
			);
			public $default_entity_type    = 'product';
			public $default_vary_by_user   = true;
			public $endpoint_vary_by_user  = array();
			public $endpoint_ids           = array();
			public $controller_hooks       = array();
			public $controller_files       = array();
			public $response_headers       = array();
			public $custom_exclude_headers = array();
			public $custom_include_headers = false;
			public $endpoint_cache_config  = array();
			public $allowed_directories    = null;
			public $file_check_interval    = null;

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
				$this->register_cached_route( 'with_controller_files' );
				$this->register_cached_route( 'standard' );
				$this->register_custom_config_route( 'custom_endpoint_config' );
				$this->register_custom_config_route( 'test_false_override' );
				$this->register_custom_config_route( 'invalid_headers' );
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

			private function register_custom_config_route( string $endpoint ) {
				$cache_args = isset( $this->endpoint_cache_config[ $endpoint ]['config'] ) ?
					$this->endpoint_cache_config[ $endpoint ]['config'] :
					array();

				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base . '/' . $endpoint,
					array(
						'methods'             => 'GET',
						'callback'            => $this->with_cache(
							function ( $request ) use ( $endpoint ) {
								return $this->handle_request( $endpoint, $request );
							},
							$cache_args
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

			protected function get_files_relevant_to_response_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array {
				return $this->controller_files;
			}

			protected function get_allowed_directories_for_file_based_response_caching(): array {
				if ( null !== $this->allowed_directories ) {
					return $this->allowed_directories;
				}
				return parent::get_allowed_directories_for_file_based_response_caching();
			}

			protected function get_file_check_interval_for_response_caching(): int {
				if ( null !== $this->file_check_interval ) {
					return $this->file_check_interval;
				}
				return parent::get_file_check_interval_for_response_caching();
			}

			protected function get_response_headers_to_include_in_caching( WP_REST_Request $request, ?string $endpoint_id = null ) {
				return $this->custom_include_headers;
			}

			protected function get_response_headers_to_exclude_from_caching( WP_REST_Request $request, ?string $endpoint_id = null ): array {
				return $this->custom_exclude_headers;
			}

			private function handle_request( string $endpoint, WP_REST_Request $request ) {
				$response = new WP_REST_Response( $this->responses[ $endpoint ], 200 );

				// Add custom headers if configured for this endpoint.
				if ( ! empty( $this->response_headers[ $endpoint ] ) ) {
					foreach ( $this->response_headers[ $endpoint ] as $name => $value ) {
						$response->header( $name, $value );
					}
				}

				return $response;
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

	/**
	 * @testdox Response includes ETag header on cache MISS.
	 */
	public function test_etag_header_on_cache_miss() {
		$response = $this->query_endpoint( 'single_entity' );

		$this->assertCacheHeader( $response, 'MISS' );
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertMatchesRegularExpression( '/^"[a-f0-9]{32}"$/', $headers['ETag'] );

		$cache_keys = $this->get_all_cache_keys();
		$this->assertCount( 1, $cache_keys );
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, 'woocommerce_rest_api_cache' );

		$this->assertIsArray( $cached_entry );
		$this->assertArrayHasKey( 'etag', $cached_entry, 'ETag should be stored in cache entry' );
		$this->assertSame( $headers['ETag'], $cached_entry['etag'], 'Cached ETag should match response ETag' );
	}

	/**
	 * @testdox Response includes ETag header on cache HIT.
	 */
	public function test_etag_header_on_cache_hit() {
		$response1 = $this->query_endpoint( 'single_entity' );
		$etag1     = $response1->get_headers()['ETag'];

		$response2 = $this->query_endpoint( 'single_entity' );

		$this->assertCacheHeader( $response2, 'HIT' );
		$headers = $response2->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertSame( $etag1, $headers['ETag'] );
	}

	/**
	 * @testdox 304 Not Modified is returned when If-None-Match header matches ETag.
	 */
	public function test_304_response_when_etag_matches() {
		$response1 = $this->query_endpoint( 'single_entity' );
		$etag      = $response1->get_headers()['ETag'];

		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response2 = $this->server->dispatch( $request );

		$this->assertSame( 304, $response2->get_status() );
		$this->assertNull( $response2->get_data() );
		$headers = $response2->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertSame( $etag, $headers['ETag'] );
	}

	/**
	 * @testdox 200 response with full data when If-None-Match header does not match.
	 */
	public function test_200_response_when_etag_does_not_match() {
		$this->query_endpoint( 'single_entity' );

		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', '"wrong-etag"' );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertNotNull( $response->get_data() );
		$this->assertCacheHeader( $response, 'HIT' );
	}

	/**
	 * @testdox Cache-Control header visibility is set correctly based on login status and vary_by_user setting.
	 *
	 * @testWith [true, "single_entity", "private"]
	 *           [false, "single_entity", "public"]
	 *           [true, "no_vary_by_user", "public"]
	 *
	 * @param bool   $logged_in           Whether user is logged in.
	 * @param string $endpoint            The endpoint to query.
	 * @param string $expected_visibility Expected Cache-Control visibility (public or private).
	 */
	public function test_cache_control_header_visibility( bool $logged_in, string $endpoint, string $expected_visibility ) {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'is_user_logged_in' => fn() => $logged_in )
		);

		$response = $this->query_endpoint( $endpoint );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertStringContainsString( $expected_visibility, $headers['Cache-Control'] );
		$this->assertStringContainsString( 'must-revalidate', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'max-age=', $headers['Cache-Control'] );
	}

	/**
	 * @testdox Date and X-WC-Date headers are present and correctly formatted on cache HIT.
	 */
	public function test_date_header_present() {
		$this->query_endpoint( 'single_entity' );
		$response = $this->query_endpoint( 'single_entity' );

		$this->assertCacheHeader( $response, 'HIT' );
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Date', $headers );
		$this->assertMatchesRegularExpression( '/^[A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} GMT$/', $headers['Date'] );
		$this->assertArrayHasKey( 'X-WC-Date', $headers );
		$this->assertEquals( $headers['Date'], $headers['X-WC-Date'], 'X-WC-Date should match Date' );
	}

	/**
	 * @testdox ETags are different for different users when vary_by_user is true.
	 */
	public function test_etags_differ_per_user() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 1 )
		);
		$response1 = $this->query_endpoint( 'single_entity' );
		$etag1     = $response1->get_headers()['ETag'];

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 2 )
		);
		$response2 = $this->query_endpoint( 'single_entity' );
		$etag2     = $response2->get_headers()['ETag'];

		$this->assertNotSame( $etag1, $etag2 );
	}

	/**
	 * @testdox User cannot get 304 with another user's ETag.
	 */
	public function test_304_not_returned_with_different_user_etag() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 1 )
		);
		$response1 = $this->query_endpoint( 'single_entity' );
		$etag1     = $response1->get_headers()['ETag'];

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'get_current_user_id' => fn() => 2 )
		);
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag1 );
		$response2 = $this->server->dispatch( $request );

		$this->assertSame( 200, $response2->get_status() );
		$this->assertNotNull( $response2->get_data() );
	}

	/**
	 * @testdox WordPress no-cache headers are suppressed for cached endpoints.
	 */
	public function test_nocache_headers_suppressed() {
		$response = $this->query_endpoint( 'single_entity' );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertStringNotContainsString( 'no-cache', $headers['Cache-Control'] );
		$this->assertStringNotContainsString( 'no-store', $headers['Cache-Control'] );
	}

	/**
	 * @testdox Cache headers work without backend caching when cache headers setting is enabled.
	 */
	public function test_cache_headers_without_backend_caching() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );

		// Should not cache in backend, but should add cache headers.
		$this->assertCount( 0, $this->get_all_cache_keys() );

		$headers = $response1->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertCacheHeader( $response1, 'HEADERS' );

		// Should return 304 on subsequent request with matching ETag.
		$etag    = $headers['ETag'];
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response2 = $this->server->dispatch( $request );

		$this->assertSame( 304, $response2->get_status() );
		$this->assertCacheHeader( $response2, 'MATCH' );
	}

	/**
	 * @testdox Backend caching works without cache headers when backend caching is enabled.
	 */
	public function test_backend_caching_without_cache_headers() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'no' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );

		// Should cache in backend, but should not add ETag or Cache-Control headers.
		$this->assertCount( 1, $this->get_all_cache_keys() );
		$this->assertCacheHeader( $response1, 'MISS' );

		$headers = $response1->get_headers();
		$this->assertArrayNotHasKey( 'ETag', $headers );
		$this->assertArrayNotHasKey( 'Cache-Control', $headers );

		// Second request should return from cache.
		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response2, 'HIT' );

		// Still no ETag or Cache-Control headers.
		$headers2 = $response2->get_headers();
		$this->assertArrayNotHasKey( 'ETag', $headers2 );
		$this->assertArrayNotHasKey( 'Cache-Control', $headers2 );
	}

	/**
	 * @testdox Both features work together when both settings are enabled.
	 */
	public function test_both_features_enabled() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );

		// Should cache in backend and add cache headers.
		$this->assertCount( 1, $this->get_all_cache_keys() );
		$this->assertCacheHeader( $response1, 'MISS' );

		$headers = $response1->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers );
		$this->assertArrayHasKey( 'Cache-Control', $headers );

		// Second request should return from cache with headers.
		$response2 = $this->query_endpoint( 'single_entity' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$headers2 = $response2->get_headers();
		$this->assertArrayHasKey( 'ETag', $headers2 );
		$this->assertArrayHasKey( 'Cache-Control', $headers2 );
	}

	/**
	 * @testdox Neither feature works when both settings are disabled.
	 */
	public function test_both_features_disabled() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'no' );
		$this->sut->reinitialize_cache();

		$response = $this->query_endpoint( 'single_entity' );

		// Should not cache in backend, nor add cache headers.
		$this->assertCount( 0, $this->get_all_cache_keys() );

		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'ETag', $headers );
		$this->assertArrayNotHasKey( 'Cache-Control', $headers );

		// Should not have X-WC-Cache header.
		$this->assertArrayNotHasKey( 'X-WC-Cache', $headers );
	}

	/**
	 * @testdox Filter woocommerce_rest_api_not_modified_response can prevent 304 response.
	 */
	public function test_filter_can_prevent_304_response() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );
		$etag      = $response1->get_headers()['ETag'];

		$filter_called     = false;
		$received_response = null;
		$filter            = function ( $response, $request, $endpoint_id ) use ( &$filter_called, &$received_response ) {
			unset( $request, $endpoint_id ); // Avoid parameter not used PHPCS errors.
			$filter_called     = true;
			$received_response = $response;
			return false;
		};
		add_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10, 3 );

		// Request with matching ETag.
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response2 = $this->server->dispatch( $request );

		// Filter should have been called with a 304 response.
		$this->assertTrue( $filter_called );
		$this->assertInstanceOf( WP_REST_Response::class, $received_response );
		$this->assertSame( 304, $received_response->get_status() );

		// Should return 200 with full data instead of 304.
		$this->assertSame( 200, $response2->get_status() );
		$this->assertNotNull( $response2->get_data() );
		$this->assertCacheHeader( $response2, 'HEADERS' );

		remove_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10 );
	}

	/**
	 * @testdox Filter woocommerce_rest_api_not_modified_response can modify 304 response.
	 */
	public function test_filter_can_modify_304_response() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );
		$etag      = $response1->get_headers()['ETag'];

		$filter = function ( $response, $request, $endpoint_id ) {
			unset( $request, $endpoint_id ); // Avoid parameter not used PHPCS errors.
			$response->header( 'X-Custom-Header', 'custom-value' );
			return $response;
		};
		add_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10, 3 );

		// Request with matching ETag.
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response2 = $this->server->dispatch( $request );

		$this->assertSame( 304, $response2->get_status() );

		// Should have custom header.
		$headers = $response2->get_headers();
		$this->assertArrayHasKey( 'X-Custom-Header', $headers );
		$this->assertSame( 'custom-value', $headers['X-Custom-Header'] );

		remove_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10 );
	}

	/**
	 * @testdox Filter woocommerce_rest_api_not_modified_response is called for cached 304 responses.
	 */
	public function test_filter_called_for_cached_304_responses() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		// First request to cache.
		$response1 = $this->query_endpoint( 'single_entity' );
		$etag      = $response1->get_headers()['ETag'];

		// Second request to populate cache.
		$this->query_endpoint( 'single_entity' );

		$filter_called = false;
		$filter        = function ( $response, $request, $endpoint_id ) use ( &$filter_called ) {
			unset( $response, $request, $endpoint_id ); // Avoid parameter not used PHPCS errors.
			$filter_called = true;
			return false;
		};
		add_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10, 3 );

		// Third request with matching ETag (should be served from cache).
		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response3 = $this->server->dispatch( $request );

		$this->assertTrue( $filter_called );

		// Should return 200 with full cached data instead of 304.
		$this->assertSame( 200, $response3->get_status() );
		$this->assertNotNull( $response3->get_data() );
		$this->assertCacheHeader( $response3, 'HIT' );

		remove_filter( 'woocommerce_rest_api_not_modified_response', $filter, 10 );
	}

	/**
	 * @testdox X-WC-Cache header shows HEADERS when only cache headers are enabled.
	 */
	public function test_x_wc_cache_headers_value() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response = $this->query_endpoint( 'single_entity' );

		$this->assertCacheHeader( $response, 'HEADERS' );
	}

	/**
	 * @testdox X-WC-Cache header shows MATCH on 304 response.
	 */
	public function test_x_wc_cache_match_value() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'no' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		$this->sut->reinitialize_cache();

		$response1 = $this->query_endpoint( 'single_entity' );
		$etag      = $response1->get_headers()['ETag'];

		$request = new WP_REST_Request( 'GET', '/wc/v3/rest_api_cache_test/single_entity' );
		$request->set_header( 'If-None-Match', $etag );
		$response2 = $this->server->dispatch( $request );

		$this->assertCacheHeader( $response2, 'MATCH' );
	}

	/**
	 * @testdox Caching is completely bypassed when rest_api_caching feature is disabled (even with caching options enabled).
	 */
	public function test_caching_bypassed_when_feature_disabled() {
		update_option( 'woocommerce_rest_api_enable_backend_caching', 'yes' );
		update_option( 'woocommerce_rest_api_enable_cache_headers', 'yes' );
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'no' );
		$this->sut->reinitialize_cache();

		$this->reset_rest_server();

		$response = $this->query_endpoint( 'single_entity' );

		$this->assertCount( 0, $this->get_all_cache_keys() );

		$headers = $response->get_headers();
		$this->assertArrayNotHasKey( 'X-WC-Cache', $headers );
		$this->assertArrayNotHasKey( 'ETag', $headers );
		$this->assertArrayNotHasKey( 'Cache-Control', $headers );

		$this->assertEquals( $this->sut->responses['single_entity'], $response->get_data() );
	}

	/**
	 * @testdox rest_send_nocache_headers filter is not registered when feature is disabled.
	 */
	public function test_nocache_headers_filter_not_registered_when_feature_disabled() {
		update_option( 'woocommerce_feature_rest_api_caching_enabled', 'no' );

		// Create a new controller with the feature disabled to test that the filter is not registered.
		$controller = $this->create_test_controller();

		$has_filter = has_filter( 'rest_send_nocache_headers', array( $controller, 'handle_rest_send_nocache_headers' ) );
		$this->assertFalse( $has_filter );
	}

	/**
	 * @testdox Cache is invalidated when a controller-level tracked file is modified or deleted.
	 *
	 * @testWith ["modify"]
	 *           ["delete"]
	 *
	 * @param string $action The action to perform on the file (modify or delete).
	 */
	public function test_cache_invalidated_when_controller_file_changes( string $action ) {
		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files = array( $test_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertCount( 1, $this->get_all_cache_keys() );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'HIT' );

		if ( 'delete' === $action ) {
			unlink( $test_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		} else {
			touch( $test_file, time() + 10 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch
		}
		clearstatcache( true, $test_file );

		$response3 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response3, 'MISS', "Cache should be invalidated when tracked file is {$action}d" );

		if ( 'modify' === $action ) {
			unlink( $test_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		}
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Files hash is stored in cache when tracking files.
	 */
	public function test_files_hash_stored_in_cache() {
		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files = array( $test_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$this->assertCount( 1, $cache_keys );
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayHasKey( 'files_hash', $cached_entry );
		$this->assertNotEmpty( $cached_entry['files_hash'] );

		unlink( $test_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Cache is invalidated when endpoint-level tracked files change.
	 */
	public function test_cache_invalidated_when_endpoint_files_change() {
		$test_file = $this->create_temp_test_file();

		$this->sut->endpoint_cache_config['custom_endpoint_config']['config'] = array(
			'relevant_files' => array( $test_file ),
		);
		$this->sut->reinitialize_cache();
		$this->reset_rest_server();

		$response1 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$response2 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHeader( $response2, 'HIT' );

		touch( $test_file, time() + 10 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch
		clearstatcache( true, $test_file );

		$response3 = $this->query_endpoint( 'custom_endpoint_config' );
		$this->assertCacheHeader( $response3, 'MISS', 'Cache should be invalidated when endpoint-tracked file changes' );

		unlink( $test_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	/**
	 * @testdox Non-existent files are not tracked and do not prevent caching.
	 */
	public function test_non_existent_files_not_tracked() {
		$non_existent_file = sys_get_temp_dir() . '/wc_test_non_existent_' . uniqid() . '.txt';

		$this->sut->controller_files = array( $non_existent_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$this->assertCount( 1, $cache_keys );
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayNotHasKey( 'files_hash', $cached_entry, 'Files hash should not be stored when no files are tracked' );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Filter woocommerce_rest_api_cache_files_hash_data can modify file tracking data.
	 */
	public function test_filter_can_modify_files_hash_data() {
		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files = array( $test_file );

		$filter_called = false;
		$filter        = function ( $files_data, $file_paths, $controller ) use ( &$filter_called, $test_file ) {
			unset( $controller );
			$filter_called = true;
			$this->assertIsArray( $files_data );
			$this->assertIsArray( $file_paths );
			$this->assertContains( $test_file, $file_paths );
			return array();
		};
		add_filter( 'woocommerce_rest_api_cache_files_hash_data', $filter, 10, 3 );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );
		$this->assertTrue( $filter_called, 'Filter should have been called' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry, 'Filter cleared files data, so no hash should be stored' );

		remove_filter( 'woocommerce_rest_api_cache_files_hash_data', $filter, 10 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Relative file paths are resolved relative to the first allowed directory.
	 */
	public function test_relative_file_paths_resolved() {
		$this->sut->controller_files = array( 'woocommerce.php' );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertArrayHasKey( 'files_hash', $cached_entry, 'Relative path should be resolved and tracked' );
		$this->assertNotEmpty( $cached_entry['files_hash'] );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Filter woocommerce_rest_api_cache_allowed_file_directories can add directories.
	 */
	public function test_filter_can_add_allowed_directories() {
		$this->sut->allowed_directories = array( WC_ABSPATH );

		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files = array( $test_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cached_entry = wp_cache_get( $cache_keys[0], self::CACHE_GROUP );
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry, 'File should not be tracked without temp dir in allowed directories' );

		$filter = function ( $directories, $controller ) {
			unset( $controller );
			$directories[] = sys_get_temp_dir();
			return $directories;
		};
		add_filter( 'woocommerce_rest_api_cache_allowed_file_directories', $filter, 10, 2 );

		wp_cache_delete( $cache_keys[0], self::CACHE_GROUP );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cached_entry = wp_cache_get( $cache_keys[0], self::CACHE_GROUP );
		$this->assertArrayHasKey( 'files_hash', $cached_entry, 'File should be tracked after adding temp dir via filter' );

		remove_filter( 'woocommerce_rest_api_cache_allowed_file_directories', $filter, 10 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Controller can override get_allowed_directories_for_file_based_response_caching.
	 */
	public function test_controller_can_override_allowed_directories() {
		$test_file = $this->create_temp_test_file();

		$this->sut->allowed_directories = array( WC_ABSPATH );
		$this->sut->controller_files    = array( $test_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cached_entry = wp_cache_get( $cache_keys[0], self::CACHE_GROUP );
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry, 'File outside allowed directories should not be tracked' );

		$this->sut->allowed_directories = array( WC_ABSPATH, sys_get_temp_dir() );

		wp_cache_delete( $cache_keys[0], self::CACHE_GROUP );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cached_entry = wp_cache_get( $cache_keys[0], self::CACHE_GROUP );
		$this->assertArrayHasKey( 'files_hash', $cached_entry, 'File should be tracked when its directory is allowed' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File check results are cached for the configured interval.
	 */
	public function test_file_check_results_are_cached() {
		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files    = array( $test_file );
		$this->sut->file_check_interval = 600; // 10 minutes.

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$file_check_keys = array_filter(
			$this->get_all_cache_keys(),
			fn( $key ) => strpos( $key, 'wc_rest_file_check_' ) === 0
		);
		$this->assertCount( 1, $file_check_keys, 'File check cache entry should be created' );

		// Modify the file - but since file check is cached, it won't be detected yet.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test file modification.
		touch( $test_file, time() + 10 );
		clearstatcache( true, $test_file );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'HIT', 'File change should not be detected while file check is cached' );

		// Delete the file check cache to simulate interval expiration.
		$file_check_key = array_values( $file_check_keys )[0];
		wp_cache_delete( $file_check_key, self::CACHE_GROUP );

		$response3 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response3, 'MISS', 'File change should be detected after file check cache expires' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File check caching can be disabled by returning 0 from get_file_check_interval_for_response_caching.
	 */
	public function test_file_check_caching_disabled_with_zero_interval() {
		$test_file = $this->create_temp_test_file();

		$this->sut->controller_files    = array( $test_file );
		$this->sut->file_check_interval = 0; // Disabled.

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$file_check_keys = array_filter(
			$this->get_all_cache_keys(),
			fn( $key ) => strpos( $key, 'wc_rest_file_check_' ) === 0
		);
		$this->assertCount( 0, $file_check_keys, 'No file check cache entry should be created when interval is 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test file modification.
		touch( $test_file, time() + 10 );
		clearstatcache( true, $test_file );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'MISS', 'File change should be detected immediately when caching is disabled' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Multiple files can be tracked simultaneously.
	 */
	public function test_multiple_files_tracked() {
		$test_file1 = $this->create_temp_test_file();
		$test_file2 = $this->create_temp_test_file();

		$this->sut->controller_files = array( $test_file1, $test_file2 );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys = $this->get_all_cache_keys();
		$cache_key  = $cache_keys[0];

		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$this->assertArrayHasKey( 'files_hash', $cached_entry );
		$this->assertNotEmpty( $cached_entry['files_hash'] );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'HIT' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- Test file modification.
		touch( $test_file2, time() + 10 );
		clearstatcache( true, $test_file2 );

		$response3 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response3, 'MISS', 'Cache should be invalidated when any tracked file changes' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file1 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup.
		unlink( $test_file2 );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File path resolution failure (realpath returns false) is handled gracefully.
	 */
	public function test_file_path_resolution_failure_handled() {
		$fake_file = '/some/nonexistent/path/file.txt';

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath' => function ( $path ) use ( $fake_file ) {
					// Return false for the fake file, real path for others.
					return $path === $fake_file ? false : \realpath( $path );
				},
			)
		);

		$this->sut->controller_files = array( $fake_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// No files_hash should be stored since file resolution failed.
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File access error (filemtime returns false) is handled gracefully.
	 */
	public function test_file_access_error_handled() {
		$fake_file      = WC_ABSPATH . 'unreadable_file.txt';
		$fake_file_real = WC_ABSPATH . 'unreadable_file_real.txt';

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath'  => function ( $path ) use ( $fake_file, $fake_file_real ) {
					if ( $path === $fake_file ) {
						return $fake_file_real;
					}
					return \realpath( $path );
				},
				'filemtime' => function ( $path ) use ( $fake_file_real ) {
					// Return false for the fake file (simulating access error).
					return $path === $fake_file_real ? false : \filemtime( $path );
				},
			)
		);

		$this->sut->controller_files = array( $fake_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// No files_hash should be stored since file access failed.
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Cache is invalidated when file becomes inaccessible after being cached.
	 */
	public function test_cache_invalidated_when_file_becomes_inaccessible() {
		$fake_file       = WC_ABSPATH . 'accessible_file.txt';
		$fake_file_real  = WC_ABSPATH . 'accessible_file_real.txt';
		$file_accessible = true;

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath'  => function ( $path ) use ( $fake_file, $fake_file_real, &$file_accessible ) {
					if ( $path === $fake_file ) {
						return $file_accessible ? $fake_file_real : false;
					}
					return \realpath( $path );
				},
				'filemtime' => function ( $path ) use ( $fake_file_real, &$file_accessible ) {
					if ( $path === $fake_file_real ) {
						return $file_accessible ? 1234567890 : false;
					}
					return \filemtime( $path );
				},
			)
		);

		$this->sut->controller_files = array( $fake_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cached_entry = wp_cache_get( $cache_keys[0], self::CACHE_GROUP );
		$this->assertArrayHasKey( 'files_hash', $cached_entry );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$file_accessible = false;

		$response3 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response3, 'MISS', 'Cache should be invalidated when file becomes inaccessible' );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File outside allowed directories is rejected.
	 */
	public function test_file_outside_allowed_directories_rejected() {
		$outside_file      = '/etc/passwd';
		$outside_file_real = '/etc/passwd';

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath' => function ( $path ) use ( $outside_file, $outside_file_real ) {
					if ( $path === $outside_file ) {
						return $outside_file_real;
					}
					return \realpath( $path );
				},
			)
		);

		$this->sut->controller_files = array( $outside_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		// No files_hash should be stored since file is outside allowed directories.
		$this->assertArrayNotHasKey( 'files_hash', $cached_entry );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox Files in directories with similar prefix names are correctly rejected (prefix collision prevention).
	 */
	public function test_directory_prefix_collision_prevented() {
		// Simulate a directory structure where /var/www/htmlevil exists alongside /var/www/html.
		// A file in /var/www/htmlevil should NOT be allowed when only /var/www/html is permitted.
		$allowed_dir = '/var/www/html';
		$evil_file   = '/var/www/htmlevil/malicious.php';

		$this->sut->allowed_directories = array( $allowed_dir );

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath' => function ( $path ) use ( $allowed_dir, $evil_file ) {
					if ( $path === $allowed_dir || $path === $evil_file ) {
						return $path;
					}
					return \realpath( $path );
				},
			)
		);

		$this->sut->controller_files = array( $evil_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$cache_keys   = $this->get_all_cache_keys();
		$cache_key    = $cache_keys[0];
		$cached_entry = wp_cache_get( $cache_key, self::CACHE_GROUP );

		$this->assertArrayNotHasKey( 'files_hash', $cached_entry, 'File in /var/www/htmlevil should not be allowed when only /var/www/html is permitted' );

		$this->sut->controller_files    = array();
		$this->sut->allowed_directories = null;
	}

	/**
	 * @testdox Cache is invalidated when file modification time changes (using mocked filemtime).
	 */
	public function test_cache_invalidated_when_mocked_file_mtime_changes() {
		$fake_file      = WC_ABSPATH . 'tracked_file.txt';
		$fake_file_real = WC_ABSPATH . 'tracked_file_real.txt';
		$file_mtime     = 1234567890;

		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array(
				'realpath'  => function ( $path ) use ( $fake_file, $fake_file_real ) {
					if ( $path === $fake_file ) {
						return $fake_file_real;
					}
					return \realpath( $path );
				},
				'filemtime' => function ( $path ) use ( $fake_file_real, &$file_mtime ) {
					if ( $path === $fake_file_real ) {
						return $file_mtime;
					}
					return \filemtime( $path );
				},
			)
		);

		$this->sut->controller_files = array( $fake_file );

		$response1 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response1, 'MISS' );

		$response2 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response2, 'HIT' );

		$file_mtime = 1234567999;

		$response3 = $this->query_endpoint( 'with_controller_files' );
		$this->assertCacheHeader( $response3, 'MISS', 'Cache should be invalidated when file mtime changes' );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File tracking warnings are suppressed by default to avoid log flooding.
	 */
	public function test_file_tracking_warnings_are_suppressed_by_default() {
		wp_cache_flush_group( 'woocommerce_rest_api_cache_warnings' );

		$warning_count = 0;
		$logger_mock   = $this->createMock( \WC_Logger::class );
		$logger_mock->expects( $this->any() )
			->method( 'warning' )
			->willReturnCallback(
				function () use ( &$warning_count ) {
					++$warning_count;
				}
			);

		$this->register_legacy_proxy_function_mocks(
			array( 'wc_get_logger' => fn() => $logger_mock )
		);

		// Use a file path in temp dir (which is in allowed_directories).
		$non_existent_file           = sys_get_temp_dir() . '/wc_test_warning_' . uniqid() . '.txt';
		$this->sut->controller_files = array( $non_existent_file );

		// First request should log the warning.
		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 1, $warning_count, 'First request should log the warning' );

		// Subsequent request should NOT log the warning again (suppressed).
		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 1, $warning_count, 'Second request should not log again (suppressed)' );

		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 1, $warning_count, 'Third request should not log again (still suppressed)' );

		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File tracking warnings are not suppressed when filter returns zero.
	 */
	public function test_file_tracking_warnings_not_suppressed_when_filter_returns_zero() {
		wp_cache_flush_group( 'woocommerce_rest_api_cache_warnings' );

		$warning_count = 0;
		$logger_mock   = $this->createMock( \WC_Logger::class );
		$logger_mock->expects( $this->any() )
			->method( 'warning' )
			->willReturnCallback(
				function () use ( &$warning_count ) {
					++$warning_count;
				}
			);

		$this->register_legacy_proxy_function_mocks(
			array( 'wc_get_logger' => fn() => $logger_mock )
		);

		// Disable warning suppression.
		add_filter( 'woocommerce_rest_api_cache_file_warning_suppression_ttl', '__return_zero' );

		// Use a file path in temp dir (which is in allowed_directories).
		$non_existent_file           = sys_get_temp_dir() . '/wc_test_warning_' . uniqid() . '.txt';
		$this->sut->controller_files = array( $non_existent_file );

		// All three requests should log the warning.
		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 1, $warning_count, 'First request should log the warning' );

		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 2, $warning_count, 'Second request should log again (suppression disabled)' );

		$this->query_endpoint( 'with_controller_files' );
		$this->assertSame( 3, $warning_count, 'Third request should log again (suppression disabled)' );

		remove_filter( 'woocommerce_rest_api_cache_file_warning_suppression_ttl', '__return_zero' );
		$this->sut->controller_files = array();
	}

	/**
	 * @testdox File tracking warning suppression filter receives correct parameters.
	 */
	public function test_file_tracking_warning_suppression_filter_receives_correct_parameters() {
		wp_cache_flush_group( 'woocommerce_rest_api_cache_warnings' );

		$filter_called   = false;
		$received_params = array();

		$filter = function ( $ttl, $file_path, $reason ) use ( &$filter_called, &$received_params ) {
			$filter_called   = true;
			$received_params = array(
				'ttl'       => $ttl,
				'file_path' => $file_path,
				'reason'    => $reason,
			);
			return $ttl;
		};

		add_filter( 'woocommerce_rest_api_cache_file_warning_suppression_ttl', $filter, 10, 3 );

		// Use a file path in temp dir (which is in allowed_directories).
		$non_existent_file           = sys_get_temp_dir() . '/wc_test_warning_' . uniqid() . '.txt';
		$this->sut->controller_files = array( $non_existent_file );

		$this->query_endpoint( 'with_controller_files' );

		$this->assertTrue( $filter_called, 'Filter should be called' );
		$this->assertSame( HOUR_IN_SECONDS, $received_params['ttl'], 'Default TTL should be HOUR_IN_SECONDS' );
		$this->assertSame( $non_existent_file, $received_params['file_path'], 'File path should be passed to filter' );
		$this->assertNotEmpty( $received_params['reason'], 'Reason should be passed to filter' );

		remove_filter( 'woocommerce_rest_api_cache_file_warning_suppression_ttl', $filter, 10 );
		$this->sut->controller_files = array();
	}

	/**
	 * Create a temporary test file for file tracking tests.
	 *
	 * @return string Path to the created file.
	 */
	private function create_temp_test_file(): string {
		$temp_file = sys_get_temp_dir() . '/wc_test_file_' . uniqid() . '.txt';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test file creation.
		file_put_contents( $temp_file, 'test content' );
		return $temp_file;
	}
}
