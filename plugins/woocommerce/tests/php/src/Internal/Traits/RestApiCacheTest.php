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

		$this->reset_rest_server();
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
			public $response_headers       = array();
			public $custom_exclude_headers = array();
			public $custom_include_headers = false;
			public $endpoint_cache_config  = array();

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
	 * @testdox Cache-Control header is "private" when user is logged in and vary_by_user is true.
	 */
	public function test_cache_control_private_when_user_logged_in() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'is_user_logged_in' => fn() => true )
		);

		$response = $this->query_endpoint( 'single_entity' );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertStringContainsString( 'private', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'must-revalidate', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'max-age=', $headers['Cache-Control'] );
	}

	/**
	 * @testdox Cache-Control header is "public" when no user is logged in even with vary_by_user true.
	 */
	public function test_cache_control_public_when_no_user() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'is_user_logged_in' => fn() => false )
		);

		$response = $this->query_endpoint( 'single_entity' );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertStringContainsString( 'public', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'must-revalidate', $headers['Cache-Control'] );
		$this->assertStringContainsString( 'max-age=', $headers['Cache-Control'] );
	}

	/**
	 * @testdox Cache-Control header is "public" when vary_by_user is false regardless of login status.
	 */
	public function test_cache_control_public_when_vary_by_user_false() {
		wc_get_container()->get( LegacyProxy::class )->register_function_mocks(
			array( 'is_user_logged_in' => fn() => true )
		);

		$response = $this->query_endpoint( 'no_vary_by_user' );

		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Cache-Control', $headers );
		$this->assertStringContainsString( 'public', $headers['Cache-Control'] );
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
}
